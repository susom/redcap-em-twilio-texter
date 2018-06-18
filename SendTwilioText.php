<?php

namespace Stanford\TwilioTexter;
/** @var \Stanford\TwilioTexter\TwilioTexter $module */

use Plugin;
use REDCap;


if (!empty($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case "send":
            $phone = $_POST["phone"];
            $message = $_POST["message"];

            $status = $module->sendText($phone, $message);

            if ($status === true) {
                $result = array('result' => 'success');
                $msg = "Text sent to $phone: ".$message;
                REDCap::logEvent("TwilioTexter Module", $msg);
            } else {
                $msg = "Error sending text sent to $phone: ".$message. " ERROR: ".$status;
                REDCap::logEvent("TwilioTexter Module", $msg);
                $result = array(
                    'result' => 'fail',
                    'error' => $status);
            }

            header('Content-Type: application/json');
            print json_encode($result);
            exit();
            break;
        default:
            Plugin::log($_POST, "Unknown Action in Save");
            print "Unknown action";
    }
}

?>
<!DOCTYPE html>
<html>
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


    <!-- Add local css and js for module -->
</head>
<body>
<div class="container">
    <div class="jumbotron">
        <h3>Send Text from this Twilio number: <?php echo $module->getProjectSetting("twilio-number");
            ?></h3>
    </div>
    <div class="well">
        <div class="container">
            <div class="form-group">
                <div class='input-group date'>
                    <span class="input-group-addon">phone number</span>
                    <input name="phone_number" type='tel' class="form-control"/>
                </div>

            </div>
            <textarea id="message" name="message" rows="5" cols="40"></textarea>
        </div>
    </div>
</div>

<div class="panel-footer">
    <button class="btn btn-primary" name="submit" onclick="submit()">SEND TEXT</button>
</div>


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
                    alert("Error texting to " +phone.val()+" \n\nERROR: " + data.error);
                }

            })
            .fail(function (data) {
                console.log(data);
                alert(data.result + "Unable to send <br><br>" + data.error + data.message );
            })
            .always(function () {
                saveBtn.prop('disabled', false);
            });
    };


</script>
