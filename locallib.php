<?php

defined('MOODLE_INTERNAL') || die;


//
define('APPLY_SUBMIT_TITLE_TAG','submit_title');
define('APPLY_SUBMIT_ONLY_TAG', 'submit_only');
define('APPLY_ADMIN_REPLY_TAG', 'admin_reply');
define('APPLY_ADMIN_ONLY_TAG',  'admin_only');

define('APPLY_CLASS_DRAFT',  0);
define('APPLY_CLASS_NEW',    1);
define('APPLY_CLASS_UPDATE', 2);
define('APPLY_CLASS_CANCEL', 3);

define('APPLY_ACKED_NOTYET', 0);
define('APPLY_ACKED_ACCEPT', 1);
define('APPLY_ACKED_REJECT', 2);

define('APPLY_EXECD_NOTYET', 0);
define('APPLY_EXECD_DONE',   1);


define('APPLY_DECIMAL',          '.');
define('APPLY_THOUSAND',         ',');
define('APPLY_RESETFORM_RESET',  'apply_reset_data_');
define('APPLY_RESETFORM_DROP',   'apply_drop_apply_');
define('APPLY_MAX_PIX_LENGTH',    400);
define('APPLY_DEFAULT_PAGE_COUNT',20);


//
require_once(dirname(__FILE__).'/lib.php'); 



function apply_init_session()
{
    global $SESSION;

    if (!empty($SESSION)) {
        if (!isset($SESSION->apply) or !is_object($SESSION->apply)) {
            $SESSION->apply = new stdClass();
        }
    }
}


function apply_get_editor_options() 
{
    return array('maxfiles'=>EDITOR_UNLIMITED_FILES, 'trusttext'=>true);
}



///////////////////////////////////////////////////////////////////////////////////
//
// Page Break
//

function apply_create_pagebreak($apply_id) 
{
    global $DB;

    $lastposition = $DB->count_records('apply_item', array('apply_id'=>$apply_id));
    if ($lastposition==apply_get_last_break_position($apply_id)) {
        return false;
    }

    $item = new stdClass();
    $item->apply_id = $apply_id;
    $item->template = 0;
    $item->name     = '';
    $item->presentation = '';
    $item->hasvalue = 0;
    $item->typ      = 'pagebreak';
    $item->required = 0;
    $item->position = $lastposition + 1;

    return $DB->insert_record('apply_item', $item);
}


function apply_get_all_break_positions($apply_id) 
{
    global $DB;

    $params = array('typ'=>'pagebreak', 'apply_id'=>$apply_id);
    $allbreaks = $DB->get_records_menu('apply_item', $params, 'position', 'id, position');
    if (!$allbreaks) return false;

    return array_values($allbreaks);
}


function apply_get_last_break_position($apply_id)
{
    if (!$allbreaks=apply_get_all_break_positions($apply_id)) {
        return false;
    }

    return $allbreaks[count($allbreaks)-1];
}


function apply_get_page_to_continue($apply_id, $user_id=0)
{
    global $DB;

    $allbreaks = apply_get_all_break_positions($apply_id);
    if (!$allbreaks) return false;

    if ($user_id) {
        $userselect = 'AND as.user_id=:user_id ';
        $usergroup  = '';
    }
    else {
        $userselect = '';
        $usergroup  = 'GROUP BY as.user_id ';
    }

    $where = 'WHERE as.id=av.submit_id AND av.version=0 AND as.apply_id=:apply_id AND ai.id=av.item_id ';
    $where.= $userselect.$usergroup;

    //
    $sql = 'SELECT MAX(ai.position) FROM {apply_submit} as, {apply_value} av, {apply_item} ai '.$where;
    $params = array();
    $params['apply_id'] = $apply_id;

    $lastpos = $DB->get_field_sql($sql, $params);

    foreach ($allbreaks as $pagenr=>$br) {
        if ($lastpos<$br) return $pagenr;
    }
    return count($allbreaks);
}



///////////////////////////////////////////////////////////////////////////////////
//
// Submit Handling
//

function apply_get_submits_select($apply_id, $user_id=0, $where='', array $params=null, $sort='', $start_page=false, $page_count=false)
{
    global $DB;

    $params = (array)$params;
    $params['apply_id'] = $apply_id;

    if ($sort) $sortsql = ' ORDER BY '.$sort;
    else       $sortsql = '';

    if ($user_id) $where .= ' s.user_id='.$user_id.' AND';

    $sql = 'SELECT s.* FROM {user} u, {apply_submit} s WHERE '.$where.' u.id=s.user_id AND s.apply_id=:apply_id '.$sortsql;

    if ($start_page===false or $page_count===false) {
        $start_page = false;
        $page_count = false;
    }

    $ret = $DB->get_records_sql($sql, $params, $start_page, $page_count);
    return $ret;
}


function apply_get_valid_submits($apply_id, $user_id=0)
{
    global $DB;

    $select = 'version>0 AND class!=0 AND apply_id=? ';     // NOT APPLY_CLASS_DRAFT
    $params = array($apply_id);

    if ($user_id) {
        $select.= 'AND user_id=?';
        $params[] = $user_id;
    }
    $submits = $DB->get_records_select('apply_submit', $select, $params);

    return $submits;
}


function apply_get_valid_submits_count($apply_id, $user_id=0)
{
    $submits = apply_get_valid_submits($apply_id, $user_id);
    if (!$submits) return 0;

    return count($submits);
}


function apply_get_all_submits($apply_id, $user_id=0)
{
    global $DB;

    $params = array('apply_id'=>$apply_id);
    if ($user_id) {
        $params['user_id'] = $user_id;
    }
    $submits = $DB->get_records('apply_submit', $params);

    return $submits;
}


function apply_get_all_submits_count($apply_id, $user_id=0)
{
    $submits = apply_get_all_submits($apply_id, $user_id);

    if (!$submits) return 0;
    return count($submits);
}


