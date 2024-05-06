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


namespace customfield_class;

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
        $cclassid = optional_param('classid', 0, PARAM_INT);
        if ($cclassid) {
            return $cclassid;
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
          $options = array(
            'ajax' => 'local_questions/coursetopics',
            'data-type' => 'classlist',
            'id' => 'id_customfield_class',
            'class' => 'class',
            'multiple' => false,
            'onchange' => "(function(e){ require(['local_questions/coursetopics'], function(s) {s.selectedclasses();}) }) (event)",
            'placeholder' => get_string('select_class', 'customfield_class')
        );
        //$formattedoptions = array();
        $formattedoptions = (array) get_string('select_class', 'customfield_class');
        $context = $this->get_field()->get_handler()->get_configuration_context();
        $elementname = $this->get_form_element_name();
        $data = data_submitted();
        $getclassid = $data->customfield_class;
        if($getclassid) {
        $getclasssdata = $DB->get_records_sql_menu("SELECT id as id,name as fullname FROM {local_hierarchy} WHERE  id = '$getclassid' ");
        }
         if($cclassid) {
           $getclasssdata =  $DB->get_records_sql_menu('SELECT id as id, name as fullname FROM {local_hierarchy} WHERE id ='.$cclassid.' AND depth=3 ');       
        }
        $formattedoptions=!empty($getclasssdata) ? $getclasssdata : $formattedoptions ;
          $classid =     $this->get_value();
          if($classid != 0 && $classid != '' && $classid != 'data-class'){
            $classname = $DB->get_records_sql_menu("select lhi.id,lhi.name as fullname from {local_hierarchy} lhi where lhi.id = $classid AND lhi.depth=3 ");
            $formattedoptions = $classname;
        }
         $classes = $mform->addElement('autocomplete', $elementname, $this->get_field()->get_formatted_name(), $formattedoptions,$options);
        $classes->setMultiple(false);
        if (($defaultkey = array_search($config['defaultvalue'], $options)) !== false) {
            $mform->setDefault($elementname, $defaultkey);
        }
         else if($cclassid){
             $mform->setConstant($elementname, $cclassid);
        }
        if ($field->get_configdata_property('required')) {
            $mform->addRule($elementname, get_string('classerror','local_questions'), 'required', null, 'client');
            $mform->addRule($elementname, get_string('classerror','local_questions'), 'nonzero', null, 'client');
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
        $options = $this->get_field()->get_options_class();
        if (array_key_exists($value, $options)) {
            return format_string($options[$value], true,
                ['context' => $this->get_field()->get_handler()->get_configuration_context()]);
        }
        return "-";
    }
}
