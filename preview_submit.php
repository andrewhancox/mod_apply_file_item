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


$id          = required_param('id', PARAM_INT);
$courseid    = optional_param('courseid', 0, PARAM_INT);
$this_action = 'preview_submit';


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
//if (!has_capability('mod/apply:submit', $context)) {
//    apply_print_error_messagebox('apply_is_disable', $id);
//    exit;
//}


///////////////////////////////////////////////////////////////////////////
// Print the page header
$base_url = new moodle_url('/mod/apply/'.$this_action.'.php');
$base_url->params(array('id'=>$cm->id, 'courseid'=>$courseid));
$this_url = new moodle_url($base_url);

$PAGE->navbar->add(get_string('apply:'.$this_action, 'apply'));
$PAGE->set_url($this_url);
$PAGE->set_pagelayout('print');
$PAGE->set_title(format_string($apply->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo '<div align="center">';
echo '<h3>'.get_string('apply:preview_submit', 'apply').'</h3><hr />';
echo $OUTPUT->heading(format_text($apply->name), 3);
echo '</div>';


///////////////////////////////////////////////////////////////////////////
// Check 2
if ((empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context))) {
    notice(get_string('activityiscurrentlyhidden'));
}


///////////////////////////////////////////////////////////////////////////
$items  = $DB->get_records_select('apply_item', 'apply_id=?', array($apply->id), 'position');
if (is_array($items)) {
    require('preview_submit_page.php');
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();

