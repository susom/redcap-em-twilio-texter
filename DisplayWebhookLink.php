<?php

namespace Stanford\RepeatingSurveyPortal;

use REDCap;

/** @var \Stanford\RepeatingSurveyPortal\RepeatingSurveyPortal $module */

$url = $module->getUrl('HandleTextReceipt.php', true, true);
echo "<br><br>This is the Webhook Link to enter in Twilio's webhook: <br>".$url;
