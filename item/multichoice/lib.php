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

define('APPLY_MULTICHOICE_TYPE_SEP', '>>>>>');
define('APPLY_MULTICHOICE_LINE_SEP', '|');
define('APPLY_MULTICHOICE_ADJUST_SEP', '<<<<<');
define('APPLY_MULTICHOICE_IGNOREEMPTY', 'i');
define('APPLY_MULTICHOICE_HIDENOSELECT', 'h');

define('APPLY_MULTICHOICE_STYLE_FIELD_SEP', ':::::');
define('APPLY_MULTICHOICE_STYLE_SEP', '-----');


class apply_item_multichoice extends apply_item_base
{
    protected $type = "multichoice";
    private $commonparams;
    private $item_form;
    private $item;


    public function init()
    {
    }


    public function build_editform($item, $apply, $cm)
    {
        global $DB, $CFG;
        require_once('multichoice_form.php');

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

        $presen = explode(APPLY_MULTICHOICE_STYLE_FIELD_SEP, $item->presentation);
        if (isset($presen[1])) {
            $styles = explode(APPLY_MULTICHOICE_STYLE_SEP, $presen[1]);
            $outside_style = isset($styles[0]) ? $styles[0] : get_string('outside_style_default', 'apply');
            $item_style    = isset($styles[1]) ? $styles[1] : get_string('item_style_default',    'apply');
        }
        else {
            $outside_style = get_string('outside_style_default', 'apply');
            $item_style    = get_string('item_style_default',    'apply');
        }        
        $item->outside_style = $outside_style;
        $item->item_style    = $item_style;

        $info = $this->get_info($item);

        $item->ignoreempty  = $this->ignoreempty($item);
        $item->hidenoselect = $this->hidenoselect($item);

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
                            'position' => $position,
                            'info' => $info);

        $this->item_form = new apply_multichoice_form('edit_item.php', $customdata);
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

