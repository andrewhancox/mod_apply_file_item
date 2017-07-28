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
 * @package    mod
 * @subpackage apply
 * @copyright  2016 Fumi.Iseki
 */

/**
 * Define all the backup steps that will be used by the backup_apply_activity_task
 */

/**
 * Define the complete apply structure for backup, with file and id annotations
 */
class backup_apply_activity_structure_step extends backup_activity_structure_step
{
    protected function define_structure()
	{
        global $DB;

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        //
        // Define each element separated
        $apply = new backup_nested_element('apply', array('id'), array(		// no course
            'name', 'intro', 'introformat', 'email_notification', 'email_notification_user', 'multiple_submit', 'use_calendar', 
			'name_pattern', 'enable_deletemode', 'time_open', 'time_close', 'time_modified'));

        //$template = new backup_nested_element('template', array('id'), array('name', 'ispublic')); // no course

        $submit = new backup_nested_element('submit', array('id'), array(	// no apply_id
			'user_id', 'version', 'title',  'class',  'acked',  'acked_user',  'acked_time',  'execd',  'execd_user',  'execd_time', 
			'time_modified',     'otitle', 'oclass', 'oacked', 'oacked_user', 'oacked_time', 'oexecd', 'oexecd_user', 'oexecd_time'));

        $item = new backup_nested_element('item', array('id'), array(		// no apply_id
			'template', 'name', 'label', 'presentation', 'typ', 'hasvalue', 'position', 'required', 'dependitem', 'dependvalue', 'options'));

        $value = new backup_nested_element('value', array('id'), array('item_id', 'version', 'value', 'time_modified')); // no submit_id

		//
        $items   = new backup_nested_element('items');
        $submits = new backup_nested_element('submits');
        $values  = new backup_nested_element('values');

        //
        // Build the tree
		$apply->add_child($items);
		$apply->add_child($submits);

		$items->add_child($item);

		$submits->add_child($submit);
		$submit->add_child($values);
		$values->add_child($value);

        //
        // Define sources
        $apply->set_source_table('apply', array('id' => backup::VAR_ACTIVITYID));
        $item->set_source_table('apply_item', array('apply_id' => backup::VAR_PARENTID));

		if ($userinfo) {
        	$submit->set_source_table('apply_submit', array('apply_id' => backup::VAR_PARENTID));
        	$value->set_source_table('apply_value', array('submit_id' => backup::VAR_PARENTID));
		}

        //
        // Define id annotations
        // (none)

        //
        // Define file annotations
        $apply->annotate_files('apply', 'intro', null); // This file area hasn't itemid

        //
        // Return the root element (apply) wrapped into standard activity structure
        return $this->prepare_activity_structure($apply);
    }
}
