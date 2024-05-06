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
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
defined('MOODLE_INTERNAL') || die;
class pagecourselib extends courselib {
    public $parentcourseinfo;
    public $batchid;
	public function __construct($moodlecourse, $oldcourseid, $parentcourseid) {
		$this->parentcourseinfo = get_course($parentcourseid);
        $this->batchid = str_replace('BAT_', '', $moodlecourse->idnumber);
		parent::__construct($moodlecourse, $oldcourseid);
	}
    public function process_zoom_mastercoursedata($child) {
        switch ($child->key_label) {
            case 'Chapter' :
            case 'Lesson' :
                $this->get_course_section($child);
            break;
            case 'Live Class' :
                $this->zoom_course_module($child, $this->course->id, $this->latestsection, 'page');
            break;
            default :
            break;
        }
        if(!empty($child->children)) {
        	$currentparent = $this->parent;
            foreach($child->children AS $newchild){
            	$this->parent = $currentparent;
                $this->process_zoom_mastercoursedata($newchild);
            }
        }
    }
    public function get_course_section($data){
        global $DB;
        if($data->is_active) {
            $section = $DB->get_record('course_sections', ['name' => $data->name, 'course' => $this->course->id]);
            $this->latestsection = $section;
        }
    }
    public function zoom_course_module($data,$courseid,$latestsection,$module) {
        global $DB,$OUTPUT,$CFG;
        $api = (new api(['debug' => false]));
        $sectionid = (int)$latestsection->id;
        $pagemoduleid =(int) $DB->get_field('modules','id',['name'=>'page']);
        $pageactivities =  $DB->get_records_sql("SELECT pg.*,com.id as cmid,com.section,com.instance FROM {page} pg 
                                                      JOIN {course_modules} com ON com.instance = pg.id AND com.course =pg.course 
                                                      WHERE com.deletioninprogress = 0 AND com.module =:pagemodule  AND pg.name LIKE :pagename AND com.section =:sectionid",['pagemodule'=>$pagemoduleid,'pagename'=> 'Live Class : '.str_replace('\\', '', $data->name),'sectionid'=>$sectionid]);
        if(empty($pageactivities)) {
            $pageactivities =  $DB->get_records_sql("SELECT pg.*,com.id as cmid,com.section,com.instance FROM {page} pg 
                                                      JOIN {course_modules} com ON com.instance = pg.id AND com.course =pg.course 
                                                      WHERE com.deletioninprogress = 0 AND com.module =:pagemodule  AND pg.name LIKE :pagename AND com.section =:sectionid",['pagemodule'=>$pagemoduleid,'pagename'=> str_replace('\\', '', $data->name),'sectionid'=>$sectionid]);
        }
        if(empty($pageactivities)) { 
            return;
        }
        foreach($pageactivities as $pageactivity) {
            $intro = '';
            $pagerecord = $DB->get_record('page',['id'=>$pageactivity->id]);
            $modulecontext = context_module::instance($pageactivity->cmid);
            $liveclassrepsonse = $api->fetchdata($data->id, $this->course->idnumber, true,'classroomdata',(int)$this->batchid);
            if (is_object($liveclassrepsonse) && isset($liveclassrepsonse->response->class_rooms) && !empty ($liveclassrepsonse->response->class_rooms)){
                $content = '';
                $isactivity = false;
                foreach ($liveclassrepsonse->response->class_rooms AS $classroom) {
                    if($classroom->is_active){
                        $hassubtopic = ($classroom->subtopic) ? true :false;
                        $timestart = strtotime($classroom->start_time);
                        $timeend = strtotime($classroom->end_time);
                        $liveclasscardtestinfo = $OUTPUT->render_from_template('local_masterdata/test_zoom_card', [
                            'title' => $classroom->contents, 
                            'hassubtopic'=>$hassubtopic,
                            'subtopic' => $classroom->subtopic,
                            'summary' => '', 
                            'timestart' => $timestart, 
                            'timeend' => $timeend,
                        ]);
                        if(strpos($pageactivity->content,$liveclasscardtestinfo) === 0 || strpos($pageactivity->content,$liveclasscardtestinfo) > 0) {
                            $isactivity = true;
                        }
                        if(!empty($classroom->chapter_notes)) {
                            $lessonnotes_url = \moodle_url::make_pluginfile_url($modulecontext->id, 'mod_page','content',0,'/',basename(implode("/", array_map("rawurlencode", explode("/", $classroom->chapter_notes)))));
                            $lessonnotesurl = $lessonnotes_url->out();
                        }
                        if(!empty($classroom->lesson_plan)) {
                            $lessonplan_url = \moodle_url::make_pluginfile_url($modulecontext->id, 'mod_page','content',0,'/',basename(implode("/", array_map("rawurlencode", explode("/", $classroom->lesson_plan)))));
                            $lessonplanurl = $lessonplan_url->out();
                        }

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
            if($isactivity) {
                $pagerecord->content = $content;
                $pagerecord->intro = $intro;
                $pagerecord->introformat = 1;
                $DB->update_record('page',$pagerecord);
                mtrace('Page (<b>'.ucfirst($pageactivity->name).'</b>) activity updated successfully'.'</br>');
            }
        }
    }
}
