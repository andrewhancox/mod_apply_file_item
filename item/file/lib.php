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
require_once($CFG->dirroot . '/mod/apply/item/apply_item_class.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/form/filepicker.php');

class apply_item_file extends apply_item_base {
    protected $type = "file";
    private $presentationoptions = null;
    private $item_form;
    private $context;
    private $item;

    public function init() {

    }

    public function build_editform($item, $apply, $cm) {
        global $DB;
        require_once('file_form.php');

        //get the lastposition number of the apply_items
        $position     = $item->position;
        $lastposition = $DB->count_records('apply_item', array('apply_id' => $apply->id));
        if ($position == -1) {
            $i_formselect_last  = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position     = $lastposition + 1;
        } else {
            $i_formselect_last  = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);

        //all items for dependitem
        $applyitems   = apply_get_depend_candidates_for_item($apply, $item);
        $commonparams = array('cmid'     => $cm->id,
                              'id'       => isset($item->id) ? $item->id : null,
                              'typ'      => $item->typ,
                              'items'    => $applyitems,
                              'apply_id' => $apply->id);

        $this->context = context_module::instance($cm->id);

        //build the form
        $customdata = array('item'         => $item,
                            'common'       => $commonparams,
                            'positionlist' => $positionlist,
                            'position'     => $position);

        $this->item_form = new apply_file_form('edit_item.php', $customdata);
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

        $item->presentation = '';

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->id = $DB->insert_record('apply_item', $item);
        } else {
            $DB->update_record('apply_item', $item);
        }

        $DB->update_record('apply_item', $item);