function apply_create_submit($apply_id, $user_id=0)
{
    global $DB, $USER;

    if (!$user_id) $user_id = $USER->id;

    $submit = new stdClass();
    $submit->apply_id       = $apply_id;
    $submit->user_id        = $user_id;
    $submit->version        = 0;
    $submit->title          = '';
    $submit->class          = APPLY_CLASS_DRAFT;
    $submit->acked          = APPLY_ACKED_NOTYET;
    $submit->acked_user     = 0;
    $submit->acked_time     = 0;
    $submit->execd          = APPLY_EXECD_NOTYET;
    $submit->execd_user     = 0;
    $submit->execd_time     = 0;
    $submit->time_modified  = time();

    $submit->otitle         = '';
    $submit->oclass         = 0;
    $submit->oacked         = 0;
    $submit->oacked_user    = 0;
    $submit->oacked_time    = 0;
    $submit->oexecd         = 0;
    $submit->oexecd_user    = 0;
    $submit->oexecd_time    = 0;

    $submit_id = $DB->insert_record('apply_submit', $submit);
    $submit    = $DB->get_record('apply_submit', array('id'=>$submit_id));

    return $submit;
}


function apply_update_submit($submit)
{
    global $DB;

    $sbmt = new stdClass();

    if (!$submit->id) {
        $sbmt = $DB->get_record('apply_submit', array('id'=>$submit->id));
    }
    if (!$sbmt->id) {
        $sbmit = apply_create_submit($submit->apply_id, $submit->user_id);
    }
    if (!$sbmt->id) return false; 
    
    //
    $submit->id = $sbmt->id;
    $submit->time_modified = time();
    $DB->update_record('apply_submit', $submit);

    return $submit->id;
}


function apply_delete_submit($submit_id)
{
    global $DB;

    if (!$submit = $DB->get_record('apply_submit', array('id'=>$submit_id))) {
        return false;
    }

    $DB->delete_records('apply_value', array('submit_id'=>$submit->id));

    $ret = $DB->delete_records('apply_submit', array('id'=>$submit->id));
    return $ret;
}


function apply_delete_all_submits($apply_id) 
{
    global $DB;

    $submits = $DB->get_records('apply_submit', array('apply_id'=>$apply_id));
    if (!$submits) return false;

    $ret = true;
    foreach ($submits as $submit) {
        $del = apply_delete_submit($submit->id);
        if (!$del) $ret = false;
    }

    return $ret;
}


function apply_delete_submit_safe($submit_id)
{
    global $DB;

    if (!$submit = $DB->get_record('apply_submit', array('id'=>$submit_id))) {
        return false;
    }
    if ($submit->version>1 and $submit->acked==APPLY_ACKED_ACCEPT) return false;

    //
    $DB->delete_records('apply_value', array('submit_id'=>$submit->id));

    $ret = $DB->delete_records('apply_submit', array('id'=>$submit->id));
    return $ret;
}


function apply_rollback_submit($submit_id)
{
    global $DB;

    $submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
    if (!$submit) return false;
    if ($submit->version<=1 or $submit->acked==APPLY_ACKED_ACCEPT) return false;

    //
    $DB->delete_records('apply_value', array('submit_id'=>$submit->id, 'version'=>$submit->version));
    $submit->version--;

    //
    $submit->title       = $submit->otitle;
    $submit->class       = $submit->oclass;
    $submit->acked       = $submit->oacked;
    $submit->acked_user  = $submit->oacked_user;
    $submit->acked_time  = $submit->oacked_time;
    $submit->execd       = $submit->oexecd;
    $submit->execd_user  = $submit->oexecd_user;
    $submit->execd_time  = $submit->oexecd_user;

    $submit->otitle      = '';
    $submit->oclass      = 0;
    $submit->oacked      = 0;
    $submit->oacked_user = 0;
    $submit->oacked_time = 0;
    $submit->oexecd      = 0;
    $submit->oexecd_user = 0;
    $submit->oexecd_user = 0;
    $submit->time_modified = time();

    $ret = $DB->update_record('apply_submit', $submit);
    if ($ret) apply_delete_draft_values($submit_id);

    return $ret;
}


function apply_cancel_submit($submit_id)
{
    global $DB;

    $submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
    if (!$submit) return false;
    if ($submit->acked!=APPLY_ACKED_ACCEPT) return false;

    $ret = apply_copy_values($submit_id, $submit->version, $submit->version+1);
    if (!$ret) return false;

    // Backup
    $submit->otitle      = $submit->title;
    $submit->oclass      = $submit->class;
    $submit->oacked      = $submit->acked;
    $submit->oacked_user = $submit->acked_user;
    $submit->oacked_time = $submit->acked_time;
    $submit->oexecd      = $submit->execd;
    $submit->oexecd_user = $submit->execd_user;
    $submit->oexecd_time = $submit->execd_time;

    //
    $submit->version++;
    $submit->class       = APPLY_CLASS_CANCEL;
    $submit->acked       = APPLY_ACKED_NOTYET;
    $submit->acked_user  = 0;
    $submit->acked_time  = 0;
    $submit->execd       = APPLY_EXECD_NOTYET;
    $submit->execd_user  = 0;
    $submit->execd_time  = 0;
    $submit->time_modified = time();

    $ret = $DB->update_record('apply_submit', $submit);
    if ($ret) apply_delete_draft_values($submit_id);

    return $ret;
}


function apply_exec_submit($submit_id)
{
    global $DB;

    $submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
    if (!$submit) return false;
    if ($submit->acked==APPLY_ACKED_ACCEPT or $submit->version==0) $submit->version++;

    // 固定タイトル
    $title = '';
    $items = $DB->get_records('apply_item',  array('apply_id'=>$submit->apply_id));
    if ($items) {
        foreach ($items as $item) {
            if ($item->typ=='fixedtitle' and $item->label==APPLY_SUBMIT_TITLE_TAG) {
                $title = $item->name;
                break;
            }
        }
    }

    //
    $ret = apply_flush_draft_values($submit->id, $submit->version, $title);
    if ($ret) apply_delete_draft_values($submit->id);

    //
    $submit->otitle      = $submit->title;
    $submit->oclass      = $submit->class;
    $submit->oacked      = $submit->acked;
    $submit->oacked_user = $submit->acked_user;
    $submit->oacked_time = $submit->acked_time;
    $submit->oexecd      = $submit->execd;
    $submit->oexecd_user = $submit->execd_user;
    $submit->oexecd_time = $submit->execd_time;

    //
    if      ($submit->version==1) $submit->class = APPLY_CLASS_NEW;
    else if ($submit->version >1) $submit->class = APPLY_CLASS_UPDATE;
    //
    $submit->title       = $title;
    $submit->acked       = APPLY_ACKED_NOTYET;
    $submit->acked_user  = 0;
    $submit->acked_time  = 0;
    $submit->execd       = APPLY_EXECD_NOTYET;
    $submit->execd_user  = 0;
    $submit->execd_time  = 0;
    $submit->time_modified = time();

    $ret = $DB->update_record('apply_submit', $submit);

    return $ret;
}


