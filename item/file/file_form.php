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
global $CFG;
require_once($CFG->dirroot . '/mod/apply/item/apply_item_form_class.php');

class apply_file_form extends apply_item_form {
    protected $type = "label";

    public function definition() {
        global $OUTPUT;

        $item = $this->_customdata['item'];

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string($this->type, 'apply'));
        $mform->addElement('advcheckbox', 'required', get_string('required', 'apply'), '', null, array(0, 1));
        $mform->addElement('text', 'name', get_string('item_name', 'apply'), array('size'=>APPLY_ITEM_NAME_TEXTBOX_SIZE, 'maxlength'=>255));
        $label_help = ' '.$OUTPUT->help_icon('item_label','apply');
        $mform->addElement('text', 'label', get_string('item_label', 'apply').$label_help, array('size'=>APPLY_ITEM_LABEL_TEXTBOX_SIZE,'maxlength'=>255));

        parent::definition();
        $this->set_data($item);
    }
}

