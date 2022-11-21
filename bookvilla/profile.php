<?php
session_start();

require_once("includes/helpers.php");

LoginRequired();

require_once("includes/config.php");
require_once("includes/connection.php");


if(isset($_POST["settings"])){
    if(!isset($_SESSION["CSRF-PROFILE"]) || !isset($_POST["csrf-token"]) || empty($_POST["csrf-token"])){
        $_SESSION["main-message"] = "Security error, tryagain later.";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: profile.php"));
    }
    $currentCsrf = $_SESSION["CSRF-PROFILE"];

    if(getSHA1($currentCsrf[0]) !== $_POST["csrf-token"]){
        $_SESSION["main-message"] = "Security error, tryagain later.";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: profile.php"));
    }

    $settings = $_POST["settings"];

    if($settings === "UPDATE-NAME"){
        if(!isset($_POST["name"]) || empty($_POST["name"])){
            $_SESSION["main-message"] = "Unable to change your name with empty data!";
            $_SESSION["main-message-type"] = "abnormal";
            die(header("Location: profile.php"));
        }
        $name = htmlentities($_POST["name"]);

        if(strlen($name) < SIGNIN_CONFIG["NAME_MIN_LEN"] || strlen($name) > SIGNIN_CONFIG["NAME_MAX_LEN"]){
            $_SESSION["main-message"] = "Your name length must be between ".SIGNIN_CONFIG["NAME_MIN_LEN"]." and ".SIGNIN_CONFIG["NAME_MAX_LEN"]."!";
            $_SESSION["main-message-type"] = "abnormal";
            die(header("Location: profile.php"));
        }

        $connection = getConnection();
        $sql = $connection -> prepare("UPDATE users SET u_name=? WHERE uuid=?");
        $sql -> bind_param("ss",$name, $_SESSION["USER-UUID"]);

        $res = $sql -> execute();

        closeConnection($connection);

        if($res !== TRUE){
            $_SESSION["main-message"] = "Something went wrong, tryagain later!";
            $_SESSION["main-message-type"] = "abnormal";
            die(header("Location: profile.php"));
        }

        $_SESSION["main-message"] = "Your name changed successfully.";
        $_SESSION["main-message-type"] = "normal";
        die(header("Location: profile.php"));
    }
    else if($settings === "UPDATE-EMAIL"){
        if(!isset($_POST["email"]) || empty($_POST["email"]) || !preg_match(SIGNIN_CONFIG["EMAIL_REGEX"], $_POST["email"]) || !isset($_POST["password"])){
            $_SESSION["main-message"] = "Please enter a valid email address to change it!";
            $_SESSION["main-message-type"] = "abnormal";
            die(header("Location: profile.php"));
        }
        $email = $_POST["email"];
        $password = $_POST["password"];
        $hashed = hash("sha256", $password);
        $uuid = $_SESSION["USER-UUID"];

        $connection = getConnection();
        
        $sql = $connection -> prepare("SELECT COUNT(*) FROM users WHERE uuid=? AND u_password=?");
        $sql -> bind_param("ss", $uuid, $hashed);

        $sql -> execute();

        $result_user = $sql -> get_result();
        $user_count = $result_user -> fetch_row()[0];
        if($user_count < 1){
            closeConnection($connection);
            $_SESSION["main-message"] = "Enter correct password to change your email!";
            $_SESSION["main-message-type"] = "abnormal";
            die(header("Location: profile.php"));
        }

        $sql = $connection -> prepare("SELECT COUNT(*) FROM users WHERE BINARY u_email=?");

        $sql -> bind_param("s", $email);
        $sql -> execute();

        $result = $sql -> get_result();
        $data_in = $result -> fetch_row()[0];

        if($data_in > 0){
            $_SESSION["main-message"] = "Email is already registered with another account!";
            $_SESSION["main-message-type"] = "abnormal";
            die(header("Location: profile.php"));
        }

        $sql = $connection -> prepare("UPDATE users SET u_email=? WHERE uuid=?");
        $sql -> bind_param("ss",$email, $_SESSION["USER-UUID"]);

        $res = $sql -> execute();

        closeConnection($connection);

        if($res !== TRUE){
            $_SESSION["main-message"] = "Something went wrong, tryagain later!";
            $_SESSION["main-message-type"] = "abnormal";
            die(header("Location: profile.php"));
        }

        $_SESSION["main-message"] = "Your email changed successfully.";
        $_SESSION["main-message-type"] = "normal";
        die(header("Location: profile.php"));
    }
    else if($settings === "SIGNOUT-CURRENT"){
        die(header("Location: signout.php"));
    }
    else if($settings === "SIGNOUT-ALL"){
        date_default_timezone_set("Asia/Kolkata");
        $current_date = date("Y-m-d H:i:s");

        $connection = getConnection();
        $sql = $connection -> prepare("UPDATE users SET signin_id=? WHERE uuid=?");
        $sql -> bind_param("ss",$current_date, $_SESSION["USER-UUID"]);

        $res = $sql -> execute();

        closeConnection($connection);

        if($res !== TRUE){
            $_SESSION["main-message"] = "Something went wrong, tryagain later!";
            $_SESSION["main-message-type"] = "abnormal";
            die(header("Location: profile.php"));
        }

        die(header("Location: signout.php"));
    }
    else if($settings === "DELETE-ACCOUNT"){
        die(header("Location: delete_account.php"));
    
    } else {
        $_SESSION["main-message"] = "Something went wrong, tryagain laterj!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: profile.php"));
        die;
    }
}

