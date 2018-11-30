<?php

require_once('jbxl/jbxl_moodle_tools.php');

if ($submit) {
//    echo $OUTPUT->box_start('boxaligncenter boxwidthwide');

    $acked_str  = '&nbsp;-&nbsp;';
    $acked_link = '&nbsp;-&nbsp;';
    $acked_time = '&nbsp;-&nbsp;';
    $execd_str  = '&nbsp;-&nbsp;';    
    $execd_link = '&nbsp;-&nbsp;';
    $execd_time = '&nbsp;-&nbsp;';

    //
    if ($submit->version==$submit_ver) {
        if ($submit->class!=APPLY_CLASS_DRAFT) {
            if ($submit->acked==APPLY_ACKED_ACCEPT) {
                $acked_str  = get_string('acked_accept', 'apply');
                $acked_link = jbxl_get_user_link($submit->acked_user, $name_pattern);
                $acked_time = userdate($submit->acked_time);
            }
            else if ($submit->acked==APPLY_ACKED_REJECT) {
                $acked_str  = get_string('acked_reject', 'apply');
                $acked_link = jbxl_get_user_link($submit->acked_user, $name_pattern);
                $acked_time = userdate($submit->acked_time);

            }
            else if ($submit->acked==APPLY_ACKED_NOTYET) {
                $acked_str  = get_string('acked_notyet', 'apply');
            }
        }

        //
        if ($submit->class!=APPLY_CLASS_DRAFT) {
            if ($submit->execd==APPLY_EXECD_DONE) {
                $execd_str  = get_string('execd_done', 'apply');
                $execd_link = jbxl_get_user_link($submit->execd_user, $name_pattern);
                $execd_time = userdate($submit->execd_time);
            }
            else {
                $execd_str  = get_string('execd_notyet', 'apply');
            }
        }
    }
    

    echo '<br />';
    echo '<table border="1" class="entry_info>';
    echo '<tr><td>';
    echo '<table border="0">';
    echo '<tr>';
    echo '<td>&nbsp;'.get_string('title_ack', 'apply').':&nbsp;</td>';
    echo '<td>&nbsp;'.$acked_str. '&nbsp;</td>';
    echo '<td>&nbsp;'.$acked_link.'&nbsp;</td>';
    echo '<td>&nbsp;'.$acked_time.'&nbsp;</td>';
    echo '</tr>';
    
    if (!$apply->only_acked_accept) {
        echo '<tr>';
        echo '<td>&nbsp;'.get_string('title_exec', 'apply').':&nbsp;</td>';
        echo '<td>&nbsp;'.$execd_str. '&nbsp;</td>';
        echo '<td>&nbsp;'.$execd_link.'&nbsp;</td>';
        echo '<td>&nbsp;'.$execd_time.'&nbsp;</td>';
        echo '</tr>';
    }

    echo '</table>';
    echo '</td><tr>';
    echo '</table>';
    echo '<br />';

    //
//    echo $OUTPUT->box_end();
}
