<?php
namespace Stanford\TwilioTexter;

use Plugin;
use Services_Twilio;
use Exception;

class TwilioTexter extends \ExternalModules\AbstractExternalModule {
    static $twilio_sid = null;
    static $twilio_token = null;
    static $twilio_number = null;
    static $delete_sms_from_log = null;

    private $client = null;


    public function setup() {
        self::$twilio_sid = $this->getProjectSetting("twilio-sid");
        self::$twilio_token = $this->getProjectSetting("twilio-token");
        self::$twilio_number = $this->getProjectSetting("twilio-number");
        self::$delete_sms_from_log = $this->getProjectSetting("delete-sms-from-log");

        $this->client = new Services_Twilio(self::$twilio_sid , self::$twilio_token);

    }


    /**
     * Initialize Twilio classes and settings (using REDCap ones since they also use the proxy for outgoing communication)
     */
    public static function init()
    {
        global $rc_autoload_function;
        // Call Twilio classes
        require_once APP_PATH_DOCROOT . "/Libraries/Twilio/Services/Twilio.php";
        // Reset the class autoload function because Twilio's classes changed it
        spl_autoload_register($rc_autoload_function);
    }

    /**
     * @return null
     */
    public static function getTwilioSid() {
        return self::$twilio_sid;
    }

    function sendText($phone, $message) {
        //if not yet set, initialize twilio settings
        if (is_null($this->client)) {

            // Initialize the twilio library if needed
            if (!class_exists("Services_Twilio")) self::init();

            $this->setup();
        }

        //Plugin::log($message, "DEBUG", "SENDING TO $phone");
        //Plugin::log(self::$twilio_number , "DEBUG", "From this twilio number");

        $status  = $this->sendSms($phone, $message, self::$twilio_number, self::$twilio_sid, self::$delete_sms_from_log);

        return $status;
    }

    /**
     * @param $destination_number
     * @param $text
     * @return bool|string        If not true, then the contents will be an error message from Twilio such as poor number format, etc..
     */
    public function sendSms($destination_number, $text, $twilio_number, $twilio_sid, $twilio_delete)
    {
//        Plugin::log("<br><br>TEXTMANAGER: " . $destination_number . " : " . $text .
//            " twilio_num: ".$twilio_number. " twilio_delete: ".$twilio_delete, "DEBUG");
        try {
            $sms = $this->client->account->messages->sendMessage(
                self::formatNumber($twilio_number),
                self::formatNumber($destination_number),
                $text
            );

            // Wait till the SMS sends completely and then remove it from the Twilio logs
            if ($twilio_delete) {
                sleep(1);
                $result = $this->deleteLogForSMS($sms->sid);

                if ($result !== true) {
                    Plugin::log("UNABLE TO DELETE SMS LOG", "ERROR");
                    return "ERROR: Unable to delete logs from Twilio";
                }


            }

//            Plugin::log($sms->status, "DEBUG", "SMS SEND STAUS");
//            Plugin::log($sms->error_code, "DEBUG", "SMS SEND STAUS");

            // Successful, so return true
            return true;
        } catch (Exception $e) {
            // On failure, return error message
            return $e->getMessage();
        }
    }


    /**
     * Delete the Twilio back-end and front-end log of a given SMS (will try every second for up to 30 seconds)
     * @param $sid
     * @return bool
     */
    public function deleteLogForSMS($sid)
    {
        // Delete the log of this SMS (try every second for up to 30 seconds)
        for ($i = 0; $i < 30; $i++) {
            // Pause for 1 second to allow SMS to get delivered to carrier
            if ($i > 0) sleep(1);
            // Has it been delivered yet? If not, wait another second.
            $log = $this->client->account->sms_messages->get($sid);

            //print "<pre>Log $i: " . print_r($log, true) . "</pre>";
            if ($log->status != 'delivered') continue;
            // Yes, it was delivered, so delete the log of it being sent.
            $this->client->account->messages->delete($sid);
            return true;
        }
        // Failed
        return false;
    }


    /**
     * Convert phone nubmer to E.164 format before handing off to Twilio
     * @param $phoneNumber
     * @return mixed|string
     */
    public static function formatNumber($phoneNumber)
    {
        // If number contains an extension (denoted by a comma between the number and extension), then separate here and add later
        $phoneExtension = "";
        if (strpos($phoneNumber, ",") !== false) {
            list ($phoneNumber, $phoneExtension) = explode(",", $phoneNumber, 2);
        }
        // Remove all non-numerals
        $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
        // Prepend number with + for international use cases
        $phoneNumber = (isPhoneUS($phoneNumber) ? "+1" : "+") . $phoneNumber;
        // If has an extension, re-add it
        if ($phoneExtension != "") $phoneNumber .= ",$phoneExtension";
        // Return formatted number
        return $phoneNumber;
    }

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

}