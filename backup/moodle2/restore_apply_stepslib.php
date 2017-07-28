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
 * Define all the restore steps that will be used by the restore_url_activity_task
 */

/**
 * Structure step to restore one apply activity
 */
class restore_apply_activity_structure_step extends restore_activity_structure_step
{
    protected function define_structure()
	{
        $paths = array();
		$userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('apply', '/activity/apply');
        $paths[] = new restore_path_element('apply_item', '/activity/apply/items/item');

		if ($userinfo) {
        	$paths[] = new restore_path_element('apply_submit', '/activity/apply/submits/submit');
        	$paths[] = new restore_path_element('apply_value',  '/activity/apply/submits/submit/values/value');
		}

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    //
    protected function process_apply($data)
	{
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

		$data->time_open  	 = $this->apply_date_offset($data->time_open);
		$data->time_close 	 = $this->apply_date_offset($data->time_close);
		$data->time_modified = $this->apply_date_offset($data->time_modified);

        $newitemid = $DB->insert_record('apply', $data);
        $this->apply_activity_instance($newitemid);
    }

	//
    protected function process_apply_item($data)
	{
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->apply_id = $this->get_new_parentid('apply');

        $data->dependitem = $this->get_mappingid('apply_item', $data->dependitem);

        $newitemid = $DB->insert_record('apply_item', $data);
        $this->set_mapping('apply_item', $oldid, $newitemid); 
    }

	//
    protected function process_apply_submit($data) 
	{
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->apply_id = $this->get_new_parentid('apply');

        $user_id 	 = $this->get_mappingid('user', $data->user_id);
        $acked_user  = $this->get_mappingid('user', $data->acked_user);
        $execd_user  = $this->get_mappingid('user', $data->execd_user);
        $oacked_user = $this->get_mappingid('user', $data->oacked_user);
        $oexecd_user = $this->get_mappingid('user', $data->oexecd_user);

		if ($user_id    !=0) $data->user_id     = $user_id;		// ==0 is means that the user is not member of course
		if ($acked_user !=0) $data->acked_user  = $acked_user;
        if ($execd_user !=0) $data->execd_user  = $execd_user;
        if ($oacked_user!=0) $data->oacked_user = $oacked_user;
        if ($oexecd_user!=0) $data->oexecd_user = $oexecd_user;

		$data->acked_time  	 = $this->apply_date_offset($data->acked_time);
		$data->execd_time  	 = $this->apply_date_offset($data->execd_time);
		$data->oacked_time 	 = $this->apply_date_offset($data->oacked_time);
		$data->oexecd_time	 = $this->apply_date_offset($data->oexecd_time);
		$data->time_modified = $this->apply_date_offset($data->time_modified);

        $newitemid = $DB->insert_record('apply_submit', $data);
        $this->set_mapping('apply_submit', $oldid, $newitemid);
    }

	//
    protected function process_apply_value($data)
	{
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->submit_id = $this->get_new_parentid('apply_submit');
        $data->item_id   = $this->get_mappingid('apply_item', $data->item_id);
		$data->time_modified = $this->apply_date_offset($data->time_modified);

        $newitemid = $DB->insert_record('apply_value', $data);
        $this->set_mapping('apply_value', $oldid, $newitemid);
    }

    //
    protected function after_execute()
	{
        // Add apply related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_apply', 'intro', null);
    }
}
