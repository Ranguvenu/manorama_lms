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
 * local_masterdata
 * @package    local_masterdata
 * @copyright  Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_masterdata;
use context_system;
use context_module;
use curl;
defined('MOODLE_INTERNAL') || die;
class courselib {
    public $parent = 0;
    public $course;
    public $courseformat;
    public $latestsection;

    public $oldcourseid;

    public $totalactivities;

    public function __construct($moodlecourse,$oldcourseid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        $this->course = $moodlecourse;
        $this->oldcourseid = $oldcourseid;
        $this->totalactivities = 0;
        $this->latestsection = $DB->get_record('course_sections', ['section' => 0, 'course' => $this->course->id]);
        $this->courseformat = \core_courseformat\base::instance($moodlecourse);
    }
    public function process_mastercoursedata($child) {
        switch ($child->key_label) {
            case 'Chapter' :
            case 'Lesson' :
                $this->create_course_section($child);
            break;
            case 'Subjective Test' :
                $this->create_course_module($child, $this->course->id, $this->latestsection->section, 'assign');
            break;
            case 'Flash Card' :
                $this->create_course_module($child, $this->course->id, $this->latestsection->section, 'folder');
            break;
            case 'Topic' :
            case 'Live Class' :
            case 'Textual Contents' :
                $this->create_course_module($child, $this->course->id, $this->latestsection->section, 'page');
            break;
            case 'Chapter Test' :
            case 'Practice Bundle' :
               $this->create_course_module($child, $this->course->id, $this->latestsection->section, 'quiz');
            break;
            default :
            break;
        }
        if(!empty($child->children)) {
        	$currentparent = $this->parent;
            foreach($child->children AS $newchild){
            	$this->parent = $currentparent;
                $this->process_mastercoursedata($newchild);
            }
        }
    }
    public function create_course_section($structure) {
    	global $DB;
        if($structure->is_active) {
            $this->parent = $this->courseformat->create_new_section($this->parent);
            $section = $DB->get_record('course_sections', ['section' => $this->parent, 'course' => $this->course->id]);
            $this->latestsection = $section;
            course_update_section($section->course, $section, array('name' => $structure->name));
        }
    }

