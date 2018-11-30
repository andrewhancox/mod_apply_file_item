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

defined('MOODLE_INTERNAL') || die;


require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->dirroot.'/calendar/lib.php');

// for Table
global $Table_in, $Table_params;

$Table_in = false;
$Table_params = new stdClass();


function apply_supports($feature)
{
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_COMPLETION_HAS_RULES:    return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}


function apply_add_instance($apply)
{
    global $DB;

    $apply->time_modified = time();
    $apply->id = '';

    if (empty($apply->open_enable)) {
        $apply->time_open = 0;
    }
    if (empty($apply->close_enable)) {
        $apply->time_close = 0;
    }

    $apply_id = $DB->insert_record('apply', $apply);
    $apply->id = $apply_id;

    // Calendar
    apply_set_calendar_events($apply);

    if (!isset($apply->coursemodule)) {
        $cm = get_coursemodule_from_id('apply', $apply->id);
        $apply->coursemodule = $cm->id;
    }

    $DB->update_record('apply', $apply);

    return $apply_id;
}


function apply_update_instance($apply)
{
    global $DB;

    $apply->time_modified = time();
    $apply->id = $apply->instance;

    if (empty($apply->open_enable)) {
        $apply->time_open = 0;
    }
    if (empty($apply->close_enable)) {
        $apply->time_close = 0;
    }

    apply_set_calendar_events($apply);

    $DB->update_record('apply', $apply);

    return true;
}


function apply_delete_instance($apply_id) 
{
    global $DB;

    $apply_items = $DB->get_records('apply_item', array('apply_id'=>$apply_id));

    if (is_array($apply_items)) {
        foreach ($apply_items as $apply_item) {
            $DB->delete_records('apply_value', array('item_id'=>$apply_item->id));
        }
        if ($del_items = $DB->get_records('apply_item', array('apply_id'=>$apply_id))) {
            foreach ($del_items as $del_item) {
                apply_delete_item($del_item->id, false);
            }
        }
    }

    $ret = $DB->delete_records('apply_submit', array('apply_id'=>$apply_id));
    if ($ret) $ret = $DB->delete_records('event', array('modulename'=>'apply', 'instance'=>$apply_id));
    if ($ret) $ret = $DB->delete_records('apply', array('id'=>$apply_id));

    return $ret;
}


function apply_user_complete($course, $user, $mod, $apply)
{
    return false;
}


function apply_delete_course($course)
{
    global $DB;

    return $DB->delete_records('apply_template', array('course'=>$course->id));
}


function apply_user_outline($course, $user, $mod, $apply)
{
    return null;
}


function appply_cron()
{
    return true;
}


function apply_print_recent_activity($course, $viewfullnames, $timestart)
{
    return false;
}


function apply_get_view_actions() 
{
    return array('view', 'view all');
}


function apply_get_post_actions() 
{
    return array('submit');
}


function apply_reset_userdata($data) 
{
    global $CFG, $DB;

    $resetapplys= array();
    $dropapplys    = array();
    $status     = array();

    $componentstr = get_string('modulenameplural', 'apply');

    foreach ($data as $key=>$value) {
        switch(true) {
            case substr($key, 0, strlen(APPLY_RESETFORM_RESET))==APPLY_RESETFORM_RESET:
                if ($value==1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $resetapplys[] = intval($templist[3]);
                    }
                }
                break;
              case substr($key, 0, strlen(APPLY_RESETFORM_DROP))==APPLY_RESETFORM_DROP:
                if ($value==1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $dropapplys[] = intval($templist[3]);
                    }
                }
                break;
        }
    }

    foreach ($resetapplys as $id) {
        $apply = $DB->get_record('apply', array('id'=>$id));
        apply_delete_all_submit($id);
        $status[] = array('component'=>$componentstr.':'.$apply->name, 'item'=>get_string('resetting_data', 'apply'), 'error'=>false);
    }

    return $status;
}



///////////////////////////////////////////////////////////////////////////////////////////////
//
// Item Handling
//

function apply_clean_input_value($item, $value) 
{
    $itemobj = apply_get_item_class($item->typ);
    return $itemobj->clean_input_value($value);
}


function apply_get_item_class($typ)
{
    global $CFG;

    $itemclass = 'apply_item_'.$typ;

    if (!class_exists($itemclass)) {
        require_once($CFG->dirroot.'/mod/apply/item/'.$typ.'/lib.php');
    }
    return new $itemclass();
}


function apply_load_apply_items($dir='mod/apply/item')
{
    global $CFG;

    $names = get_list_of_plugins($dir);
    $ret_names = array();

    foreach ($names as $name) {
        require_once($CFG->dirroot.'/'.$dir.'/'.$name.'/lib.php');
        if (class_exists('apply_item_'.$name)) {
            $ret_names[] = $name;
        }
    }
    return $ret_names;
}


