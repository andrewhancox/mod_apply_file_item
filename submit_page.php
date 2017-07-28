<?php

//print the items
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
{
	echo '<form action="submit.php" method="post" onsubmit=" ">';
	echo '<fieldset>';
	echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

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
		unset($start_item);
		$select = 'apply_id=? AND hasvalue=1 AND position<?';
		$params = array($apply->id, $start_position);
		$last_break_position = 0;

		foreach ($items as $item) {
			if (!isset($start_item)) {
				if ($item->typ=='pagebreak') continue;
				$start_item = $item;
			}
			if ($item->dependitem>0) {
				$compare_value = apply_compare_item_value($submit_id, $item->dependitem, $item->dependvalue, true);
				if (!isset($submit_id) OR !$compare_value) {
					$last_item = $item;
					$last_break_position = $item->position;
					continue;
				}
			}
			if ($item->dependitem>0) {
				$depend_style = ' apply_submit_depend';
			}
			else {
				$depend_style = '';
			}

			// restore value
			$value = '';
			$frmvaluename = $item->typ.'_'.$item->id;
			if (isset($save_return)) {
				if (isset($formdata->{$frmvaluename})) {
					$value = $formdata->{$frmvaluename};
					$value = apply_clean_input_value($item, $value);
				}
				else {
					$value = apply_get_item_value($submit_id, $item->id, 0);	// from draft
				}
			}
			else {
				if (isset($submit)) {
					$value = apply_get_item_value($submit_id, $item->id, $submit_ver);
				}
			}

			//
			$last_break_position = $item->position; //last item-pos (item or pagebreak)
			if ($item->typ!='pagebreak') {
				if ($item->label!=APPLY_ADMIN_REPLY_TAG and $item->label!=APPLY_ADMIN_ONLY_TAG) {
					echo $OUTPUT->box_start('apply_print_item');
					apply_print_item_submit($item, $value, $highlightrequired);
					echo $OUTPUT->box_end();
				}
				$last_item = $item;
			}
			else {
				break;
			}
		}
	}
	echo $OUTPUT->box_end();

	//
	echo '<input type="hidden" name="id" value="'.$id.'" />';
	echo '<input type="hidden" name="apply_id" value="'.$apply->id.'" />';
	echo '<input type="hidden" name="last_page" value="'.$go_page.'" />';
	//
	echo '<input type="hidden" name="submit_id" value="'.$submit_id.'" />';
	echo '<input type="hidden" name="submit_ver" value="'.$submit_ver.'" />';
	echo '<input type="hidden" name="courseid" value="'.$courseid.'" />';
	echo '<input type="hidden" name="prev_values" value="1" />';
	if (isset($start_item)) {
		echo '<input type="hidden" name="start_itempos" value="'.$start_item->position.'" />';
		echo '<input type="hidden" name="last_itempos"  value="'.$last_item->position.'" />';
	}
	
	// Button
	echo '<br />';

	if ($last_break_position>=$max_item_count) { //last page
		$input_value = 'value="'.get_string('save_entry_button', 'apply').'"';
		echo '<input name="save_values" type="submit" '.$input_value.' />';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	//echo '&nbsp;&nbsp;&nbsp;&nbsp;';

	$input_value = 'value="'.get_string('save_draft_button', 'apply').'"';
	echo '<input name="save_draft"  type="submit" '.$input_value.' />';
	echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	//
	echo '<input type="reset" value="'.get_string('clear').'" />';

	//
	if (($is_pagebreak and $last_break_position>$first_pagebreak->position) or $last_break_position<$max_item_count) {
		echo '<br /><br />';
	}
	if ($is_pagebreak and $last_break_position>$first_pagebreak->position) {
		$input_value = 'value="'.get_string('previous_page_button', 'apply').'"';
		echo '<input name="go_prev_page" type="submit" '.$input_value.' />';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	if ($last_break_position<$max_item_count) {
		$input_value = 'value="'.get_string('next_page_button', 'apply').'"';
		echo '<input name="go_next_page" type="submit" '.$input_value.' />';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
	}

	echo '</fieldset>';
	echo '</form>';

	//
	//
	echo $OUTPUT->box_start('apply_submit_cancel');
	{
		$action = 'action="'.$CFG->wwwroot.'/mod/apply/view.php?id='.$id.'"';

		echo '<form '.$action.' method="post" onsubmit=" ">';
		echo '<fieldset>';
		echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
		echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
		echo '<button type="submit">'.get_string('back_button', 'apply').'</button>';
		echo '</fieldset>';
		echo '</form>';
	}
	echo $OUTPUT->box_end();
}
echo $OUTPUT->box_end();

