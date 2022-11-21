<?php
// starting session
session_start();

// including files
require_once("includes/helpers.php");
require_once("includes/config.php");
require_once("includes/connection.php");
require_once("includes/email_sender.php");


// redirect to home if loggedin
RedirectIfLoggedin();

// setting CSRF to SESSION if Request Method is GET
if($_SERVER["REQUEST_METHOD"] === "GET"){
    $_SESSION["CSRF-TOKEN"] = generate_csrf();
}

// POST method functions
if($_SERVER["REQUEST_METHOD"] === "POST"){
    // getting data
    $u_csrf = $_POST["csrf-token"];
    $s_csrf = $_SESSION["CSRF-TOKEN"][0];
    $uemail = $_POST["email"];

    // setting timezone IST
    date_default_timezone_set("Asia/Kolkata");

    // checking if any data is empty
    if(!isset($uemail) || !isset($u_csrf) || empty($u_csrf) || empty($s_csrf) || empty($uemail)){
        $_SESSION["main_message"] = "All fields are required!";
        die(header("location: forgot_password.php"));
    }

    $uemail = htmlentities($uemail);

    // matching CSRF token
    if($u_csrf !== getSHA1($s_csrf)){
        $_SESSION["main_message"] = "Something went wrong, tryagain later.";
        die(header("location: forgot_password.php"));
    }

    // if invalid email
    if(!preg_match(SIGNIN_CONFIG["EMAIL_REGEX"],$uemail)){
        $_SESSION["main_message"] = "Please enter a valid email!";
        die(header("location: forgot_password.php"));
    }

    // getting db connection
    $connection = getConnection();

    // getting user data from users table
    $sql = $connection -> prepare("SELECT id, u_name, uuid, email_confirmed_at FROM users WHERE BINARY u_email=?");
    $sql -> bind_param("s", $uemail);

    // executing query
    $res = $sql -> execute();

    if($res !== TRUE){
        // if not executed
        $sql -> close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Something went wrong, tryagain later!";
        die(header("location: forgot_password.php"));
    }

    // getting result from users table
    $result = $sql->get_result();

    // if user doesn't exists
    if($result->num_rows < 1){
        $sql -> close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Account not found associated with entered email!";
        die(header("location: forgot_password.php"));
    }

    // getting row data
    $data = $result -> fetch_row();
    $id = $data[0];
    $u_name = $data[1];
    $uuid = $data[2];
    $email_confirmed_at = $data[3];

    // closing the query
    $sql -> close();

    // if email is not confirmed yet
    if($email_confirmed_at === NULL){
        closeConnection($connection);
        $_SESSION["main_message"] = "Please confirm the email before trying to reset the password!";
        die(header("location: forgot_password.php"));
    }

    // getting record from reset password table
    // if user has requested before 
    $pwdSql = $connection -> prepare("SELECT id, expire_at FROM user_password_reset WHERE BINARY uuid=? ORDER BY expire_at DESC LIMIT 1");
    $pwdSql -> bind_param("s",$uuid);

    // executing query
    $res = $pwdSql -> execute();
    
    // if not executed
    if($res !== TRUE){
        $pwdSql -> close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Something went wrong, tryagain later!";
        die(header("location: forgot_password.php"));
    }

    // getting result
    $result = $pwdSql->get_result();

    // if result found
    if($result->num_rows > 0){
        // getting row
        $data = $result -> fetch_row();
        $reset_id = $data[0];
        $reset_expired_at = $data[1];

        // closing password query
        $pwdSql -> close();

        // getting current timestamp
        $current_timestamp = strtotime(date("Y-m-d H:i:s"));
        // converting expire_time to timestamp
        $reset_expired_at_timestamp = strtotime($reset_expired_at);

        // if link is not expired then it won't be sent
        if($reset_expired_at_timestamp > $current_timestamp){
            closeConnection($connection);
            $_SESSION["main_message"] = "An email containing password reset link already sent, you can request for a new link after $reset_expired_at";
            die(header("location: forgot_password.php"));
        }
    } else {
        // closing the query
        $pwdSql -> close();
    }

    // generating password reset token
    $password_reset_token = generate_verification_token();
    $password_reset_token_hashed = getSHA1($password_reset_token);
    // getting current time and expire time
    $current_time = date("Y-m-d H:i:s");
    $expire_timestamp = strtotime("+".SIGNIN_CONFIG["EMAIL_CODE_EXPIRE_TIME"], strtotime($current_time));
    $expire_time = date("Y-m-d H:i:s", $expire_timestamp);

    // closing connection
    closeConnection($connection);

    // sending reset password link throught email
    $_SESSION["main_message"] = "Something went wrong when sending email, tryagain later.";
    send_password_reset_link($uemail, $u_name, $password_reset_token) or die(header("location: forgot_password.php"));

    // new connection
    $connection = getConnection();

    // inserting record for new password reset
    $sql = $connection -> prepare("INSERT INTO user_password_reset(uuid, reset_token, expire_at) VALUES(?,?,?)");
    $sql -> bind_param("sss", $uuid, $password_reset_token_hashed, $expire_time);

    // executing query
    $res = $sql -> execute();

    // closing
    $sql -> close();

    // if query not executed
    if($res !== TRUE){
        closeConnection($connection);
        $_SESSION["main_message"] = "Something went wrong when sending email, tryagain later.";
        die(header("location: forgot_password.php"));
    }
    // closing the connection
    closeConnection($connection);

    $_SESSION["main_message"] = "Password reset link has been sent on your email.";
    die(header("location: forgot_password.php"));
}

?>

<?php

// setting header parameters
$PageTitle = "Forgot password";
$stylesheets = array("static/css/signin.css");

include("includes/header.php");

?>

<form class="form" method="POST" action="forgot_password.php">
    <header class="header">Forgot Password</header>
    
    <div class="message-main">
        <?php
            $message = printFlashMessage("main_message");
            if(!empty($message)){ echo "<p class='warn'>".$message."</p>"; }
        ?>
    </div>

    <input type="hidden" name="csrf-token"  value="<?= $_SESSION["CSRF-TOKEN"][1] ?>" />

    <div class="label">
        <label for="email">Your email</label>
        <input type="email" name="email" id="email" placeholder="Email here" value="<?= printFlashMessage("email") ?>"/>
    </div>

    <button type="submit">Send password reset link</button>

    <div class="form-act">
        <a class="link" href="<?= url_for("signin.php") ?>">Signin to your account</a>
    </div>
</form>

<?php include("includes/footer.php") ?>