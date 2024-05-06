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
 *
 * @package    local_masterdata
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
ini_set("memory_limit", "-1");
ini_set('max_execution_time', 60000);
set_time_limit(0);
require_once('../../config.php');
global $CFG,$OUTPUT;
$api = new \local_masterdata\api(['debug' => false]);
echo $OUTPUT->header();
    // $quizmoduleid = $DB->get_field('modules','id',['name'=>'quiz']);
    // $allcourses = $DB->get_records_sql("SELECT co.* FROM {course} co JOIN {course_categories} cc ON cc.id = co.category WHERE cc.idnumber =:idnumber ",['idnumber'=>'yearbookv2']);
    // $i = 0;
    // foreach($allcourses AS $course) {
    //     if(str_contains($course->idnumber, 'YB_MOCK_TEST_')) {
    //         $examid =(int) str_replace("YB_MOCK_TEST_","",$course->idnumber);
    //     } else if(str_contains($course->idnumber, 'MOCK_TEST_')) {
    //         $examid =(int) str_replace("MOCK_TEST_","",$course->idnumber);
    //     } else if(str_contains($course->idnumber, 'TEST_')) {
    //         $examid =(int) str_replace("TEST_","",$course->idnumber);
    //     }
    //     $moduleinfo =$DB->get_record_sql('SELECT * FROM {course_modules} WHERE course =:courseid AND module =:quizmoduleid ORDER BY id ASC LIMIT 1',['courseid'=>$course->id,'quizmoduleid'=>$quizmoduleid]);
    //     if($moduleinfo->id > 0) {
    //         echo $api->update_neet_schema($examid,$moduleinfo);
    //     }
    //     $i++;
    // }
    // mtrace('Total <b>'.$i.'</b> quiz modules updated successfully'.'</br>');
echo $OUTPUT->footer();
