<?php

namespace Stanford\TwilioTexter;

use REDCap;

/** @var \Stanford\TwilioTexter\TwilioTexter $module */

$url = $module->getUrl('HandleTextReceipt.php', true, true);
echo "<br><br>This is the Webhook Link to enter in Twilio's webhook: <br>".$url;
