<?php

/*
Dependencies:
PHPMailer, functions.misc.inc.php - see github.com/elliebre Repo: DiesundDas
*/

ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set("Europe/Berlin");

include_once('phpmailer/PHPMailerAutoload.php');
include_once('functions.misc.inc.php');

$C = array(
    'mail_to' => array(
        /*
         either one email address per row or an array containing one email address per row
         a not valid email address will be used as a headline (disabled option)
        eg:
        'mail@john.doe',
        array(
            'mail@john.doe',
            'mail@jane.doe',
        ),
         */
        'mail@john.doe',
    ),
    'mail_from_user' => 'Marcus Haase',
    'mail_from_email' => 'mail@haase-it.com',
    'mail_subject' => 'HTML Testmail - '.date("Y-m-d H:i:s"),
    'log_mails' => true,
);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="author" content="Marcus Haase, Haase IT">
    <title>Haase IT Mail Testscript</title>
</head>
<body>
<style type="text/css">
    body{
        background: #cfcfcf;
    }
    select option:disabled{
        background: #cfcfcf;
        color: #000;
    }
</style>
<?php
if (isset($_POST["action"]) && $_POST["action"] == 'send') {
    $sRandomstring = generateRandomString(6);
    if (isset($_POST["recipient"]) && array_key_exists($_POST["recipient"], $C["mail_to"])) {
        $mMailto = $C["mail_to"][$_POST["recipient"]];
    } else {
        $mMailto = $C["mail_to"][0];
    }
    if (1 == 1) {
        $mail = new PHPMailer;
        $mail->CharSet = 'UTF-8';

        $mail->isSendmail();

        $mail->From = $C["mail_from_email"];
        $mail->FromName = $C["mail_from_user"];
        if (is_array($mMailto)) {
            foreach ($mMailto as $sMailto) {
                $mail->addAddress($sMailto);
            }
        } else {
            $mail->addAddress($mMailto);
        }

        $mail->isHTML(true);

        $mail->Subject = $C["mail_subject"] . ' ' . $sRandomstring;
        $mail->Body = $_POST["mailcontent"];
        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        if (!$mail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $mail->ErrorInfo;
        } else {
            echo 'Mail versandt: ' . showClienttime() . '<br>An: ';
            if (is_array($C["mail_to"][$_POST["recipient"]])) {
                echo implode(', ', $C["mail_to"][$_POST["recipient"]]);
            } else {
                echo $C["mail_to"][$_POST["recipient"]];
            }
            echo '<br>ID: ' . $sRandomstring;
        }
    } else {
        mail_utf8($mMailto, $C["mail_from_user"], $C["mail_from_email"], $C["mail_subject"] . ' ' . $sRandomstring, $_POST["mailcontent"]);
        echo 'Mail versandt: ' . showClienttime() . '<br>An: ' . $C["mail_to"][$_POST["recipient"]] . '<br>ID: ' . $sRandomstring;
    }

    if (!is_blank($C["log_mails"]) && $C["log_mails"]) {
        // write to file
        $fp = fopen('./log/' . date("Y-m-d-H-i-s") . '-' . $sRandomstring . '.html', 'a');
        fwrite($fp, $_POST["mailcontent"]);
        fclose($fp);
    }
}
?>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
    <select name="recipient">
        <?php
        foreach ($C["mail_to"] as $sKey => $mValue) {
            echo '<option value="'.$sKey.'"'.($_POST["recipient"] == $sKey ? ' selected' : '').(is_array($mValue) || validateEmail($mValue) ? '' : ' disabled').'>';
            if (is_array($mValue)) {
                echo implode(', ', $mValue);
            } else {
                echo $mValue;
            }
            echo '</option>';
        }
        ?>
    </select><br>
    <textarea name="mailcontent" rows="40" cols="120"><?php
        $sMailcontent = getFormfield('mailcontent');
        if (isset($_POST["preservenbsp"]) && $_POST["preservenbsp"] == "yes") {
            $sMailcontent = mb_ereg_replace('&nbsp;', '&amp;nbsp;', $sMailcontent);
        }
        echo $sMailcontent;
        ?></textarea>
    <input type="hidden" name="action" value="send">
    <br>
    <input type="checkbox" name="preservenbsp" id="preservenbsp" value="yes"<?php echo (getCheckbox('preservenbsp', 'yes') ? ' checked' : '') ?>><label for="preservenbsp">Preserve &amp;nbsp;</label>
    <br>
    <input type="submit" value="Send">
</form>
<br>
<?php if (!is_blank($C["log_mails"]) && $C["log_mails"]) { ?><a href="log/" target="_blank">Log des Mailversands</a><?php } ?>
</body>
