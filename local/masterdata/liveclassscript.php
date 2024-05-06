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
 * TODO describe file quizscript
 *
 * @package    local_masterdata
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
ini_set("memory_limit", "-1");
ini_set('max_execution_time', 60000);
set_time_limit(0);
require('../../config.php');
require_login();
$nodeid = required_param('nodeid',PARAM_INT);
$batchid = required_param('batchid',PARAM_INT);
$api = new \local_masterdata\api(['debug' => false]);
echo $OUTPUT->header();
exit;
if($nodeid && $batchid) {
   $batch = 'BAT_'.$batchid;
   $courseid = (int)$DB->get_field('course','id',['idnumber'=>$batch]);
   if($courseid) {
    echo $api->create_liveclass($nodeid,$courseid,$batchid);
   }
}
echo $OUTPUT->footer();
