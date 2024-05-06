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
define('CLI_SCRIPT', true);


require_once(__DIR__ . '/../../config.php');
global $DB;

$currenttime =  strtotime(date('Y-m-d H:i'));
$notificationsql = "SELECT id as emailid, notification_type, courseid as course, cmid as coursemodule, from_userid as fromuserid, to_userid as touserid,  messagebody as emailbody,subject as emailsubject, send_after
    FROM {local_notification_logs}      
    WHERE status = 0 AND send_after < $currenttime" ;
$notificationlogs  = $DB->get_records_sql($notificationsql, [], 0, 100);  
foreach($notificationlogs AS  $notificationlog){
    (new local_masterdata\api())->send_zoom_pending_notification($notificationlog); 
}