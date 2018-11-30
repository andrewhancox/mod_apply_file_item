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
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_apply/lib.php that by Andreas Grabs)
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT);

$url = new moodle_url('/mod/apply/index.php', array('id'=>$id));
$PAGE->set_url($url);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('invalidcourseid');
}
$context = context_course::instance($course->id);

require_login($course);
$PAGE->set_pagelayout('incourse');


/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$PAGE->navbar->add($strapplys);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(get_string('modulename', 'apply').'&nbsp;'.get_string('activities'));
echo $OUTPUT->header();

/// Get all the appropriate data
if (! $applys = get_all_instances_in_course('apply', $course)) {
    $url = new moodle_url('/course/view.php', array('id'=>$course->id));
    notice(get_string('thereareno', 'moodle', $strapplys), $url);
    die;
}
$usesections = course_format_uses_sections($course->format);

/// Print the list of instances (your module will probably extend this)
$timenow = time();
$strname  = get_string('name');
$strsectionname = get_string('sectionname', 'format_'.$course->format);
$strsubmitnum   = get_string('submit_num', 'apply');

$table = new html_table();

if ($usesections) {
    $table->head  = array($strsectionname, $strname, $strsubmitnum);
    $table->align = array('center', 'left', 'center');
} 
else {
    $table->head  = array($strname, $strsubmitnum);
    $table->align = array('left', 'center');
}

if (has_capability('mod/apply:viewreports', $context)) {
    $userid = 0;
}
else {
    $userid = $USER->id;
}

//
foreach ($applys as $apply) {
    $viewurl = new moodle_url('/mod/apply/view.php', array('id'=>$apply->coursemodule, 'do_show'=>'view'));

    $dimmedclass = $apply->visible ? '' : 'class="dimmed"';
    $link = '<a '.$dimmedclass.' href="'.$viewurl->out().'">'.$apply->name.'</a>';

    if ($usesections) {
        $tabledata = array(get_section_name($course, $apply->section), $link);
    }
    else {
        $tabledata = array($link);
    }
    $tabledata[] = intval(apply_get_valid_submits_count($apply->id, $userid));

    $table->data[] = $tabledata;
}
echo '<br />';

echo html_writer::table($table);

/// Finish the page
echo $OUTPUT->footer();

