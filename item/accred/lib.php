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

class apply_item_accred extends apply_item_base {
    protected $type = "accred";
    private $commonparams;
    private $item_form;
    private $item;

    public function init() {

    }

    public function build_editform($item, $apply, $cm) {
        global $DB, $CFG;
        require_once('accred_form.php');

        //get the lastposition number of the apply_items
        $position = $item->position;
        $lastposition = $DB->count_records('apply_item', array('apply_id'=>$apply->id));
        if ($position == -1) {
            $i_formselect_last = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position = $lastposition + 1;
        } else {
            $i_formselect_last = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);

        $item->presentation = empty($item->presentation) ? 1 : $item->presentation;
        $item->required = 0;

        //all items for dependitem
        $applyitems = apply_get_depend_candidates_for_item($apply, $item);
        $commonparams = array('cmid'=>$cm->id,
                              'id'=>isset($item->id) ? $item->id : null,
                              'typ'=>$item->typ,
                              'items'=>$applyitems,
                              'apply_id'=>$apply->id);

        //build the form
        $this->item_form = new apply_accred_form('edit_item.php',
                                                   array('item'=>$item,
                                                   'common'=>$commonparams,
                                                   'positionlist'=>$positionlist,
                                                   'position' => $position));
    }

    //this function only can used after the call of build_editform()
    public function show_editform() {
        $this->item_form->display();
    }

    public function is_cancelled() {
        return $this->item_form->is_cancelled();
    }

    public function get_data() {
        if ($this->item = $this->item_form->get_data()) {
            return true;
        }
        return false;
    }

    public function save_item() {
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
        } else {
            $DB->update_record('apply_item', $item);
        }

