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
define('CLI_SCRIPT', true);
ini_set("memory_limit", "-1");
ini_set('max_execution_time', 60000);
set_time_limit(0);
require(__DIR__.'/../../config.php');
global $CFG,$OUTPUT,$DB;
$user = $DB->get_record('user', ['id' => 183276]);
complete_user_login($user);

require_login();
echo $OUTPUT->header();
$qattempts = $DB->get_records_sql('SELECT lqa.id, lqa.last_try_date, lqa.attempt_start_date FROM {local_question_attempts} lqa WHERE lqa.last_try_time = 0 OR lqa.attempt_start_time = 0 ');
if($qattempts) {
    foreach ($qattempts AS $qattempt) {
        $qattempt->last_try_time = strtotime($qattempt->last_try_date);
        $qattempt->attempt_start_time = strtotime($qattempt->attempt_start_date);
        $DB->update_record('local_question_attempts', $qattempt);
        mtrace("timestamp updated for question <b>$qattempt->id</b>");
    }
}
echo $OUTPUT->footer();
