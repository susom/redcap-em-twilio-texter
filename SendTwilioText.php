<?php

namespace Stanford\TwilioTexter;
/** @var \Stanford\TwilioTexter\TwilioTexter $module */

use Plugin;
use REDCap;


if (!empty($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case "send":
            $phone_numbers = explode(',',  $_POST["phone"]);
            $message       = $_POST["message"];

            $sent = array();   //hold sent numbers for reporting
            $unsent = array();

            foreach ($phone_numbers as $phone) {

                $status = $module->emText($phone, $message);

                if ($status === true) {

                    $sent[] = $phone;

                } else {
                    $unsent[] = $phone;
                    $msg = "Error sending text sent to $phone: ".$message. " ERROR: ".$status;
                    REDCap::logEvent("TwilioTexter Module", $msg);

                }
            }

            //report the successes to logging
            if (!empty($sent)) {
                $msg = "Text sent to ".implode(' ,',$sent)." with message:  ".$message;
                REDCap::logEvent("TwilioTexter Module", $msg);
            }

            if (empty($unsent)) {
                $result = array('result' => 'success');
            } else {

                $module->emDebug("FAILED send to ".implode(' ,',$unsent));
                $result = array(
                    'result' => 'fail',
                    'error' => "Unable to send to these numbers: ".implode(' ,',$unsent));
            }

            header('Content-Type: application/json');
            print json_encode($result);
            exit();
            break;
        default:
            print "Unknown action";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $module->getModuleName() ?></title>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" type="text/css" media="screen"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png"
          href="<?php print $module->getUrl("favicon/stanford_favicon.ico", false, true) ?>">

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="<?php print $module->getUrl("js/jquery-3.2.1.min.js", false, true) ?>"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'></script>

</head>
<body>
<div class="container">
    <hr>
    <h2>Sending text from this Twilio number : <?php echo $module->getNumber();?></h2>
    <hr>

    <div class="form-row">
        <div class="form-group col-md-4">
            <label for="stanford_mrn">Phone Number </label>
            <input type="text" class="form-control" id="phone_number" name="phone_number" placeholder="Comma separated list of phone numbers">
        </div>
        <div class="form-group col-md-6">
            <label for="last_name">Text Message</label>
            <textarea class="form-control" id="message" name="message"  rows="5" cols="40"></textarea>
        </div>

    </div>

    <button class="btn btn-primary" name="submit" onclick="submit()">SEND TEXT</button>
</div>
</body>
</html>


<script type="text/javascript">

    function submit() {

        var saveBtn = $('button[name="submit"]');
        var phone = $('input[name="phone_number"]');
        var message = $('#message');


        var data = {
            "action"  : "send",
            "phone"   : phone.val(),
            "message" : message.val()
        };
        $.ajax({
            method: 'POST',
            data: data,
            dataType: "json"
        })
            .done(function (data) {
                if (data.result === 'success') {
                    alert("Text was successfully sent to "+phone.val());
                    phone.val('');
                    message.val('');
                } else {
                    // an error occurred
                    console.log(data);
                    alert("ERROR: " + data.error);
                }

            })
            .fail(function (data) {
                console.log(data);
                alert(data.result + "Unable to send " + data.error + data.message );
            })
            .always(function () {
                saveBtn.prop('disabled', false);
            });
    };


</script>
