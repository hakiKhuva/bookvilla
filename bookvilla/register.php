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
    // Getting all fields value
    $form_csrf = $_POST["csrf-token"];
    $session_csrf = $_SESSION["CSRF-TOKEN"][0];
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["conf-password"];
    
    // if any null values or empty values found then redirect to register.php
    if(!isset($form_csrf) || !isset($session_csrf) || !isset($name) || !isset($email) || !isset($password) || !isset($confirm_password)){
        $_SESSION["main_message"] = "All fields are required!";
        die(header("location:register.php"));
    }

    if(empty($form_csrf) || empty($session_csrf) || empty($name) || empty($email) || empty($password) || empty($confirm_password)){
        $_SESSION["main_message"] = "All fields are required!";
        die(header("location:register.php"));
    }

    // setting name and email to session
    // used to fillup in input field
    $name = ltrim($name);
    $name = rtrim($name);

    $password = ltrim($password);
    $password = rtrim($password);

    $_SESSION["name"] = htmlentities($name); $_SESSION["email"] = htmlentities($email);

    // verifying the token
    if($form_csrf !== getSHA1($session_csrf)){
        $_SESSION["main_message"] = "Something went wrong, tryagain!";
        die(header("location:register.php"));
    }

    // warning count
    $warning_eval = 0;

    // checking from fields' values
    if(strlen($name) < SIGNIN_CONFIG["NAME_MIN_LEN"]){
        $_SESSION["name_message"] = "Name must be greater than ".SIGNIN_CONFIG["NAME_MIN_LEN"]." characters.";
        $warning_eval = 1;
    } else if(strlen($name) > SIGNIN_CONFIG["NAME_MAX_LEN"]){
        $_SESSION["name_message"] = "Name must be less than ".SIGNIN_CONFIG["NAME_MAX_LEN"]." characters.";
        $warning_eval = 1;
    }

    if(!preg_match(SIGNIN_CONFIG["EMAIL_REGEX"], $email)){
        $_SESSION["email_message"] = "Please enter a valid email.";
        $warning_eval = 1;
    }

    if(strlen($password) < SIGNIN_CONFIG["PASSWORD_MIN_LEN"]){
        $_SESSION["pwd1_message"] = "Your password must contain atleast ".SIGNIN_CONFIG["PASSWORD_MIN_LEN"]." characters.";
        $warning_eval = 1;
    }

    if($password !== $confirm_password){
        $_SESSION["pwd2_message"] = "Password confirmation doesn't match!";
        $warning_eval = 1;
    }

    // redirect if warning count not equal 0
    if($warning_eval !== 0){
        die(header("location:register.php"));
    }

    // getting database connection
    $connection = getConnection();

    // checking if user exists or not
    $sql = $connection->prepare("SELECT u_name, u_email, email_confirmed_at, expire_at FROM users WHERE u_email=?");
    $sql -> bind_param("s",$email);
    $sql -> execute();

    $result = $sql -> get_result();
    $record_count = $result->num_rows;

    // checking record found or not
    if( $record_count > 0){
        // record found
        $data = $result -> fetch_row();

        $sql -> close();
        
        $u_name = $data[0];
        $u_email = $data[1];
        $email_confirmed_at = $data[2];
        $expire_at = $data[3];


        // email is registered and verified
        if($email_confirmed_at !== NULL){
            $_SESSION["main_message"] = "Email is already registered with another account.";
        } else {
            // email is not registered
            // sending link again if link is expired
            date_default_timezone_set("Asia/Kolkata");
            $expire_at_timestamp = strtotime($expire_at);
            $current_timestamp = strtotime(date("Y-m-d H:i:s"));

            if($current_timestamp < $expire_at_timestamp){
                // if verification link is not expired
                $_SESSION["main_message"] = "Email is already sent to your account, you can request for another verification link after ".$expire_at;
            } else {
                // if verification link is expired
                $u_name = $name;
                $u_email = $email;
                $confirm_token = generate_verification_token();
                $confirm_token_hashed = getSHA1($confirm_token);
                $created_at = date("Y-m-d H:i:s");
                $expire_at = date("Y-m-d H:i:s",strtotime("+".SIGNIN_CONFIG["EMAIL_CODE_EXPIRE_TIME"], strtotime($created_at)));

                // Updating confirm token , created time and expire time
                // using email address
                $sql = $connection -> prepare("UPDATE users SET confirm_token=?, created_at=?, expire_at=? WHERE u_email=?");
                $sql -> bind_param("ssss", $confirm_token_hashed, $created_at, $expire_at, $u_email);

                $res = $sql -> execute();

                $sql->close();

                if($res === TRUE){
                    // if updated
                    $_SESSION["main_message"] = "Unable to send email, tryagain later!";
                    send_email_verification($u_email, $u_name, $confirm_token) or die(header("location: register.php"));
                    $_SESSION["main_message"] = "Verification link has been sent to your email!";
                } else {
                    // if not updated
                    $_SESSION["main_message"] = "Unable to send email, tryagain later!";
                }
            }
        }
        
        // closing connection
        closeConnection($connection);
        die(header("location:register.php"));
    }
    
    $u_name = htmlentities($name);
    $u_email = htmlentities($email);
    $u_password = getSHA256($password);
    $uuid = getSHA1(getSHA1($u_email.$u_name).$u_password);
    $confirm_token = generate_verification_token();
    $confirm_token_hashed = getSHA1($confirm_token);

    // setting to Indian Timezone
    date_default_timezone_set("Asia/Kolkata");
    $created_at = date("Y-m-d H:i:s");
    $expire_at = date("Y-m-d H:i:s",strtotime("+".SIGNIN_CONFIG["EMAIL_CODE_EXPIRE_TIME"], strtotime($created_at)));

    // closing connection
    closeConnection($connection);

    // sending email
    if(!send_email_verification($u_email, $u_name, $confirm_token)){
        $_SESSION["main_message"] = "Unable to send email, tryagain later!";
        die(header("location: register.php"));
    }

    // new connection obj
    $connection = getConnection();

    // checking users exists or not
    // to create an admin account
    $s = $connection -> prepare("SELECT COUNT(*) FROM users");
    $s -> execute();

    $res = $s -> get_result();
    $rows_count = $res->fetch_assoc()["COUNT(*)"];

    $res -> close();
    
    // setting normal or admin
    if($rows_count > 0){
        $u_role_type = "normal_user";
    } else {
        $u_role_type = "admin_user";
    }

    // adding user to table
    $sql = $connection -> prepare("INSERT INTO users(u_name, u_email, u_password, uuid, u_role_type, confirm_token, created_at, expire_at) VALUES(?,?,?,?,?,?,?,?)");
    $sql -> bind_param("ssssssss", $u_name,$u_email, $u_password, $uuid, $u_role_type, $confirm_token_hashed, $created_at, $expire_at);
    
    // executing and checking response
    $resq = @$sql -> execute();
    if($resq !== TRUE){
        $sql = $connection -> query("ALTER TABLE users AUTO_INCREMENT=1");

        $_SESSION["main_message"] = "Something went wrong while creating new account!".$sql -> error;
        $sql -> close();
        closeConnection($connection);
        die(header("location: register.php"));
    }
    
    // closing query
    $sql -> close();
    // closing connection
    closeConnection($connection);

    $_SESSION["main_message"] = "Email verification link has been sent.";
    die(header("location: register.php"));
}

