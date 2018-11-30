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

defined('MOODLE_INTERNAL') OR die('not allowed');
require_once($CFG->dirroot.'/mod/apply/item/apply_item_class.php');

define('APPLY_NUMERIC_SEP', '|');


class apply_item_numeric extends apply_item_base
{
    protected $type = "numeric";
    public $sep_dec, $sep_thous;
    private $commonparams;
    private $item_form;
    private $item;


    public function init()
    {
        $this->sep_dec = get_string('separator_decimal', 'apply');
        if (substr($this->sep_dec, 0, 2) == '[[') {
            $this->sep_dec = APPLY_DECIMAL;
        }

        $this->sep_thous = get_string('separator_thousand', 'apply');
        if (substr($this->sep_thous, 0, 2) == '[[') {
            $this->sep_thous = APPLY_THOUSAND;
        }
    }


    public function build_editform($item, $apply, $cm)
    {
        global $DB, $CFG;
        require_once('numeric_form.php');

        //get the lastposition number of the apply_items
        $position = $item->position;
        $lastposition = $DB->count_records('apply_item', array('apply_id'=>$apply->id));
        if ($position == -1) {
            $i_formselect_last  = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position     = $lastposition + 1;
        }
        else {
            $i_formselect_last  = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);

        $item->presentation = empty($item->presentation) ? '' : $item->presentation;

        $presentation = explode(APPLY_NUMERIC_SEP, $item->presentation);
        if (isset($presentation[0]) AND is_numeric($presentation[0])) {
            $range_from = str_replace(APPLY_DECIMAL, $this->sep_dec, floatval($presentation[0]));
        }
        else {
            $range_from = '-';
        }
        if (isset($presentation[1]) AND is_numeric($presentation[1])) {
            $range_to = str_replace(APPLY_DECIMAL, $this->sep_dec, floatval($presentation[1]));
        }
        else {
            $range_to = '-';
        }
        $item->rangefrom = $range_from;
        $item->rangeto   = $range_to;

        //
        $outside_style = isset($presentation[2]) ? $presentation[2]: get_string('outside_style_default', 'apply');
        $item_style    = isset($presentation[3]) ? $presentation[3]: get_string('item_style_default',    'apply');
        $item->outside_style = $outside_style;
        $item->item_style    = $item_style;

        //all items for dependitem
        $applyitems = apply_get_depend_candidates_for_item($apply, $item);
        $commonparams = array('cmid'=>$cm->id,
                             'id'=>isset($item->id) ? $item->id : null,
                             'typ'=>$item->typ,
                             'items'=>$applyitems,
                             'apply_id'=>$apply->id);
        //build the form
        $customdata = array('item' => $item,
                            'common' => $commonparams,
                            'positionlist' => $positionlist,
                            'position' => $position);
        $this->item_form = new apply_numeric_form('edit_item.php', $customdata);
    }


    //this function only can used after the call of build_editform()
    public function show_editform()
    {
        $this->item_form->display();
    }


    public function is_cancelled()
    {
        return $this->item_form->is_cancelled();
    }


    public function get_data()
    {
        if ($this->item = $this->item_form->get_data()) {
            return true;
        }
        return false;
    }


    public function save_item()
    {
        global $DB;

        if (!$item = $this->item_form->get_data()) {
            return false;
        }

        if (isset($item->clone_item) AND $item->clone_item) {
            $item->id = ''; //to clone this item
            $item->position++;
        }

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->id = $DB->insert_record('apply_item', $item);
        }
        else {
            $DB->update_record('apply_item', $item);
        }

