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
require_once($CFG->libdir.'/formslib.php');


class apply_item_printpagebreak extends apply_item_base
{
    protected $type = "printpagebreak";
    private $commonparams;
    private $item_form;
    private $item;


    public function init()
    {
    }


    public function build_editform($item, $apply, $cm)
    {
        global $DB, $CFG;

        require_once('printpagebreak_form.php');

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

        if (!property_exists($item, 'label')) $item->label = '';
        if (!property_exists($item, 'name'))  $item->name  = '';
        if ($item->label=='') $item->label = 'print_page_break';
        if ($item->name=='' ) $item->name  = get_string('pagebreak_title','apply');

        $pagebreak_style = isset($item->presentation) ? $item->presentation: get_string('pagebreak_style_default', 'apply');
        $item->pagebreak_style = $pagebreak_style;

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

        $this->item_form = new apply_printpagebreak_form('edit_item.php', $customdata);
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


    /**
     * print the item at the edit-page of apply
     *
     * @global object
     * @param object $item
     * @return void
     */
    public function print_item_preview($item)
    {
        global $DB;

        $pagebreak_style = isset($item->presentation) ? $item->presentation: get_string('pagebreak_style_default', 'apply');
        $item->pagebreak_style = $pagebreak_style;

        $align = right_to_left() ? 'right' : 'left';

        $output  = '';
        $output .= '<div class="apply_item_label_'.$align.'">';
        $output .= '('.$item->label.') ';
        $output .= format_text($item->name, true, false, false).' ['.$item->position.']';

        if ($item->dependitem) {
            if ($dependitem = $DB->get_record('apply_item', array('id'=>$item->dependitem))) {
                $output .= ' <span class="apply_depend">';
                $output .= '('.$dependitem->label.'-&gt;'.$item->dependvalue.')';
                $output .= '</span>';
            }
        }
        $output .= '</div>';

        apply_open_table_item_tag($output, true);
        if ($item->pagebreak_style!='') echo '<hr style="border: '.$item->pagebreak_style.'" />';
        else echo ' ';
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
        global $Table_in;

        apply_open_table_item_tag('');
        if ($Table_in) echo ' ';
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
        global $Table_in;

        $pagebreak_style = isset($item->presentation) ? $item->presentation: get_string('pagebreak_style_default', 'apply');
        $item->pagebreak_style = $pagebreak_style;

        $align  = right_to_left() ? 'right' : 'left';
        $output = '<br />'.format_text($item->name, true, false, false);

        apply_open_table_item_tag($output);
        if (!$Table_in) {
            if ($item->pagebreak_style!='') echo '<hr style="border: '.$item->pagebreak_style.'" />';
            echo '<span style="page-break-after: always;"></span>';
        }
        else echo ' ';
        apply_close_table_item_tag();
    }


    public function create_value($data) 
    {
        return false;
    }


    public function compare_value($item, $dbvalue, $dependvalue)
    {
        return false;
    }


    public function get_presentation($data)
    {
        return $data->pagebreak_style;
    }

   
    public function get_hasvalue()
    {
        return 0;
    }


    public function can_switch_require()
    {
        return false;
    }


    public function check_value($value, $item)
    {
    }


    public function excelprint_item(&$worksheet,
                             $row_offset,
                             $xls_formats,
                             $item,
                             $groupid,
                             $courseid = false)
    {
        return $row_offset;
    }


    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false)
    {
    }
   

    public function get_printval($item, $value)
    {
    }


    public function get_analysed($item, $groupid = false, $courseid = false)
    {
    }


    public function value_type()
    {
    }


    public function clean_input_value($value)
    {
        return $value;
    }
}
