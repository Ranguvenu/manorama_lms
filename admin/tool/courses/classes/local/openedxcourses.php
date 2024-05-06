<?php
namespace tool_courses\local;

use moodle_url;
use stdClass;
use dml_exception;
use Exception;

require_once($CFG->dirroot.'/config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->dirroot. '/course/format/lib.php');
require_once($CFG->dirroot.'/mod/page/locallib.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot. '/question/editlib.php');

class openedxcourses {

    protected $courseid;

    protected $itemid;

    public function display_form() {
        global $OUTPUT, $CFG, $DB;
        echo $OUTPUT->header();
        $mform = new \tool_courses\form\courses_import();

        if ($mform->is_cancelled()) {
            // redirect($returnurl);
        } elseif ($formdata = $mform->get_data()) {
            $this->itemid = $formdata->userfile;
            if ($this->itemid) {
                $this->store_file();
                $courseid = $this->createcourse();
                if ($courseid) {
                    redirect($CFG->wwwroot.'/course/view.php?id='.$courseid);
                }
            }
        } else {
            echo $OUTPUT->heading(get_string('uploadcourses', 'tool_courses'));
            $mform->display();
        }
        echo $OUTPUT->footer();
        die;
    }
    public function store_file() {
        global $OUTPUT, $CFG, $USER, $DB;
        $fs = get_file_storage();

        $sql = "SELECT *
                FROM {files} 
                WHERE itemid = {$this->itemid} AND filesize != 0 ";
        $filerecord = $DB->get_record_sql($sql);
        $file = $fs->get_file($filerecord->contextid,$filerecord->component,$filerecord->filearea,$filerecord->itemid,$filerecord->filepath,$filerecord->filename);

        mkdir($CFG->dataroot.DIRECTORY_SEPARATOR.'openedxcourses'.DIRECTORY_SEPARATOR.$this->itemid, 0777, true);

        $dir = $CFG->dataroot.DIRECTORY_SEPARATOR.'openedxcourses'.DIRECTORY_SEPARATOR.$this->itemid;
        $requestdir = $dir;
        $file->copy_content_to("{$requestdir}/".$filerecord->filename);

        $file2 = $CFG->dataroot.DIRECTORY_SEPARATOR.'openedxcourses'.DIRECTORY_SEPARATOR.$this->itemid.DIRECTORY_SEPARATOR.$filerecord->filename;
        $phar = new \PharData($file2);
        $phar->extractTo($CFG->dataroot.DIRECTORY_SEPARATOR.'openedxcourses'.DIRECTORY_SEPARATOR.$this->itemid);
    }
    public function createcourse() {
        global $DB, $CFG;

        $xmldata = simplexml_load_file($CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/course.xml');
        $courseinfo = (array)$xmldata;
        $code = $courseinfo['@attributes']['course'];

        $xmldata = simplexml_load_file($CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/course/course.xml');
        $courseinfo = (array)$xmldata;
        $data = $courseinfo['@attributes'];

        // Course Creation
        if (array_key_exists('enrollment_start', $data)) {
            $startdate = str_replace("\"","", $data['enrollment_start']);
            $actualstartdate = strtotime($startdate);
        }
    
        $courseconfig = get_config('moodlecourse');
        $parentid = $DB->get_field('course_categories', 'id', ['idnumber' => 'cbsc']);

        $conditions = ['courseshortname' => $code, 'component' => 'course', 'filename' => 'course/course.xml', 'status' => 'started', 'reason' => '', 'timecreated' => time()];
        $DB->insert_record('openedxcourses_logs', $conditions);
    
        if ($data) {
            $courserecord = new stdClass();
            $courserecord->category = $parentid;
            $courserecord->fullname = $data['display_name'];
            $courserecord->idnumber = '';
            $courserecord->shortname = $code;
            $courserecord->summary = NULL;
            $courserecord->summary_format = true;
            $courserecord->startdate = !empty($actualstartdate) ? $actualstartdate : 0;
            $courserecord->enddate = !empty($actualstartdate) ? strtotime(date('Y-m-d', strtotime('+1 years'))) : 0;
            $courserecord->timecreated = time();
            $courserecord->timemodified = time();
        
            // Apply course default settings
            $courserecord->format             = 'flexsections';
            $courserecord->newsitems          = $courseconfig->newsitems;
            $courserecord->showgrades         = $courseconfig->showgrades;
            $courserecord->showreports        = $courseconfig->showreports;
            $courserecord->maxbytes           = $courseconfig->maxbytes;
            $courserecord->groupmode          = $courseconfig->groupmode;
            $courserecord->groupmodeforce     = $courseconfig->groupmodeforce;
            $courserecord->visible            = $courseconfig->visible;
            $courserecord->visibleold         = $courserecord->visible;
            $courserecord->lang               = $courseconfig->lang;
            $courserecord->enablecompletion   = $courseconfig->enablecompletion;
            $courserecord->numsections        = 0;
        
            // $transaction = $DB->start_delegated_transaction();
            try {
                $course = create_course($courserecord);

                $fs = get_file_storage();
                $image = $CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/static/'.$data['course_image'];
                
                if (file_exists($image)) {
                    $context = \context_course::instance($course->id);
                    $filerecorda = array(
                        'contextid' => $context->id,
                        'component' => 'course',
                        'filearea'  => 'overviewfiles',
                        'filepath'  => '/',
                        'filename'  => "{$data['course_image']}",
                    );
                    $filerecorda['itemid'] = 0;
    
                    $image = $CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/static/'.$data['course_image'];
                    $fs->create_file_from_pathname($filerecorda, $image);
                }

                $this->courseid = $course->id;
                if ($course->id) {
                    $conditions = ['courseshortname' => $code, 'component' => 'course', 'filename' => 'course/course.xml', 'status' => 'created', 'reason' => '', 'timecreated' => time()];
                    $DB->insert_record('openedxcourses_logs', $conditions);
                }

                if ($courseinfo['chapter']) {
                    $chapters = $this->create_chapter($courseinfo['chapter']);
                    if ($chapters) {
                        echo get_string('finished', 'tool_courses');
                        $conditions = ['courseshortname' => $code, 'component' => 'course', 'filename' => 'course/course.xml', 'status' => 'finished', 'reason' => '', 'timecreated' => time()];
                        $DB->insert_record('openedxcourses_logs', $conditions);
                        // $transaction->allow_commit();
                        return $this->courseid;
                    }
                }
            } catch (dml_exception $e) {
                // $transaction->rollback($e);
                $DB->insert_record('openedxcourses_logs', ['courseshortname' => $code, 'component' => 'course', 'filename' => 'course/course.xml',  'status' => 'failed', 'reason' => serialize($e), 'timecreated' => time()]);
                print_r($e);
            }
        }
    }
    public function create_chapter($chapters) {
        global $CFG, $DB;
        // Chapter
        foreach($chapters as $chapter) {
            $chapter = (array)$chapter;
            $filename = $chapter['@attributes']['url_name'];
            if ($filename) {
                $xmldata = simplexml_load_file($CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/chapter/'.$filename.'.xml');
                $chapterinfo = (array)$xmldata;
                $section = course_create_section($this->courseid);

                $sectiondata = new stdClass();
                $sectiondata->name = $chapterinfo['@attributes']['display_name'];
                course_update_section($this->courseid, $section, $sectiondata);
                if ($section->id) {
                    $conditions = ['courseshortname' => $sectiondata->name, 'component' => 'chapter', 'filename' => $filename, 'status' => 'created', 'reason' => '', 'timecreated' => time()];
                    $DB->insert_record('openedxcourses_logs', $conditions);
                } else {
                    $conditions = ['courseshortname' => $sectiondata->name, 'component' => 'chapter', 'filename' => $filename, 'status' => 'failed', 'reason' => '', 'timecreated' => time()];
                    $DB->insert_record('openedxcourses_logs', $conditions);
                }

                $lessons = $chapterinfo['sequential'];
                if ($lessons) {
                    $this->create_lessons($section, $lessons);
                }
            }
        }

        return true;
    }

    public function create_lessons($section, $lessons) {
        global $CFG, $DB;
        $count = 0;
        foreach($lessons as $lesson) {
            $lesson = (array)$lesson;
            $lessonfile = $lesson['@attributes']['url_name'];
            $xmllesson = simplexml_load_file($CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/sequential/'.$lessonfile.'.xml');
            $xmllesson = (array)$xmllesson;

            $course = $DB->get_record('course', ['id' => $this->courseid]);

            $sectiondata = new stdClass();
            $sectiondata->name = $xmllesson['@attributes']['display_name'];

            $childsection = course_create_section($this->courseid);
            course_update_section($this->courseid, $childsection, $sectiondata);

            /** @var format_flexsections $format */
            $format = course_get_format($this->courseid);
            $format->move_section($childsection->section, $section);

            if ($section->id) {
                $conditions = ['courseshortname' => $sectiondata->name, 'component' => 'lesson', 'filename' => $lessonfile, 'status' => 'created', 'reason' => '', 'timecreated' => time()];
                $DB->insert_record('openedxcourses_logs', $conditions);
            } else {
                $conditions = ['courseshortname' => $sectiondata->name, 'component' => 'lesson', 'filename' => $lessonfile, 'status' => 'failed', 'reason' => '', 'timecreated' => time()];
                $DB->insert_record('openedxcourses_logs', $conditions);
            }

            if ($childsection && $xmllesson['vertical']) {
                $topics = (array)$xmllesson['vertical'];
                $this->create_topic($childsection, $topics);
            }
        }
    }

    public function create_topic($section, $topics) {
        global $DB, $CFG;
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        $count = 0;
        foreach($topics as $topic) {
            $topicfile = $topic['url_name'];
            $xmltopic = simplexml_load_file($CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/vertical/'.$topicfile.'.xml');
            $xmltopic = (array)$xmltopic;

            $sectiondata = new stdClass();
            $sectiondata->name = $xmltopic['@attributes']['display_name'];

            $childsection = course_create_section($this->courseid);
            course_update_section($this->courseid, $childsection, $sectiondata);

            /** @var format_flexsections $format */
            $format = course_get_format($this->courseid);
            $format->move_section($childsection->section, $section);

            $filename = (array)$topicfile;
            if ($section->id) {
                $conditions = ['courseshortname' => $sectiondata->name, 'component' => 'topic', 'filename' => $filename[0], 'status' => 'created', 'reason' => '', 'timecreated' => time()];
                $DB->insert_record('openedxcourses_logs', $conditions);
            } else {
                $conditions = ['courseshortname' => $sectiondata->name, 'component' => 'topic', 'filename' => $filename[0], 'status' => 'failed', 'reason' => '', 'timecreated' => time()];
                $DB->insert_record('openedxcourses_logs', $conditions);
            }

            if($xmltopic['html']) {
                $activityname = 'page';
                $contents = (array)$xmltopic['html'];
                $this->create_module($childsection, $contents, $activityname);
            }
        }
    }

    public function create_module($section, $contents, $activityname) {
        global $DB, $CFG;
        $count = 0;
        foreach($contents as $content) {
            $content = (array)$content;
            $filename = !empty($content['@attributes']['url_name']) ? $content['@attributes']['url_name'] : $content['url_name'];
            if ($activityname == 'page') {
                $this->create_page($section, $filename);
            }
            //  elseif($activityname == 'quiz') {
            //     $this->create_quiz($section, $filename);
            //     ++$count;
            // }
        }
    }

    public function create_page($section, $filename) {
        global $DB, $CFG;
        $fs = get_file_storage();
        $xmltopic = simplexml_load_file($CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/html/'. $filename .'.xml');
        $xmltopic = (array)$xmltopic;
        $display_name = !empty($xmltopic['@attributes']['display_name']) ? $xmltopic['@attributes']['display_name'] : "PAGE";
        $htmlfile = $xmltopic['@attributes']['filename'];
        $html = file_get_contents($CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/html/'. $htmlfile .'.html');

        $data = new stdClass();
        $data->course = $this->courseid;
        $data->name = $display_name;
        $data->modulename = 'page';
        $data->content = $html;
        $data->contentformat = 1;
        $data->visible = 1;
        $data->section = $section->section;
        $data->introeditor['text'] = '';
        $data->introeditor['format'] = FORMAT_HTML;

        try {
            $coursemodule = create_module($data);
            $cmid = $DB->get_field('course_modules', 'id', ['course' => $coursemodule->course, 'instance' => $coursemodule->instance]);

            // read all image tags into an array
            preg_match_all('/<img[^>]+>/i', $html, $imgTags); 
            
            for ($i = 0; $i < count($imgTags[0]); $i++) {
                preg_match('/src="([^"]+)/i',$imgTags[0][$i], $imgage);
                $src = str_ireplace( 'src="', '',  $imgage[0]);

                if (str_contains($src, 'static')) { 
                    $imagepath = $CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course'.$src;
                    $cmcontext = \context_module::instance($cmid);
                    if (file_exists($imagepath)) {
                        $filename = str_replace('/static/', '', $src);
                        // $imageurl = moodle_url::make_pluginfile_url($cmcontext->id, 'mod_page', 'content', 0, '/', $filename);
                        $img =  $fs->get_file($cmcontext->id, 'mod_page', 'content', 0, '/', $filename);
                        if (!$img) {
                            $filerecorda = array(
                                'contextid' => $cmcontext->id,
                                'component' => 'mod_page',
                                'filearea'  => 'content',
                                'filepath'  => '/',
                                'filename'  => "{$filename}",
                            );
                            $filerecorda['itemid'] = 0;
                            $fs->create_file_from_pathname($filerecorda, $imagepath);
                        }
                        $imageurl = moodle_url::make_pluginfile_url($cmcontext->id, 'mod_page', 'content', 0, '/', $filename);                    
                        $html = preg_replace('(src="(.*?)")', 'src="'.$imageurl.'"', $html);
                    }
                }
            }
            $DB->update_record('page', ['id' => $coursemodule->instance, 'content' => $html]);

            $conditions = ['courseshortname' => $display_name, 'component' => 'pageactivity', 'filename' => $htmlfile, 'status' => 'created', 'reason' => '', 'timecreated' => time()];
            $DB->insert_record('openedxcourses_logs', $conditions);

        } catch (Exception $e) {
            $conditions = ['courseshortname' => $display_name, 'component' => 'pageactivity', 'filename' => $htmlfile, 'status' => 'failed', 'reason' => '', 'timecreated' => time()];
            $DB->insert_record('openedxcourses_logs', $conditions);
            throw $e;
        }
    }

    public function create_quiz($section, $filename) {
        global $DB, $CFG;
        $quiz = simplexml_load_file($CFG->dataroot.'/openedxcourses/'.$this->itemid.'/course/problem/'. $filename .'.xml');
        $question = (array)$quiz;

        $display_name = $question['@attributes']['display_name'];

        if (1) {
            $quiz = new stdClass();
            $quiz->name = $display_name;
            $quiz->modulename = 'quiz';
            $quiz->timeopen = 0;
            $quiz->timeclose = 0;
            $quiz->timelimit = 0;
            $quiz->grade=10;
            $quiz->course = $this->courseid;
            $quiz->gradecat = 2;
            $quiz->section = $section->section;
            $quiz->visible = 1;
            $quiz->quizpassword=0;
            $quiz->completion = 2;
            $quiz->completiongradeitemnumber=0;
            $quiz->cmidnumber = '';
            $quiz->preferredbehaviour='deferredfeedback';
            $quiz->attempts=0;
            //************ */
            $quiz->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
            $quiz->hidden = 0;
            $quiz->overduehandling = 'autosubmit';
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
            $quiz->questionsperpage = 1;
            $quiz->shuffleanswers = 1;
            //***************/
            $quiz->groupmode=1;
            $quiz->introeditor = ['text' => '', 'format' => FORMAT_HTML, 'itemid' => null];
            $moduleinfo = create_module($quiz);

            if ($question) {
                $this->add_question($question, $moduleinfo->id, $filename);
            }
        }
    }

    public function add_question($question, $quizid, $filename) {
        global $DB;
        $displayname = $question['@attributes']['display_name'];
        $weight = $question['@attributes']['weight'];
        $questiontext = $question['@attributes']['markdown'];

        $multiplechoices = $question['multiplechoiceresponse'];
        if (!is_array($multiplechoices)) {
            $multiplechoices = (array)$multiplechoices;
            $this->prepare_question($multiplechoices, $displayname, $quizid, $questiontext);
        } else {
            foreach($multiplechoices as $multiplechoice) {
                $mc = (array)$multiplechoice;
                $this->prepare_question($mc, $displayname, $quizid, $questiontext);
            }
        }
    }

    public function prepare_question($multiplechoices, $displayname, $quizid, $questiontext) {
        global $DB;
        $quiz = $DB->get_record('quiz', ['id' => $quizid]);
        $choices = $multiplechoices['choicegroup'];
        $qchoices = [];
        $fraction = [];
        $feedbackformat = [];
        $choices = $choices;
        foreach($choices as $key => $choice) {
            $choice = (array)$choice;
            $row = [];
            $row['text'] = $choice[0];
            $row['format'] = FORMAT_HTML;
            $format['format'] = 1;
            $fraction[] = !empty($choice['@attributes']['correct'] == 'true') ? 1 : 0;
            $qchoices[] = $row;
            $feedbackformat[] = $format;
        }

        $hint = (array)$multiplechoices['solution'];
        if ($hint) {
            $row = '';
            $div = (array)$hint['div'];
            foreach($div['p'] as $paragraph) {
                $row .= '<p>'.$paragraph.'</p>';
            }
            $hintinfo = "<div class='detailed-solution'>".$div['p']."</div>";
        } else {
            $hintinfo = '';
        }   
       
        // Create a question object
        $questiondata = new stdClass();
        $questiondata->qtype = 'multichoice'; // Type of question
        $questiondata->name = $displayname; // Name of the question
        $questiondata->questiontext = $questiontext; // Question text
        $questiondata->questiontextformat = FORMAT_HTML; // Format of the question text
        $questiondata->generalfeedback = '<p>That is correct!</p>'; // General feedback for the question
        $questiondata->defaultmark = !empty($weight) ? $weight : 1; // Default mark for the question
        
        $questiondata->options = new stdClass();
        $questiondata->options->answers = $qchoices;

        $form = new stdClass();
        $form->category = 2;
        $form->name = $displayname;
        $form->questiontext['text'] = $questiontext;
        $form->questiontext['format'] = FORMAT_HTML; 
        $form->status = 'ready';
        $form->defaultmark = !empty($weight) ? $weight : 1;
        $form->single = 1;
        $form->answernumbering = 'abc';
        $form->shuffleanswers = 1;
        $form->correctfeedback = ['text' => 'Your answer is correct', 'format' => FORMAT_HTML];
        $form->partiallycorrectfeedback = ['text' => 'Your answer is partially correct', 'format' => FORMAT_HTML];
        $form->incorrectfeedback = ['text' => 'Your answer is incorrect', 'format' => FORMAT_HTML];

        $form->answer = $qchoices;
        $form->fraction = $fraction;
        $form->feedback = $feedbackformat;
        $form->hint = [['text' => $hintinfo, 'format' => FORMAT_HTML]];


        $transaction = $DB->start_delegated_transaction();
        try {
            $qtypeobj = \question_bank::get_qtype($questiondata->qtype);

            $question = $qtypeobj->save_question($questiondata, $form);

            quiz_add_quiz_question($question->id, $quiz);
            // Update sumgrades in the database.
            $DB->execute("
                    UPDATE {quiz}
                    SET sumgrades = COALESCE((
                            SELECT SUM(maxmark)
                            FROM {quiz_slots}
                            WHERE quizid = {quiz}.id
                        ), 0)
                    WHERE id = ?
                ", [$quiz->id]);

            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }
}