function apply_operate_submit($submit_id, $submit_ver, $accept, $execd)
{
    global $DB, $USER;
    
    $submit = $DB->get_record('apply_submit', array('id'=>$submit_id, 'version'=>$submit_ver));
    if (!$submit) return false;

    $flag = false;
    $time_modified = time();

    //
    if ($accept=='accept') {
        if ($submit->acked!=APPLY_ACKED_ACCEPT) {
            $submit->acked = APPLY_ACKED_ACCEPT;
            $submit->acked_user = $USER->id;
            $submit->acked_time = $time_modified;
            $flag = true;
        }
    }
    else if ($accept=='reject') {
        if ($submit->acked!=APPLY_ACKED_REJECT) {
            $submit->acked = APPLY_ACKED_REJECT;
            $submit->acked_user = $USER->id;
            $submit->acked_time = $time_modified;
            $flag = true;
        }
        $execd = '';
    }

    //
    if ($execd=='done')    {
        if ($submit->execd!=APPLY_EXECD_DONE) {
            $submit->execd = APPLY_EXECD_DONE;
            $submit->execd_user = $USER->id;
            $submit->execd_time = $time_modified;
            $flag = true;
        }
    }
    else {
        if ($submit->execd!=APPLY_EXECD_NOTYET) {
            $submit->execd = APPLY_EXECD_NOTYET;
            $submit->execd_user = 0;
            $submit->execd_time = 0;
            $flag = true;
        }
    }

    if ($flag) {
        $submit->tiome_modified = $time_modified;
        $ret = $DB->update_record('apply_submit', $submit);
    }

    return true;
}



///////////////////////////////////////////////////////////////////////////////////
//
// Value Handling
//

function apply_check_values($first_item, $last_item)
{
    global $DB, $CFG;

    $apply_id = optional_param('apply_id', 0, PARAM_INT);

    $select = 'apply_id=? AND position>=? AND position<=?  AND hasvalue=1';
    $params = array($apply_id, $first_item, $last_item);
    $items  = $DB->get_records_select('apply_item', $select, $params);
    if (!$items) return true;

    foreach ($items as $item) {
        $itemobj = apply_get_item_class($item->typ);
        $formvalname = $item->typ . '_' . $item->id;

        if ($itemobj->value_is_array()) {
            $value = optional_param_array($formvalname, null, PARAM_RAW);
        } 
        else {
            $value = optional_param($formvalname, null, PARAM_RAW);
        }
        $value = $itemobj->clean_input_value($value);

        if (is_null($value) and $item->required==1) {
            return false;
        }
        if (!$itemobj->check_value($value, $item)) {
            return false;
        }
    }

    return true;
}


function apply_get_item_value($submit_id, $item_id, $version=-1) 
{
    global $DB;

    if ($version<0) {
        $submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
        if ($submit) $version = $submit->version;
        else return null;
    }

    $params = array('submit_id'=>$submit_id, 'item_id'=>$item_id, 'version'=>$version);
    $ret = $DB->get_field('apply_value', 'value', $params);

    return $ret;
}


function apply_compare_item_value($submit_id, $item_id, $dependvalue, $version=-1)
{
    global $DB;

    $dbvalue = apply_get_item_value($submit_id, $item_id, $version);
    $item = $DB->get_record('apply_item', array('id'=>$item_id));

    $itemobj = apply_get_item_class($item->typ);
    $ret = $itemobj->compare_value($item, $dbvalue, $dependvalue);

    return $ret;
}


function apply_save_draft_values($apply_id, $submit_id, $user_id=0)
{
    global $DB, $USER;

    if (!$user_id) $user_id = $USER->id;

    $submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
    if (!$submit) $submit = apply_create_submit($apply_id, $user_id);
    //
    $submit_id = apply_update_draft_values($submit);

    return $submit_id;
}


function apply_update_draft_values($submit)
{
    global $DB;

    $items  = $DB->get_records('apply_item',  array('apply_id'=>$submit->apply_id));
    if (!$items) return 0;
    $values = $DB->get_records('apply_value', array('submit_id'=>$submit->id, 'version'=>0));

    // 固定タイトル
    $title = '';
    foreach ($items as $item) {
        if ($item->typ=='fixedtitle' and $item->label==APPLY_SUBMIT_TITLE_TAG) {
            $title = $item->name;
            break;
        }
    }

    $time_modified = time();

    foreach ($items as $item) {
        //
        if (!$item->hasvalue) continue;
        //
        $itemobj = apply_get_item_class($item->typ);
        $keyname = $item->typ.'_'.$item->id;

        if ($itemobj->value_is_array()) {
            $itemvalue = optional_param_array($keyname, null, $itemobj->value_type());
        }
        else {
            $itemvalue = optional_param($keyname, null, $itemobj->value_type());
        }
        if (is_null($itemvalue)) continue;

        //
        $newvalue = new stdClass();
        $newvalue->submit_id = $submit->id;
        $newvalue->item_id   = $item->id;
        $newvalue->version   = 0;
        $newvalue->value     = $itemobj->create_value($itemvalue);
        $newvalue->time_modified = $time_modified;

        $exist = false;
        if ($values) {
            foreach ($values as $value) {
                if ($value->item_id==$newvalue->item_id) {
                    $newvalue->id = $value->id;
                    $exist = true;
                    break;
                }
            }
        }
        //
        if ($exist) $DB->update_record('apply_value', $newvalue);
        else        $DB->insert_record('apply_value', $newvalue);

        // for Title of Draft (version=0)
        if ($title=='') {
            if ($item->label==APPLY_SUBMIT_TITLE_TAG and $item->typ=='textfield') {
                $title = $newvalue->value;
            }
        }
    }

    if ($title!='' and $submit->version==0) {
        $submit->title = $title;
        $DB->update_record('apply_submit', $submit);
    }

    return $submit->id;
}