    public function create_course_module($data,$courseid,$latestsection,$module) {
    	global $DB,$OUTPUT,$CFG;
        if ($CFG->debug !== DEBUG_DEVELOPER) {
            $refetchfromservice = false;
        } else {
            $refetchfromservice = true;
        }
        $api = (new api(['debug' => false]));
        $moduledata = new \stdClass();
        $moduledata->name = $data->key_label.' : '.$data->name;
        $moduledata->modulename = $module;
        $moduledata->course =(int)$courseid;
        $moduledata->section = $latestsection;
        $moduledata->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
        $moduledata->visible = 1;
        if($module == 'quiz') {
            $moduledata->testtype = ($data->key_label == 'Chapter Test') ? 0 : 1;
            $moduledata->quizpassword = 0;
            $moduledata->preferredbehaviour = 'adaptive';
            $moduledata->questionsperpage = 1;
        } else if ($module == 'page') {
            $moduledata->pagetype = ($data->key_label == 'Live Class') ? 1 : 0;
        }
        $moduledata->visible = ((int)$data->is_active > 0) ? 1 : 0;
        
        $noderesponse = $api->fetch_node_data($data->id, $this->course->idnumber, $refetchfromservice);
        if($module == 'assign') {
            $moduleinfo = create_module($moduledata);
            $this->totalactivities++;
            $subjectivetestrepsonse = $api->fetchdata($data->id, $this->course->idnumber, $refetchfromservice,'subjectivetest');
        }  else  if ($module == 'folder') {
            $moduleinfo = create_module($moduledata);
            $modulecontext = context_module::instance($moduleinfo->coursemodule);
            $this->totalactivities++;
            if($noderesponse){
                $noderesponse = json_decode($noderesponse);
                $mediacontenturl = $api->settings->mediacontenturl;
                $i=1;
                foreach($noderesponse->response->content_docs_data as $mediacontents){
                    $mediacontenturl = $api->settings->mediacontenturl;
                    $intro = '';
                    if(!empty($mediacontents->elearning_content[0]->content)){
                        $fileinfo = $DB->get_record('files',['component' => 'mod_folder', 
                        'filearea' => 'content',
                        'contextid' => $modulecontext->id,
                        'itemid' => 0,
                        'filename' => $mediacontents->learning_objective[0]->name,
                        'filepath' => '/'
                        ]);
                        $filename = ($fileinfo->id > 0) ? $mediacontents->learning_objective[0]->name.'-'.$i: $mediacontents->learning_objective[0]->name;
                        $filerecord = [ 'component' => 'mod_folder', 
                        'filearea' => 'content',
                        'contextid' => $modulecontext->id,
                        'itemid' => 0,
                        'filename' => $filename,
                        'filepath' => '/'
                        ];
                        $mediaurl = $mediacontenturl.implode("/", array_map("rawurlencode", explode("/", $mediacontents->elearning_content[0]->content)));
                        $content = $api->get($mediaurl, [], ['CURLOPT_HTTPHEADER' =>  []]);
                        $fs = get_file_storage();
                        $fs->create_file_from_string($filerecord, $content);
                        $i++;
                    }
                }
            }
           
        } else if ($module == 'page') {
            $moduleinfo = create_module($moduledata);
            $modulecontext = context_module::instance($moduleinfo->coursemodule);
            $this->totalactivities++;
            $pagerecord = $DB->get_record('page',['id'=>$moduleinfo->instance]);
            $intro = '';
            if ($moduledata->pagetype) {
                $liveclassrepsonse = $api->fetchdata($data->id, $this->course->idnumber, $refetchfromservice,'classroomdata');
                if (is_object($liveclassrepsonse) && isset($liveclassrepsonse->response->class_rooms) && !empty ($liveclassrepsonse->response->class_rooms)){
                    $content = '';
                    foreach ($liveclassrepsonse->response->class_rooms AS $classroom) {
                        if($classroom->is_active){
                            $timestart = strtotime($classroom->start_time);
                            $timeend = strtotime($classroom->end_time);
                            // $timeend = $timestart + ($classroom->duration*60);//Minute to second conversion.
                            // Class Notes
                            if(!empty($classroom->chapter_notes)) {
    
                                $filerecord = [ 'component' => 'mod_page', 
                                    'filearea' => 'content',
                                    'contextid' => $modulecontext->id,
                                    'itemid' => 0,
                                    'filename' => basename(implode("/", array_map("rawurlencode", explode("/", $classroom->chapter_notes)))), 
                                    'filepath' => '/'
                                ];
                                $chapter_notescontent = $api->get($classroom->chapter_notes, [], ['CURLOPT_HTTPHEADER' =>  []]);
                                $fs = get_file_storage();
                                $fs->create_file_from_string($filerecord, $chapter_notescontent);
                                $lessonnotes_url = \moodle_url::make_pluginfile_url($modulecontext->id, 'mod_page','content',0,'/',basename(implode("/", array_map("rawurlencode", explode("/", $classroom->chapter_notes)))));
                                $lessonnotesurl = $lessonnotes_url->out();
    
                            }
                            // Lesson Plans
                            if(!empty($classroom->lesson_plan)) {
    
                                $filerecord = [ 'component' => 'mod_page', 
                                    'filearea' => 'content',
                                    'contextid' => $modulecontext->id,
                                    'itemid' => 0,
                                    'filename' => basename(implode("/", array_map("rawurlencode", explode("/", $classroom->lesson_plan)))), 
                                    'filepath' => '/'
                                ];
                              
                                $lessonplanscontent = $api->get($classroom->lesson_plan, [], ['CURLOPT_HTTPHEADER' =>  []]);
                                $fs = get_file_storage();
                                $fs->create_file_from_string($filerecord, $lessonplanscontent);
    
                                $lessonplan_url = \moodle_url::make_pluginfile_url($modulecontext->id, 'mod_page','content',0,'/',basename(implode("/", array_map("rawurlencode", explode("/", $classroom->lesson_plan)))));
                                $lessonplanurl = $lessonplan_url->out();
    
                            }
                            $hassubtopic = ($classroom->subtopic) ? true :false;
                            $liveclasscard = $OUTPUT->render_from_template('mod_zoom/zoom_card', [
                                'title' => $classroom->contents, 
                                'hassubtopic'=>$hassubtopic,
                                'subtopic' => $classroom->subtopic,
                                'summary' => '', 
                                'timestart' => $timestart, 
                                'timeend' => $timeend,
                                'classnotes'=>$lessonnotesurl,
                                'lessonplan'=>$lessonplanurl
                                ]);
                            $introcontent = $liveclasscard;
                            $content .= $introcontent.'<br/><video controls="true">
                            <source src="'.$classroom->recording_url.'">'.$classroom->recording_url.'
                            </video> <br/>';
                            $intro .= $introcontent;
                        }
                        
                    }
                }
            }else{
                $content = '';
                if($noderesponse){
                    $noderesponse = json_decode($noderesponse);
                    $this->totalactivities++;
                    foreach($noderesponse->response->content_docs_data as $videocontent){
                        $pagerecord = $DB->get_record('page',['id'=>$moduleinfo->instance]);
                        $intro = '';
                        if(!empty($videocontent->elearning_content[0]->content)){
                            $content .= '<div class ="w-100"><iframe  class ="w-100 video-frame" src="'.$videocontent->elearning_content[0]->content.'" title="description"></iframe>'.'</div>';
                            $content .='<div class ="conceptvideocard card mb-3"><div class="card-header"><h5 class="mb-0"> Concepts Covered </h5></div><div class ="card-body"><p>'.$videocontent->learning_objective[0]->name.'</p> </div></div>';
                        }
                    }
                  
                }
            }
            $pagerecord->content = $content;
            $pagerecord->intro = $intro;
            $pagerecord->introformat = 1;

            $DB->update_record('page',$pagerecord);
        } else if ($module == 'quiz') {
            $adminuserid =(int) $DB->get_field('user','id',['username'=>'admin']);
            if ($noderesponse && $moduledata->testtype == 1) {
                $moduleinfo = create_module($moduledata);
                $quiz = $DB->get_record('quiz',['id'=>(int)$moduleinfo->instance]);
                $quiz->reviewcorrectness = 69632;
                $quiz->reviewmarks = 69632;
                $quiz->reviewspecificfeedback = 69632;
                $quiz->reviewgeneralfeedback = 69632;
                $quiz->reviewrightanswer= 69632;
                $quiz-> reviewoverallfeedback = 69632;
                $quiz->attemptimmediately = 1;
                $quiz->correctnessimmediately = 1;
                $quiz->marksimmediately = 1;
                $quiz->specificfeedbackimmediately = 1;
                $quiz->generalfeedbackimmediately = 1;
                $quiz->rightanswerimmediately = 1;
                $quiz->overallfeedbackimmediately = 1;
                $quiz->attemptopen = 1;
                $quiz->correctnessopen = 1;
                $quiz->marksopen = 1;
                $quiz->specificfeedbackopen = 1;
                $quiz->generalfeedbackopen = 1;
                $quiz->rightansweropen = 1;
                $quiz->overallfeedbackopen = 1;
                $quiz->attemptclosed = 1;
                $quiz->correctnessclosed = 1;
                $quiz->marksclosed = 1;
                $quiz->specificfeedbackclosed = 1;
                $quiz->generalfeedbackclosed = 1;
                $quiz->rightanswerclosed = 1;
                $quiz->overallfeedbackclosed = 1;
                $DB->update_record('quiz',$quiz);
                $quizobj = \mod_quiz\quiz_settings::create($moduleinfo->instance, $adminuserid);
                $noderesponse = json_decode($noderesponse);
                if($noderesponse->status == 'success' && !empty($noderesponse->response->node_info->details)){
                    $this->proces_practice_bundle_topics($noderesponse->response->node_info->details,$quizobj);
                }
            }

        }
        mtrace('<b>'.ucfirst($module).'</b> module having name <b>'.$data->name.'</b> created successfully'.'</br>');
    }

