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

namespace local_masterdata\local;

/**
 * Class smsapi
 *
 * @package    local_masterdata
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once($CFG->libdir . '/filelib.php');

use context_user;
use context_system;
use stdClass;
use curl;
class smsapi {
    public function sendsms($text,$phonenumber){

        $time = time();
        if ($time > strtotime('7 am') && $time < strtotime('9 pm') ) {
            $curl = curl_init();     
            $phone = $phonenumber;        
           $data = array(
                "enterpriseid"  =>  "malotp",
                "subEnterpriseid"   =>   "malotp",
                "pusheid"   =>  "malotp",      
                "pushepwd"  =>  "malot_20",
                "contenttype"   => "1",      
                "sender"    =>  "MMHRZN",        
                "alert" =>   "1",        
                "msisdn"    =>  "$phone",        
                "intflag"   => "false",    
                "msgtext"   =>   $text
                    );
                 $jsondata = json_encode($data);
              curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://otp2.aclgateway.com/OTP_ACL_Web/otpjsonlistener',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>$jsondata, 
              CURLOPT_HTTPHEADER => array(
                  'Content-Type: application/json',
                  'Content-Length: ' . strlen($jsondata)
              ),
              ));      
              $response = curl_exec($curl);      
              curl_close($curl);

            if($response){
                // $responsedata = json_decode($response,true) ;  
                return  true;
            
            } 

        }
        return false;

 
// $encodetext = rawurlencode($text);
// $params = array(
//   'User' => 'sms_horizon',
//   'passwd' => 'Horizon@202i',
//     'mobilenumber' => $phone,
//     'message' => $encodetext ,
//     "sid" => 'MMHRZN' ,
//     "sender" => "MMHRZN",
//      "mtype" => 'N',
//      "DR" => 'Y'

//   // Add more parameters as needed
// );
// $paramsJoined = array();

// foreach($params as $param => $value) {
//    $paramsJoined[] = "$param=$value";
// }

// $queryString = implode('&', $paramsJoined);
// $url = 'https://api.smscountry.com/SMSCwebservice_bulk.aspx?' . $queryString;



// curl_setopt_array($curl, array(
//   CURLOPT_URL => $url,
//   CURLOPT_RETURNTRANSFER => true,
//   CURLOPT_ENCODING => '',
//   CURLOPT_MAXREDIRS => 10,
//   CURLOPT_TIMEOUT => 0,
//   CURLOPT_FOLLOWLOCATION => true,
//   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//   CURLOPT_CUSTOMREQUEST => 'POST',
//   CURLOPT_HTTPHEADER => array(
//     'Content-Length: 0' // Explicitly set the Content-Length header to 0
//  ),

// ));
// //Please use 7899 as the OTP for 2fa authentication at MMHA -Manorama Horizon
// $response = curl_exec($curl);




    }

}