function apply_save_admin_values($submit_id, $submit_ver)
{
    global $DB;

    $submit = $DB->get_record('apply_submit', array('id'=>$submit_id, 'version'=>$submit_ver));
    if (!$submit) return null;
    //
    $submit = apply_update_admin_values($submit);

    return $submit;
}


function apply_update_admin_values($submit)
{
    global $DB;

    $items  = $DB->get_records('apply_item',  array('apply_id'=>$submit->apply_id));
    if (!$items) return null;
    $values = $DB->get_records('apply_value', array('submit_id'=>$submit->id, 'version'=>$submit->version));

    $time_modified = time();

    foreach ($items as $item) {
        //
        if ($item->hasvalue and ($item->label==APPLY_ADMIN_REPLY_TAG or $item->label==APPLY_ADMIN_ONLY_TAG)) {
            //
            $itemobj = apply_get_item_class($item->typ);
            $keyname = $item->typ.'_'.$item->id;

            if ($itemobj->value_is_array()) {
                $itemvalue = optional_param_array($keyname, null, $itemobj->value_type());
            }
            else {
                $itemvalue = optional_param($keyname, null, $itemobj->value_type());
            }
            if (is_null($itemvalue)) continue;

            //
            $newvalue = new stdClass();
            $newvalue->submit_id = $submit->id;
            $newvalue->item_id   = $item->id;
            $newvalue->version   = $submit->version;
            $newvalue->value     = $itemobj->create_value($itemvalue);
            $newvalue->time_modified = $time_modified;

            $exist = false;
            if ($values) {
                foreach ($values as $value) {
                    if ($value->item_id==$newvalue->item_id) {
                        $newvalue->id = $value->id;
                        $exist = true;
                        break;
                    }
                }
            }
            //
            if ($exist) $DB->update_record('apply_value', $newvalue);
            else        $DB->insert_record('apply_value', $newvalue);
        }
    }

    return $submit;
}


function apply_delete_draft_values($submit_id)
{
    global $DB;

    $DB->delete_records('apply_value', array('submit_id'=>$submit_id, 'version'=>0));
}


function apply_exist_draft_values($submit_id)
{
    global $DB;

    $ret = $DB->get_records('apply_value', array('submit_id'=>$submit_id, 'version'=>0));
    if ($ret) return true;
    return false;
}


/**
 * copy value from draft record to taget version record.
 * if item label is APPLY_SUBMIT_TITLE_TAG and item type 'textfield', that item value is return.
 *
 * @global object
 * @param  $submit_id id of submit(application)
 * @param  $version target version to copy
 * @param[out] $title if item label is APPLY_SUBMIT_TITLE_TAG and item type 'textfield', that item value is setted.
 * @return boolean
 */
function apply_flush_draft_values($submit_id, $version, &$title)
{
    global $DB;

    $values = $DB->get_records('apply_value', array('submit_id'=>$submit_id, 'version'=>0));
    if (!$values) return false;

    $ret = false;
    $time_modified = time();

    foreach($values as $value) {
        //
        $val = $DB->get_record('apply_value', array('submit_id'=>$submit_id, 'item_id'=>$value->item_id, 'version'=>$version));
        if ($val) {
            $value->id = $val->id;
            $value->version = $val->version;
            $value->time_modified = $time_modified;
            $ret = $DB->update_record('apply_value', $value);
        }
        else {
            $value->version = $version;
            $value->time_modified = $time_modified;
            $ret = $DB->insert_record('apply_value', $value);
        }
        if (!$ret) break;

        //
        if ($title=='') {
            $item = $DB->get_record('apply_item', array('id'=>$value->item_id));
            if ($item) {
                if ($item->label==APPLY_SUBMIT_TITLE_TAG and $item->typ=='textfield') {
                    $title = $value->value;
                }
            }
        }
    }

    return $ret;
}


function apply_copy_values($submit_id, $fm_ver, $to_ver)
{
    global $DB;

    $values = $DB->get_records('apply_value', array('submit_id'=>$submit_id, 'version'=>$fm_ver));
    if (!$values) return false;

    $ret = false;
    $time_modified = time();
    
    foreach($values as $value) {
        $val = $DB->get_record('apply_value', array('submit_id'=>$submit_id, 'item_id'=>$value->item_id, 'version'=>$to_ver));
        if ($val) {
            $value->id = $val->id;
            $value->version = $val->version;
            $value->time_modified = $time_modified;
            $ret = $DB->update_record('apply_value', $value);
        }
        else {
            $value->version = $to_ver;
            $value->time_modified = $time_modified;
            $ret = $DB->insert_record('apply_value', $value);
        }
        if (!$ret) break;
    }

    return $ret;
}


/**
 * get the values of an item depending on the given groupid.
 * if the feedback is anonymous so the values are shuffled
 *
 * @global object
 * @global object
 * @param object $item
 * @param int $groupid
 * @param int $courseid
 * @param bool $ignore_empty if this is set true so empty values are not delivered
 * @return array the value-records
 */
