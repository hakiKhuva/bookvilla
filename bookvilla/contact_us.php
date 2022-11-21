<?php
session_start();

require_once("includes/helpers.php");

LoginRequired();

require_once("includes/connection.php");
require_once("includes/config.php");

if($_SERVER["REQUEST_METHOD"] === "POST"){
    if(!isset($_SESSION["CONTACT-FORM-CSRF"]) || !isset($_POST["csrf"])){
        $_SESSION["main-message"] = "Something went wrong, tryagain.";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: contact_us.php"));
    }

    $csrf = getSHA1($_SESSION["CONTACT-FORM-CSRF"][0]);
    $csrf_form = $_POST["csrf"];

    if($csrf !== $csrf_form){
        $_SESSION["main-message"] = "Something went wrong, tryagain.";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: contact_us.php"));
    }

    $subject = $_POST["subject"];
    $message = $_POST["message"];

    if(!isset($subject) || !isset($message)){
        $_SESSION["main-message"] = "All fields are required!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: contact_us.php"));
    }

    $min_len_sub = CONTACT_FORM_CONFIG["SUBJECT_LENGTH"][0];
    $max_len_sub = CONTACT_FORM_CONFIG["SUBJECT_LENGTH"][1];

    if(strlen($subject) < $min_len_sub || strlen($subject) > $max_len_sub ){
        $_SESSION["main-message"] = "Subject length must be between $min_len_sub and $max_len_sub!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: contact_us.php"));
    }

    $min_len_message = CONTACT_FORM_CONFIG["MESSAGE_LENGTH"][0];
    $max_len_message = CONTACT_FORM_CONFIG["MESSAGE_LENGTH"][1];

    if(strlen($message) < $min_len_message || strlen($message) > $max_len_message ){
        $_SESSION["main-message"] = "Message length must be between $min_len_message and $max_len_message!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: contact_us.php"));
    }

    $subject = htmlentities($subject);
    $message = htmlentities($message);

    $connection = getConnection();

    $sql = $connection -> prepare("SELECT u_name, u_email FROM users WHERE uuid=?");
    $sql -> bind_param("s", $_SESSION["USER-UUID"]);

    $sql -> execute();

    $result = $sql -> get_result();
    $count = $result -> num_rows;

    if($count < 1){
        $_SESSION["main-message"] = "Contact form dismissed!";
        $_SESSION["main-message-type"] = "abnormal";
        closeConnection($connection);
        die(header("Location: contact_us.php"));
    }

    $data = $result -> fetch_assoc();
    $u_name = $data["u_name"];
    $u_email = $data["u_email"];

    $sql = $connection -> prepare("INSERT INTO contact_forms(u_name, u_email, subject, message) VALUES (?,?,?,?)");
    $sql -> bind_param("ssss",$u_name, $u_email, $subject, $message);

    $resp = $sql -> execute();

    if($resp === TRUE){
        $_SESSION["main-message"] = "Your form was submitted successfully.";
        $_SESSION["main-message-type"] = "normal";
    } else {
        $_SESSION["main-message"] = "Unable to submit your request!";
        $_SESSION["main-message-type"] = "normal";
    }

    closeConnection($connection);
    die(header("Location: contact_us.php"));
}

$_SESSION["CONTACT-FORM-CSRF"] = generate_csrf();

$PageTitle = "Contact us";
require_once("includes/header.php");

?>
<form action="contact_us.php" method="POST" class="form-w" style="width: 650px; margin: auto;">
    <div class="header">Contact us</div>

    <input type="hidden" name="csrf" value="<?= $_SESSION["CONTACT-FORM-CSRF"][1] ?>" required />

    <div class="label">
        <label for="subject">Subject</label>
        <input type="text" name="subject" id="subject" style="font-size: 16.5px;" required placeholder="Subject" />
    </div>

    <div class="label">
        <label for="message">Your Message</label>
        <textarea name="message" style="resize: none;" class="input" id="message" style="font-size: 16.5px;" cols="30" rows="20" style="resize: none;" placeholder="Your message"></textarea>
    </div>

    <div class="label">
        <p>*Your registered name and your registered email will be recorded and by deleting your account this data won't be deleted.</p>
    </div>

    <button type="submit">Submit</button>
</form>

<?php 
require_once("includes/footer_full.php");
?>