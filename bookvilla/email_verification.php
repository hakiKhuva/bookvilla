<?php

require_once("includes/helpers.php");
require_once("includes/connection.php");

if(!isset($_GET["uid"]) || empty($_GET["uid"])){
    die(header("location: register.php"));
}

$uid = getSHA1($_GET["uid"]);

$connection = getConnection();

$sql = $connection -> prepare("SELECT uuid, email_confirmed_at, expire_at FROM users WHERE BINARY confirm_token=?");
$sql -> bind_param("s", $uid);

$res = $sql -> execute();

if($res === TRUE){
    $result = $sql -> get_result();
    if($result->num_rows > 0){
        
        $data = $result -> fetch_row();

        $sql -> close();

        $uuid = $data[0];
        $email_confirmed_at = $data[1];
        $expire_at = $data[2];

        if($email_confirmed_at !== NULL){
            $message = "This link is already used to verify the email!";
        } else {
            date_default_timezone_set("Asia/Kolkata");
            $current_time = date("Y-m-d H:i:s");

            if(strtotime($current_time) < strtotime($expire_at)){
                $sql = $connection -> prepare("UPDATE users SET email_confirmed_at=?, signin_id=? WHERE BINARY uuid=?");
                $sql -> bind_param("sss", $current_time, $current_time ,$uuid);
                $res = $sql -> execute();

                if($res === TRUE){
                    $message = "Email is verified successfully.";
                } else {
                    $message = "Something went wrong while verifying your email, tryagain later!";
                }

                $sql -> close();
            } else {
                $message = "Verification link is expired!";
            }
        }

    } else {
        $message = "Unable to confirm email, user not found with passed email!";
    }
    
} else {
    $message = "Something went wrong while verifying email address, tryagain later.";
}

closeConnection($connection);
?>

<?php

// setting header parameters
$PageTitle = "Email verification";
$stylesheets = array("static/css/email_verification.css");

include_once("includes/header.php");

?>

<div class="wide-bg">
    <header>Email Verification</header>
    <div class="message">
        <p class="msg"><?php if(isset($message)){ echo $message;} ?></p>
    </div>
</div>

<?php include_once("includes/footer.php") ?>