function apply_get_group_values($item, $groupid = false, $courseid = false, $ignore_empty = false)
{
    global $CFG, $DB;

    //if the groupid is given?
    if (intval($groupid) > 0) {
        $params = array();
        if ($ignore_empty) {
            $value = $DB->sql_compare_text('fbv.value');
            $ignore_empty_select = "AND $value != :emptyvalue AND $value != :zerovalue";
            $params += array('emptyvalue' => '', 'zerovalue' => '0');
        }
        else {
            $ignore_empty_select = "";
        }

        $query = 'SELECT fbv .  *
                    FROM {apply_value} fbv, {apply_completed} fbc, {groups_members} gm
                   WHERE fbv.item = :itemid
                         AND fbv.completed = fbc.id
                         AND fbc.userid = gm.userid
                         '.$ignore_empty_select.'
                         AND gm.groupid = :groupid
                ORDER BY fbc.timemodified';
        $params += array('itemid' => $item->id, 'groupid' => $groupid);
        $values = $DB->get_records_sql($query, $params);
    } 
    else {
        $params = array();
        if ($ignore_empty) {
            $value = $DB->sql_compare_text('value');
            $ignore_empty_select = "AND $value != :emptyvalue AND $value != :zerovalue";
            $params += array('emptyvalue' => '', 'zerovalue' => '0');
        }
        else {
            $ignore_empty_select = "";
        }

        if ($courseid) {
            $select = "item = :itemid AND course_id = :courseid ".$ignore_empty_select;
            $params += array('itemid' => $item->id, 'courseid' => $courseid);
            $values = $DB->get_records_select('apply_value', $select, $params);
        }
        else {
            $select = "item = :itemid ".$ignore_empty_select;
            $params += array('itemid' => $item->id);
            $values = $DB->get_records_select('apply_value', $select, $params);
        }
    }

    $params = array('id'=>$item->apply);
    if ($DB->get_field('apply', 'anonymous', $params) == FEEDBACK_ANONYMOUS_YES) {
        if (is_array($values)) {
            shuffle($values);
        }
    }

    return $values;
}



///////////////////////////////////////////////////////////////////////////////////
//
// Users
//

function apply_get_user_info($user_id)
{
    global $DB;

    $ufields = user_picture::fields('u');    // u.id, u.picture, u.firstname, u.lastname, u.imagealt, u.email
    $sql = 'SELECT '.$ufields.' FROM {user} u WHERE u.id='.$user_id;

    $ret = $DB->get_record_sql($sql);
    return $ret;
}


/*
function apply_get_submitted_users($apply_id, $where='', array $params=null, $sort='', $start_page=false, $page_count=false)
{
    global $DB;

    $params = (array)$params;
    $params['apply_id'] = $apply_id;

    if ($sort) $sortsql = ' ORDER BY '.$sort;
    else       $sortsql = '';

    $ufields = user_picture::fields('u');    // u.id, u.picture, u.firstname, u.lastname, u.imagealt, u.email
    $sql = 'SELECT '.$ufields.', s.id as submit_id FROM {user} u, {apply_submit} s '.
                'WHERE '.$where.' u.id=s.user_id AND s.apply_id=:apply_id AND s.version>0 '.$sortsql;

    if ($start_page===false or $page_count===false) {
        $start_page = false;
        $page_count = false;
    }

    $ret = $DB->get_records_sql($sql, $params, $start_page, $page_count);
    return $ret;
}
*/



/*
function apply_get_submitted_users_count($cm)
{
    global $DB;

    $params = array($cm->instance);
    $sql = 'SELECT COUNT(u.id) FROM {user} u, {apply_submit} s WHERE u.id=s.user_id AND s.apply_id=? AND s.version>0';

    return $DB->count_records_sql($sql, $params);
}
*/



///////////////////////////////////////////////////////////////////////////////////
//
// E-Mail
//

// メール受信可能な管理者
function apply_get_receivemail_users($context)
{
    $ret = get_users_by_capability($context, 'mod/apply:receivemail', '', 'lastname', '', '', false, '', false);
    return $ret;
}


// send to teacher
function apply_send_email($cm, $apply, $course, $user_id)
{
    global $CFG, $DB;

    require_once(dirname(__FILE__).'/jbxl/jbxl_moodle_tools.php');

    if ($apply->email_notification==0) return;
    $ccontext = context_course::instance($course->id);

    $user = $DB->get_record('user', array('id'=>$user_id));
    $teachers = apply_get_receivemail_users($ccontext);

    if ($teachers) {
        $submitted = get_string('submitted', 'apply');
        $printusername = fullname($user);

        foreach ($teachers as $teacher) {
            if (jbxl_is_teacher($teacher->id, $ccontext, false)) {
                $info = new stdClass();
                $info->username = $printusername;
                $info->apply = format_string($apply->name, true);
                $info->url= $CFG->wwwroot.'/mod/apply/view_entries.php?id='.$cm->id.'&user_id='.$user_id.'&do_show=view_entries';

                $postsubject = $submitted.': '.$info->username.' -> '.$apply->name;

                $posthtml = '';
                $posttext = apply_send_email_text($info, $course);
                if ($teacher->mailformat==1) {
                    $posthtml = apply_send_email_html($info, $course, $cm);
                }

                $eventdata = new stdClass();
                $eventdata->name              = 'submission';
                $eventdata->component         = 'mod_apply';
                $eventdata->userfrom          = $user;
                $eventdata->userto            = $teacher;
                $eventdata->subject           = $postsubject;
                $eventdata->fullmessage       = $posttext;
                $eventdata->fullmessagehtml   = $posthtml;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->smallmessage      = '';
                $eventdata->notification      = 1;
                //
                message_send($eventdata);
            }
        }
    }
}


function apply_send_email_text($info, $course) 
{
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context'=>$coursecontext));

    $posttext  = $courseshortname.' -> '.get_string('modulenameplural', 'apply').' -> '.$info->apply;
    $posttext .= "\n---------------------------------------------------------------------\n";
    $posttext .= get_string('email_teacher','apply',$info).get_string('email_confirm_text','apply',$info);
    $posttext .= "\n---------------------------------------------------------------------\n";

    return $posttext;
}


function apply_send_email_html($info, $course, $cm, $isuser=false)
{
    global $CFG;

    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context'=>$coursecontext));
    $course_url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
    $apply_all_url = $CFG->wwwroot.'/mod/apply/index.php?id='.$course->id;
    $apply_url = $CFG->wwwroot.'/mod/apply/view.php?id='.$cm->id;

    $posthtml = '<p><font face="sans-serif">'.
                '<a href="'.$course_url.'">'.$courseshortname.'</a>&nbsp;->&nbsp;'.
                '<a href="'.$apply_all_url.'">'.get_string('modulenameplural', 'apply').'</a>&nbsp;->&nbsp;'.
                '<a href="'.$apply_url.'">'.$info->apply.'</a></font></p>';
    $posthtml.= '<hr /><font face="sans-serif"><p>';
    $posthtml.= get_string('email_teacher','apply',$info).get_string('email_confirm_html','apply',$info);
    $posthtml.= '</p></font><hr />';

    return $posthtml;
}


