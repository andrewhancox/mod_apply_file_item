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
 * prints the tabbed bar
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

defined('MOODLE_INTERNAL') OR die('not allowed');

$tabs = array();
$row  = array();
$inactive  = array();
$activated = array();

//some pages deliver the cmid instead the id
if (isset($cmid) and intval($cmid) and $cmid>0) {
	$used_id = $cmid;
}
else {
	$used_id = $id;
}
if (!$courseid) $courseid = optional_param('courseid', false, PARAM_INT);

//
$context = context_module::instance($used_id);

if (!isset($current_tab)) {
	$current_tab = '';
}

// View my applications
$viewurl = new moodle_url('/mod/apply/view.php', array('id'=>$used_id, 'do_show'=>'view'));
$row[] 	 = new tabobject('view', $viewurl->out(), get_string('overview', 'apply'));

// View all Report
if (has_capability('mod/apply:viewreports', $context)) {
	$url_params = array('id'=>$used_id, 'do_show'=>'view_entries', 'sort'=>'time_modified', 'order'=>'DESC');
	$reporturl  = new moodle_url('/mod/apply/view_entries.php', $url_params);
	$row[] 		= new tabobject('view_entries', $reporturl->out(), get_string('view_entries', 'apply'));
}

// Edit Item and Template
if (has_capability('mod/apply:edititems', $context)) {
	//
	$editurl = new moodle_url('/mod/apply/edit.php', array('id'=>$used_id, 'do_show'=>'edit'));
	$row[] 	 = new tabobject('edit', $editurl->out(), get_string('edit_items', 'apply'));
	//
//	$templateurl = new moodle_url('/mod/apply/edit.php', array('id'=>$used_id, 'do_show'=>'templates'));
//	$row[]		 = new tabobject('templates', $templateurl->out(), get_string('templates', 'apply'));
}


$row[] = new tabobject('', $CFG->wwwroot.'/course/view.php?id='.$courseid, get_string('returnto_course', 'apply'));

if (count($row) > 1) {
	$tabs[] = $row;

    echo '<table align="center" style="margin-bottom:0.0em;"><tr><td>';
    echo '<style type="text/css">';
    include('./html/html.css');
    echo '</style>';
	print_tabs($tabs, $current_tab, $inactive, $activated);
    echo '</td></tr></table>';
}

