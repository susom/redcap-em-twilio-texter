<?php
namespace Stanford\TwilioTexter;

use REDCap;
use TwiML;
use Message;

class TwilioTexter extends \ExternalModules\AbstractExternalModule {




    /**
     * The filter in the REDCap::getData expects the phone number to be in
     * this format (###) ###-####
     *
     * @param $number
     * @return
     */
    public static function formatToREDCapNumber($number)
    {
        $formatted = preg_replace('~.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4}).*~', '($1) $2-$3', $number);
        return trim($formatted);

    }

    public function findRecordByPhone($phone, $phone_field, $phone_field_event) {

        $this->emDebug("Locate record for this phone: ".$phone);
        $get_fields = array(
            REDCap::getRecordIdField(),
            $phone_field
        );
        $event_name = REDCap::getEventNames(true, false, $phone_field_event);
        $filter = "[" . $event_name . "][" .$phone_field . "] = '$phone'";


        $records = REDCap::getData('array', null, $get_fields, null, null, false, false, false, $filter);
        //$this->emDebug($filter, $records, $project_id, $pid, $filter, $event_name);

        // return record_id or false
        reset($records);
        $first_key = key($records);
        return ($first_key);
    }


    /**
     * * Log the STOP text to the sms_log
     *
     * @param $log_field
     * @param $log_event
     * @param $rec_id
     * @param $msg_info
     * @return mixed
     */
    function logSms($log_field, $log_event, $rec_id, $msg_info) {
        $msg = array();
        $msg[] = "---- " . date("Y-m-d H:i:s") . " ----";
        $msg[] = $msg_info;

        $data = array(
            REDCap::getRecordIdField() => $rec_id,
            'redcap_event_name' => $log_event,
            $log_field => implode("\n", $msg),

        );

        REDCap::saveData($data);
        $response = REDCap::saveData('json', json_encode(array($data)));
        //$this->emDebug($response,  "Save Response for count");


        if (!empty($response['errors'])) {
            $msg = "Error creating record - ask administrator to review logs: " . json_encode($response);
            $this->emDebug($msg);
            return ($response);
        }

    }

    function sendEmail($to, $from, $subject, $msg)
    {

        // Prepare message
        $email = new Message();
        $email->setTo($to);
        $email->setFrom($from);
        $email->setSubject($subject);
        $email->setBody($msg);

        //logIt("about to send " . print_r($email,true), "DEBUG");

        // Send Email
        if (!$email->send()) {
            $module->emLog('Error sending mail: ' . $email->getSendError() . ' with ' . json_encode($email));
            return false;
        }

        return true;
    }



    /*******************************************************************************************************************/
    /* EXTERNAL MODULES METHODS                                                                                                    */
    /***************************************************************************************************************** */

    function emText($number, $text) {
        global $module;

        $emTexter = \ExternalModules\ExternalModules::getModuleInstance('twilio_utility');
        $this->emDebug($emTexter);
        $text_status = $emTexter->emSendSms($number, $text);
        return $text_status;
    }



      /**
     *
     * emLogging integration
     *
     */
    function emLog() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "INFO");
    }

    function emDebug() {
        // Check if debug enabled
        if ( $this->getSystemSetting('enable-system-debug-logging') || ( !empty($_GET['pid']) && $this->getProjectSetting('enable-project-debug-logging'))) {
            $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
            $emLogger->emLog($this->PREFIX, func_get_args(), "DEBUG");
        }
    }

    function emError() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "ERROR");
    }

}