    public function proces_practice_bundle_topics($node_info_details,$quizobj){
        global $DB;
        $migratequestions = new \local_masterdata\questionslib();
        foreach ($node_info_details AS $detail) {
            $hierarchy = explode('/', $detail->topic_path);
            $questionids = $migratequestions->get_hierary_questions($hierarchy);
            if(COUNT($questionids) > 0) {
                foreach ($questionids AS $question) {
                    if((int)$question->id > 0) {
                        quiz_add_quiz_question((int)$question->id, $quizobj->get_quiz());
                    }
                    
                }
                \mod_quiz\quiz_settings::create((int)$quizobj->get_quiz()->id)->get_grade_calculator()->recompute_quiz_sumgrades();
            }
        }
    }

    public  function create_data_log($statusmessage){
        global $DB,$USER;
        $logrecord = new \stdClass();
        $logrecord->courseid = $this->course->id;
        $logrecord->activitiescount = $this->totalactivities;
        $logrecord->oldcourseid =  $this->oldcourseid;
        $logrecord->status =  ($statusmessage == 1) ? 0 : 1;
        $logrecord->status_message =  ($statusmessage == 1) ? null : $statusmessage;
        $logrecord->timecreated =time();
        $logrecord->usercreated = $USER->id;
        $DB->insert_record('local_masterdata_log',$logrecord);


    }

}
