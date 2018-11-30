<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * prints the form so the user can fill out the apply
 *
 * @package apply
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @attention modified from mod_feedback that by Andreas Grabs
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

apply_init_session();


$id            = required_param('id', PARAM_INT);
$courseid      = optional_param('courseid', 0, PARAM_INT);
$submit_id     = optional_param('submit_id', 0, PARAM_INT);
$submit_ver    = optional_param('submit_ver', -1, PARAM_INT);
$prev_values   = optional_param('prev_values', 0, PARAM_INT);
$go_page       = optional_param('go_page', -1, PARAM_INT);
$last_page     = optional_param('last_page', false, PARAM_INT);
$start_itempos = optional_param('start_itempos', 0, PARAM_INT);
$last_itempos  = optional_param('last_itempos',  0, PARAM_INT);

$urlparams['id']       = $id;
$urlparams['courseid'] = $courseid;

$highlightrequired = false;


///////////////////////////////////////////////////////////////////////////
// Form Data
if (($formdata = data_submitted()) and !confirm_sesskey()) {
    print_error('invalidsesskey');
}

//
$save_values = false;
$save_draft  = false;
if (isset($formdata->save_values)) $save_values = true;
if (isset($formdata->save_draft))  $save_draft  = true;

// Page
if ( isset($formdata->sesskey)      and
    !isset($formdata->save_values)  and
    !isset($formdata->go_next_page) and
    !isset($formdata->go_prev_page) and
     isset($formdata->last_page)) {

    $go_page = $formdata->last_page;
}

if ($go_page<0 and !$save_values) {
    if (isset($formdata->go_next_page)) {
        $go_page = $last_page + 1;
        $go_next_page = true;
        $go_prev_page = false;
    }
    else if (isset($formdata->go_prev_page)) {
        $go_page = $last_page - 1;
        $go_next_page = false;
        $go_prev_page = true;
    }
    else {
        print_error('missingparameter');
    }
}
else {
    $go_next_page = $go_prev_page = false;
}


