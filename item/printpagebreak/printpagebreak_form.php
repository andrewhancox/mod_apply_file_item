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

class apply_printpagebreak_form extends apply_item_form
{
    protected $type = "printpagebreak";
    private $area;

    public function definition()
    {
        global $CFG, $OUTPUT;

        $item = $this->_customdata['item'];
        $common = $this->_customdata['common'];
        $positionlist = $this->_customdata['positionlist'];
        $position = $this->_customdata['position'];

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string($this->type, 'apply'));
        $mform->addElement('text', 'name',  get_string('item_name',  'apply'), array('size'=>APPLY_ITEM_NAME_TEXTBOX_SIZE, 'maxlength'=>255));
        $mform->addElement('text', 'label', get_string('item_label', 'apply'), array('size'=>APPLY_ITEM_LABEL_TEXTBOX_SIZE,'maxlength'=>255));
        $mform->addHelpButton('label', 'item_label', 'apply');
        $mform->setType('label', PARAM_TEXT);

        $mform->addElement('text', 'pagebreak_style',  get_string('pagebreak_style', 'apply'), array('size'=>APPLY_ITEM_STYLE_TEXTBOX_SIZE, 'maxlength'=>255));
        $mform->addHelpButton('pagebreak_style', 'pagebreak_style', 'apply');
        $mform->setDefault('pagebreak_style', get_string('pagebreak_style_default', 'apply'));
        $mform->setType('pagebreak_style', PARAM_TEXT);

        parent::definition();
        $this->set_data($item);
    }


    public function get_data()
    {
        if (!$item = parent::get_data()) {
            return false;
        }

        $item->presentation = $item->pagebreak_style;
        return $item;
    }
}

