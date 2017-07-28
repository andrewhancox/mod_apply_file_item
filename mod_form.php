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

/**
 * print the form to add or edit a apply-instance
 *
 * @author  Fumi Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_apply that by Andreas Grabs)
 */

if (!defined('MOODLE_INTERNAL')) {
	die('Direct access to this script is forbidden.');
}


require_once($CFG->dirroot.'/course/moodleform_mod.php');


class mod_apply_mod_form extends moodleform_mod
{
	// 設定の編集画面
	public function definition()
	{
		global $CFG, $DB;

		$mform =& $this->_form;

		//-------------------------------------------------------------------------------
		$mform->addElement('header', 'general', get_string('general', 'form'));
		//
		$mform->addElement('text', 'name', get_string('name', 'apply'), array('size'=>'64'));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', null, 'required', null, 'client');
        if (method_exists($this, 'standard_intro_elements')) {
		    $this->standard_intro_elements(get_string('description', 'apply'));
        }
        else {
            $this->add_intro_editor(true, get_string('description', 'apply'));
        }

		//-------------------------------------------------------------------------------
		$mform->addElement('header', 'timinghdr', get_string('timing', 'form'));
		//
		$enable_open_group 	  = array();
		$enable_open_group[]  =& $mform->createElement('checkbox', 'open_enable', get_string('time_open', 'apply'));
		$enable_open_group[]  =& $mform->createElement('date_time_selector', 'time_open', '');
		$mform->addGroup($enable_open_group, 'enable_open_group', get_string('time_open', 'apply'), ' ', false);
		$mform->addHelpButton('enable_open_group', 'time_open', 'apply');
		$mform->disabledIf('enable_open_group', 'open_enable', 'notchecked');

		$enable_close_group   = array();
		$enable_close_group[] =& $mform->createElement('checkbox', 'close_enable', get_string('time_close', 'apply')); 
		$enable_close_group[] =& $mform->createElement('date_time_selector', 'time_close', '');
		$mform->addGroup($enable_close_group, 'enable_close_group', get_string('time_close', 'apply'), ' ', false);

		$mform->addHelpButton('enable_close_group', 'time_close', 'apply');
		$mform->disabledIf('enable_close_group', 'close_enable', 'notchecked');

		//-------------------------------------------------------------------------------
		$mform->addElement('header', 'applyhdr', get_string('apply_options', 'apply'));
		//
		$mform->addElement('selectyesno', 'email_notification', get_string('email_notification', 'apply'));
		$mform->addHelpButton('email_notification', 'email_notification', 'apply');
		$mform->setDefault('email_notification', 0);
		//
		$mform->addElement('selectyesno', 'email_notification_user', get_string('email_notification_user', 'apply'));
		$mform->addHelpButton('email_notification_user', 'email_notification_user', 'apply');
		$mform->setDefault('email_notification_user', 0);
		//
		$mform->addElement('selectyesno', 'multiple_submit', get_string('multiple_submit', 'apply')); 
		$mform->addHelpButton('multiple_submit', 'multiple_submit', 'apply');
		$mform->setDefault('multiple_submit', 1);
		//
		$mform->addElement('selectyesno', 'use_calendar', get_string('use_calendar', 'apply')); 
		$mform->addHelpButton('use_calendar', 'use_calendar', 'apply');
		$mform->setDefault('use_calendar', 0);

		$choices['fullname']  = get_string('use_item', 'apply', get_string('fullnameuser'));
		$choices['firstname'] = get_string('use_item', 'apply', get_string('firstname'));
		$choices['lastname']  = get_string('use_item', 'apply', get_string('lastname'));
		$mform->addElement('select', 'name_pattern', get_string('username_manage', 'apply'), $choices); 
		$mform->addHelpButton('name_pattern', 'username_manage', 'apply');
		$mform->setDefault('name_pattern', 'fullname');

		//
		$mform->addElement('selectyesno', 'enable_deletemode', get_string('enable_deletemode', 'apply')); 
		$mform->addHelpButton('enable_deletemode', 'enable_deletemode', 'apply');
		$mform->setDefault('enable_deletemode', 0);

		//
		//-------------------------------------------------------------------------------
		// for Group
		$this->standard_coursemodule_elements();

		// buttons
		$this->add_action_buttons();
	}



	public function data_preprocessing(&$default_values)
	{
		if (empty($default_values['time_open'])) {
			$default_values['open_enable'] = 0;
		}
		else {
			$default_values['open_enable'] = 1;
		}
		//
		if (empty($default_values['time_close'])) {
			$default_values['close_enable'] = 0;
		}
		else {
			$default_values['close_enable'] = 1;
		}
	}



	public function get_data()
	{
		$data = parent::get_data();
		return $data;
	}



	public function validation($data, $files)
	{
		$errors = parent::validation($data, $files);
		return $errors;
	}
}
