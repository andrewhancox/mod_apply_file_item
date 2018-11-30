<?php

//
// submit用のプレビューページを生成
//

//print the items
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
{
    //echo '<form action="" method="post" onsubmit=" ">';
    //echo '<fieldset>'; // for mobile viewer

    $params = array('apply_id' => $apply->id, 'required' => 1);
    $countreq = $DB->count_records('apply_item', $params);
    if ($countreq>0) {
        echo '<span class="apply_required_mark">(*)';
        echo get_string('items_are_required', 'apply');
        echo '</span>';
    }
    
    //
    echo $OUTPUT->box_start('generalbox');
    {
        foreach ($items as $item) {
            //
            if ($item->typ!='pagebreak') {
                if ($item->label!=APPLY_ADMIN_REPLY_TAG and $item->label!=APPLY_ADMIN_ONLY_TAG and $item->typ!='fixedtitle') {
                    apply_print_line_space();
                    echo $OUTPUT->box_start('apply_print_item');
                    apply_print_item_submit($item, '', false);
                    echo $OUTPUT->box_end();
                }
            }
            else {
                echo '<hr class="apply_pagebreak" />';
            }
        }

        if ($Table_in) {   // テーブルはまだ閉じられていない．
            echo $OUTPUT->box_start('apply_print_item');
            apply_close_table_tag();
            echo $OUTPUT->box_end();
            //echo '<div style="color:#c00000">['.get_string('not_close_table','apply').']</div>';
        }
    }
    echo $OUTPUT->box_end();

    //echo '</fieldset>'; // for mobile viewer
    //echo '</form>';
}
echo $OUTPUT->box_end();