/*
function apply_get_receivemail_users($cmid)
{
    $context = context_module::instance($cmid);

    //get_users_by_capability($context, $capability, $fields, $sort, $limitfrom, $limitnum, $groups, $exceptions, $doanything)
    $ret = get_users_by_capability($context, 'mod/apply:receivemail', '', 'lastname', '', '', false, '', false);

    return $ret;
}
*/


/*
 send to user

 $accept: 'accept' or 'reject' or ''
 $execd : 'done' or ''
*/
function apply_send_email_user($cm, $apply, $course, $submit, $accept, $execd, $fuser=null)
{
    global $CFG, $DB, $USER;

    require_once(dirname(__FILE__).'/jbxl/jbxl_moodle_tools.php');

    if ($apply->email_notification_user==0) return;

    $user_id = $submit->user_id;
    $user = $DB->get_record('user', array('id'=>$user_id));
    if ($fuser==null) $fuser = $USER;

    $info = new stdClass();
    $info->username = fullname($user);
    $info->apply = format_string($apply->name, true);
    $urlparam = '&courseid='.$course->id.'&user_id='.$user_id.'&submit_id='.$submit->id.'&submit_ver='.$submit->version;
    $info->url= $CFG->wwwroot.'/mod/apply/view.php?id='.$cm->id.$urlparam.'&do_show=view_one_entry';

    $postsubject = get_string('submitted','apply').': '.$info->username.' -> '.$apply->name;
    //
    $posttext = apply_send_email_text_user($info, $course, $accept, $execd);
    $posthtml = '';
    if ($user->mailformat==1) {
        $posthtml = apply_send_email_html_user($info, $course, $cm, $accept, $execd);
    }

    $eventdata = new stdClass();
    $eventdata->name              = 'processed';
    $eventdata->component         = 'mod_apply';
    $eventdata->userfrom          = $fuser;
    $eventdata->userto            = $user;
    $eventdata->subject           = $postsubject;
    $eventdata->fullmessage       = $posttext;
    $eventdata->fullmessagehtml   = $posthtml;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->smallmessage      = '';
    $eventdata->notification      = 1;
    //
    message_send($eventdata);
}


function apply_send_email_text_user($info, $course, $accept, $execd) 
{
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context'=>$coursecontext));

    $posttext  = $courseshortname.' -> '.get_string('modulenameplural', 'apply').' -> '.$info->apply."\n";
    $posttext .= '---------------------------------------------------------------------'."\n";

    if      ($accept=='reject') $posttext .= get_string('email_user_reject', 'apply', $info);
    else if ($execd=='done')    $posttext .= get_string('email_user_done',   'apply', $info);
    else if ($accept=='accept') $posttext .= get_string('email_user_accept', 'apply', $info);
    else                        $posttext .= get_string('email_user_other',  'apply', $info);
    $posttext .= get_string('email_confirm_text', 'apply', $info)."\n";

    $posttext .= '---------------------------------------------------------------------'."\n";
    $posttext .= get_string('email_noreply','apply')."\n";

    return $posttext;
}


function apply_send_email_html_user($info, $course, $cm, $accept, $execd)
{
    global $CFG;

    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context'=>$coursecontext));
    $course_url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
    $apply_all_url = $CFG->wwwroot.'/mod/apply/index.php?id='.$course->id;
    $apply_url = $CFG->wwwroot.'/mod/apply/view.php?id='.$cm->id;

    $posthtml  = '<p><font face="sans-serif">'.
                 '<a href="'.$course_url.'">'.$courseshortname.'</a>&nbsp;->&nbsp;'.
                 '<a href="'.$apply_all_url.'">'.get_string('modulenameplural', 'apply').'</a>&nbsp;->&nbsp;'.
                 '<a href="'.$apply_url.'">'.$info->apply.'</a></font></p>';
    $posthtml .= '<hr /><font face="sans-serif"><p>';

    if      ($accept=='reject') $posthtml .= get_string('email_user_reject', 'apply', $info);
    else if ($execd=='done')    $posthtml .= get_string('email_user_done',   'apply', $info);
    else if ($accept=='accept') $posthtml .= get_string('email_user_accept', 'apply', $info);
    else                        $posthtml .= get_string('email_user_other',  'apply', $info);
    $posthtml .= get_string('email_confirm_html', 'apply', $info);

    $posthtml .= '</p></font><hr />';
    $posthtml .= get_string('email_noreply','apply').'<br />';

    return $posthtml;
}




///////////////////////////////////////////////////////////////////////////////////
//
// Tools
//

function apply_print_error_messagebox($str, $id, $view_url='mod/apply')
{
    global $OUTPUT, $CFG;

    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

    if ($str!='' and $str!=null) {
        echo '<h2><font color="red"><div align="center">';
        echo get_string($str, 'apply');
        echo '</div></font></h2>';
    }

    echo $OUTPUT->continue_button($CFG->wwwroot.'/'.$view_url.'/view.php?id='.$id);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
}


function apply_print_messagebox($str, $append=null, $color='steel blue')
{
    global $OUTPUT;

    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

    if ($str!='' and $str!=null) {
        echo '<h3><font color="'.$color.'"><div align="center">';
        echo get_string($str, 'apply');
        echo '</div></font></h3>';
    }

    if ($append!=null) echo $append;
    echo $OUTPUT->box_end();
}


function apply_single_button($url, array $params, $label, $method='POST', $target='')
{
    if ($target!='') $target = 'target="'.$target.'"';
    $form = '<form action="'.$url.'" method="'.$method.'" '.$target.'>';

    foreach($params as $key => $param) {
        $form.= '<input type="hidden" name="'.$key.'" value="'.$param.'" />';
    }
    $form.= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    $form.= '<input name="single_button" type="submit" value="'.$label.'" />';
    $form.= '</form>';

    return $form;
}




///////////////////////////////////////////////////////////////////////////////////
//
// Table
//

