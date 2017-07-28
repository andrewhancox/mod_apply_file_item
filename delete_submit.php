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
 * prints the form to confirm the deleting of a submit
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/delete_submit_form.php');
require_once(dirname(__FILE__).'/jbxl/jbxl_moodle_tools.php');
require_once(dirname(__FILE__).'/locallib.php');

$id 		= required_param('id', PARAM_INT);
$submit_id 	= required_param('submit_id', PARAM_INT);
$submit_ver = optional_param('submit_ver', -1, PARAM_INT);
$courseid 	= optional_param('courseid', false, PARAM_INT);
$action 	= optional_param('action', '', PARAM_ALPHAEXT);

$urlparams['id']       = $id;
$urlparams['courseid'] = $courseid;
$urlparams['action']   = $action;


if (!confirm_sesskey()) {
	print_error('invalidsesskey');
}

//
if (!$submit_id) {
    print_error('no_submit_data', 'apply', 'mod/apply/view.php?id='.$id.'&do_show=view');
}

$submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
if (!$submit) {
    print_error('no_submit_data', 'apply', 'mod/apply/view.php?id='.$id.'&do_show=view');
}

//
$PAGE->set_url('/mod/apply/delete_submit.php', array('id'=>$id, 'submit_id'=>$submit_id, 'submit_ver'=>$submit_ver));

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


//
require_login($course, true, $cm);

//
$context = context_module::instance($cm->id);

if ($USER->id!=$submit->user_id) {
	require_capability('mod/apply:deletesubmissions', $context);
}


//
$mform = new mod_apply_delete_submit_form();
$newformdata = array('id'=>$id, 'submit_id'=>$submit_id, 'confirmdelete'=>'1', 'do_show'=>'edit', 'action'=>$action);

$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
	redirect('view.php?id='.$id.'&do_show=view');
}


// Discard
if (isset($formdata->confirmdelete) and $formdata->confirmdelete==1) {
    if ($submit = $DB->get_record('apply_submit', array('id'=>$submit_id))) {
		//
		if (isset($formdata->action) and $formdata->action=='delete_submit' and $apply->enable_deletemode) {
			// 任意の申請を削除
			require_capability('mod/apply:deletesubmissions', $context);
        	apply_delete_submit($submit_id);
			$event = apply_get_event($cm, 'delete', $urlparams, 'delete_submit');
			jbxl_add_to_log($event);
        	redirect('view_entries.php?id='.$id.'&do_show=view_entries');
		}
		//
		else {
			$log_url = 'delete_submit.php?id='.$cm->id.'&submit_id='.$submit_id.'&submit_ver='.$submit_ver;
			if ($submit->version<=1 and $submit->acked!=APPLY_ACKED_ACCEPT) {
				// 全体を削除可能
        		apply_delete_submit_safe($submit_id);
				$event = apply_get_event($cm, 'delete', $urlparams, 'delete');
				jbxl_add_to_log($event);
			}
			else if ($submit->acked!=APPLY_ACKED_ACCEPT) {
				// 最新の申請（未認証）のみ取消可能（ロールバック）
        		apply_rollback_submit($submit_id);
				$event = apply_get_event($cm, 'delete', $urlparams, 'rollback');
				jbxl_add_to_log($event);
			}
			else {
				// 申請の解除
        		apply_cancel_submit($submit_id);
				$event = apply_get_event($cm, 'delete', $urlparams, 'cancel');
				jbxl_add_to_log($event);
			}
			//
        	redirect('view.php?id='.$id.'&do_show=view');
		}
    }
}


///////////////////////////////////////////////////////////////////////////
// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');


if ($action=='delete_submit' and $apply->enable_deletemode) {
	// 任意の申請を削除
	require_capability('mod/apply:deletesubmissions', $context);
	$PAGE->navbar->add(get_string('delete_submit', 'apply'));
}
//
else {
	if ($submit->version<=1 and $submit->acked!=APPLY_ACKED_ACCEPT) {
		// 全体を削除可能
		$PAGE->navbar->add(get_string('delete_entry', 'apply'));
	}
	else if ($submit->acked!=APPLY_ACKED_ACCEPT) {
		// 最新の申請（未認証）のみ取消可能（ロールバック）
		$PAGE->navbar->add(get_string('rollback_entry', 'apply'));
	}
	else {
		// 申請の解除
		$PAGE->navbar->add(get_string('cancel_entry', 'apply'));
	}
}


$PAGE->set_heading(format_string($course->fullname), 3);
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();


///////////////////////////////////////////////////////////////////////////
///Print the main part of the page
echo '<div align="center">';
echo $OUTPUT->heading(format_text($apply->name), 3);
echo '</div>';
echo '<br />';
echo $OUTPUT->box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');

//
if ($action=='delete_submit' and $apply->enable_deletemode) {
	// 任意の申請を削除
	require_capability('mod/apply:deletesubmissions', $context);
	$user_name = jbxl_get_user_name($submit->user_id, $apply->name_pattern);
	echo $OUTPUT->heading(get_string('confirm_delete_submit', 'apply'), 4);
	echo '<br />';
	echo $OUTPUT->heading($user_name.'&nbsp;:&nbsp;'.$submit->title, 4);
}
//
else {
	if ($submit->version<=1 and $submit->acked!=APPLY_ACKED_ACCEPT) {
		// 全体を削除可能
		echo $OUTPUT->heading(get_string('confirm_delete_entry', 'apply'), 4);
		echo '<br />';
		echo $OUTPUT->heading($submit->title, 4);
	}
	else if ($submit->acked!=APPLY_ACKED_ACCEPT) {
		// 最新の申請（未認証）のみ取消可能（ロールバック）
		echo $OUTPUT->heading(get_string('confirm_rollback_entry', 'apply'), 4);
		echo '<br />';
		echo $OUTPUT->heading($submit->title, 4);
	}
	else {
		// 申請の解除
		echo $OUTPUT->heading(get_string('confirm_cancel_entry', 'apply'), 4);
		echo '<br />';
		echo $OUTPUT->heading($submit->title, 4);
	}
}

//
$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