function apply_load_apply_items_options()
{
    global $CFG;

    $apply_options = array('pagebreak'=>get_string('add_pagebreak', 'apply'));

    if (!$apply_names = apply_load_apply_items('mod/apply/item')) {
        return array();
    }

    foreach ($apply_names as $fn) {
        $apply_options[$fn] = get_string($fn, 'apply');
    }
    asort($apply_options);
    $apply_options = array_merge( array(' '=>get_string('select')), $apply_options );

    return $apply_options;
}


function apply_get_depend_candidates_for_item($apply, $item) 
{
    global $DB;

    $where = "apply_id=? AND typ!='pagebreak' AND hasvalue=1";
    $params = array($apply->id);
    if (isset($item->id) and $item->id) {
        $where .= ' AND id!=?';
        $params[] = $item->id;
    }
    $dependitems = array(0=>get_string('choose'));
    $applyitems  = $DB->get_records_select_menu('apply_item', $where, $params, 'position', 'id, label');

    if (!$applyitems) {
        return $dependitems;
    }

    foreach ($applyitems as $key=>$val) {
        $dependitems[$key] = $val;
    }

    return $dependitems;
}


/*
function apply_create_item($data)
{
    global $DB;

    $item = new stdClass();
    $item->apply_id = $data->apply_id;

    $item->template = 0;
    if (isset($data->templateid)) {
        $item->template = intval($data->templateid);
    }

    $itemname = trim($data->itemname);
    $item->name = ($itemname ? $data->itemname : get_string('no_itemname', 'apply'));

    if (!empty($data->itemlabel)) {
        $item->label = trim($data->itemlabel);
    }
    else {
        $item->label = get_string('no_itemlabel', 'apply');
    }

    $itemobj = apply_get_item_class($data->typ);
    $item->presentation = ''; //the date comes from postupdate() of the itemobj
    $item->hasvalue = $itemobj->get_hasvalue();
    $item->typ         = $data->typ;
    $item->required = 0;
    $item->position = $data->position;

    if (!empty($data->required)) {
        $item->required = $data->required;
    }

    $item->id = $DB->insert_record('apply_item', $item);

    $data->id         = $item->id;
    $data->apply_id = $item->apply_id;
    $data->name     = $item->name;
    $data->label     = $item->label;
    $data->required = $item->required;

    return $itemobj->postupdate($data);
}
*/


function apply_update_item($item)
{
    global $DB;

    if ($item->label==APPLY_ADMIN_REPLY_TAG or $item->label==APPLY_ADMIN_ONLY_TAG) {
        $item->required = 0;
    }

    return $DB->update_record('apply_item', $item);
}


function apply_delete_item($item_id, $renumber=true, $template=false) 
{    
    global $DB;

    $item = $DB->get_record('apply_item', array('id'=>$item_id));

    $fs = get_file_storage();

    if ($template) {
        if ($template->ispublic) {
            $context = context_system::instance();
        } 
        else {
            $context = context_course::instance($template->course);
        }
        $templatefiles = $fs->get_area_files($context->id, 'mod_apply', 'template', $item->id, 'id', false);

        if ($templatefiles) {
            $fs->delete_area_files($context->id, 'mod_apply', 'template', $item->id);
        }
    }
    //
    else {
        if (!$cm = get_coursemodule_from_instance('apply', $item->apply_id)) {
            return false;
        }
        $context = context_module::instance($cm->id);

        $itemfiles = $fs->get_area_files($context->id, 'mod_apply', 'item', $item->id, 'id', false);
        if ($itemfiles) {
            $fs->delete_area_files($context->id, 'mod_apply', 'item', $item->id);
        }
    }

    //
    $DB->delete_records('apply_value', array('item_id'=>$item_id));

    $DB->set_field('apply_item', 'dependvalue', '', array('dependitem'=>$item_id));
    $DB->set_field('apply_item', 'dependitem',   0, array('dependitem'=>$item_id));

    $DB->delete_records('apply_item', array('id'=>$item_id));
    if ($renumber) {
        apply_renumber_items($item->apply_id);
    }
}


function apply_delete_all_items($apply_id)
{
    global $DB;

    if (!$apply = $DB->get_record('apply', array('id'=>$apply_id))) {
        return false;
    }
    if (!$cm = get_coursemodule_from_instance('apply', $apply->id)) {
        return false;
    }
    if (!$course = $DB->get_record('course', array('id'=>$apply->course))) {
        return false;
    }
    if (!$items = $DB->get_records('apply_item', array('apply_id'=>$apply_id))) {
        return false;
    }

    foreach ($items as $item) {
        apply_delete_item($item->id, false);
    }

    if ($submits = $DB->get_records('apply_submit', array('apply_id'=>$apply->id))) {
        foreach ($submits as $submit) {
            $DB->delete_records('apply_submit', array('id'=>$submit->id));
        }
    }
}


