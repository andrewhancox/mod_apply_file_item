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
 * prints the form to edit the apply items such moving, deleting and so on
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/edit_form.php');

apply_init_session();

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);

if (($formdata = data_submitted()) and !confirm_sesskey()) {
    print_error('invalidsesskey');
}

$do_show      = optional_param('do_show', 'edit', PARAM_ALPHAEXT);
$moveupitem   = optional_param('moveupitem', false, PARAM_INT);
$movedownitem = optional_param('movedownitem', false, PARAM_INT);
$moveitem     = optional_param('moveitem', false, PARAM_INT);
$movehere     = optional_param('movehere', false, PARAM_INT);
$switchitemrequired = optional_param('switchitemrequired', false, PARAM_INT);

$current_tab = $do_show;
$this_action = 'edit';

$this_url = new moodle_url('/mod/apply/edit.php', array('id'=>$id, 'do_show'=>$do_show));

if (! $cm = get_coursemodule_from_id('apply', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}
if (! $apply = $DB->get_record("apply", array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
if (!$courseid) $courseid = $course->id;

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/apply:edititems', $context);

//move up/down items
if ($moveupitem) {
    $item = $DB->get_record('apply_item', array('id'=>$moveupitem));
    apply_moveup_item($item);
}
if ($movedownitem) {
    $item = $DB->get_record('apply_item', array('id'=>$movedownitem));
    apply_movedown_item($item);
}

//moving of items
if ($movehere && isset($SESSION->apply->moving->movingitem)) {
    $item = $DB->get_record('apply_item', array('id'=>$SESSION->apply->moving->movingitem));
    apply_move_item($item, intval($movehere));
    $moveitem = false;
}
if ($moveitem) {
    $item = $DB->get_record('apply_item', array('id'=>$moveitem));
    if (!property_exists($SESSION->apply, 'moving')) $SESSION->apply->moving = new stdClass();
    $SESSION->apply->moving->shouldmoving = 1;
    $SESSION->apply->moving->movingitem = $moveitem;
} 
else {
    unset($SESSION->apply->moving);
}

if ($switchitemrequired) {
    $item = $DB->get_record('apply_item', array('id'=>$switchitemrequired));
    @apply_switch_item_required($item);
    redirect($this_url->out(false));
    exit;
}

//the create_template-form
$create_template_form = new apply_edit_create_template_form();
$create_template_form->set_applydata(array('context'=>$context, 'course'=>$course));
$create_template_form->set_form_elements();
$create_template_form->set_data(array('id'=>$id, 'do_show'=>'templates'));
$create_template_formdata = $create_template_form->get_data();

if (isset($create_template_formdata->savetemplate) && $create_template_formdata->savetemplate==1){
    //
    //check the capabilities to create templates
    if (!has_capability('mod/apply:createprivatetemplate', $context) AND
        !has_capability('mod/apply:createpublictemplate',  $context)) {
        print_error('cannotsavetempl', 'apply');
    }
    if (trim($create_template_formdata->templatename) == '') {
        $savereturn = 'notsaved_name';
    } 
    //
    else {
        //if the apply is located on the frontpage then templates can be public
        if (has_capability('mod/apply:createpublictemplate', context_system::instance())) {
            $create_template_formdata->ispublic = isset($create_template_formdata->ispublic) ? 1 : 0;
        }
        else {
            $create_template_formdata->ispublic = 0;
        }
        if (!apply_save_as_template($apply, $create_template_formdata->templatename, $create_template_formdata->ispublic)) {
            $savereturn = 'failed';
        }
        else {
            $savereturn = 'saved';
        }
    }
}


//get the applyitems
$lastposition = 0;
$applyitems = $DB->get_records('apply_item', array('apply_id'=>$apply->id), 'position');
if (is_array($applyitems)) {
    $applyitems = array_values($applyitems);
    if (count($applyitems)>0) {
        $lastitem = $applyitems[count($applyitems)-1];
        $lastposition = $lastitem->position;
    } 
    else {
        $lastposition = 0;
    }
}
$lastposition++;


//the add_item-form
$add_item_form = new apply_edit_add_question_form('edit_item.php');
$add_item_form->set_data(array('cmid'=>$id, 'position'=>$lastposition));

//the use_template-form
$use_template_form = new apply_edit_use_template_form('use_templ.php');
$use_template_form->set_applydata(array('course' => $course));
$use_template_form->set_form_elements();
$use_template_form->set_data(array('id'=>$id));

/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

if ($do_show=='templates') {
    $PAGE->navbar->add(get_string('apply:edit_templates', 'apply'));
}
else {
    $PAGE->navbar->add(get_string('apply:edititems', 'apply'));
}

$PAGE->set_url($this_url);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

/// print the tabs
require('tabs.php');


echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

///////////////////////////////////////////////////////////////////////////
/// Print the main part of the page

$savereturn=isset($savereturn)?$savereturn:'';

//print the messages
if ($savereturn == 'notsaved_name') {
    echo '<p align="center"><b><font color="red">'.get_string('name_required', 'apply').'</font></b></p>';
}
if ($savereturn == 'saved') {
    echo '<p align="center"><b><font color="green">'.get_string('template_saved', 'apply').'</font></b></p>';
}
if ($savereturn == 'failed') {
    echo '<p align="center"><b><font color="red">'.get_string('saving_failed', 'apply').'</font></b></p>';
}


///////////////////////////////////////////////////////////////////////////
///print the template-section

if ($do_show=='templates') {
    //
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    $use_template_form->display();

    if (has_capability('mod/apply:createprivatetemplate', $context) OR
                has_capability('mod/apply:createpublictemplate', $context)) {
        $deleteurl = new moodle_url('/mod/apply/delete_template.php', array('id' => $id));
        $create_template_form->display();
        echo '<p><a href="'.$deleteurl->out().'">'.
             get_string('delete_templates', 'apply').
             '</a></p>';
    }
    else {
        echo '&nbsp;';
    }

/*
    //Import & Export
    if (has_capability('mod/apply:edititems', $context)) {
        $urlparams = array('action'=>'exportfile', 'id'=>$id);
        $exporturl = new moodle_url('/mod/apply/export.php', $urlparams);
        $importurl = new moodle_url('/mod/apply/import.php', array('id'=>$id));
        echo '<p>
            <a href="'.$exporturl->out().'">'.get_string('export_templates', 'apply').'</a>/
            <a href="'.$importurl->out().'">'.get_string('import_templates', 'apply').'</a>
        </p>';
    }
*/
    echo $OUTPUT->box_end();
}


///////////////////////////////////////////////////////////////////////////
///print the Item-Edit-section

if ($do_show=='edit') {
    //
    // preview icon
    $preview_img = $OUTPUT->pix_icon('t/preview', get_string('preview'));
    $preview_url = new moodle_url('/mod/apply/preview_submit.php');
    $preview_url->params(array('id'=>$cm->id, 'courseid'=>$courseid, 'action'=>'preview_submit'));
    echo '<a href="'.$preview_url->out().'" '.'target="_blank">'.$preview_img.'</a>';

    // Item Add Menu
    $add_item_form->display();

    if (is_array($applyitems)) {
        //
        $align = right_to_left() ? 'right' : 'left';

//        $helpbutton = $OUTPUT->help_icon('preview', 'apply');
//        echo $OUTPUT->heading($helpbutton . get_string('preview', 'apply'), 4);

        if (isset($SESSION->apply->moving) AND $SESSION->apply->moving->shouldmoving == 1) {
            $anker = '<a href="edit.php?id='.$id.'">';
            $anker .= get_string('cancel_moving', 'apply');
            $anker .= '</a>';
            echo $OUTPUT->heading($anker, 4);
        }

        //check, if there exists required-elements
        $params = array('apply_id' => $apply->id, 'required' => 1);
        $countreq = $DB->count_records('apply_item', $params);
        if ($countreq > 0) {
            echo '<span class="apply_required_mark">(*)';
            echo get_string('items_are_required', 'apply');
            echo '</span>';
        }

        //use list instead a table
        echo $OUTPUT->box_start('apply_items');
        if (isset($SESSION->apply->moving) AND $SESSION->apply->moving->shouldmoving == 1) {
            $moveposition = 1;
            $movehereurl = new moodle_url($this_url, array('movehere'=>$moveposition));
            //only shown if shouldmoving = 1
            echo $OUTPUT->box_start('apply_item_box_'.$align.' clipboard');
            $buttonlink = $movehereurl->out();
            $strbutton = get_string('move_here', 'apply');
            $src = $OUTPUT->image_url('movehere');
            echo '<a title="'.$strbutton.'" href="'.$buttonlink.'">
                    <img class="movetarget" alt="'.$strbutton.'" src="'.$src.'" />
                  </a>';
            echo $OUTPUT->box_end();
        }


        //print the inserted items
        $itempos = 0;
        foreach ($applyitems as $applyitem) {
            //
            if ($applyitem->typ=='tablestart') echo '<hr style="border: 1px dashed" />';

            $itempos++;
            //hiding the item to move
            if (isset($SESSION->apply->moving)) {
                if ($SESSION->apply->moving->movingitem == $applyitem->id) {
                    continue;
                }
            }
            if ($applyitem->dependitem > 0) {
                $dependstyle = ' apply_depend';
            }
            else {
                $dependstyle = '';
            }
            echo $OUTPUT->box_start('apply_item_box_'.$align.$dependstyle);

            /*
            //if ($applyitem->hasvalue==1) {
                echo $OUTPUT->box_start('apply_item_number_'.$align);
                echo $itempos;
                echo $OUTPUT->box_end();
            //} */

            echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
            echo $OUTPUT->box_start('apply_item_commands_'.$align);
            echo '<span class="apply_item_commands">';
            echo '('.get_string('position', 'apply').':'.$itempos .')';
            echo '</span>';
            //print the moveup-button
            if ($applyitem->position > 1) {
                echo '<span class="apply_item_command_moveup">';
                $moveupurl = new moodle_url($this_url, array('moveupitem'=>$applyitem->id));
                $buttonlink = $moveupurl->out();
                $strbutton = get_string('moveup_item', 'apply');
                echo '<a class="icon up" title="'.$strbutton.'" href="'.$buttonlink.'">
                        <img alt="'.$strbutton.'" src="'.$OUTPUT->image_url('t/up') . '" />
                      </a>';
                echo '</span>';
            }
            //print the movedown-button
            if ($applyitem->position < $lastposition - 1) {
                echo '<span class="apply_item_command_movedown">';
                $urlparams = array('movedownitem'=>$applyitem->id);
                $movedownurl = new moodle_url($this_url, $urlparams);
                $buttonlink = $movedownurl->out();
                $strbutton = get_string('movedown_item', 'apply');
                echo '<a class="icon down" title="'.$strbutton.'" href="'.$buttonlink.'">
                        <img alt="'.$strbutton.'" src="'.$OUTPUT->image_url('t/down') . '" />
                      </a>';
                echo '</span>';
            }
            //print the move-button
            echo '<span class="apply_item_command_move">';
            $moveurl = new moodle_url($this_url, array('moveitem'=>$applyitem->id));
            $buttonlink = $moveurl->out();
            $strbutton = get_string('move_item', 'apply');
            echo '<a class="editing_move" title="'.$strbutton.'" href="'.$buttonlink.'">
                    <img alt="'.$strbutton.'" src="'.$OUTPUT->image_url('t/move') . '" />
                  </a>';
            echo '</span>';
            //print the button to edit the item
            if ($applyitem->typ != 'pagebreak') {
                echo '<span class="apply_item_command_edit">';
                $editurl = new moodle_url('/mod/apply/edit_item.php');
                $editurl->params(array('do_show'=>$do_show, 'cmid'=>$id, 'id'=>$applyitem->id, 'typ'=>$applyitem->typ));

                // in edit_item.php the param id is used for the itemid
                // and the cmid is the id to get the module
                $buttonlink = $editurl->out();
                $strbutton = get_string('edit_item', 'apply');
                echo '<a class="editing_update" title="'.$strbutton.'" href="'.$buttonlink.'">
                        <img alt="'.$strbutton.'" src="'.$OUTPUT->image_url('t/edit') . '" />
                      </a>';
                echo '</span>';
            }

            //print the toggle-button to switch required yes/no
            if ($applyitem->hasvalue == 1) {
                echo '<span class="apply_item_command_toggle">';
                if ($applyitem->required == 1) {
                    $buttontitle = get_string('switch_item_to_not_required', 'apply');
                    $buttonimg = $OUTPUT->image_url('required', 'apply');
                }
                else {
                    $buttontitle = get_string('switch_item_to_required', 'apply');
                    $buttonimg = $OUTPUT->image_url('notrequired', 'apply');
                }
                $urlparams = array('switchitemrequired'=>$applyitem->id);
                $requiredurl = new moodle_url($this_url, $urlparams);
                $buttonlink = $requiredurl->out();
                echo '<a class="icon '.
                        'apply_switchrequired" '.
                        'title="'.$buttontitle.'" '.
                        'href="'.$buttonlink.'">'.
                        '<img alt="'.$buttontitle.'" src="'.$buttonimg.'" />'.
                        '</a>';
                echo '</span>';
            }

            //print the delete-button
            echo '<span class="apply_item_command_toggle">';
            $deleteitemurl = new moodle_url('/mod/apply/delete_item.php');
            $deleteitemurl->params(array('id'=>$id, 'do_show'=>$do_show, 'deleteitem'=>$applyitem->id));

            $buttonlink = $deleteitemurl->out();
            $strbutton = get_string('delete_item', 'apply');
            $src = $OUTPUT->image_url('t/delete');
            echo '<a class="icon delete" title="'.$strbutton.'" href="'.$buttonlink.'">
                    <img alt="'.$strbutton.'" src="'.$src.'" />
                  </a>';
            echo '</span>';
            echo $OUTPUT->box_end();
            if ($applyitem->typ != 'pagebreak') {
                echo '<div style="padding-bottom:10px"></div>';
                apply_print_item_preview($applyitem);
            }
            else {
                echo $OUTPUT->box_start('apply_pagebreak');
                echo get_string('pagebreak', 'apply').'<hr class="apply_pagebreak" />';
                echo $OUTPUT->box_end();
            }
            echo $OUTPUT->box_end();
            echo $OUTPUT->box_end();

            //
            if ($applyitem->typ=='tableend') echo '<hr style="border: 1px dotted" />';

            if (isset($SESSION->apply->moving) AND $SESSION->apply->moving->shouldmoving == 1) {
                $moveposition++;
                $movehereurl->param('movehere', $moveposition);
                echo $OUTPUT->box_start('clipboard'); //only shown if shouldmoving = 1
                $buttonlink = $movehereurl->out();
                $strbutton = get_string('move_here', 'apply');
                $src = $OUTPUT->image_url('movehere');
                echo '<a title="'.$strbutton.'" href="'.$buttonlink.'">
                        <img class="movetarget" alt="'.$strbutton.'" src="'.$src.'" />
                      </a>';
                echo $OUTPUT->box_end();
            }
            echo '<div class="clearer">&nbsp;</div>';
        }
        // close Table
        if ($Table_in) {
            apply_close_table_tag();
            echo '<div style="color:#c00000">['.get_string('not_close_table','apply').']</div>';
            echo '<hr style="border: 1px dotted" />';
        }

        echo $OUTPUT->box_end();
    }
    else {
        echo $OUTPUT->box(get_string('no_items_available_yet', 'apply'), 'generalbox boxaligncenter');
    }
}

echo $OUTPUT->box_end();


///////////////////////////////////////////////////////////////////////////
/// Finish the page

echo $OUTPUT->footer();
