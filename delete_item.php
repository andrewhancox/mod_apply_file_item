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
 * deletes an item of the apply
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/delete_item_form.php');

$id 		= required_param('id', PARAM_INT);
$deleteitem = required_param('deleteitem', PARAM_INT);
$courseid 	= optional_param('courseid', false, PARAM_INT);

$PAGE->set_url('/mod/apply/delete_item.php', array('id'=>$id, 'deleteitem'=>$deleteitem));

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

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

require_capability('mod/apply:edititems', $context);

$mform = new mod_apply_delete_item_form();
$newformdata = array('id'=>$id, 'deleteitem'=>$deleteitem, 'confirmdelete'=>'1'); 
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect('edit.php?id='.$id);
}

if (isset($formdata->confirmdelete) AND $formdata->confirmdelete == 1) {
    apply_delete_item($formdata->deleteitem);
    redirect('edit.php?id='.$id);
}


/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$PAGE->navbar->add(get_string('delete_item', 'apply'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();


///////////////////////////////////////////////////////////////////////////
/// Print the main part of the page

echo '<div align="center">';
echo $OUTPUT->heading(format_text($apply->name), 3);
echo $OUTPUT->box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
echo $OUTPUT->heading(get_string('confirm_delete_item', 'apply'), 4);
print_string('related_items_deleted', 'apply');
echo '</div>';

$mform->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();


