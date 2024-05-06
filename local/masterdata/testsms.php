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
require_once('../../config.php');
$userphonenumber = optional_param('phoneno', 918686658571, PARAM_INT);
$smsmessagetext = optional_param('smsmessagetext', 'Dear Student, please be informed that your testzoom class on 04-04-2024 at 19:25 PM stands cancelled. Compensatory classes will be provided - Team Horizon', PARAM_RAW);
$smsapi = new \local_masterdata\local\smsapi();            
$sendsms = $smsapi->sendsms($smsmessagetext, $userphonenumber);
var_dump($sendsms);
