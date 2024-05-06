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
 * TODO describe file userscript
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
require_login();
echo $OUTPUT->header();
$users = $DB->get_records_sql('SELECT * FROM {users_laravel}');
// $fieldid =(int)$DB->get_field('user_info_field','id',['shortname' =>'userauthtype']);
if(COUNT($users) > 0){
    foreach ($users AS $user) {

        $data =  new stdClass();
        $data->id = $user->mdl_user;
        $data->email = $user->email;
        $DB->update_record('user',$data);
        mtrace('Email <b>'.$user->email.'</b> Updated for user <b>'.$user->mdl_user.'</b>.');
        echo "</br>";
        
        // if($fieldid) {
        //     $data =  new stdClass();
        //     $data->userid = $user->mdl_user;
        //     $data->fieldid = $fieldid;
        //     $data->data = ($user->registration_mode) ? 'SSO' : 'Horizon';
        //     $dataid = $DB->get_field('user_info_data', 'id', array('userid' => $user->mdl_user, 'fieldid' => $fieldid));
        //     if ($dataid) {
        //         $data->id = $dataid;
        //         $DB->update_record('user_info_data', $data);
        //         mtrace('Updated user info data for <b>'.$user->mdl_user.'</b>.');
        //         echo "</br>";
        //     } else {
        //         $DB->insert_record('user_info_data', $data);
        //         mtrace(' User info data created for <b>'.$user->mdl_user.'</b>.');
        //         echo "</br>";
        //     }
        // }
    }
}
echo $OUTPUT->footer();