function apply_print_initials_bar($table, $first=true, $last=true)
{
    $alpha = explode(',', get_string('alphabet', 'langconfig'));

    if ($first) {
        if (!empty($table->sess->i_first)) $ifirst = $table->sess->i_first;
        else                                $ifirst = '';
        apply_print_one_initials_bar($table, $alpha, $ifirst, 'firstinitial', get_string('firstname'), $table->request[TABLE_VAR_IFIRST]);
    }

    if ($last) {
        if (!empty($table->sess->i_last)) $ilast = $table->sess->i_last;
        else                               $ilast = '';
        apply_print_one_initials_bar($table, $alpha, $ilast, 'lastinitial', get_string('lastname'), $table->request[TABLE_VAR_ILAST]);
    }
}


function apply_print_one_initials_bar($table, $alpha, $current, $class, $title, $urlvar)
{
    echo html_writer::start_tag('div', array('class'=>'initialbar '.$class)).$title.' : ';
    if ($current) {
        echo html_writer::link($table->baseurl->out(false, array($urlvar=>'')), get_string('all'));
    }
    else {
        echo html_writer::tag('strong', get_string('all'));
    }

    foreach ($alpha as $letter) {
        if ($letter === $current) {
            echo html_writer::tag('strong', $letter);
        }
        else {
            echo html_writer::link($table->baseurl->out(false, array($urlvar=>$letter)), $letter);
        }
    }
    echo html_writer::end_tag('div');
}




///////////////////////////////////////////////////////////////////////////////////
//
// Template
//

/*
function apply_create_template($courseid, $name, $ispublic=0) 
{
    global $DB;

    $templ = new stdClass();
    $templ->course   = ($ispublic ? 0 : $courseid);
    $templ->name     = $name;
    $templ->ispublic = $ispublic;

    $templ_id = $DB->insert_record('apply_template', $templ);
    $newtempl = $DB->get_record('apply_template', array('id'=>$templ_id));

    return $newtempl;
}


function apply_save_as_template($apply, $name, $ispublic=0)
{
    global $DB;
    $fs = get_file_storage();

    if (!$applyitems = $DB->get_records('apply_item', array('apply_id'=>$apply->id))) {
        return false;
    }

    if (!$newtempl = apply_create_template($apply->course, $name, $ispublic)) {
        return false;
    }

    if ($ispublic) {
        $s_context = context_system::instance();
    }
    else {
        $s_context = context_course::instance($newtempl->course);
    }
    $cm = get_coursemodule_from_instance('apply', $apply->id);
    $f_context = context_module::instance($cm->id);

    $dependitemsmap = array();
    $itembackup = array();

    //
    foreach ($applyitems as $item) {
        $t_item = clone($item);
        unset($t_item->id);
        $t_item->apply = 0;
        $t_item->template = $newtempl->id;
        $t_item->id = $DB->insert_record('apply_item', $t_item);
        $itemfiles = $fs->get_area_files($f_context->id, 'mod_apply', 'item', $item->id, 'id', false);
        //
        if ($itemfiles) {
            foreach ($itemfiles as $ifile) {
                $file_record = new stdClass();
                $file_record->contextid = $s_context->id;
                $file_record->component = 'mod_apply';
                $file_record->filearea = 'template';
                $file_record->itemid = $t_item->id;
                $fs->create_file_from_storedfile($file_record, $ifile);
            }
        }

        $itembackup[$item->id] = $t_item->id;
        if ($t_item->dependitem) {
            $dependitemsmap[$t_item->id] = $t_item->dependitem;
        }
    }

    foreach ($dependitemsmap as $key=>$dependitem) {
        $newitem = $DB->get_record('apply_item', array('id'=>$key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('apply_item', $newitem);
    }

    return true;
}


function apply_delete_template($template)
{
    global $DB;

    if ($t_items = $DB->get_records('apply_item', array('template'=>$template->id))) {
        foreach ($t_items as $t_item) {
            apply_delete_item($t_item->id, false, $template);
        }
    }
    $DB->delete_records('apply_template', array('id'=>$template->id));
}


function apply_items_from_template($apply, $template_id, $deleteold=false)
{
    global $DB, $CFG;

    $fs = get_file_storage();

    if (!$template = $DB->get_record('apply_template', array('id'=>$template_id))) {
        return false;
    }
    if (!$templitems = $DB->get_records('apply_item', array('template'=>$template_id))) {
        return false;
    }

    if ($template->ispublic) {
        $s_context = context_system::instance();
    }
    else {
        $s_context = context_course::instance($apply->course);
    }
    //
    $course = $DB->get_record('course', array('id'=>$apply->course));
    $cm = get_coursemodule_from_instance('apply', $apply->id);
    $f_context = context_module::instance($cm->id);

    if ($deleteold) {
        if ($applyitems = $DB->get_records('apply_item', array('apply_id'=>$apply->id))) {
            foreach ($applyitems as $item) {
                apply_delete_item($item->id, false);
            }

            $params = array('apply_id'=>$apply->id);
            if ($submits = $DB->get_records('apply_submit', $params)) {
                foreach ($submits as $submit) {
                    $DB->delete_records('apply_submit', array('id'=>$submit->id));
                }
            }
        }
        $positionoffset = 0;
    }
    else {
        $positionoffset = $DB->count_records('apply_item', array('apply'=>$apply->id));
    }

    $dependitemsmap = array();
    $itembackup = array();
    //
    foreach ($templitems as $t_item) {
        $item = clone($t_item);
        unset($item->id);
        $item->apply = $apply->id;
        $item->template = 0;
        $item->position = $item->position + $positionoffset;

        $item->id = $DB->insert_record('apply_item', $item);

        $templatefiles = $fs->get_area_files($s_context->id, 'mod_apply', 'template', $t_item->id, 'id', false);
        if ($templatefiles) {
            foreach ($templatefiles as $tfile) {
                $file_record = new stdClass();
                $file_record->contextid = $f_context->id;
                $file_record->component = 'mod_apply';
                $file_record->filearea = 'item';
                $file_record->itemid = $item->id;
                $fs->create_file_from_storedfile($file_record, $tfile);
            }
        }

        $itembackup[$t_item->id] = $item->id;
        if ($item->dependitem) {
            $dependitemsmap[$item->id] = $item->dependitem;
        }
    }

    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('apply_item', array('id'=>$key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('apply_item', $newitem);
    }
}
*/


