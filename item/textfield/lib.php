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

define('APPLY_TEXTFIELD_SEP', '|');


class apply_item_textfield extends apply_item_base
{
    protected $type = "textfield";
    private $commonparams;
    private $item_form;
    private $item;

    public function init()
    {
    }


    public function build_editform($item, $apply, $cm)
    {
        global $DB, $CFG;
        require_once('textfield_form.php');

        //get the lastposition number of the apply_items
        $position = $item->position;
        $lastposition = $DB->count_records('apply_item', array('apply_id'=>$apply->id));
        if ($position == -1) {
            $i_formselect_last  = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position = $lastposition + 1;
        }
        else {
            $i_formselect_last  = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);

        $item->presentation = empty($item->presentation) ? '' : $item->presentation;
        $presentation = explode(APPLY_TEXTFIELD_SEP, $item->presentation);

        if (isset($presentation[0]) AND $presentation[0] >= 5) {
            $itemsize = $presentation[0];
        }
        else {
            $itemsize = 30;
        }
        $itemlength = isset($presentation[1]) ? $presentation[1] : 30;
        $item->itemsize = $itemsize;
        $item->itemmaxlength = $itemlength;

        $outside_style = isset($presentation[2]) ? $presentation[2]: get_string('outside_style_default', 'apply');
        $item_style    = isset($presentation[3]) ? $presentation[3]: get_string('item_style_default',    'apply');
        $item->outside_style = $outside_style;
        $item->item_style    = $item_style;

        //all items for dependitem
        $applyitems = apply_get_depend_candidates_for_item($apply, $item);
        $commonparams = array('cmid' => $cm->id,
                             'id' => isset($item->id) ? $item->id : null,
                             'typ' => $item->typ,
                             'items' => $applyitems,
                             'apply_id' => $apply->id);

        //build the form
        $customdata = array('item' => $item,
                            'common' => $commonparams,
                            'positionlist' => $positionlist,
                            'position' => $position);

        $this->item_form = new apply_textfield_form('edit_item.php', $customdata);
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

        $analysed_val = new stdClass();
        $analysed_val->data = null;
        $analysed_val->name = $item->name;

        $values = apply_get_group_values($item, $groupid, $courseid);
        if ($values) {
            $data = array();
            foreach ($values as $value) {
                $data[] = str_replace("\n", '<br />', $value->value);
            }
            $analysed_val->data = $data;
        }
        return $analysed_val;
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
        $values = apply_get_group_values($item, $groupid, $courseid);
        if ($values) {
            echo '<tr><th colspan="2" align="left">';
            echo $itemnr.'&nbsp;('.$item->label.') '.$item->name;
            echo '</th></tr>';
            foreach ($values as $value) {
                echo '<tr><td colspan="2" valign="top" align="left">';
                echo '-&nbsp;&nbsp;'.str_replace("\n", '<br />', $value->value);
                echo '</td></tr>';
            }
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
            $worksheet->write_string($row_offset, 2, $data[0], $xls_formats->value_bold);
            $row_offset++;
            $sizeofdata = count($data);
            for ($i = 1; $i < $sizeofdata; $i++) {
                $worksheet->write_string($row_offset, 2, $data[$i], $xls_formats->default);
                $row_offset++;
            }
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

        $presentation = explode(APPLY_TEXTFIELD_SEP, $item->presentation);
        //$outside_style = isset($presentation[2]) ? $presentation[2]: get_string('outside_style_default', 'apply');
        //$item_style    = isset($presentation[3]) ? $presentation[3]: get_string('item_style_default',    'apply');
        $item->outside_style = '';  //$outside_style;
        $item->item_style    = '';  //$item_style;

        //print the question and label
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';
        $output  = '';
        $output .= '<div class="apply_item_label_'.$align.'">';
        $output .= '('.$item->label.') ';
        $output .= format_text($item->name.$requiredmark, true, false, false).' ['.$item->position.']';
        if ($item->dependitem) {
            if ($dependitem = $DB->get_record('apply_item', array('id'=>$item->dependitem))) {
                $output .= ' <span class="apply_depend">';
                $output .= '('.$dependitem->label.'-&gt;'.$item->dependvalue.')';
                $output .= '</span>';
            }
        }
        $output .= '</div>';

        apply_open_table_item_tag($output, true);

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.'">';
        echo '<span class="apply_item_textfield">';
        apply_item_box_start($item);
        echo '<input type="text" '.
                    'name="'.$item->typ.'_'.$item->id.'" '.
                    'size="'.$presentation[0].'" '.
                    'maxlength="'.$presentation[1].'" '.
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

        $presentation = explode(APPLY_TEXTFIELD_SEP, $item->presentation);
        $outside_style = isset($presentation[2]) ? $presentation[2]: get_string('outside_style_default', 'apply');
        $item_style    = isset($presentation[3]) ? $presentation[3]: get_string('item_style_default',    'apply');
        $item->outside_style = '';  //$outside_style;
        $item->item_style    = '';  //$item_style;

        $align = right_to_left() ? 'right' : 'left';
        if ($highlightrequire AND $item->required AND strval($value) == '') $highlight = ' missingrequire';
        else                                                                $highlight = '';

        $str_required_mark = '<span class="apply_required_mark">*</span>';
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';
        //print the question and label
        $output  = '';
        $output .= '<div class="apply_item_label_'.$align.$highlight.'">';
        $output .= format_text($item->name.$requiredmark, true, false, false);
        $output .= '</div>';

        apply_open_table_item_tag($output);

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.$highlight.'">';
        echo '<span class="apply_item_textfield">';
        apply_item_box_start($item);
        echo '<input type="text" '.
                    'name="'.$item->typ.'_'.$item->id.'" '.
                    'size="'.$presentation[0].'" '.
                    'maxlength="'.$presentation[1].'" '.
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

        $presentation = explode(APPLY_TEXTFIELD_SEP, $item->presentation);
        $outside_style = isset($presentation[2]) ? $presentation[2]: get_string('outside_style_default', 'apply');
        $item_style    = isset($presentation[3]) ? $presentation[3]: get_string('item_style_default',    'apply');
        $item->outside_style = $outside_style;
        $item->item_style    = $item_style;

        $align = right_to_left() ? 'right' : 'left';
        $str_required_mark = '<span class="apply_required_mark">*</span>';
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';
        //print the question and label
        $output  = '';
        $output .= '<div class="apply_item_label_'.$align.'">';
        $output .= format_text($item->name . $requiredmark, true, false, false);
        $output .= '</div>';

        apply_open_table_item_tag($output);

        echo $OUTPUT->box_start('generalbox boxalign'.$align);
        apply_item_box_start($item);
        echo $value ? $value : '&nbsp;';
        apply_item_box_end();
        echo $OUTPUT->box_end();

        apply_close_table_item_tag();
    }


    public function check_value($value, $item)
    {
        //if the item is not required, so the check is true if no value is given
        if ((!isset($value) OR $value == '') AND $item->required != 1) {
            return true;
        }
        if ($value == "") {
            return false;
        }
        return true;
    }


    public function create_value($data)
    {
        $data = s($data);
        return $data;
    }


    //compares the dbvalue with the dependvalue
    //dbvalue is the value put in by the user
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
        return $data->itemsize.APPLY_TEXTFIELD_SEP.$data->itemmaxlength.
                               APPLY_TEXTFIELD_SEP.$data->outside_style.APPLY_TEXTFIELD_SEP.$data->item_style;
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
        return PARAM_RAW;
    }


    public function clean_input_value($value)
    {
        return s($value);
    }
}