        return $DB->get_record('apply_item', array('id'=>$item->id));
    }


    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    public function get_analysed($item, $groupid = false, $courseid = false)
    {
        global $DB;

        $analysed = new stdClass();
        $analysed->data = array();
        $analysed->name = $item->name;
        $values = apply_get_group_values($item, $groupid, $courseid);

        $avg = 0.0;
        $counter = 0;
        if ($values) {
            $data = array();
            foreach ($values as $value) {
                if (is_numeric($value->value)) {
                    $data[] = $value->value;
                    $avg += $value->value;
                    $counter++;
                }
            }
            $avg = $counter > 0 ? $avg / $counter : 0;
            $analysed->data = $data;
            $analysed->avg = $avg;
        }
        return $analysed;
    }


    public function get_printval($item, $value)
    {
        if (!isset($value->value)) {
            return '';
        }

        return $value->value;
    }


    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false)
    {
        $values = $this->get_analysed($item, $groupid, $courseid);
        if (isset($values->data) AND is_array($values->data)) {
            echo '<tr><th colspan="2" align="left">';
            echo $itemnr.'&nbsp;('.$item->label.') '.$item->name;
            echo '</th></tr>';

            foreach ($values->data as $value) {
                echo '<tr><td colspan="2" valign="top" align="left">';
                echo '-&nbsp;&nbsp;'.number_format($value, 2, $this->sep_dec, $this->sep_thous);
                echo '</td></tr>';
            }

            if (isset($values->avg)) {
                $avg = number_format($values->avg, 2, $this->sep_dec, $this->sep_thous);
            }
            else {
                $avg = number_format(0, 2, $this->sep_dec, $this->sep_thous);
            }
            echo '<tr><td align="left" colspan="2"><b>';
            echo get_string('average', 'apply').': '.$avg;
            echo '</b></td></tr>';
        }
    }


    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false)
    {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);

        $worksheet->write_string($row_offset, 0, $item->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $item->name, $xls_formats->head2);
        $data = $analysed_item->data;
        if (is_array($data)) {

            //mittelwert anzeigen
            $worksheet->write_string($row_offset,
                                     2,
                                     get_string('average', 'apply'),
                                     $xls_formats->value_bold);

            $worksheet->write_number($row_offset + 1,
                                     2,
                                     $analysed_item->avg,
                                     $xls_formats->value_bold);
            $row_offset++;
        }
        $row_offset++;
        return $row_offset;
    }


    /**
     * print the item at the edit-page of apply
     *
     * @global object
     * @param object $item
     * @return void
     */
    public function print_item_preview($item)
    {
        global $OUTPUT, $DB;

        $align = right_to_left() ? 'right' : 'left';
        $str_required_mark = '<span class="apply_required_mark">*</span>';

        //get the range
        $presentation = explode(APPLY_NUMERIC_SEP, $item->presentation);

        //get the min-value
        $range_from = 0;
        $range_to = 0;
        if (isset($presentation[0]) and is_numeric($presentation[0])) $range_from = floatval($presentation[0]);
        if (isset($presentation[1]) and is_numeric($presentation[1])) $range_to   = floatval($presentation[1]);

        //$outside_style = isset($presentation[2]) ? $presentation[2]: get_string('outside_style_default', 'apply');
        //$item_style    = isset($presentation[3]) ? $presentation[3]: get_string('item_style_default',    'apply');
        $item->outside_style = '';  //$outside_style;
        $item->item_style    = '';  //$item_style;

        //print the question and label
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';
        $output  = '';
        $output .= '<div class="apply_item_label_'.$align.'">';
        $output .= '('.$item->label.') ';
        $output .= format_text($item->name . $requiredmark, true, false, false).' ['.$item->position.']';
        if ($item->dependitem) {
            $params = array('id'=>$item->dependitem);
            if ($dependitem = $DB->get_record('apply_item', $params)) {
                $output .= ' <span class="apply_depend">';
                $output .= '('.$dependitem->label.'-&gt;'.$item->dependvalue.')';
                $output .= '</span>';
            }
        }

        $output .= '<span class="apply_item_numinfo">';
        switch(true) {
            case ($range_from === '-' AND is_numeric($range_to)):
                $output .= ' ('.get_string('maximal', 'apply').
                              ': '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_to).')';
                break;
            case (is_numeric($range_from) AND $range_to === '-'):
                $output .= ' ('.get_string('minimal', 'apply').
                              ': '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_from).')';
                break;
            case ($range_from === '-' AND $range_to === '-'):
                break;
            default:
                $output .= ' ('.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_from).
                              ' - '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_to).')';
                break;
        }
        $output .= '</span>';
        $output .= '</div>';

        apply_open_table_item_tag($output, true);

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.'">';
        echo '<span class="apply_item_textfield">';
        apply_item_box_start($item);
        echo '<input type="text" '.
                    'name="'.$item->typ.'_'.$item->id.'" '.
                    'size="10" '.
                    'maxlength="10" '.
                    'value="" />';
        apply_item_box_end();
        echo '</span>';
        echo '</div>';

        apply_close_table_item_tag();
    }


    /**
     * print the item at the complete-page of apply
     *
     * @global object
     * @param object $item
     * @param string $value
     * @param bool $highlightrequire
     * @return void
     */
    public function print_item_submit($item, $value = '', $highlightrequire = false)
    {
        global $OUTPUT;

        $align = right_to_left() ? 'right' : 'left';
        if ($highlightrequire AND (!$this->check_value($value, $item))) {
            $highlight = ' missingrequire';
        }
        else {
            $highlight = '';
        }

        //get the range
        $presentation = explode(APPLY_NUMERIC_SEP, $item->presentation);

        //get the min-value
        $range_from = 0;
        $range_to = 0;
        if (isset($presentation[0]) AND is_numeric($presentation[0])) $range_from = floatval($presentation[0]);
        if (isset($presentation[1]) AND is_numeric($presentation[1])) $range_to   = floatval($presentation[1]);

        //$outside_style = isset($presentation[2]) ? $presentation[2]: get_string('outside_style_default', 'apply');
        //$item_style    = isset($presentation[3]) ? $presentation[3]: get_string('item_style_default',    'apply');
        $item->outside_style = '';  //$outside_style;
        $item->item_style    = '';  //$item_style;

        $str_required_mark = '<span class="apply_required_mark">*</span>';
        $requiredmark = ($item->required == 1) ? $str_required_mark : '';
        //print the question and label
        $output  = ''; 
        $output .= '<div class="apply_item_label_'.$align.$highlight.'">';
        $output .= format_text($item->name . $requiredmark, true, false, false);
        $output .= '<span class="apply_item_numinfo">';

        switch(true) {
          case ($range_from === '-' AND is_numeric($range_to)):
            $output .= ' ('.get_string('maximal', 'apply').': '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_to).')';
            break;
          case (is_numeric($range_from) AND $range_to === '-'):
            $output .= ' ('.get_string('minimal', 'apply').': '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_from).')';
            break;
          case ($range_from === '-' AND $range_to === '-'):
            break;
          default:
            $output .= ' ('.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_from).' - '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_to).')';
            break;
        }
        $output .= '</span>';
        $output .= '</div>';

        apply_open_table_item_tag($output);

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.$highlight.'">';
        echo '<span class="apply_item_textfield">';
        apply_item_box_start($item);
        echo '<input type="text" '.
                     'name="'.$item->typ.'_'.$item->id.'" '.
                     'size="10" '.
                     'maxlength="10" '.
                     'value="'.$value.'" />';
        apply_item_box_end();
        echo '</span>';
        echo '</div>';

        apply_close_table_item_tag();
    }


    /**
     * print the item at the complete-page of apply
     *
     * @global object
     * @param object $item
     * @param string $value
     * @return void
     */
    public function print_item_show_value($item, $value = '')
    {
        global $OUTPUT;

        $align = right_to_left() ? 'right' : 'left';

        //get the range
        $presentation = explode(APPLY_NUMERIC_SEP, $item->presentation);
        //get the min-value
        if (isset($presentation[0]) AND is_numeric($presentation[0])) {
            $range_from = floatval($presentation[0]);
        }
        else {
            $range_from = 0;
        }
        //get the max-value
        if (isset($presentation[1]) AND is_numeric($presentation[1])) {
            $range_to = floatval($presentation[1]);
        }
        else {
            $range_to = 0;
        }
        $item->rangefrom = $range_from;
        $item->rangeto   = $range_to;

        //
        $outside_style = isset($presentation[2]) ? $presentation[2]: get_string('outside_style_default', 'apply');
        $item_style    = isset($presentation[3]) ? $presentation[3]: get_string('item_style_default',    'apply');
        $item->outside_style = $outside_style;
        $item->item_style    = $item_style;

        $str_required_mark = '<span class="apply_required_mark">*</span>';
        $requiredmark = ($item->required == 1) ? $str_required_mark : '';
        //print the question and label
        $output  = '';
        $output .= '<div class="apply_item_label_'.$align.'">';
        $output .= format_text($item->name . $requiredmark, true, false, false);

        switch(true) {
          case ($range_from === '-' AND is_numeric($range_to)):
            $output .= ' ('.get_string('maximal', 'apply').': '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_to).')';
            break;
          case (is_numeric($range_from) AND $range_to === '-'):
            $output .= ' ('.get_string('minimal', 'apply').': '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_from).')';
            break;
          case ($range_from === '-' AND $range_to === '-'):
             break;
          default:
             $output .= ' ('.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_from).' - '.str_replace(APPLY_DECIMAL, $this->sep_dec, $range_to).')';
             break;
        }
        $output .= '</div>';

        apply_open_table_item_tag($output);

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.'">';
        echo $OUTPUT->box_start('generalbox boxalign'.$align);
        apply_item_box_start($item);

        if (is_numeric($value)) {
            $str_num_value = number_format($value, 2, $this->sep_dec, $this->sep_thous);
        }
        else {
            $str_num_value = '&nbsp;';
        }
        echo $str_num_value;

        apply_item_box_end();
        echo $OUTPUT->box_end();
        echo '</div>';

        apply_close_table_item_tag();
    }


    public function check_value($value, $item)
    {
        $value = str_replace($this->sep_dec, APPLY_DECIMAL, $value);
        //if the item is not required, so the check is true if no value is given
        if ((!isset($value) OR $value == '') AND $item->required != 1) {
            return true;
        }
        if (!is_numeric($value)) {
            return false;
        }

        $presentation = explode(APPLY_NUMERIC_SEP, $item->presentation);
        if (isset($presentation[0]) AND is_numeric($presentation[0])) {
            $range_from = floatval($presentation[0]);
        }
        else {
            $range_from = '-';
        }
        if (isset($presentation[1]) AND is_numeric($presentation[1])) {
            $range_to = floatval($presentation[1]);
        }
        else {
            $range_to = '-';
        }

        switch(true) {
            case ($range_from === '-' AND is_numeric($range_to)):
                if (floatval($value) <= $range_to) {
                    return true;
                }
                break;
            case (is_numeric($range_from) AND $range_to === '-'):
                if (floatval($value) >= $range_from) {
                    return true;
                }
                break;
            case ($range_from === '-' AND $range_to === '-'):
                return true;
                break;
            default:
                if (floatval($value) >= $range_from AND floatval($value) <= $range_to) {
                    return true;
                }
                break;
        }
        return false;
    }


    public function create_value($data)
    {
        $data = str_replace($this->sep_dec, APPLY_DECIMAL, $data);

        if (is_numeric($data)) {
            $data = floatval($data);
        }
        else {
            $data = '';
        }
        return $data;
    }


    //compares the dbvalue with the dependvalue
    //dbvalue is the number put in by the user
    //dependvalue is the value that is compared
    public function compare_value($item, $dbvalue, $dependvalue)
    {
        if ($dbvalue == $dependvalue) {
            return true;
        }
        return false;
    }


    public function get_presentation($data)
    {
        $num1 = str_replace($this->sep_dec, APPLY_DECIMAL, $data->numericrangefrom);
        if (is_numeric($num1)) {
            $num1 = floatval($num1);
        }
        else {
            $num1 = '-';
        }

        $num2 = str_replace($this->sep_dec, APPLY_DECIMAL, $data->numericrangeto);
        if (is_numeric($num2)) {
            $num2 = floatval($num2);
        }
        else {
            $num2 = '-';
        }

        $num = '';
        if ($num1 === '-' OR $num2 === '-') {
            $num = $num1.APPLY_NUMERIC_SEP.$num2;
        }
        else {
            if ($num1 > $num2) {
                $num = $num2.APPLY_NUMERIC_SEP.$num1;
            }
            else {
                $num = $num1.APPLY_NUMERIC_SEP.$num2;
            }
        }       

        $num .= APPLY_NUMERIC_SEP.$data->outside_style.APPLY_NUMERIC_SEP.$data->item_style; 
        return $num;
    }


    public function get_hasvalue()
    {
        return 1;
    }


    public function can_switch_require()
    {
        return true;
    }


    public function value_type()
    {
        //return PARAM_FLOAT;
        return PARAM_TEXT;      // POST されたデータをこの型で受け取る (submit.php)
    }


    public function clean_input_value($value)
    {
        $value = str_replace($this->sep_dec, APPLY_DECIMAL, $value);
        if (!is_numeric($value)) {
            if ($value == '') {
                return null; //an empty string should be null
            }
            else {
                return clean_param($value, PARAM_TEXT); //we have to know the value if it is wrong
            }
        }
        return clean_param($value, $this->value_type());
    }
}
