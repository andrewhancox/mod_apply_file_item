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

define('APPLY_TABLESTART_SEP', '>>>>>');

//
class apply_item_tablestart extends apply_item_base
{
    protected $type = "tablestart";
    private $commonparams;
    private $item_form;
    private $item;


    public function init()
    {
    }


    public function build_editform($item, $apply, $cm)
    {
        global $DB, $CFG;
        require_once('tablestart_form.php');

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
        $presentation = explode(APPLY_TABLESTART_SEP, $item->presentation);

        $columns      = (intval($presentation[0])!=0) ? $presentation[0] : 3;
        $border       = isset($presentation[1]) ? $presentation[1]: 1;
        $border_style = isset($presentation[2]) ? $presentation[2]: 'solid';
        $th_sizes     = isset($presentation[3]) ? $presentation[3]: '';
        $th_strings   = isset($presentation[4]) ? $presentation[4]: '';
        $disp_iname   = isset($presentation[5]) ? $presentation[5]: 0;

        if (!property_exists($item, 'label')) $item->label = '';
        if ($item->label=='') $item->label = 'table_start';
        $item->columns      = $columns;
        $item->border       = $border;
        $item->border_style = $border_style;
        $item->th_sizes     = $th_sizes;
        $item->th_strings   = $th_strings;
        $item->disp_iname   = $disp_iname;

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

        $this->item_form = new apply_tablestart_form('edit_item.php', $customdata);
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


    public function get_analysed($item, $groupid = false, $courseid = false)
    {
        return null;
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
        return $itemnr;
    }


    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false)
    {

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
        global $Table_in;

        $align = right_to_left() ? 'right' : 'left';
        echo '<div class="apply_item_label_'.$align.'">';
        echo '('.$item->label.') ';
        echo format_text($item->name, true, false, false).' ['.$item->position.']';
        //
        //Warnning!! Table is nested. This table is ignored.
        if ($Table_in) echo '&nbsp;&nbsp;<span style="color:#c00000">['.get_string('nested_table','apply').']</span>';

        if ($item->dependitem) {
            if ($dependitem = $DB->get_record('apply_item', array('id'=>$item->dependitem))) {
                echo ' <span class="apply_depend">';
                echo '('.$dependitem->label.'-&gt;'.$item->dependvalue.')';
                echo '</span>';
            }
        }
        echo '</div>';
        //
        apply_open_table_tag($item);
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
        global $Table_in;

        if ($Table_in) return;

        $align = right_to_left() ? 'right' : 'left';

        if ($highlightrequire AND strval($value) == '') {
            $highlight = ' missingrequire';
        }
        else {
            $highlight = '';
        }
        //print the question and label
        echo '<div class="apply_item_label_'.$align.$highlight.'">';
        echo format_text($item->name, true, false, false);
        echo '</div>';

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.$highlight.'">';
        echo $value;
        echo '</div>';

        apply_open_table_tag($item);
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
        global $Table_in;

        if ($Table_in) return;

        $align = right_to_left() ? 'right' : 'left';

        //print the question and label
        echo '<div class="apply_item_label_'.$align.'">';
        echo format_text($item->name, true, false, false);
        echo '</div>';

        //print the presentation
        echo $OUTPUT->box_start('generalbox boxalign'.$align);
        echo $value;
        echo $OUTPUT->box_end();

        apply_open_table_tag($item);
    }


    public function check_value($value, $item)
    {
        return true;
    }


    public function create_value($data) {
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
        $presen = $data->columns.APPLY_TABLESTART_SEP.$data->border.APPLY_TABLESTART_SEP.$data->border_style.APPLY_TABLESTART_SEP.
                  $data->th_sizes.APPLY_TABLESTART_SEP.$data->th_strings.APPLY_TABLESTART_SEP.$data->disp_iname;
        return $presen;
    }


    public function get_hasvalue()
    {
        return 0;
    }


    public function can_switch_require()
    {
        return false;
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