        return $DB->get_record('apply_item', array('id' => $item->id));
    }

    public function print_item($item, $value) {
        global $OUTPUT, $PAGE, $CFG;

        if (!isset($this->currentitem)) {
            throw new coding_exception('setcurrentitem must be called before print_item');
        }

        if (!isset($this->apply_value_id)) {
            throw new coding_exception('set_apply_value_id must be called before print_item');
        }

        list($context, $filearea) = $this->get_file_area_params($item);

        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        $elname = 'file_' . $item->id;

        $draftitemid = file_get_submitted_draft_itemid($elname);
        if ($this->apply_value_id === false) {
            $valueid = null;
        } else {
            $valueid = $this->apply_value_id;
        }

        file_prepare_draft_area($draftitemid, $context->id, 'mod_apply', $filearea, $valueid);

        $options = array(
                'maxbytes'      => -1,
                'maxfiles'      => -1,
                'itemid'        => $draftitemid,
                'subdirs'       => false,
                'client_id'     => uniqid(),
                'acepted_types' => '*',
                'return_types'  => FILE_INTERNAL,
                'context'       => $PAGE->context
        );

        $str_required_mark = '<span class="apply_required_mark">*</span>';
        $requiredmark =  ($item->required == 1) ? $str_required_mark : '';
        $align = right_to_left() ? 'right' : 'left';

        //print the question and label
        echo '<div class="apply_item_label_'.$align.'">';
        //    echo '('.$item->label.') ';
        echo format_text($item->name . $requiredmark, true, false, false);
        echo '</div>';

        $fm = new form_filemanager((object) $options);
        $filesrenderer = $PAGE->get_renderer('core', 'files');
        echo $filesrenderer->render($fm);

        echo '<input value="' . $draftitemid . '" name="' . $elname . '" type="hidden" />';
        echo '<input value="" id="id_' . $elname . '" type="hidden" />';
    }

    /**
     * print the item at the edit-page of apply
     *
     * @global object
     * @param object $item
     * @return void
     */
    public function print_item_preview($item) {
        global $DB;

        $align = right_to_left() ? 'right' : 'left';
        echo '<div class="apply_item_label_' . $align . '">';
        echo '(' . $item->label . ') ';

        if ($item->dependitem) {
            if ($dependitem = $DB->get_record('apply_item', array('id' => $item->dependitem))) {
                echo ' <span class="apply_depend">';
                echo '(' . $dependitem->label . '-&gt;' . $item->dependvalue . ')';
                echo '</span>';
            }
        }
        echo '</div>';

        $this->apply_value_id = false;
        $this->print_item($item, false);
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
    public function print_item_submit($item, $value = '', $highlightrequire = false) {
        $this->print_item($item, $value);
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
        global $CFG, $DB;

        if (!isset($this->apply_value_id)) {
            throw new coding_exception('set_apply_value_id must be called before print_item');
        }

        if (!$item->apply_id AND $item->template) {
            $template = $DB->get_record('apply_template', array('id' => $item->template));
            if ($template->ispublic) {
                $context = context_system::instance();
            } else {
                $context = context_course::instance($template->course);
            }
            $filearea = "template_fileuploads";
        } else {
            $cm       = get_coursemodule_from_instance('apply', $item->apply_id);
            $context = context_module::instance($cm->id);
            $filearea = "fileuploads";
        }

        $contextid = $context->id;
        $fs = get_file_storage();

        $result = '<ul>';
        if (!empty($this->apply_value_id)) {
            $dir = $fs->get_area_tree($contextid, 'mod_apply', $filearea, $this->apply_value_id);

            foreach ($dir['subdirs'] as $subdir) {
                $result .= '<li>' . s($subdir['dirname']) . ' ' . $this->htmllize_tree($contextid, $filearea, $subdir) . '</li>';
            }
            foreach ($dir['files'] as $file) {
                $url      = file_encode_url("$CFG->wwwroot/pluginfile.php",
                        '/' . $contextid . '/mod_apply/' . $filearea . '/' . $this->apply_value_id . '/' . $file->get_filepath() .
                        $file->get_filename(), true);
                $filename = $file->get_filename();
                $result   .= '<li><span>' . html_writer::link($url, $filename) . '</span></li>';
            }
        }
        $result .= '</ul>';

        $align = right_to_left() ? 'right' : 'left';
        echo '<div class="apply_item_label_'.$align.'">';
        //    echo '('.$item->label.') ';
        echo format_text($item->name, true, false, false);
        echo '</div>';
        echo $result;
    }


    protected function render_tree($contextid, $filearea, $dir) {
        global $CFG;

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $result .= '<li>'.s($subdir['dirname']).' '.$this->htmllize_tree($contextid, $subdir).'</li>';
        }
        foreach ($dir['files'] as $file) {
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$contextid.'/mod_apply/'. $filearea . '/' . $this->apply_value_id . '/' .$file->get_filepath().$file->get_filename(), true);
            $filename = $file->get_filename();
            $result .= '<li><span>'.html_writer::link($url, $filename).'</span></li>';
        }
        $result .= '</ul>';

        return $result;
    }

    private $currentitem;

    public function setcurrentitem($currentitem) {
        $this->currentitem = $currentitem;
    }

    private $apply_value_id;

    public function set_apply_value_id($apply_value_id) {
        $this->apply_value_id = $apply_value_id;
    }

    public function create_value($data) {
        return true;
    }

    public function postprocessrecord($applyvalueid) {
        if (!isset($this->currentitem)) {
            throw new coding_exception('item not set, please call setcurrentitem before create_value');
        }
        list($context, $filearea) = $this->get_file_area_params($this->currentitem);
        $elname      = 'file_' . $this->currentitem->id;
        $draftitemid = file_get_submitted_draft_itemid($elname);

        file_save_draft_area_files($draftitemid, $context->id, 'mod_apply', $filearea, $applyvalueid);
    }

    public function compare_value($item, $dbvalue, $dependvalue) {
        return $this->check_value(null, $item);
    }

    //used by create_item and update_item functions,
    //when provided $data submitted from apply_show_edit
    public function get_presentation($data) {
    }

    public function postupdate($item) {
        global $DB;

        $context = context_module::instance($item->cmid);

        $item = file_postupdate_standard_editor($item,
                'presentation',
                $this->presentationoptions,
                $context,
                'mod_apply',
                'item',
                $item->id);

        $DB->update_record('apply_item', $item);
        return $item->id;
    }

    public function get_hasvalue() {
        return true;
    }

    public function can_switch_require() {
        return false;
    }

    public function check_value($value, $item) {
        list($context, $filearea) = $this->get_file_area_params($item);
        $elname      = 'file_' . $item->id;
        $draftitemid = file_get_submitted_draft_itemid($elname);

        if (empty($draftitemid)) {
            if (!isset($this->apply_value_id)) {
                throw new coding_exception('set_apply_value_id must be called before check_value');
            }
            $fs         = get_file_storage();
            $draftfiles = $fs->get_area_files($context->id, 'mod_apply', $filearea, $this->apply_value_id);
            return count($draftfiles) > 1;
        } else {
            $info = file_get_draft_area_info($draftitemid);
            if ($info['filecount'] == 0 && $item->required == 1) {
                return false;
            } else {
                return true;
            }
        }
    }

    public function excelprint_item(&$worksheet,
            $row_offset,
            $xls_formats,
            $item,
            $groupid,
            $courseid = false) {
    }

    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false) {
    }

    public function get_printval($item, $value) {
    }

    public function get_analysed($item, $groupid = false, $courseid = false) {
    }

    public function value_type() {
        return PARAM_BOOL;
    }

    public function clean_input_value($value) {
        return '';
    }

    /**
     * @param $item
     */
    public function get_file_area_params($item) {
        global $DB;

        if (!$item->apply_id AND $item->template) {
            $template = $DB->get_record('apply_template', array('id' => $item->template));
            if ($template->ispublic) {
                $context = get_system_context();
            } else {
                $context = context_course::instance($template->course);
            }
            $filearea = "template_fileuploads";
        } else {
            $cm       = get_coursemodule_from_instance('apply', $item->apply_id);
            $context = context_module::instance($cm->id);
            $filearea = "fileuploads";
        }

        return array($context, $filearea);
    }
}