?>

<?php

// setting header parameters
$PageTitle = "Register for a new account";
$stylesheets = array("static/css/signin.css");

include("includes/header.php");

?>

<form class="form" method="POST" action="register.php">
    <header class="header">Signup</header>
    
    <div class="message-main">
        <?php
            $message = printFlashMessage("main_message");
            if(!empty($message)){ echo "<p class='warn'>".$message."</p>"; }
        ?>
    </div>

    <input type="hidden" name="csrf-token"  value="<?= $_SESSION["CSRF-TOKEN"][1] ?>" />
    <div class="label">
        <label for="name">Your name</label>
        <input type="text" name="name" id="name" placeholder="Name here" value="<?= printFlashMessage("name") ?>" />
        <small><?= printFlashMessage("name_message") ?></small>
    </div>
    <div class="label">
        <label for="email">Your email</label>
        <input type="email" name="email" id="email" placeholder="Email here" value="<?= printFlashMessage("email") ?>"/>
        <small><?= printFlashMessage("email_message") ?></small>
    </div>
    <div class="label">
        <label for="password">Your Password</label>
        <input type="password" name="password" id="password" placeholder="Password here" />
        <small><?= printFlashMessage("pwd1_message") ?></small>
    </div>
    <div class="label">
        <label for="conf-password">Confirm Password</label>
        <input type="password" name="conf-password" id="conf-password" placeholder="Confirm Password here" />
        <small><?= printFlashMessage("pwd2_message") ?></small>
    </div>
    <button type="submit">Signup</button>
    <div class="form-act">
        <a class="link" href="<?= url_for("signin.php") ?>">Already have an account? Click to signin</a>
    </div>
</form>

<?php include("includes/footer.php") ?>