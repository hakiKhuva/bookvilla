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

if(!isset($_REQUEST["uid"]) || empty($_REQUEST["uid"])){
    $_SESSION["main_message"] = "Reset password token not found!";
    die(header("location: signin.php"));
}

$uid = $_REQUEST["uid"];

// setting timezone IST
date_default_timezone_set("Asia/Kolkata");

// setting CSRF to SESSION if Request Method is GET
if($_SERVER["REQUEST_METHOD"] === "GET"){
    $_SESSION["CSRF-TOKEN"] = generate_csrf();

    $connection = getConnection();

    // getting record of reset token
    $sql = $connection -> prepare("SELECT uuid, expire_at, used_at FROM user_password_reset WHERE reset_token=?");
    // generating SHA1 hash of token
    $hashed_uid = getSHA1($uid);
    $sql -> bind_param("s", $hashed_uid);

    // execute query
    $res = $sql -> execute();

    // if query is not executed
    if($res !== TRUE){
        $sql->close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Something went wrong, tryagain later!";
        die(header("location: signin.php"));
    }

    // getting result
    $result = $sql -> get_result();

    // if number of rows are less than 1
    // generally it's 1 but for checking used 1
    if($result->num_rows < 1){
        $sql->close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Reset password token doesn't exists!";
        die(header("location: signin.php"));
    }

    // getting row from the result
    $data = $result -> fetch_row();
    // setting data to its name variable
    $uuid = $data[0];
    $expire_at = $data[1];
    $used_at = $data[2];

    // closing the query
    $sql->close();
    // closing the connection
    closeConnection($connection);
    
    // if the token isn't used then it will be NULL
    // checking is link used
    if($used_at !== NULL){
        $_SESSION["main_message"] = "Password reset link is already used!";
        die(header("location: signin.php"));
    }

    // getting timestamp
    $expire_at_timestamp = strtotime($expire_at);
    $current_timestamp = strtotime(date("Y-m-d H:i:s"));

    // checking is link expired or not
    if($current_timestamp > $expire_at_timestamp){
        $_SESSION["main_message"] = "Password reset link is expired!";
        die(header("location: signin.php"));
    }

    // setting current token to reset_token variable
    // this token is not hashed: contains original value
    $reset_token = $uid;
}

// POST method functions
if($_SERVER["REQUEST_METHOD"] === "POST"){
    // getting data
    $u_csrf = $_POST["csrf-token"];
    $s_csrf = $_SESSION["CSRF-TOKEN"][0];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm-password"];
    $reset_token = $_POST["reset-token"];


    // checking if any data is empty
    if(empty($u_csrf) || empty($s_csrf) || empty($password) || empty($confirm_password) || empty($reset_token)){
        $_SESSION["main_message"] = "All fields are required!";
        die(header("location: reset_password.php?uid=$reset_token"));
    }

    // matching CSRF token
    if($u_csrf !== getSHA1($s_csrf)){
        $_SESSION["main_message"] = "Something went wrong, tryagain later.";
        die(header("location: reset_password.php?uid=$reset_token"));
    }

    // if password not matched with another password
    if($password !== $confirm_password){
        $_SESSION["main_message"] = "Password confirmation doesn't match!";
        die(header("location: reset_password.php?uid=$reset_token"));
    }

    // checking password length
    if(strlen($password) < SIGNIN_CONFIG["PASSWORD_MIN_LEN"]){
        $_SESSION["main_message"] = "Your password must contain atleast ".SIGNIN_CONFIG["PASSWORD_MIN_LEN"]." characters.";
        die(header("location: reset_password.php?uid=$reset_token"));
    }

    $connection = getConnection();

    // same as GET Request
    // getting the record contains reset token
    $sql = $connection -> prepare("SELECT uuid, expire_at, used_at FROM user_password_reset WHERE reset_token=?");
    $hashed_token = getSHA1($reset_token);
    $sql -> bind_param("s", $hashed_token);

    // executing query
    $res = $sql -> execute();

    // if not executed
    if($res !== TRUE){
        $sql->close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Something went wrong, tryagain later!";
        die(header("location: reset_password.php?uid=$reset_token"));
    }

    // getting result
    $result = $sql -> get_result();

    // if result's number of rows less than 1
    if($result->num_rows < 1){
        $sql->close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Reset password token doesn't exists!";
        die(header("location: signin.php"));
    }

    // getting row from result
    $data = $result -> fetch_row();
    $uuid = $data[0];
    $expire_at = $data[1];
    $used_at = $data[2];

    // closing the query
    $sql->close();

    // if the token isn't used then it will be NULL
    // checking is link used
    if($used_at !== NULL){
        closeConnection($connection);
        $_SESSION["main_message"] = "Password reset link is already used!";
        die(header("location: signin.php"));
    }

    // getting timestamp
    $expire_at_timestamp = strtotime($expire_at);
    $current_timestamp = strtotime(date("Y-m-d H:i:s"));

    // checking is link expired or not
    if($current_timestamp > $expire_at_timestamp){
        closeConnection($connection);
        $_SESSION["main_message"] = "Password reset link is expired!";
        die(header("location: signin.php"));
    }

    // update query
    $sql = $connection -> prepare("UPDATE user_password_reset SET used_at=? WHERE reset_token=?");
    $hashed_token = getSHA1($reset_token);
    $current_time = date("Y-m-d H:i:s");
    $sql->bind_param("ss", $current_time, $hashed_token);

    // execute query
    $res = $sql -> execute();

    // closing the query
    $sql->close();

    // if not executed
    if($res !== TRUE){
        closeConnection($connection);
        $_SESSION["main_message"] = "Something went wrong, tryagain later!";
        die(header("location: reset_password.php?uid=$reset_token"));
    }

    // hashing password to SHA256
    $hashed_password = getSHA256($password);
    // updating users table
    // updating u_password field
    $sql = $connection -> prepare("UPDATE users SET u_password=? WHERE uuid=?");
    $sql -> bind_param("ss", $hashed_password, $uuid);

    // execute query
    $res = $sql -> execute();

    // closing the query
    $sql->close();

    // if not executed
    if($res !== TRUE){
        closeConnection($connection);
        $_SESSION["main_message"] = "Something went wrong while updating password, tryagain later using new reset password link.";
        die(header("location: reset_password.php?uid=$reset_token"));
    }

    // closing the connection
    closeConnection($connection);
    $_SESSION["main_message"] = "Password changed successfully.";
    die(header("location: signin.php"));
}

?>

<?php

// setting header parameters
$PageTitle = "Reset account password";
$stylesheets = array("static/css/signin.css");

include("includes/header.php");

?>

<form class="form" method="POST" action="reset_password.php?uid=<?= $reset_token ?>">
    <header class="header">Reset Password</header>
    
    <div class="message-main">
        <?php
            $message = printFlashMessage("main_message");
            if(!empty($message)){ echo "<p class='warn'>".$message."</p>"; }
        ?>
    </div>

    <input type="hidden" name="csrf-token" value="<?= $_SESSION["CSRF-TOKEN"][1] ?>" />
    <input type="hidden" name="reset-token" value="<?= $reset_token ?>" />

    <div class="label">
        <label for="password">New Password</label>
        <input type="password" name="password" id="password" required="" placeholder="New Password here" />
    </div>

    <div class="label">
        <label for="confirm-password">Your Password</label>
        <input type="password" name="confirm-password" id="confirm-password" required="" placeholder="Confirm Password here" />
    </div>

    <button type="submit">Reset Password</button>

</form>

<?php include("includes/footer.php") ?>