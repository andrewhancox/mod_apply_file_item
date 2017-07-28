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
 * deletes a template
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/delete_template_form.php');
require_once($CFG->libdir.'/tablelib.php');


$id 		   = required_param('id', PARAM_INT);
$cancel_delete = optional_param('cancel_delete', 0, PARAM_INT);
$should_delete = optional_param('should_delete', 0, PARAM_INT);
$delete_templ  = optional_param('delete_templ',  0, PARAM_INT);
$courseid 	   = optional_param('courseid', 	 0, PARAM_INT);

$current_tab = 'templates';
$this_action = 'delete_template';

//
if (!confirm_sesskey()) {
    print_error('invalidsesskey');
}

////////////////////////////////////////////////////////
// Get the objects
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


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);
require_capability('mod/apply:deletetemplate', $context);


////////////////////////////////////////////////////////
// URL
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$base_url = new moodle_url('/mod/apply/'.$this_action.'.php');
$base_url->params(array('id'=>$id, 'courseid'=>$courseid));

$delete_url = new moodle_url($base_url);
if ($cancel_delete) {
    $delete_url->param('cancel_delete', $cancel_delete);
}
if ($should_delete) {
    $delete_url->param('should_delete', $should_delete);
}
if ($delete_templ) {
    $delete_url->param('delete_templ', $delete_templ);
}


////////////////////////////////////////////////////////
// Form Data
$mform = new mod_apply_delete_template_form();
$newformdata = array('id'=>$id, 'delete_templ'=>$delete_templ, 'confirm_delete'=>'1');

$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect($base_url->out(false));
}

if (isset($formdata->confirm_delete) and $formdata->confirm_delete==1) {
    if (!$template = $DB->get_record('apply_template', array('id'=>$delete_templ))) {
        print_error('error');
    }
    if ($template->ispublic) {
        $systemcontext = context_system::instance();
        require_capability('mod/apply:createpublictemplate', $systemcontext);
        require_capability('mod/apply:deletetemplate', $systemcontext);
    }
    apply_delete_template($template);
    redirect($base_url->out(false));
}

if ($cancel_delete==1) {
    $edit_url = new moodle_url('/mod/apply/edit.php', array('id'=>$id, 'do_show'=>'templates'));
    redirect($edit_url->out());
}


///////////////////////////////////////////////////////////////////////////
/// Print the page header

$PAGE->navbar->add(get_string('apply:delete_template', 'apply'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_url($delete_url);
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

require('tabs.php');


///////////////////////////////////////////////////////////////////////////
/// Print the main part of the page
$strdeleteapply = get_string('delete_template', 'apply');
//
echo '<div align="center">';
echo $OUTPUT->heading($strdeleteapply, 3);
echo '</div>';

if ($should_delete==1) {
    echo $OUTPUT->box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
    echo $OUTPUT->heading(get_string('confirm_delete_template', 'apply'), 4);
    $mform->display();
    echo $OUTPUT->box_end();
} 
else {
    //first we get the own templates
    $templates = apply_get_template_list($course, 'own');
    if (!is_array($templates)) {
        echo $OUTPUT->box(get_string('no_templates_available_yet', 'apply'), 'generalbox boxaligncenter');
        echo $OUTPUT->box(get_string('no_templates_available_yet', 'apply'), 'generalbox boxaligncenter boxwidthwide');
    }
	else {
        echo $OUTPUT->heading(get_string('course'), 4);
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
        $tablecolumns = array('template', 'action');
        $tableheaders = array(get_string('templates', 'apply'), '');
        $tablecourse = new flexible_table('apply_template_course_table');

        $tablecourse->define_columns($tablecolumns);
        $tablecourse->define_headers($tableheaders);
        $tablecourse->define_baseurl($base_url);
        $tablecourse->column_style('action', 'width', '10%');

        $tablecourse->sortable(false);
        $tablecourse->set_attribute('width', '100%');
        $tablecourse->set_attribute('class', 'generaltable');
        $tablecourse->setup();

        foreach ($templates as $template) {
            $data = array();
            $data[] = $template->name;
            $url = new moodle_url($base_url, array('id'=>$id, 'delete_templ'=>$template->id, 'should_delete'=>1,));

            $data[] = $OUTPUT->single_button($url, $strdeleteapply, 'post');
            $tablecourse->add_data($data);
        }
        $tablecourse->finish_output();
        echo $OUTPUT->box_end();
    }
    //now we get the public templates if it is permitted
    $systemcontext = context_system::instance();
    if (has_capability('mod/apply:createpublictemplate', $systemcontext) AND
        has_capability('mod/apply:deletetemplate', $systemcontext)) {
        $templates = apply_get_template_list($course, 'public');
        if (!is_array($templates)) {
            echo $OUTPUT->box(get_string('no_templates_available_yet', 'apply'), 'generalbox boxaligncenter');
        }
		else {
            echo $OUTPUT->heading(get_string('public', 'apply'), 4);
            echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
            $tablecolumns = array('template', 'action');
            $tableheaders = array(get_string('templates', 'apply'), '');
            $tablepublic = new flexible_table('apply_template_public_table');

            $tablepublic->define_columns($tablecolumns);
            $tablepublic->define_headers($tableheaders);
            $tablepublic->define_baseurl($base_url);
            $tablepublic->column_style('action', 'width', '10%');

            $tablepublic->sortable(false);
            $tablepublic->set_attribute('width', '100%');
            $tablepublic->set_attribute('class', 'generaltable');
            $tablepublic->setup();

            foreach ($templates as $template) {
                $data = array();
                $data[] = $template->name;
                $url = new moodle_url($base_url, array('id'=>$id, 'delete_templ'=>$template->id, 'should_delete'=>1,));

                $data[] = $OUTPUT->single_button($url, $strdeleteapply, 'post');
                $tablepublic->add_data($data);
            }
            $tablepublic->finish_output();
            echo $OUTPUT->box_end();
        }
    }

    echo $OUTPUT->box_start('boxaligncenter boxwidthnormal');
    $url = new moodle_url($base_url, array('id'=>$id, 'cancel_delete'=>1,));

    echo $OUTPUT->single_button($url, get_string('back'), 'post');
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();