///////////////////////////////////////////////////////////////////////////
//
if (! $cm = get_coursemodule_from_id('apply', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
    print_error('coursemisconf');
}
if (! $apply  = $DB->get_record('apply', array('id'=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
if (!$courseid) $courseid = $course->id;

$context = context_module::instance($cm->id);


///////////////////////////////////////////////////////////////////////////
// Check 1
require_login($course, true, $cm);
//
if (!has_capability('mod/apply:submit', $context)) {
    apply_print_error_messagebox('apply_is_disable', $id);
    exit;
}


///////////////////////////////////////////////////////////////////////////
// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$back_params = array('id'=>$cm->id, 'courseid'=>$courseid, 'do_show'=>'view');
$url_params  = array('id'=>$cm->id, 'courseid'=>$courseid, 'go_page'=>$go_page);
$back_url = new moodle_url($CFG->wwwroot.'/mod/apply/view.php', $back_params);
$this_url = new moodle_url('/mod/apply/submit.php', $url_params);

$PAGE->navbar->add(get_string('apply:submit', 'apply'));
$PAGE->set_url($this_url);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();


///////////////////////////////////////////////////////////////////////////
// Check 2
if ((empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context))) {
    notice(get_string('activityiscurrentlyhidden'));
}

if (!$apply->multiple_submit and $submit_id==0) {
    if (apply_get_valid_submits_count($apply->id, $USER->id)>0) {
        apply_print_error_messagebox('apply_is_already_submitted', $id);
        exit;
    }
}

$checktime = time();
$apply_is_not_open =  $apply->time_open>$checktime;
$apply_is_closed   = ($apply->time_close<$checktime and $apply->time_close>0);
if ($apply_is_not_open or $apply_is_closed) {
    if ($apply_is_not_open) apply_print_error_messagbox('apply_is_not_open', $id);
    else                    apply_print_error_messagbox('apply_is_closed',   $id);
    exit;
}


///////////////////////////////////////////////////////////////////////////
// first time view
if (!$SESSION->apply->is_started) {
    $itemscount = $DB->count_records('apply_item', array('apply_id'=>$apply->id, 'hasvalue'=>1));
    if ($itemscount<=0) {
        apply_print_error_messagebox('apply_is_not_ready', $id);
        exit;
    }
}


///////////////////////////////////////////////////////////////////////////
// Submit

if ($prev_values) {
    //
    if (!$SESSION->apply->is_started) {
        print_error('error', '', $CFG->wwwroot.'/mod/apply/view.php?id='.$id);
    }

//    if (apply_check_values($start_itempos, $last_itempos) or $save_draft or $go_next_page or $go_prev_page) {
    if (apply_check_values($start_itempos, $last_itempos)) {
        $user_id   = $USER->id;
        $submit_id = apply_save_draft_values($apply->id, $submit_id, $user_id);    // save to draft

        if ($submit_id) {
            //$event = apply_get_event($cm, 'user_submit', $urlparams, 'draft');
            //jbxl_add_to_log($event);
            if ($go_next_page or $go_prev_page) $save_return = 'page';
            else                                $prev_values = false;
            if ($save_draft) $save_return = 'draft';
        }
        else {
            $save_return = 'failed';
            if (isset($last_page)) $go_page = $last_page;
            else print_error('missingparameter');
        }
    }
    //
    else {
        $save_return = 'missing';
        $highlightrequired = true;
        if (isset($last_page)) $go_page = $last_page;
        else print_error('missingparameter');
    }
}


//saving the items
if ($save_values and !$save_draft and !$prev_values) {
    //
    apply_exec_submit($submit_id);

    if ($submit_id) {
        $save_return = 'saved';
        //$log_url = 'submit.php?id='.$cm->id.'&apply_id='.$apply->id.'&submit_id='.$submit_id.'$submit_ver='.$submit_ver;
        //add_to_log($courseid, 'apply', 'submit', $log_url, 'submit');
        $event = apply_get_event($cm, 'user_submit', $urlparams, 'submit');
        jbxl_add_to_log($event);
        //
        if ($apply->email_notification) {
            apply_send_email($cm, $apply, $course, $user_id);
        }
    }
    else {
        $save_return = 'failed';
    }
}


///////////////////////////////////////////////////////////////////////////
//
$allbreaks = apply_get_all_break_positions($apply->id);
if ($allbreaks) {
    if ($go_page<=0) {
        $start_position = 0;
    }
    else {
        if (!isset($allbreaks[$go_page-1])) $go_page = count($allbreaks);
        $start_position = $allbreaks[$go_page-1];
    }
    $is_pagebreak = true;
} 
else {
    $start_position = 0;
    $newpage = 0;
    $is_pagebreak = false;
}

//
//get the items after the last shown pagebreak
$select = 'apply_id=? AND position>?';
$params = array($apply->id, $start_position);
$items  = $DB->get_records_select('apply_item', $select, $params, 'position');

//get the first pagebreak
$params = array('apply_id'=>$apply->id, 'typ'=>'pagebreak');
if ($pagebreaks = $DB->get_records('apply_item', $params, 'position')) {
    $pagebreaks = array_values($pagebreaks);
    $first_pagebreak = $pagebreaks[0];
}
else {
    $first_pagebreak = false;
}
$max_item_count = $DB->count_records('apply_item', array('apply_id'=>$apply->id));



///////////////////////////////////////////////////////////////////////////
// Print the main part of the page
//
$SESSION->apply->is_started = false;
echo '<div align="center">';
echo $OUTPUT->heading(format_text($apply->name), 3);
echo '</div>';

// submit: データが保存された
if (isset($save_return) and $save_return=='saved') {
    echo '<div align="center">';
    echo '<strong><font color="green">';
    echo get_string('entry_saved', 'apply');
    echo '<br /></font></strong>';
    echo '</strong>';
    echo '<br />';
    echo $OUTPUT->continue_button($back_url);
}

// submit: Draftにデータが保存された
else if (isset($save_return) and $save_return=='draft') {
    echo '<div align="center">';
    echo '<strong><font color="green">';
    echo get_string('entry_saved_draft', 'apply');
    echo '<br /></font></strong>';
    echo '</strong>';
    echo '<br />';
    echo $OUTPUT->continue_button($back_url);
}

// 
else {
    // submit: 失敗
    if (isset($save_return)) {
        if ($save_return=='failed') {
            echo $OUTPUT->box_start('mform error boxaligncenter boxwidthwide');
            echo get_string('saving_failed', 'apply');
            echo $OUTPUT->box_end();
        }
        else if ($save_return=='missing') {
            echo $OUTPUT->box_start('mform error boxaligncenter boxwidthwide');
            echo get_string('saving_failed_because_missing_or_false_values', 'apply');
            echo $OUTPUT->box_end();
        }
    }

    // 最初に表示するページ
    if (is_array($items)) {
        //
        if ($submit_id) {
            $submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
            if ($submit_ver==-1 and apply_exist_draft_values($submit_id)) $submit_ver = 0;
        }
        require('submit_page.php');
        //
        $SESSION->apply->is_started = true;
    }
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