        $this->set_ignoreempty($item, $item->ignoreempty);
        $this->set_hidenoselect($item, $item->hidenoselect);

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->id = $DB->insert_record('apply_item', $item);
        }
        else {
            $DB->update_record('apply_item', $item);
        }

        return $DB->get_record('apply_item', array('id'=>$item->id));
    }


    //gets an array with three values(typ, name, XXX)
    //XXX is an object with answertext, answercount and quotient
    public function get_analysed($item, $groupid = false, $courseid = false)
    {
        $analysed_item = array();
        $analysed_item[] = $item->typ;
        $analysed_item[] = $item->name;

        //get the possible answers
        $info = $this->get_info($item);
        $answers = explode(APPLY_MULTICHOICE_LINE_SEP, $info->presentation);
        if (!is_array($answers)) return null;

        //get the values
        $values = apply_get_group_values($item, $groupid, $courseid, $this->ignoreempty($item));
        if (!$values) return null;

        //get answertext, answercount and quotient for each answer
        $analysed_answer = array();
        if ($info->subtype == 'c') {
            $sizeofanswers = count($answers);
            for ($i = 1; $i <= $sizeofanswers; $i++) {
                $ans = new stdClass();
                $ans->answertext = $answers[$i-1];
                $ans->answercount = 0;
                foreach ($values as $value) {
                    //ist die Antwort gleich dem index der Antworten + 1?
                    $vallist = explode(APPLY_MULTICHOICE_LINE_SEP, $value->value);
                    foreach ($vallist as $val) {
                        if ($val == $i) {
                            $ans->answercount++;
                        }
                    }
                }
                $ans->quotient = $ans->answercount / count($values);
                $analysed_answer[] = $ans;
            }
        }
        else {
            $sizeofanswers = count($answers);
            for ($i = 1; $i <= $sizeofanswers; $i++) {
                $ans = new stdClass();
                $ans->answertext = $answers[$i-1];
                $ans->answercount = 0;
                foreach ($values as $value) {
                    //ist die Antwort gleich dem index der Antworten + 1?
                    if ($value->value == $i) {
                        $ans->answercount++;
                    }
                }
                $ans->quotient = $ans->answercount / count($values);
                $analysed_answer[] = $ans;
            }
        }
        $analysed_item[] = $analysed_answer;
        return $analysed_item;
    }


    public function get_printval($item, $value)
    {
        $printval = '';
        if (!isset($value->value)) return $printval;

        $info = $this->get_info($item);
        $presentation = explode(APPLY_MULTICHOICE_LINE_SEP, $info->presentation);

        if ($info->subtype == 'c') {
            $vallist = array_values(explode(APPLY_MULTICHOICE_LINE_SEP, $value->value));
            $sizeofvallist = count($vallist);
            $sizeofpresentation = count($presentation);
            for ($i = 0; $i < $sizeofvallist; $i++) {
                for ($k = 0; $k < $sizeofpresentation; $k++) {
                    if ($vallist[$i] == ($k + 1)) {//Die Werte beginnen bei 1, das Array aber mit 0
                        $printval .= trim($presentation[$k]) . chr(10);
                        break;
                    }
                }
            }
        }
        else {
            $index = 1;
            foreach ($presentation as $pres) {
                if ($value->value == $index) {
                    $printval = $pres;
                    break;
                }
                $index++;
            }
        }
        return $printval;
    }


    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false)
    {
        global $OUTPUT;
        $sep_dec = get_string('separator_decimal', 'apply');
        if (substr($sep_dec, 0, 2) == '[[') {
            $sep_dec = APPLY_DECIMAL;
        }

        $sep_thous = get_string('separator_thousand', 'apply');
        if (substr($sep_thous, 0, 2) == '[[') {
            $sep_thous = APPLY_THOUSAND;
        }

        $analysed_item = $this->get_analysed($item, $groupid, $courseid);
        if ($analysed_item) {
            $itemname = $analysed_item[1];
            echo '<tr><th colspan="2" align="left">';
            echo $itemnr.'&nbsp;('.$item->label.') '.$itemname;
            echo '</th></tr>';

            $analysed_vals = $analysed_item[2];
            $pixnr = 0;
            foreach ($analysed_vals as $val) {
                $intvalue = $pixnr % 10;
                $pix = $OUTPUT->image_url('multichoice/' . $intvalue, 'apply');
                $pixnr++;
                $pixwidth = intval($val->quotient * APPLY_MAX_PIX_LENGTH);
                $quotient = number_format(($val->quotient * 100), 2, $sep_dec, $sep_thous);
                $str_quotient = '';
                if ($val->quotient > 0) {
                    $str_quotient = '&nbsp;('. $quotient . '&nbsp;%)';
                }
                echo '<tr>';
                echo '<td align="left" valign="top">
                            -&nbsp;&nbsp;'.trim($val->answertext).':
                      </td>
                      <td align="left" style="width:'.APPLY_MAX_PIX_LENGTH.';">
                        <img alt="'.$intvalue.'" src="'.$pix.'" height="5" width="'.$pixwidth.'" />
                        &nbsp;'.$val->answercount.$str_quotient.'
                      </td>';
                echo '</tr>';
            }
        }
    }


    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false)
    {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);

        $data = $analysed_item[2];

        //frage schreiben
        $worksheet->write_string($row_offset, 0, $item->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $analysed_item[1], $xls_formats->head2);
        if (is_array($data)) {
            $sizeofdata = count($data);
            for ($i = 0; $i < $sizeofdata; $i++) {
                $analysed_data = $data[$i];

                $worksheet->write_string($row_offset,
                                         $i + 2,
                                         trim($analysed_data->answertext),
                                         $xls_formats->head2);

                $worksheet->write_number($row_offset + 1,
                                         $i + 2,
                                         $analysed_data->answercount,
                                         $xls_formats->default);

                $worksheet->write_number($row_offset + 2,
                                         $i + 2,
                                         $analysed_data->quotient,
                                         $xls_formats->procent);
            }
        }
        $row_offset += 3;
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

        /*
        $presen = explode(APPLY_MULTICHOICE_STYLE_FIELD_SEP, $item->presentation);
        if (isset($presen[1])) {
            $styles = explode(APPLY_MULTICHOICE_STYLE_SEP, $presen[1]);
            $outside_style = isset($styles[0]) ? $styles[0] : get_string('outside_style_default', 'apply');
            $item_style    = isset($styles[1]) ? $styles[1] : get_string('item_style_default',    'apply');
        }
        else {
            $outside_style = get_string('outside_style_default', 'apply');
            $item_style    = get_string('item_style_default',    'apply');
        } */
        $item->outside_style = '';  //$outside_style;
        $item->item_style    = '';  //$item_style;

        //
        $info = $this->get_info($item);
        $presentation = explode(APPLY_MULTICHOICE_LINE_SEP, $info->presentation);
        $str_required_mark = '<span class="apply_required_mark">*</span>';

        //test if required and no value is set so we have to mark this item
        //we have to differ check and the other subtypes
        $align = right_to_left() ? 'right' : 'left';
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';

        //print the question and label
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
        apply_item_box_start($item);
        //echo '<ul>';

        $index = 1;
        $checked = '';
        if ($info->horizontal) $hv = 'h';
        else                   $hv = 'v';

        if ($info->subtype == 'r' AND !$this->hidenoselect($item)) {
        //print the "not_selected" item on radiobuttons
        ?>
        <li class="apply_item_radio_<?php echo $hv.'_'.$align;?>">
            <span class="apply_item_radio_<?php echo $hv.'_'.$align;?>">
                <?php
                    echo '<input type="radio" '.
                            'name="'.$item->typ.'_'.$item->id.'[]" '.
                            'id="'.$item->typ.'_'.$item->id.'_xxx" '.
                            'value="" checked="checked" />';
                ?>
            </span>
            <span class="apply_item_radiolabel_<?php echo $hv.'_'.$align;?>">
                <!-- <label for="<?php echo $item->typ . '_' . $item->id.'_xxx';?>"> -->
                    <?php print_string('not_selected', 'apply');?>&nbsp;
                <!-- </label> -->
            </span>
        </li>
        <?php
        }

        switch($info->subtype) {
            case 'r':
                $this->print_item_radio($presentation, $item, false, $info, $align);
                break;
            case 'c':
                $this->print_item_check($presentation, $item, false, $info, $align);
                break;
            case 'd':
                $this->print_item_dropdown($presentation, $item, false, $info, $align);
                break;
        }
        //echo '</ul>';
        apply_item_box_end();
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
    public function print_item_submit($item, $value = null, $highlightrequire = false)
    {
        global $OUTPUT;

        if ($value == null) $value = array();

        /*
        $presen = explode(APPLY_MULTICHOICE_STYLE_FIELD_SEP, $item->presentation);
        if (isset($presen[1])) {
            $styles = explode(APPLY_MULTICHOICE_STYLE_SEP, $presen[1]);
            $outside_style = isset($styles[0]) ? $styles[0] : get_string('outside_style_default', 'apply');
            $item_style    = isset($styles[1]) ? $styles[1] : get_string('item_style_default',    'apply');
        }
        else {
            $outside_style = get_string('outside_style_default', 'apply');
            $item_style    = get_string('item_style_default',    'apply');
        } */
        $item->outside_style = '';  //$outside_style;
        $item->item_style    = '';  //$item_style;

        $info = $this->get_info($item);
        $presentation = explode(APPLY_MULTICHOICE_LINE_SEP, $info->presentation);

        //
        $align = right_to_left() ? 'right' : 'left';
        $str_required_mark = '<span class="apply_required_mark">*</span>';

        if (is_array($value)) {
            $values = $value;
        }
        else {
            $values = explode(APPLY_MULTICHOICE_LINE_SEP, $value);
        }

        $highlight = '';
        if ($highlightrequire AND $item->required) {
            if (count($values) == 0 OR $values[0] == '' OR $values[0] == 0) {
                $highlight = ' missingrequire';
            }
        }

        $requiredmark = ($item->required == 1) ? $str_required_mark : '';
        //print the question and label
        $output  = '';
        $output .= '<div class="apply_item_label_'.$align.$highlight.'">';
        $output .= format_text($item->name.$requiredmark, true, false, false);
        $output .= '</div>';

        apply_open_table_item_tag($output);

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.$highlight.'">';
        apply_item_box_start($item);
        //echo '<ul>';

        if ($info->horizontal) {
            $hv = 'h';
        }
        else {
            $hv = 'v';
        }

        //print the "not_selected" item on radiobuttons
        if ($info->subtype == 'r' AND !$this->hidenoselect($item)) {
        ?>
            <li class="apply_item_radio_<?php echo $hv.'_'.$align;?>">
                <span class="apply_item_radio_<?php echo $hv.'_'.$align;?>">
                    <?php
                    $checked = '';
                    if (count($values) == 0 OR $values[0] == '' OR $values[0] == 0) {
                        $checked = 'checked="checked"';
                    }
                    echo '<input type="radio" '.
                            'name="'.$item->typ.'_'.$item->id.'[]" '.
                            'id="'.$item->typ.'_'.$item->id.'_xxx" '.
                            'value="" '.$checked.' />';
                    ?>
                </span>
                <span class="apply_item_radiolabel_<?php echo $hv.'_'.$align;?>">
                    <!-- <label for="<?php echo $item->typ.'_'.$item->id.'_xxx';?>"> -->
                        <?php print_string('not_selected', 'apply');?>&nbsp;
                    <!--< /label> -->
                </span>
            </li>
        <?php
        }

        switch($info->subtype) {
            case 'r':
                $this->print_item_radio($presentation, $item, $value, $info, $align);
                break;
            case 'c':
                $this->print_item_check($presentation, $item, $value, $info, $align);
                break;
            case 'd':
                $this->print_item_dropdown($presentation, $item, $value, $info, $align);
                break;
        }
        //echo '</ul>';
        apply_item_box_end();
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
    public function print_item_show_value($item, $value = null)
    {
        global $OUTPUT;

        $presen = explode(APPLY_MULTICHOICE_STYLE_FIELD_SEP, $item->presentation);
        if (isset($presen[1])) {
            $styles = explode(APPLY_MULTICHOICE_STYLE_SEP, $presen[1]);
            $outside_style = isset($styles[0]) ? $styles[0] : get_string('outside_style_default', 'apply');
            $item_style    = isset($styles[1]) ? $styles[1] : get_string('item_style_default',    'apply');
        }
        else {
            $outside_style = get_string('outside_style_default', 'apply');
            $item_style    = get_string('item_style_default',    'apply');
        }
        $item->outside_style = $outside_style;
        $item->item_style    = $item_style;

        $align = right_to_left() ? 'right' : 'left';
        $requiredmark = '';
        if ($item->required == 1) {
            $requiredmark = '<span class="apply_required_mark">*</span>';
        }

        //
        if ($value == null) $value = array();

        $info = $this->get_info($item);
        $presentation = explode(APPLY_MULTICHOICE_LINE_SEP, $info->presentation);

        //test if required and no value is set so we have to mark this item
        //we have to differ check and the other subtypes
        if ($info->subtype == 'c') {
            if (is_array($value)) {
                $values = $value;
            }
            else {
                $values = explode(APPLY_MULTICHOICE_LINE_SEP, $value);
            }
        }

        //print the question and label
        $output  = ''; 
        $output .=  '<div class="apply_item_label_'.$align.'">';
        $output .=  format_text($item->name . $requiredmark, true, false, false);
        $output .=  '</div>';

        apply_open_table_item_tag($output);

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.'">';

        $index = 1;
        if ($info->subtype == 'c') {
            $match = false;
            echo $OUTPUT->box_start('generalbox boxalign'.$align);
            apply_item_box_start($item);
            foreach ($presentation as $pres) {
                foreach ($values as $val) {
                    if ($val == $index) {
                        echo '<div class="apply_item_multianswer">';
                        echo text_to_html($pres, true, false, false);
                        echo '</div>';
                        $match = true;
                        break;
                    }
                }
                $index++;
            }
            if (!$match) echo '&nbsp;';
            apply_item_box_end();
            echo $OUTPUT->box_end();
        } 
        else {
            $match = false;
            echo $OUTPUT->box_start('generalbox boxalign'.$align);
            apply_item_box_start($item);
            foreach ($presentation as $pres) {
                if ($value == $index) {
                    echo $html = text_to_html($pres, true, false, false);
                    $match = true;
                    break;
                }
                $index++;
            }
            if (!$match) echo '&nbsp;';
            apply_item_box_end();
            echo $OUTPUT->box_end();
        }
        echo '</div>';

        apply_close_table_item_tag();
    }


    public function check_value($value, $item)
    {
        $info = $this->get_info($item);

        if ($item->required != 1) {
            return true;
        }

        if (!isset($value) OR !is_array($value) OR $value[0] == '' OR $value[0] == 0) {
            return false;
        }

        return true;
    }


    public function create_value($data)
    {
        $vallist = $data;
        if (is_array($vallist)) {
            $vallist = array_unique($vallist);
        }
        return trim($this->item_array_to_string($vallist));
    }


    //compares the dbvalue with the dependvalue
    //dbvalue is the number of one selection
    //dependvalue is the presentation of one selection
    public function compare_value($item, $dbvalue, $dependvalue)
    {
        if (is_array($dbvalue)) {
            $dbvalues = $dbvalue;
        }
        else {
            $dbvalues = explode(APPLY_MULTICHOICE_LINE_SEP, $dbvalue);
        }

        $info = $this->get_info($item);
        $presentation = explode(APPLY_MULTICHOICE_LINE_SEP, $info->presentation);

        $index = 1;
        foreach ($presentation as $pres) {
            foreach ($dbvalues as $dbval) {
                if ($dbval == $index AND trim($pres) == $dependvalue) {
                    return true;
                }
            }
            $index++;
        }
        return false;
    }


    public function get_presentation($data)
    {
        $present = str_replace("\n", APPLY_MULTICHOICE_LINE_SEP, trim($data->itemvalues));
        if (!isset($data->subtype)) {
            $subtype = 'r';
        }
        else {
            $subtype = substr($data->subtype, 0, 1);
        }
        if (isset($data->horizontal) AND $data->horizontal == 1 AND $subtype != 'd') {
            $present .= APPLY_MULTICHOICE_ADJUST_SEP.'1';
        }

        $presentation = $subtype.APPLY_MULTICHOICE_TYPE_SEP.$present.
                                 APPLY_MULTICHOICE_STYLE_FIELD_SEP.$data->outside_style.
                                 APPLY_MULTICHOICE_STYLE_SEP.$data->item_style;
        return $presentation;
    }


    public function get_hasvalue()
    {
        return 1;
    }


    public function get_info($item)
    {
        $presentation = empty($item->presentation) ? '' : $item->presentation;

        $info = new stdClass();
        //check the subtype of the multichoice
        //it can be check(c), radio(r) or dropdown(d)
        $info->subtype = '';
        $info->presentation = '';
        $info->horizontal = false;

        $presen = explode(APPLY_MULTICHOICE_STYLE_FIELD_SEP, $item->presentation);
        $parts  = explode(APPLY_MULTICHOICE_TYPE_SEP, $presen[0]);
        @list($info->subtype, $info->presentation) = $parts;

        if (!isset($info->subtype)) {
            $info->subtype = 'r';
        }

        if ($info->subtype != 'd') {
            $parts = explode(APPLY_MULTICHOICE_ADJUST_SEP, $info->presentation);
            @list($info->presentation, $info->horizontal) = $parts;
            if (isset($info->horizontal) AND $info->horizontal == 1) {
                $info->horizontal = true;
            }
            else {
                $info->horizontal = false;
            }
        }
        return $info;
    }


    private function item_array_to_string($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        $retval = '';
        $arrvals = array_values($value);
        $arrvals = clean_param_array($arrvals, PARAM_INT);  //prevent sql-injection
        $retval = $arrvals[0];
        $sizeofarrvals = count($arrvals);
        for ($i = 1; $i < $sizeofarrvals; $i++) {
            $retval .= APPLY_MULTICHOICE_LINE_SEP.$arrvals[$i];
        }
        return $retval;
    }


    private function print_item_radio($presentation, $item, $value, $info, $align)
    {
        $index = 1;
        $checked = '';

        if (is_array($value)) {
            $values = $value;
        }
        else {
            $values = array($value);
        }

        if ($info->horizontal) {
            $hv = 'h';
        }
        else {
            $hv = 'v';
        }

        foreach ($presentation as $radio) {
            foreach ($values as $val) {
                if ($val == $index) {
                    $checked = 'checked="checked"';
                    break;
                }
                else {
                    $checked = '';
                }
            }
            $inputname = $item->typ . '_' . $item->id;
            $inputid = $inputname.'_'.$index;
        ?>
            <li class="apply_item_radio_<?php echo $hv.'_'.$align;?>">
                <span class="apply_item_radio_<?php echo $hv.'_'.$align;?>">
                    <?php
                        echo '<input type="radio" '.
                                'name="'.$inputname.'[]" '.
                                'id="'.$inputid.'" '.
                                'value="'.$index.'" '.$checked.' />';
                    ?>
                </span>
                <span class="apply_item_radiolabel_<?php echo $hv.'_'.$align;?>">
                    <!-- <label for="<?php echo $inputid;?>"> -->
                        <?php echo text_to_html($radio, true, false, false);?>&nbsp;
                    <!-- </label> -->
                </span>
            </li>
        <?php
            $index++;
        }
    }


    private function print_item_check($presentation, $item, $value, $info, $align)
    {
        if (is_array($value)) {
            $values = $value;
        }
        else {
            $values = explode(APPLY_MULTICHOICE_LINE_SEP, $value);
        }

        if ($info->horizontal) {
            $hv = 'h';
        }
        else {
            $hv = 'v';
        }

        $index = 1;
        $checked = '';
        foreach ($presentation as $check) {
            foreach ($values as $val) {
                if ($val == $index) {
                    $checked = 'checked="checked"';
                    break;
                }
                else {
                    $checked = '';
                }
            }
            $inputname = $item->typ. '_' . $item->id;
            $inputid = $item->typ. '_' . $item->id.'_'.$index;
        ?>
            <li class="apply_item_check_<?php echo $hv.'_'.$align;?>">
                <span class="apply_item_check_<?php echo $hv.'_'.$align;?>">
                    <?php
                        echo '<input type="checkbox" '.
                              'name="'.$inputname.'[]" '.
                              'id="'.$inputid.'" '.
                              'value="'.$index.'" '.$checked.' />';
                    ?>
                </span>
                <span class="apply_item_radiolabel_<?php echo $hv.'_'.$align;?>">
                    <!-- <label for="<?php echo $inputid;?>"> -->
                        <?php echo text_to_html($check, true, false, false);?>&nbsp;
                    <!-- </label> -->
                </span>
            </li>
        <?php
            $index++;
        }
    }


    private function print_item_dropdown($presentation, $item, $value, $info, $align)
    {
        if (is_array($value)) {
            $values = $value;
        }
        else {
            $values = array($value);
        }

        if ($info->horizontal) {
            $hv = 'h';
        }
        else {
            $hv = 'v';
        }

        ?>
        <li class="apply_item_select_<?php echo $hv.'_'.$align;?>">
            <label class="accesshide" for="<?php echo $item->typ .'_' . $item->id;?>"><?php echo $item->name; ?></label>
            <select  id="<?php echo $item->typ .'_' . $item->id;?>" name="<?php echo $item->typ .'_' . $item->id;?>[]" size="1">
                <option value="0">&nbsp;</option>
                <?php
                $index = 1;
                $selected = '';
                foreach ($presentation as $dropdown) {
                    foreach ($values as $val) {
                        if ($val == $index) {
                            $selected = 'selected="selected"';
                            break;
                        }
                        else {
                            $selected = '';
                        }
                    }
                ?>
                    <option value="<?php echo $index;?>" <?php echo $selected;?>>
                        <?php echo text_to_html($dropdown, true, false, false);?>
                    </option>
                <?php
                    $index++;
                }
                ?>
            </select>
        </li>
        <?php
    }


    public function set_ignoreempty($item, $ignoreempty=true) 
    {
        $item->options = str_replace(APPLY_MULTICHOICE_IGNOREEMPTY, '', $item->options);
        if ($ignoreempty) {
            $item->options .= APPLY_MULTICHOICE_IGNOREEMPTY;
        }
    }


    public function ignoreempty($item)
    {
        if (strstr($item->options, APPLY_MULTICHOICE_IGNOREEMPTY)) {
            return true;
        }
        return false;
    }


    public function set_hidenoselect($item, $hidenoselect=true)
    {
        $item->options = str_replace(APPLY_MULTICHOICE_HIDENOSELECT, '', $item->options);
        if ($hidenoselect) {
            $item->options .= APPLY_MULTICHOICE_HIDENOSELECT;
        }
    }


    public function hidenoselect($item)
    {
        if (strstr($item->options, APPLY_MULTICHOICE_HIDENOSELECT)) {
            return true;
        }
        return false;
    }


    public function can_switch_require()
    {
        return true;
    }

    
    public function value_type()
    {
        return PARAM_INT;
    }

    
    public function value_is_array()
    {
        return true;
    }


    public function clean_input_value($value)
    {
        return clean_param_array($value, $this->value_type());
    }
}