function apply_get_template_list($course, $onlyownorpublic='') 
{
    global $DB;

    switch($onlyownorpublic) {
        case '':
            $templates = $DB->get_records_select('apply_template', 'course = ? OR ispublic=1', array($course->id), 'name');
            break;
        case 'own':
            $templates = $DB->get_records('apply_template', array('course'=>$course->id), 'name'); 
            break;
        case 'public':
            $templates = $DB->get_records('apply_template', array('ispublic'=>1), 'name');
            break;
    }
    return $templates;
}




//////////////////////////////////////////////////////////////////////////////////////////////
//
//
//

function apply_get_event($cm, $action, $params='', $info='')
{
    global $CFG;
    require_once($CFG->dirroot.'/mod/apply/jbxl/jbxl_tools.php');
    require_once($CFG->dirroot.'/mod/apply/jbxl/jbxl_moodle_tools.php');

    $ver = jbxl_get_moodle_version();

    $event = null;
    if (!is_array($params)) $params = array();

    if (floatval($ver)>=2.7) {
        $params = array(
            'context' => context_module::instance($cm->id),
            'other' => array('params' => $params, 'info'=> $info),
        );
        //
        if ($action=='view') {
            $event = \mod_apply\event\view_log::create($params);
        }
        else if ($action=='op_submit') {
            $event = \mod_apply\event\operate_submit::create($params);
        }
        else if ($action=='user_submit') {
            $event = \mod_apply\event\user_submit::create($params);
        }
        else if ($action=='delete') {
            $event = \mod_apply\event\delete_submit::create($params);
        }
    }

    // for Legacy add_to_log()      
    else {
        if ($action=='view') {
            $file = 'view.php';
        }
        else if ($action=='op_submit') {
            $file = 'operate_submit.php';
        }
        else if ($action=='user_submit') {
            $file = 'submit.php';
        }
        else if ($action=='delete') {
            $file = 'delete_submit.php';
        }
        else {
            $file = 'view.php';
        }
        $param_str = jbxl_get_url_params_str($params);
        //
        $event = new stdClass();
        $event->courseid= $cm->course;
        $event->name    = 'apply';
        $event->action  = $action;
        $event->url     = $file.$param_str;
        $event->info    = $info;
    }
   
    return $event;
}



///////////////////////////////////////////////////////////////////////////////////
//
// Table function
//

function apply_open_table_tag($item)
{
    global $Table_in, $Table_params;
  
    if ($Table_in) return;

    $presentation = explode(APPLY_TABLESTART_SEP, $item->presentation);
    $columns      = $presentation[0];
    $border       = $presentation[1];
    $border_style = $presentation[2];
    $th_sizes     = $presentation[3];
    $th_strings   = $presentation[4];
    $disp_iname   = $presentation[5];

    $th_widths = explode(',', $th_sizes);
    $th_titles = explode("\n", $th_strings);
    $table_border = $border + 1;

    $style = '';
    $style_value = '';
    if ($border>=0)        $style_value .= 'border-width:'.$border.'px;';
    if ($border_style!='') $style_value .= 'border-style:'.$border_style.'; ';

    $Table_in = true;
    $Table_params->col        = 0;
    $Table_params->columns    = $columns;
    //$Table_params->widths     = $th_widths;
    $Table_params->titles     = $th_titles;
    $Table_params->disp_iname = $disp_iname;
    $Table_params->style      = array();

    // th widths
    for ($col=0; $col<$columns; $col++) {
        $value = $style_value;
        if (array_key_exists($col, $th_widths)) {
            $width = intval($th_widths[$col]);
            if ($width>0) $value .= 'width:'.$width.'px;';
        }
        if ($value!='') $Table_params->style[$col] = 'style="'.$value.'"';
        else            $Table_params->style[$col] = '';
    }

    echo "\n";
    echo '<table style="border:'.$table_border.'px; border-style:solid; table-layout:fixed;">';

    // th titles
    if ($th_strings!='') {
        echo '<tr>';
        for ($col=0; $col<$columns; $col++) {
            $title = ' ';
            if (array_key_exists($col, $Table_params->titles)) $title = $Table_params->titles[$col];
            echo '<th '.$Table_params->style[$col].'>'.$title.'</th>';
        }
        echo '</tr>';
    }
}


function apply_close_table_tag()
{
    global $Table_in, $Table_params;

    if (!$Table_in) return;

    if ($Table_params->col!=0) {
        for ($col=$Table_params->col; $col<$Table_params->columns; $col++) {
            echo '<td '.$Table_params->style[$col].'> </td>';
        }
        echo '</tr>';
    }

    echo '</table>';
    $Table_in = false;
}


function apply_open_table_item_tag($title='', $view=false)
{
    global $Table_in, $Table_params;

    if (!$Table_in or $view) {
        if ($title!='') echo $title;   // outside of table
        if (!$Table_in) return;
    }    

    if ($Table_params->col==0) echo '<tr>';
    echo '<td '.$Table_params->style[$Table_params->col].'>';

    if ($Table_params->disp_iname or $view) {
        if ($title!='') echo $title;   // inside of table
    }    
}


function apply_close_table_item_tag()
{
    global $Table_in, $Table_params;

    if (!$Table_in) return;

    $Table_params->col++;
    $Table_params->col %= $Table_params->columns;

    echo '</td>';
    if ($Table_params->col==0) echo '</tr>';
}



///////////////////////////////////////////////////////////////////////////////////
//
// Layout
//

function apply_print_line_space($size='')
{
    global $Table_in;

    $size = intval($size);
    if ($size<=0) $size = '15';
    if (!$Table_in) echo '<div style="padding-bottom:'.$size.'px"></div>';
}


function apply_item_box_start($item)
{
    global $Table_in;

    if (!$Table_in) {
        $outside_style = '';
        if ($item->outside_style!='') $outside_style = 'style="'.$item->outside_style.'"';
        echo '<table '.$outside_style,'>';
    }
    else {
        echo '<table style="border: 0px none;">';
    }
    echo '<tr><td style="padding: 5px 5px 0px 5px;">';

    $item_style = '';
    if ($item->item_style!='')  $item_style = 'style="'.$item->item_style.'"';
    echo '<div '.$item_style.'>';
}


function apply_item_box_end()
{
    global $Table_in;

    echo '</div></td></tr></table>';
}


