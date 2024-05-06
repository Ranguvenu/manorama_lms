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


namespace customfield_board;

defined('MOODLE_INTERNAL') || die;
use stdClass;

class data_controller extends \core_customfield\data_controller {

    /**
     * Return the name of the field where the information is stored
     * @return string
     */
    public function datafield() : string {
        return 'charvalue';
    }

    /**
     * Returns the default value as it would be stored in the database (not in human-readable format).
     *
     * @return mixed
     */
   public function get_default_value() {
        $cboardid = optional_param('boardid', 0, PARAM_INT);
        if ($cboardid) {
            return $cboardid;
        }
        return 0;
    }

    /**
     * Add fields for editing a textarea field.
     *
     * @param \MoodleQuickForm $mform
     */
    public function instance_form_definition(\MoodleQuickForm $mform) {
        global $DB;
        $field = $this->get_field();
        $config = $field->get('configdata');
        $formattedoptions = array();
        $formattedoptions[] = get_string("select_board",'customfield_board');
        $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'boardlist',
            'id' => 'id_customfield_board',
            'class' => 'board',
            'multiple' => false,
            'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.selectedboard();}) }) (event)",
            'placeholder' => get_string('select_board', 'customfield_board')
        );
        $context = $this->get_field()->get_handler()->get_configuration_context();         
         $boardid =     $this->get_value();
       
        if($boardid != 0 && $boardid != '' && $boardid != 'data-board'){

            $boardname = $DB->get_records_sql_menu("select lhi.id,lhi.name as fullname from {local_hierarchy} lhi where lhi.id = $boardid AND lhi.depth=2 ");
            $formattedoptions = $boardname;
        }
        $elementname = $this->get_form_element_name();
        $data = data_submitted();
        $getboardid = $data->customfield_board;
        if($getboardid) {
        $getboarddata = $DB->get_records_sql_menu("SELECT id as id,name as fullname FROM {local_hierarchy} WHERE  id = '$getboardid' ");
        }
         if($cboardid) {
           $getgoaldata =  $DB->get_records_sql_menu('SELECT id as id, name as fullname FROM {local_hierarchy} WHERE id ='.$cboardid.' AND depth=2 ');       
        }
        $formattedoptions=!empty($getboarddata) ? $getboarddata : $formattedoptions ;
        $board = $mform->addElement('autocomplete', $elementname, $this->get_field()->get_formatted_name(), $formattedoptions,$options, ['placeholder' => get_string('select_board', 'local_board')]);
        $board->setMultiple(false);
        if (($defaultkey = array_search($config['defaultvalue'], $options)) !== false) {
            $mform->setDefault($elementname, $defaultkey);
        }
         else if($cgoalid){
             $mform->setConstant($elementname, $cboardid);
        }
        if ($field->get_configdata_property('required')) {
            $mform->addRule($elementname, get_string('boarderror','local_questions'), 'required', null, 'client');
            $mform->addRule($elementname, get_string('boarderror','local_questions'), 'nonzero', null, 'client');

        }

    }
 

    /**
     * Validates data for this field.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function instance_form_validation(array $data, array $files) : array {
        $errors = parent::instance_form_validation($data, $files);
        if ($this->get_field()->get_configdata_property('required')) {
            // Standard required rule does not work on select element.
            $elementname = $this->get_form_element_name();
            if (empty($data[$elementname])) {
                $errors[$elementname] = get_string('err_required', 'form');
            }
        }
        return $errors;
    }

    /**
     * Returns value in a human-readable format
     *
     * @return mixed|null value or null if empty
     */
    public function export_value() {
        $value = $this->get_value();

        if ($this->is_empty($value)) {
            return null;
        }

        $options = $this->get_field()->get_options();
        if (array_key_exists($value, $options)) {
            return format_string($options[$value], true,
                ['context' => $this->get_field()->get_handler()->get_configuration_context()]);
        }

        return "-";
    }
}
