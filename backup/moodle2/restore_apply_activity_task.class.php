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
 * Defines restore_apply_activity_task class
 *
 * @package     mod_apply
 * @category    backup
 * @copyright   2016 Fumi.Iseki
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/apply/backup/moodle2/restore_apply_stepslib.php'); // Because it exists (must)

/**
 * apply restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_apply_activity_task extends restore_activity_task
{
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings()
    {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps()
    {
        // apply only has one structure step
        $this->add_step(new restore_apply_activity_structure_step('apply_structure', 'apply.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents()
    {
        $contents = array();

        $contents[] = new restore_decode_content('apply', array('intro'), 'apply');
        $contents[] = new restore_decode_content('apply_item', array('presentation'), 'apply_item');
        $contents[] = new restore_decode_content('apply_value', array('value'), 'apply_value');
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules()
    {
        return array();
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * apply logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules()
    {
        $rules = array();

        $rules[] = new restore_log_rule('apply', 'view',   'view.php?id={course_module}', '{apply}');
        $rules[] = new restore_log_rule('apply', 'submit', 'submit.php?id={course_module}', '{apply}');
        $rules[] = new restore_log_rule('apply', 'delete', 'delete_submit.php?id={course_module}', '{apply}');
        $rules[] = new restore_log_rule('apply', 'edit',   'edit.php?id={course_module}', '{apply}');
        $rules[] = new restore_log_rule('apply', 'view_entries', 'view_entries.php?id={course_module}&user_id={user}', '{apply}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course()
    {
        $rules = array();

        $rules[] = new restore_log_rule('apply', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
