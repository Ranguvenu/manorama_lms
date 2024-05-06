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
 * TODO describe file feedback
 *
 * @package    local_masterdata
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
ini_set("memory_limit", "-1");
ini_set('max_execution_time', 60000);
set_time_limit(0);
require(__DIR__.'/../../config.php');
global $CFG,$OUTPUT,$DB;
require_login();
echo $OUTPUT->header();
$qrecords = $DB->get_records_sql('SELECT que.id,qmo.correctfeedback FROM {question} que JOIN {qtype_multichoice_options} qmo ON qmo.questionid = que.id');
if($qrecords) {
    foreach ($qrecords AS $qrecord) {
        if($qrecord->correctfeedback) {
            $question = $DB->get_record('question',['id'=>(int)$qrecord->id]);
            $question->generalfeedback = $qrecord->correctfeedback;
            $DB->update_record('question',$question);
            mtrace("Correct Feedback updated for question <b>$qrecord->id</b>");
        }
    }
}
echo $OUTPUT->footer();
