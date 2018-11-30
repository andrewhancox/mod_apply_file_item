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
 * print the entries
 *
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir.'/tablelib.php');


////////////////////////////////////////////////////////
//get the params
$id         = required_param('id', PARAM_INT);
$do_show    = optional_param('do_show', 'view_entries', PARAM_ALPHAEXT);
$courseid   = optional_param('courseid',    0, PARAM_INT);
$user_id    = optional_param('user_id',     0, PARAM_INT);
$submit_id  = optional_param('submit_id',   0, PARAM_INT);
$submit_ver = optional_param('submit_ver', -1, PARAM_INT);
$show_all   = optional_param('show_all',    0, PARAM_INT);
$perpage    = optional_param('perpage', APPLY_DEFAULT_PAGE_COUNT, PARAM_INT);  // how many per page

//
//$sifirst    = optional_param('sifirst', '', PARAM_ALPHA);
//$silast     = optional_param('silast',  '', PARAM_ALPHA);
//$ssort      = optional_param('ssort',   '', PARAM_ALPHAEXT);
//$spage      = optional_param('spage', 0, PARAM_INT);

$sort  = optional_param('sort',  '', PARAM_ALPHAEXT);
$order = optional_param('order', 'DESC', PARAM_ALPHAEXT);
if ($sort and $order) $sort = $sort.' '.$order;

$current_tab = $do_show;
$this_action = 'view_entries';

$norder = $order;
if ($sort) {
    if ($order=='DESC') $norder = 'ASC';
    else $norder = 'DESC';
}


////////////////////////////////////////////////////////
//get the objects
if (! $cm = get_coursemodule_from_id('apply', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
    print_error('coursemisconf');
}
if (! $apply = $DB->get_record('apply', array('id'=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
if (!$courseid) $courseid = $course->id;

$req_own_data = false;
$name_pattern = $apply->name_pattern;
$context = context_module::instance($cm->id);


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);

$formdata = data_submitted();
if ($formdata) {
    if (!confirm_sesskey()) {
        print_error('invalidsesskey');
    }
    if ($user_id) {
        $formdata->user_id = intval($user_id);
    }
}

require_capability('mod/apply:viewreports', $context);

///////////////////////////////////////////////////////////////////////////
// URL
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$base_url = new moodle_url('/mod/apply/'.$this_action.'.php');
$base_url->params(array('id'=>$id, 'courseid'=>$courseid));
//
$this_url = new moodle_url($base_url);
$back_url = new moodle_url($base_url);
$this_url->params(array('do_show'=>$do_show, 'show_all'=>$show_all, 'submit_id'=>$submit_id, 'submit_ver'=>$submit_ver));
$back_url->params(array('do_show'=>'view_entries'));

//$log_url = explode('/', $this_url);
//add_to_log($course->id, 'apply', 'view_entries', end($log_url), 'apply_id='.$apply->id);


////////////////////////////////////////////////////////
/// Print the page header
$PAGE->navbar->add(get_string('apply:viewentries', 'apply'));
$PAGE->set_url($this_url);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

require('tabs.php');

//
$cap_view_hidden_activities = has_capability('moodle/course:viewhiddenactivities', $context);
if ((empty($cm->visible) and !$cap_view_hidden_activities)) {
    notice(get_string('activityiscurrentlyhidden'));
}
if ((empty($cm->visible) and !$cap_view_hidden_activities)) {
    notice(get_string('activityiscurrentlyhidden'));
}


///////////////////////////////////////////////////////////////////////////
// view_enties: Print the main part of the page

if ($do_show=='view_entries') {
    ////////////////////////////////////////////////////////////
    // Setup Table
    $table = new flexible_table('apply-show_entry-list-'.$courseid);
    $matchcount = apply_get_valid_submits_count($cm->instance);
    //
    $page_params = '';
    require('entry_header.php');
    //
    echo $OUTPUT->box_start('mdl-align');
    echo '<h2>'.$apply->name.'</h2>';
    echo $OUTPUT->box_end();

    ///////////////////////////////////////////////////////////////////////
    //
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    echo $OUTPUT->box_start('mdl-align');

    ////////////////////////////////////////////////////////////
    // Print Initials Bar
    /*
    if ($name_pattern=='firstname') {
        apply_print_initials_bar($table, true, false);
        if ($show_all) echo '<br />';
    }
    else if ($name_pattern=='lastname') {
        apply_print_initials_bar($table, false, true);
        if ($show_all) echo '<br />';
    }
    */

    ////////////////////////////////////////////////////////////
    // Submits Data
    $where  .= 'version>0 AND ';
    $submits = apply_get_submits_select($apply->id, 0, $where, $params, $sort, $start_page, $page_count);

    if (!$submits) {
        //echo '<br />';
        $table->print_html();
    } 
    else {
        foreach ($submits as $submit) {
            $student = apply_get_user_info($submit->user_id);
            if ($student) {
                $data = array();
                //
                require('entry_record.php');
                if (!empty($data)) $table->add_data($data);
            }
        }
        //
        //echo '<br />';
        $table->print_html();

        $allurl = new moodle_url($base_url);
        if ($show_all) {
            $allurl->param('show_all', 0);
            echo $OUTPUT->container(html_writer::link($allurl, get_string('show_perpage', 'apply', APPLY_DEFAULT_PAGE_COUNT)), array(), 'show_all');
        }
        else if ($matchcount>0 && $perpage<$matchcount) {
            $allurl->param('show_all', 1);
            echo $OUTPUT->container(html_writer::link($allurl, get_string('show_all', 'apply', $matchcount)), array(), 'show_all');
        }
    }

    echo $OUTPUT->box_end();
    echo $OUTPUT->box_end();
}


///////////////////////////////////////////////////////////////////////////
// view_one_entry: Print the list of the given user

if ($do_show=='view_one_entry' and $submit_id) {
    $params = array('apply_id'=>$apply->id, 'user_id'=>$user_id, 'id'=>$submit_id);
    $submit = $DB->get_record('apply_submit', $params); 

    if ($submit) {
        echo '<div align="center">';
        echo $OUTPUT->heading(format_text($apply->name), 3);
        echo '</div>';
        $items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id), 'position');
        if (is_array($items)) {
            require('entry_view.php');
            require('entry_button.php');
        }
    }
    else {
        echo '<div align="center">';
        echo $OUTPUT->heading(get_string('no_submit_data', 'apply'), 3);
        echo $OUTPUT->single_button($back_url->out(), get_string('back_button', 'apply'));
        echo '</div>';
    }
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

