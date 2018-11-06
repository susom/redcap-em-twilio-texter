<?php
namespace Stanford\TwilioTexter;

use REDCap;

/**
 *
 * This is called from Twilio Webhook set up from the calling number
 */

/** @var \Stanford\TwilioTexter\TwilioTexter $module */

$module->emDebug('--- Incoming Text to Twilio ---');

$webhook = $module->getUrl("HandleTextReceipt.php", true, true);
$module->emDebug($webhook);

$phone_field = $module->getProjectSetting('phone-lookup-field');
$phone_field_event = $module->getProjectSetting('phone-lookup-field-event');

// Get the phone number to search REDCap
$from = $_POST['from'];

if (!isset($from)) {
    $module->emLog("No phone number reported");
    exit;
}
$from_10 = substr($from, -10);

// Get the body of the message
$body = isset($_POST['Body']) ? $_POST['Body'] : '';

//use the phone number to look for the record id
$rec_id = $module->findRecordByPhone($module->formatToREDCapNumber($from_10), $phone_field, $phone_field_event);
$module->emDebug("Rec ID is $rec_id");

if (empty($body)) {
  $module->emDebug(json_encode($_POST), "Received incoming text without a body: ". $from_10);
  exit();
}

//Looks like there is no record affiliated with that phone number
if (!$rec_id) {
    $module->emLog($body, "Received incoming text from unknown number: " . $from_10);

    // email coordinator to let them know of text from unaffiliated number
    $to =$module->getProjectSetting('email-to');
    $from = $module->getProjectSetting('email-from');
    $subject = $module->getProjectSetting('forwarding-email-subject');
    $msg = "We have received a text from a phone number that is not in the project: " . $from_10 . ".\n" .
        "BODY OF TEXT: ".$body ;

    $module->sendEmail($to, $from, $subject, $msg);

    exit();
}

$module->emLog("Text from phone " . $from_10 . " with entry: " . $body. " forwarding to ".
    $module->getProjectSetting('email-to'));

//TODO: should this be logged or emailed?  Email for now
    // email coordinator to let them know
$to =$module->getProjectSetting('email-to');
$from = $module->getProjectSetting('email-from');
$subject = $module->getProjectSetting('forwarding-email-subject');
$msg = "We have received a text from phone number: " . $from_10 . ".\n" .
    "BODY OF TEXT: ".$body ;

$module->sendEmail($to, $from, $subject, $msg);

//If log field is specified, log to REDCap
$log_field = $module->getProjectSetting('log-field');
$log_event = $module->getProjectSetting('log-field-event');
$log_event_name = REDCap::getEventNames(true, false, $log_event);


if (isset($log_field)) {
    $module->logSms($log_field, $log_event_name, $rec_id, $msg );
    $module->emDebug($rec_id, $msg);
}