$connection = getConnection();

$sql = $connection->prepare("SELECT u_name, u_email, email_confirmed_at FROM users WHERE uuid=? LIMIT 1");
$sql->bind_param("s", $_SESSION["USER-UUID"]);

$res = $sql->execute();
if (!$res) {
    closeConnection($connection);

    $_SESSION["main-message-type"] = "abnormal";
    $_SESSION["main-message"] = "Unable to fetch your account information, tryagain later.";

    die(header("Location: home.php"));
}

$result = $sql->get_result();

if ($result->num_rows <= 0) {
    closeConnection($connection);
    $_SESSION["main-message-type"] = "abnormal";
    $_SESSION["main-message"] = "Unable to fetch your account information, account deleted or suspended.";

    die(header("Location: signout.php"));
}

$data = $result->fetch_row();
$sql->close();

closeConnection($connection);

$_SESSION["CSRF-PROFILE"] = generate_csrf();

$PageTitle = "Profile";
$stylesheets = array("static/css/profile.css");
include_once("includes/header.php");
?>

<div class="flex-row">
    <div class="section" id="profile-nav">
        <div class="nav">
            <a href="#" class="header-in">Profile</a>
            <a href="#personal-details">Personal Details</a>
            <a href="#email-settings">Email settings</a>
            <a href="#security-tab">Security</a>
        </div>
    </div>

    <div class="section" id="profile-view">
        <div id="personal-details">
            <form action="profile.php" class="form-w" method="POST">
                <div class="header-in">Personal Details</div>
                <input type="hidden" name="csrf-token" value="<?= $_SESSION["CSRF-PROFILE"][1] ?>">
                <div class="label">
                    <label for="name">Your name</label>
                    <input type="text" name="name" id="name" value="<?= $data[0] ?>" />
                </div>
                <button type="submit" name="settings" value="UPDATE-NAME">Update name</button>
            </form>
        </div>

        <div id="email-settings">
            <form action="profile.php" class="form-w" method="post">
                <div class="header-in">Email</div>
                <input type="hidden" name="csrf-token" value="<?= $_SESSION["CSRF-PROFILE"][1] ?>">
                <div class="label">
                    <label for="email">Your email</label>
                    <input type="email" name="email" id="email" value="<?= $data[1] ?> " required />
                    <small>*Make sure you enter a valid email.</small>
                </div>
                <div class="label">
                    <label for="password">Your password</label>
                    <input type="password" name="password" id="password" required />
                </div>
                <button type="submit" name="settings" value="UPDATE-EMAIL">Update email</button>
                
            </form>
        </div>

        <div id="security-tab">
            <form action="profile.php" class="form-w" method="post">
                <div class="header-in">Security Settings</div>
                <input type="hidden" name="csrf-token" value="<?= $_SESSION["CSRF-PROFILE"][1] ?>">
                
                <div class="flex-l-a">
                    <div class="label">
                        <div class="header-in1">Signout from this device</div>
                        <div>This will signout from the current device you are loggedin.</div>
                    </div>
                    <button class="btn-in-1" type="submit" name="settings" value="SIGNOUT-CURRENT">Signout</button>
                </div>

                <div class="flex-l-a">
                    <div class="label">
                        <div class="header-in1">Signout form all devices</div>
                        <div>This will signout from all devices you are loggedin.</div>
                    </div>
                    <button class="btn-in-1" type="submit" name="settings" value="SIGNOUT-ALL">Signout</button>
                </div>

                <div class="flex-l-a">
                    <div class="label">
                        <div class="header-in1">Delete account</div>
                        <div>This action will delete your account, deleting account will delete your uploaded books, saved and other details. This cannot be reversed.</div>
                    </div>
                    
                    <button class="btn-in-1" type="submit" name="settings" value="DELETE-ACCOUNT">Delete account</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script src="<?= url_for("static/js/profile.js") ?>"></script>

<?php
include_once("includes/footer_full.php");
?>