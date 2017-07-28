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
 * operate the submitted entry
 *
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

apply_init_session();


////////////////////////////////////////////////////////
//get the params
$id			= required_param('id', PARAM_INT);
//$user_id	= optional_param('user_id',   0, PARAM_INT);
$submit_id  = optional_param('submit_id', 0, PARAM_INT);
$submit_ver = optional_param('submit_ver', -1, PARAM_INT);
$sendemail  = optional_param('send_email', 0, PARAM_INT);
$courseid   = optional_param('courseid',  0, PARAM_INT);
$operate	= optional_param('operate',  'show_page', PARAM_ALPHAEXT);

$urlparams['id']       = $id;
$urlparams['courseid'] = $courseid;
$urlparams['opetate']  = $operate;

$current_tab = '';


///////////////////////////////////////////////////////////////////////////
// Form Data
if (($formdata = data_submitted()) and !confirm_sesskey()) {
	print_error('invalidsesskey');
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


////////////////////////////////////////////////////////
$back_params = array('id'=>$cm->id, 'courseid'=>$courseid, 'do_show'=>'view_entries', 'sort'=>'time_modified', 'order'=>'DESC');
$back_url = new moodle_url($CFG->wwwroot.'/mod/apply/view_entries.php', $back_params);
if (isset($formdata->back_to_entries)) {
	redirect($back_url->out());
}

//
$context = context_module::instance($cm->id);

$name_pattern = $apply->name_pattern;
$req_own_data = false;


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);
//
if (!has_capability('mod/apply:operatesubmit', $context)) {
	apply_print_error_messagebox('operate_is_disable', $id);
	exit;
}


////////////////////////////////////////////////////////
// 
$accept  = '';
$execd   = '';
$sbmtted = false;
if (isset($formdata->radiobtn_accept)) $accept  = $formdata->radiobtn_accept;
if (isset($formdata->checkbox_execd))  $execd   = 'done';
if (isset($formdata->operate_values))  $sbmtted = true;


////////////////////////////////////////////////////////
/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$url_params  = array('id'=>$cm->id, 'courseid'=>$courseid);
$this_url = new moodle_url('/mod/apply/operate_submit.php', $url_params);

$PAGE->navbar->add(get_string('apply:operatesubmit', 'apply'));
$PAGE->set_url($this_url);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();


///////////////////////////////////////////////////////////////////////////
// Check 2
if ((empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context))) {
	notice(get_string('activityiscurrentlyhidden'));
}


//
require('tabs.php');


///////////////////////////////////////////////////////////////////////////
// Operate

$err_message = '';

if ($operate=='operate' and $sbmtted) {
	if (!$SESSION->apply->is_started) {
		print_error('error', '', $CFG->wwwroot.'/mod/apply/view.php?id='.$id);
	}

	// POST/GETデータを拾う
	$submit = apply_save_admin_values($submit_id, $submit_ver); 

	if ($submit) {
		if ($execd=='done') {
			if ($accept!='accept') {
				if ($submit->acked!=APPLY_ACKED_ACCEPT) {
					$err_message = get_string('operation_error_execd', 'apply');
					$operate = 'show_page';
				}
			}
		}
		//
		if ($operate=='operate') {
			$ret = apply_operate_submit($submit->id, $submit->version, $accept, $execd);
			if ($ret) {
				if ($sendemail) {
					apply_send_email_user($cm, $apply, $course, $submit, $accept, $execd);
				}
				$log_info = 'accept='.$accept.' exec='.$execd;
				$event = apply_get_event($cm, 'op_submit', $urlparams, $log_info);
				jbxl_add_to_log($event);
				redirect($back_url, get_string('entry_saved_operation', 'apply'), 1);
			}
			else {
				echo '<div align="center">';
				echo $OUTPUT->heading(get_string('no_submit_data', 'apply'), 4);
				echo $OUTPUT->single_button($back_url, get_string('back_button', 'apply'));
				echo '</div>';
			}
		}
	}

	// Error
	else {
		echo '<div align="center">';
		echo $OUTPUT->heading(get_string('no_submit_data', 'apply'), 4);
		echo $OUTPUT->single_button($back_url, get_string('back_button', 'apply'));
		echo '</div>';
	}
}



///////////////////////////////////////////////////////////////////////////
// Print Entry

$SESSION->apply->is_started = false;
if ($operate=='show_page' and $submit_id) {
	$params = array('id'=>$submit_id);
	$submit = $DB->get_record('apply_submit', $params); 

	echo '<div align="center">';
	echo $OUTPUT->heading(format_text($apply->name), 3);
	echo '</div>';

	$items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id), 'position');
	if (is_array($items)) {
		require('operate_submit_page.php');
		//
		$SESSION->apply->is_started = true;
	}
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