function apply_switch_item_required($item)
{
    global $DB;

    if ($item->label==APPLY_ADMIN_REPLY_TAG or $item->label==APPLY_ADMIN_ONLY_TAG) return false;

    $itemobj = apply_get_item_class($item->typ);

    if ($itemobj->can_switch_require()) {
        $new_require_val = (int)!(bool)$item->required;
        $params = array('id'=>$item->id);
        $DB->set_field('apply_item', 'required', $new_require_val, $params);
    }
    return true;
}


function apply_renumber_items($apply_id)
{
    global $DB;

    $items = $DB->get_records('apply_item', array('apply_id'=>$apply_id), 'position');
    $pos = 1;
    if ($items) {
        foreach ($items as $item) {
            $DB->set_field('apply_item', 'position', $pos, array('id'=>$item->id));
            $pos++;
        }
    }
}


function apply_moveup_item($item)
{
    global $DB;

    if ($item->position==1) {
        return true;
    }

    $params = array('apply_id'=>$item->apply_id);
    if (!$items = $DB->get_records('apply_item', $params, 'position')) {
        return false;
    }

    $itembefore = null;
    foreach ($items as $i) {
        if ($i->id==$item->id) {
            if (is_null($itembefore)) {
                return true;
            }
            $itembefore->position = $item->position;
            $item->position--;
            apply_update_item($itembefore);
            apply_update_item($item);
            apply_renumber_items($item->apply_id);
            return true;
        }
        $itembefore = $i;
    }
    return false;
}


function apply_movedown_item($item)
{
    global $DB;

    $params = array('apply_id'=>$item->apply_id);
    if (!$items = $DB->get_records('apply_item', $params, 'position')) {
        return false;
    }

    $movedownitem = null;
    foreach ($items as $i) {
        if (!is_null($movedownitem) and $movedownitem->id==$item->id) {
            $movedownitem->position = $i->position;
            $i->position--;
            apply_update_item($movedownitem);
            apply_update_item($i);
            apply_renumber_items($item->apply_id);
            return true;
        }
        $movedownitem = $i;
    }
    return false;
}


function apply_move_item($moveitem, $pos)
{
    global $DB;

    $params = array('apply_id'=>$moveitem->apply_id);
    if (!$items = $DB->get_records('apply_item', $params, 'position')) {
        return false;
    }
    if (is_array($items)) {
        $index = 1;
        foreach ($items as $item) {
            if ($index==$pos) {
                $index++;
            }
            if ($item->id==$moveitem->id) {
                $moveitem->position = $pos;
                apply_update_item($moveitem);
                continue;
            }
            $item->position = $index;
            apply_update_item($item);
            $index++;
        }
        return true;
    }
    return false;
}


function apply_print_item_preview($item)
{
    if ($item->typ=='pagebreak') return;

    $itemobj = apply_get_item_class($item->typ);
    $itemobj->print_item_preview($item);
}


function apply_print_item_submit($item, $value=false, $highlightrequire=false)
{
    if ($item->typ=='pagebreak') return;

    $itemobj = apply_get_item_class($item->typ);
    $itemobj->print_item_submit($item, $value, $highlightrequire);
}


function apply_print_item_show_value($item, $value=false)
{
    if ($item->typ=='pagebreak') return;

    $itemobj = apply_get_item_class($item->typ);
    $itemobj->print_item_show_value($item, $value);
}



///////////////////////////////////////////////////////////////////////////////////
//
// Calendar Events
//

function apply_set_calendar_events($apply)
{
    global $DB;

    $DB->delete_records('event', array('modulename'=>'apply', 'instance'=>$apply->id));

    if (!$apply->use_calendar) return;

    if (!isset($apply->coursemodule)) {
        $cm = get_coursemodule_from_id('apply', $apply->id);
        $apply->coursemodule = $cm->id;
    }

    // the open-event
    if ($apply->time_open>0) {
        $event = new stdClass();
        $event->name        = get_string('start', 'apply').' '.$apply->name;
        $event->description = format_module_intro('apply', $apply, $apply->coursemodule);
        $event->courseid    = $apply->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'apply';
        $event->instance    = $apply->id;
        $event->eventtype   = 'open';
        $event->timestart   = $apply->time_open;
        $event->visible     = instance_is_visible('apply', $apply);
        if ($apply->time_close>0) {
            $event->timeduration = ($apply->time_close - $apply->time_open);
        }
        else {
            $event->timeduration = 0;
        }

        calendar_event::create($event);
    }

    // the close-event
    if ($apply->time_close>0) {
        $event = new stdClass();
        $event->name        = get_string('stop', 'apply').' '.$apply->name;
        $event->description = format_module_intro('apply', $apply, $apply->coursemodule);
        $event->courseid    = $apply->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'apply';
        $event->instance    = $apply->id;
        $event->eventtype   = 'close';
        $event->timestart   = $apply->time_close;
        $event->visible     = instance_is_visible('apply', $apply);
        $event->timeduration= 0;

        calendar_event::create($event);
    }
}

