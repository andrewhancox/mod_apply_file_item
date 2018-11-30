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
 * diplay info header
 *
 * @author  Fumi Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

defined('MOODLE_INTERNAL') OR die('not allowed');


//echo $OUTPUT->box_start('boxaligncenter boxwidthwide');
//{
    if ($apply->time_open) {
        echo $OUTPUT->box_start('apply_info');
        echo '<span class="apply_info">';
        echo get_string('time_open', 'apply').': ';
        echo '</span>';
        echo '<span class="apply_info_value">';
        echo userdate($apply->time_open);
        echo '</span>';
        echo $OUTPUT->box_end();
    }
    if ($apply->time_close) {
        echo $OUTPUT->box_start('apply_info');
        echo '<span class="apply_info">';
        echo get_string('time_close', 'apply').': ';
        echo '</span>';
        echo '<span class="apply_info_value">';
        echo userdate($apply->time_close);
        echo '</span>';
        echo $OUTPUT->box_end();
    }
//}
//echo $OUTPUT->box_end();
