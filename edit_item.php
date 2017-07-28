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
 * prints the form to edit a dedicated item
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/jbxl/jbxl_moodle_tools.php');

apply_init_session();

$cmid 	  = required_param('cmid', PARAM_INT);
$typ 	  = optional_param('typ', false, PARAM_ALPHAEXT);
$id 	  = optional_param('id',  false, PARAM_INT);
$action   = optional_param('action', false, PARAM_ALPHAEXT);
$courseid = optional_param('courseid', false, PARAM_INT);

$editurl = new moodle_url('/mod/apply/edit.php', array('id'=>$cmid));

if (!$typ) redirect($editurl->out(false));

$url = new moodle_url('/mod/apply/edit_item.php', array('cmid'=>$cmid));
if ($typ!==false) $url->param('typ', $typ);
if ($id !==false) $url->param('id', $id);
$PAGE->set_url($url);

// set up some general variables
$usehtmleditor = jbxl_can_use_html_editor();


if (($formdata = data_submitted()) and !confirm_sesskey()) {
    print_error('invalidsesskey');
}
if (! $cm = get_coursemodule_from_id('apply', $cmid)) {
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


//if the typ is pagebreak so the item will be saved directly
if ($typ == 'pagebreak') {
    apply_create_pagebreak($apply->id);
    redirect($editurl->out(false));
    exit;
}

if ($id and $item = $DB->get_record('apply_item', array('id'=>$id))) {
    $typ = $item->typ;
}
else {
    $item = new stdClass();
    $item->id = null;
    $item->position = -1;
    if (!$typ) print_error('typemissing', 'apply', $editurl->out(false));
    $item->typ = $typ;
    $item->options = '';
}

require_once($CFG->dirroot.'/mod/apply/item/'.$typ.'/lib.php');

$itemobj = apply_get_item_class($typ);
$itemobj->build_editform($item, $apply, $cm);

if ($itemobj->is_cancelled()) {
    redirect($editurl->out(false));
    exit;
}
if ($itemobj->get_data()) {
    if ($item = $itemobj->save_item()) {
        apply_move_item($item, $item->position);
        redirect($editurl->out(false));
    }
}


////////////////////////////////////////////////////////////////////////////////////
/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

if ($item->id) {
    $PAGE->navbar->add(get_string('edit_item', 'apply'));
}
else {
    $PAGE->navbar->add(get_string('add_item', 'apply'));
}
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

require('tabs.php');

/// Print the main part of the page
echo '<div align="center">';
echo $OUTPUT->heading(format_text($apply->name), 3);
if (isset($error)) echo $error;
echo '</div>';

$itemobj->show_editform();

if ($typ!='label') {
    $PAGE->requires->js('/mod/apply/apply.js');
    $PAGE->requires->js_function_call('set_item_focus', Array('id_itemname'));
}


////////////////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();