        return $DB->get_record('apply_item', array('id'=>$item->id));
    }

    //liefert eine Struktur ->name, ->data = array(mit Antworten)
    //LUIS translation: provides a Structure ->name, ->data = array(with Answers)
    public function get_analysed($item, $groupid = false, $courseid = false) {
        $presentation = $item->presentation;
        $analysed_val = new stdClass();
        $analysed_val->data = null;
        $analysed_val->name = $item->name;
        $values = apply_get_group_values($item, $groupid, $courseid);
        if ($values) {
            $data = array();
            foreach ($values as $value) {
                $datavalue = new stdClass();

                switch($presentation) {
                    case 1:
                        $datavalue->value = $value->value;
                        $datavalue->show = $datavalue->value;
                        break;
                    case 2:
                        $datavalue->value = $value->value;
                        $datavalue->show = $datavalue->value;
                        break;
                    case 3:
                        $datavalue->value = $value->value;
                        $datavalue->show = $datavalue->value;
                        break;
                }

                $data[] = $datavalue;
            }
            $analysed_val->data = $data;
        }
        return $analysed_val;
    }

    public function get_printval($item, $value) {
        if (!isset($value->value)) {
            return '';
        }
        return userdate($value->value);
    }

    //LUIS: Is this used anywhere???
    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);
        $data = $analysed_item->data;
        if (is_array($data)) {
            echo '<tr><th colspan="2" align="left">';
            echo $itemnr.'&nbsp;('.$item->label.') '.$item->name;
            echo '</th></tr>';
            $sizeofdata = count($data);
            for ($i = 0; $i < $sizeofdata; $i++) {
                echo '<tr><td colspan="2" valign="top" align="left">-&nbsp;&nbsp;';
                echo str_replace("\n", '<br />', $data[$i]->show);
                echo '</td></tr>';
            }
        }
    }

    //LUIS: Is this used anywhere???
    public function excelprint_item(&$worksheet, $row_offset, $xls_formats, $item, $groupid, $courseid = false) {
        $analysed_item = $this->get_analysed($item, $groupid, $courseid);

        $worksheet->write_string($row_offset, 0, $item->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $item->name, $xls_formats->head2);
        $data = $analysed_item->data;
        if (is_array($data)) {
            $worksheet->write_string($row_offset, 2, $data[0]->show, $xls_formats->value_bold);
            $row_offset++;
            $sizeofdata = count($data);
            for ($i = 1; $i < $sizeofdata; $i++) {
                $worksheet->write_string($row_offset, 2, $data[$i]->show, $xls_formats->default);
                $row_offset++;
            }
        }
        $row_offset++;
        return $row_offset;
    }

    /**
     * print the item at the edit-page of apply
     *
     * @global object
     * @param object $item
     * @return void
     */
    public function print_item_preview($item) {
        global $USER, $DB, $OUTPUT;

        $align = right_to_left() ? 'right' : 'left';
        $presentation = $item->presentation;
        $requiredmark = ($item->required == 1) ? '<span class="apply_required_mark">*</span>':'';

        if ($item->apply_id) {
            $courseid = $DB->get_field('apply', 'course', array('id'=>$item->apply_id));
        } else { // the item must be a template item
            $cmid = required_param('id', PARAM_INT);
            $courseid = $DB->get_field('course_modules', 'course', array('id'=>$cmid));
        }
        if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
            print_error('error');
        }
        if ($course->id !== SITEID) {
            $coursecategory = $DB->get_record('course_categories', array('id'=>$course->category));
        } else {
            $coursecategory = false;
        }

        switch($presentation) {
            case 1:
                $itemvalue = "This section will display the advisors' accreditation exam results.<br />This is dependant on the advisors' SRS PartyID being captured on the advisors' Moodle profile.";
                $itemshowvalue = $itemvalue;
                break;
            case 2:
                $coursecontext = context_course::instance($course->id);
                $itemvalue = format_string($course->shortname,
                                           true,
                                           array('context' => $coursecontext));

                $itemshowvalue = $itemvalue;
                break;
            case 3:
                if ($coursecategory) {
                    $category_context = context_coursecat::instance($coursecategory->id);
                    $itemvalue = format_string($coursecategory->name,
                                               true,
                                               array('context' => $category_context));

                    $itemshowvalue = $itemvalue;
                } else {
                    $itemvalue = '';
                    $itemshowvalue = '';
                }
                break;
        }

        //print the question and label
        echo '<div class="apply_item_label_'.$align.'">';
        echo '('.$item->label.') ';
        echo format_text($item->name.$requiredmark, true, false, false);
        if ($item->dependitem) {
            if ($dependitem = $DB->get_record('apply_item', array('id'=>$item->dependitem))) {
                echo ' <span class="apply_depend">';
                echo '('.$dependitem->label.'-&gt;'.$item->dependvalue.')';
                echo '</span>';
            }
        }
        echo '</div>';
        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.'">';
        echo '<input type="hidden" name="'.$item->typ.'_'.$item->id.'" value="'.$itemvalue.'" />';
        echo '<span class="apply_item_accred">'.$itemshowvalue.'</span>';
        echo '</div>';
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
    // LUIS:
    // The $item->presentation values can be "1", "2" or "3" (i.e. strings 1, 2 or 3).
    // Each of those values corresponds to this output on the form:
    // 1: "Friday, 29 April 2016, 5:04 PM" i.e. the CURRENT DATE/TIME
    // 2: "crs_spvn" i.e. the course shortname
    // 3: "Supervision" i.e. the Course Category (I think)
    // But, why only 3 ??? And HOW is it determined? What does 1, 2 and 3 represent?
    public function print_item_submit($item, $value = '', $highlightrequire = false) {
        global $USER, $DB, $OUTPUT;
        $align = right_to_left() ? 'right' : 'left';

        $presentation = $item->presentation;
        if ($highlightrequire AND $item->required AND strval($value) == '') {
            $highlight = ' missingrequire';
        } else {
            $highlight = '';
        }
        $requiredmark =  ($item->required == 1) ? '<span class="apply_required_mark">*</span>':'';

        $apply = $DB->get_record('apply', array('id'=>$item->apply_id));

        if ($courseid = optional_param('courseid', 0, PARAM_INT)) {
            $course = $DB->get_record('course', array('id'=>$courseid));
        } else {
            $course = $DB->get_record('course', array('id'=>$apply->course));
        }

        if ($course->id !== SITEID) {
            $coursecategory = $DB->get_record('course_categories', array('id'=>$course->category));
        } else {
            $coursecategory = false;
        }

        //LUIS: This switch(presentation) is evaluated three times in this code: lines 122 (get_analysed), 215 (print_item_preview) and 317 (print_item_submit)!!!
        switch($presentation) {
            case 1:
                //LUIS:
                //$itemvalue = time();
                $itemvalue = "";
                //$itemshowvalue = userdate($itemvalue);
                $itemshowvalue = $itemvalue;
                break;
            case 2:
                $coursecontext = context_course::instance($course->id);
                $itemvalue = format_string($course->shortname,
                                           true,
                                           array('context' => $coursecontext));

                $itemshowvalue = $itemvalue;
                break;
            case 3:
                if ($coursecategory) {
                    $category_context = context_coursecat::instance($coursecategory->id);
                    $itemvalue = format_string($coursecategory->name,
                                               true,
                                               array('context' => $category_context));

                    $itemshowvalue = $itemvalue;
                } else {
                    $itemvalue = '';
                    $itemshowvalue = '';
                }
                break;
        }

        //print the presentation
        echo '<div class="apply_item_presentation_'.$align.'">';
        echo '<input type="hidden" name="'.$item->typ.'_'.$item->id.'" value="'.$itemvalue.'" />';

        $accred = $DB->get_records_sql("SELECT
                                            gi.idnumber AS examcode,
                                            gi.itemname AS examname,
                                            gg.timemodified AS examdate,
                                            ROUND(gg.finalgrade, 0) AS pointsobtained,
                                            ROUND(gg.finalgrade / gi.grademax * 100, 0) AS finalgradepercent,
                                            (CASE
                                                WHEN ROUND(gg.finalgrade,0) >= gi.gradepass THEN 'Passed'
                                                ELSE 'Failed'
                                            END) AS passorfail
                                        FROM {grade_grades} gg
                                        INNER JOIN {grade_items} gi ON gg.itemid = gi.id
                                        INNER JOIN {course} c ON gi.courseid = c.id
                                        INNER JOIN {course_categories} cc ON c.category = cc.id
                                        WHERE (gi.itemname IS NOT NULL)
                                        AND (gi.itemtype = 'mod' OR gi.itemtype = 'manual')
                                        AND (gi.itemmodule = 'quiz' OR gi.itemmodule IS NULL)
                                        AND (gg.timemodified IS NOT NULL)
                                        AND (gg.finalgrade IS NOT NULL)
                                        AND (gg.userid = $USER->id )
                                        ORDER BY gi.itemname");

        $table = new html_table();
        $table->attributes = array('align'=>'.$align');

        //LUIS TODO: Convert these headings to language strings
        $table->head = array("Exam Code", "Exam Name", "Date", "Grade", "Result");

        if (isset($accred) && count($accred) > 0) {
            foreach ($accred as $q) {
                if ($q->examdate > 0) {
                    $timemodified = date('Y-m-d',$q->examdate);
                } else {
                    $timemodified = '';
                }
                
                @$row = new html_table_row(array($q->examcode,$q->examname,$timemodified,round($q->finalgradepercent,0),$q->passorfail));
                $table->data[] = $row;
            }
        } else {
            echo $OUTPUT->notification(get_string('accred_usernotfound', 'apply'));
            $table->data[] = array(get_string('accred_tablenorows', 'apply'));
        }

        echo html_writer::table($table);

        echo '</div>';
    }

    /**
     * print the item at the complete-page of apply
     *
     * @global object
     * @param object $item
     * @param string $value
     * @return void
     */
    public function print_item_show_value($item, $value = '') {
        global $USER, $DB, $OUTPUT;
        $align = right_to_left() ? 'right' : 'left';

        $presentation = $item->presentation;
        $requiredmark =  ($item->required == 1) ? '<span class="apply_required_mark">*</span>':'';

        if ($presentation == 1) {
            $value = "";

            echo '<div class="apply_item_presentation_'.$align.'">';

            $entryuser = $_SESSION['submituser']; // Session set on entry_view.php

            $accred = $DB->get_records_sql("SELECT
                                                gi.idnumber AS examcode,
                                                gi.itemname AS examname,
                                                gg.timemodified AS examdate,
                                                ROUND(gg.finalgrade, 0) AS pointsobtained,
                                                ROUND(gg.finalgrade / gi.grademax * 100, 0) AS finalgradepercent,
                                                (CASE
                                                    WHEN ROUND(gg.finalgrade,0) >= gi.gradepass THEN 'Passed'
                                                    ELSE 'Failed'
                                                END) AS passorfail
                                            FROM {grade_grades} gg
                                            INNER JOIN {grade_items} gi ON gg.itemid = gi.id
                                            INNER JOIN {course} c ON gi.courseid = c.id
                                            INNER JOIN {course_categories} cc ON c.category = cc.id
                                            WHERE (gi.itemname IS NOT NULL)
                                            AND (gi.itemtype = 'mod' OR gi.itemtype = 'manual')
                                            AND (gi.itemmodule = 'quiz' OR gi.itemmodule IS NULL)
                                            AND (gg.timemodified IS NOT NULL)
                                            AND (gg.finalgrade IS NOT NULL)
                                            AND (gg.userid = $entryuser )
                                            ORDER BY gi.itemname");

            echo $OUTPUT->box_start('generalbox boxalign'.$align);

            $table = new html_table();
            $table->attributes = array('align'=>'.$align');

            // LUIS TODO: Convert these to language strings
            $table->head = array("Exam Code", "Exam Name", "Date", "Grade", "Result");
 
            if (isset($accred) && count($accred) > 0) {
                foreach ($accred as $q) {
                    if ($q->examdate > 0) {
                        $timemodified = date('Y-m-d',$q->examdate);
                    } else {
                        $timemodified = '';
                    }

                    // @$row = new html_table_row(array($q->title,$q->id_number,date('Y-m-d',$q->timecreated),$timemodified));
                    @$row = new html_table_row(array($q->examcode,$q->examname,$timemodified,round($q->finalgradepercent,0),$q->passorfail));
                    $table->data[] = $row;
                }
            } else {
                echo $OUTPUT->notification(get_string('accred_usernotfound', 'apply'));
                $table->data[] = array(get_string('accred_tablenorows', 'apply'));
            }

            echo html_writer::table($table);

            //LUIS: This was origionally below, but we want it displayed INSIDE the Accred 'box', not outside it.
            echo '<div class="apply_item_label_normal_'.$align.'">';
            //echo format_text($item->name . $requiredmark, true, false, false);
            echo format_text($requiredmark . "* These are the Liberty Accreditation Exams that have been completed online.<br />", true, false, false);
            echo '<br /></div>';

            echo $OUTPUT->box_end();
        }
    }

    public function check_value($value, $item) {
        return true;
    }

    public function create_value($data) {
        $data = clean_text($data);
        return $data;
    }

    //compares the dbvalue with the dependvalue
    //the values can be the shortname of a course or the category name
    //the date is not compareable :(.
    public function compare_value($item, $dbvalue, $dependvalue) {
        if ($dbvalue == $dependvalue) {
            return true;
        }
        return false;
    }

    public function get_presentation($data) {
        return $data->infotype;
    }

    public function get_hasvalue() {
        return 1;
    }

    public function can_switch_require() {
        return false;
    }

    public function value_type() {
        return PARAM_TEXT;
    }

    public function clean_input_value($value) {
        return clean_param($value, $this->value_type());
    }
}
