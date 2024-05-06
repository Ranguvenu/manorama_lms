<?php
namespace tool_courses\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');

use moodleform;

class courses_import extends moodleform {
	public function definition() {
		$mform = $this->_form;

	        $filemanageroptions = array(
	                'maxbytes'=>10240,
	                'maxfiles'=>1
                );
        
		$mform->addElement('filemanager', 'userfile', get_string('file'), null, $filemanageroptions);
		$mform->addHelpButton('userfile', 'uploaddec', 'local_exams');
		$mform->addRule('userfile', null, 'required');

		$this->add_action_buttons(true, get_string('upload'));
	}
}