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


namespace customfield_unit;

defined('MOODLE_INTERNAL') || die;
use stdClass;
use question_bank;

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
        $cunitid = optional_param('topicsid', 0, PARAM_INT);
        if ($cunitid) {
            return $cunitid;
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
        $categoryid = optional_param('category', 0, PARAM_INT);
        $questionid = optional_param('id', 0 , PARAM_INT);
        if(!$questionid){
            $id = optional_param('qcategory', 0 , PARAM_INT);
            if(!$id){
            $id = optional_param('category', 0 , PARAM_INT);
            }
        }else{
            $questioninfo = question_bank::load_question($questionid);
           $id = $questioninfo->category;
        }
        $field = $this->get_field();
        $config = $field->get('configdata');
        $options = $field->get_options();
        $context = $this->get_field()->get_handler()->get_configuration_context();
        foreach ($options as $key => $option) {
            // Multilang formatting with filters.
            $formattedoptions[$key] = format_string($option, true, ['context' => $context]);
        }

        $elementname = $this->get_form_element_name();
        
        $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'unitlist',
            'id' => 'id_customfield_unit',
            'class' => 'unit',
            'multiple' => false,
            'placeholder' => get_string('select_topic', 'customfield_coursetopics'),
            'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.selectedconcepts();}) }) (event)",
        );

        $unitarray=array();
        $unitarray[] = get_string("select_topic",'customfield_coursetopics');

        $unitid =  $this->get_value();
       

        if($unitid != 0 && $unitid != '' && $unitid != 'data-unit'){
            $units = $DB->get_records_sql_menu("SELECT lt.id AS id, lt.name AS fullname 
                         FROM {local_topics} AS lt WHERE id = $unitid ");
            $unitarray = $units;
        }
         $data = data_submitted();
        $getunitid = $data->customfield_topics;
        if($getunitid) {
        $getunitdata = $DB->get_records_sql_menu("SELECT id as id,name as fullname FROM {local_topics} WHERE  id = '$getunitid' ");

        }
          if($cunitid) {
           $getunitdata =  $DB->get_records_sql_menu("SELECT id as id, name as fullname FROM {local_topics} WHERE id ='.$cunitid. '");       
        }
        $unitarray=!empty($getunitdata) ? $getunitdata : $unitarray ;
        $course = $mform->addElement('autocomplete', $elementname, $this->get_field()->get_formatted_name(),$unitarray, $options);
        $course->setMultiple(false);
        if (($defaultkey = array_search($config['defaultvalue'], $options)) !== false) {
            $mform->setDefault($elementname, $defaultkey);
        }
        else if($cunitid){
             $mform->setConstant($elementname, $cunitid);
        }
        if ($field->get_configdata_property('required')) {
            $mform->addRule($elementname, get_string('topicerror','local_questions'), 'required', null, 'client');
            $mform->addRule($elementname, get_string('topicerror','local_questions'), 'nonzero', null, 'client');
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
