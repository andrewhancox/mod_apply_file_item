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

require_once($CFG->dirroot.'/mod/apply/item/apply_item_form_class.php');

class apply_tablestart_form extends apply_item_form
{
    protected $type = "tablestart";

    public function definition()
    {
        global $OUTPUT;

        $border_styles = array('none'=>'none', 'hidden'=>'hidden', 'solid'=>'solid', 'double'=>'double', 'dashed'=>'dashed', 
                               'dotted'=>'dotted', 'groove'=>'groove', 'ridge'=>'ridge', 'inset'=>'inset', 'outset'=>'outset');

        $item = $this->_customdata['item'];
        $common = $this->_customdata['common'];
        $positionlist = $this->_customdata['positionlist'];
        $position = $this->_customdata['position'];

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string($this->type, 'apply'));
        $mform->addElement('text', 'name',  get_string('item_name', 'apply'), array('size'=>APPLY_ITEM_NAME_TEXTBOX_SIZE, 'maxlength'=>255));
        $mform->addElement('text', 'label', get_string('item_label','apply'), array('size'=>APPLY_ITEM_LABEL_TEXTBOX_SIZE,'maxlength'=>255));
        $mform->setType('label', PARAM_TEXT);

        $mform->addElement('select', 'columns', get_string('table_columns', 'apply'), array_slice(range(0, 20), 1, 20, true));
        $mform->setDefault('columns', 3);
        $mform->setType('columns', PARAM_INT);

        $mform->addElement('select', 'border',  get_string('table_border',  'apply'), range(0, 10));
        $mform->addHelpButton('border', 'table_border', 'apply');
        $mform->setDefault('border', 1);
        $mform->setType('border', PARAM_INT);

        $mform->addElement('select', 'border_style',  get_string('table_border_style', 'apply'), $border_styles);
        $mform->addHelpButton('border_style', 'table_border_style', 'apply');
        $mform->setDefault('border_style', 'solid');
        $mform->setType('border_style', PARAM_ALPHA);

        $mform->addElement('text', 'th_sizes', get_string('table_th_sizes', 'apply'), 'wrap="virtual" cols="40"');
        $mform->addHelpButton('th_sizes', 'table_th_sizes', 'apply');
        $mform->setDefault('th_sizes', '');
        $mform->setType('th_sizes', PARAM_TEXT);

        $mform->addElement('static', 'hint', get_string('table_th_strings', 'apply'), get_string('use_one_line_for_each_value', 'apply'));
        $mform->addElement('textarea', 'th_strings', '', 'wrap="virtual" rows="5" cols="30"');
        $mform->addHelpButton('th_strings', 'table_th_strings', 'apply');
        $mform->setDefault('th_strings', '');
        $mform->setType('th_strings', PARAM_RAW);

        $mform->addElement('selectyesno', 'disp_iname', get_string('table_disp_iname', 'apply'), 'wrap="virtual" cols="0"');
        $mform->addHelpButton('disp_iname', 'table_disp_iname', 'apply');
        $mform->setDefault('disp_iname', 0);
        $mform->setType('disp_iname', PARAM_INT);

        parent::definition();
        $this->set_data($item);
    }


    public function get_data()
    {
        if (!$item = parent::get_data()) {
            return false;
        }

        // その他の値を格納する変数
        $item->presentation = $item->columns.APPLY_TABLESTART_SEP.$item->border.APPLY_TABLESTART_SEP.$item->border_style.APPLY_TABLESTART_SEP.
                              $item->th_sizes.APPLY_TABLESTART_SEP.$item->th_strings.APPLY_TABLESTART_SEP.$item->disp_iname;
        return $item;
    }
}

