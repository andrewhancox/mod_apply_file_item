<?php

// needs  $submit, $items, $name_pattern, $user

require_once('jbxl/jbxl_moodle_tools.php');

if ($submit->user_id!=$USER->id) {
    require_capability('mod/apply:viewreports', $context);
}

$student = $DB->get_record('user', array('id'=>$submit->user_id));

$user_name = jbxl_get_user_name($student, $name_pattern);
$title = $user_name.' ('.userdate($submit->time_modified, '%Y/%m/%d %H:%M').')';

if 		($submit->class==APPLY_CLASS_DRAFT)  $title .= '&nbsp;<font color="#e22">'.get_string('class_draft', 'apply').'</font>';
else if ($submit->class==APPLY_CLASS_CANCEL) $title .= '&nbsp;<font color="#e22">'.get_string('class_cancel','apply').'</font>';
if ($submit->version!=$submit_ver) $title .= '&nbsp;<font color="#22e"> Ver.'.$submit_ver.'</font>';

//
if ($this_action!='preview') {
	$preview_img = $OUTPUT->pix_icon('t/preview', get_string('preview'));
	$preview_url = new moodle_url('/mod/apply/preview.php');
	$preview_url->params(array('id'=>$cm->id, 'courseid'=>$courseid, 'action'=>$this_action));
	$preview_url->params(array('submit_id'=>$submit->id, 'submit_ver'=>$submit_ver, 'user_id'=>$user_id));
	$title .= '&nbsp;&nbsp;<a href="'.$preview_url->out().'">'.$preview_img.'</a>';
}

echo '<div align="center">';
echo $OUTPUT->heading(format_text($title), 4);
echo '</div>';
echo '<br />';

//
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide entry_view');
foreach ($items as $item) {
	//get the values
	$params = array('submit_id'=>$submit->id, 'item_id'=>$item->id, 'version'=>$submit_ver);
	$value  = $DB->get_record('apply_value', $params);

	if ($item->typ!='pagebreak' and $item->label!=APPLY_SUBMIT_ONLY_TAG and $item->label!=APPLY_ADMIN_ONLY_TAG) {
		echo $OUTPUT->box_start('apply_print_item');
		if (isset($value->value)) {
			apply_print_item_show_value($item, $value->value);
		}
		else {
			apply_print_item_show_value($item, false);
		}
		echo $OUTPUT->box_end();
	}
	//
	else if ($item->label==APPLY_ADMIN_ONLY_TAG and has_capability('mod/apply:viewreports', $context)) {
		echo $OUTPUT->box_start('apply_print_item');
		if (isset($value->value)) {
			apply_print_item_show_value($item, $value->value);
		}
		else {
			apply_print_item_show_value($item, false);
		}
		echo $OUTPUT->box_end();
	}
	
}
require('entry_info.php');
echo $OUTPUT->box_end();

//require('entry_button.php');

