<?php
// starting session
session_start();

// including files
require_once("includes/helpers.php");
require_once("includes/connection.php");

// redirect to home if loggedin
RedirectIfLoggedin();

// setting CSRF to SESSION if Request Method is GET
if($_SERVER["REQUEST_METHOD"] === "GET"){
    $_SESSION["CSRF-TOKEN"] = generate_csrf();
}

// POST method of signin
if($_SERVER["REQUEST_METHOD"] === "POST"){
    $u_email = $_POST["email"];
    $u_password = $_POST["password"];
    $u_csrf = $_POST["csrf-token"];
    $s_csrf = $_SESSION["CSRF-TOKEN"][0];

    // checking if parameter empty
    if(empty($u_email) || empty($u_password) || empty($u_csrf) || empty($s_csrf)){
        $_SESSION["main_message"] = "All fields are required!";
        die(header("location: signin.php"));
    }

    // matching csrf token
    if(getSHA1($s_csrf) !== $u_csrf){
        $_SESSION["main_message"] = "Something went wrong, tryagain!";
        die(header("location: signin.php"));
    }

    // hashing password
    $u_password = getSHA256($u_password);

    $connection = getConnection();

    // checking if email and password exist
    $sql = $connection -> prepare("SELECT uuid, signin_id, email_confirmed_at FROM users WHERE BINARY u_email=? AND u_password=?");
    $sql -> bind_param("ss", $u_email, $u_password);

    $res = $sql -> execute();

    // is response is Not True
    if($res !== TRUE){
        $sql -> close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Something went wrong, tryagain!";
        die(header("location: signin.php"));
    }

    // getting result
    $result = $sql -> get_result();

    // if number of rows less than 1
    if($result->num_rows < 1){
        $sql -> close();
        closeConnection($connection);
        $_SESSION["main_message"] = "Account not found with entered email and password!";
        die(header("location: signin.php"));
    }

    // fetching the row
    $data = $result -> fetch_row();
    $uuid = $data[0];
    $signin_id = $data[1];
    $email_confirmed_at = $data[2];

    // closing query
    $sql -> close();

    // if email is not confirmed yet
    if($email_confirmed_at === NULL){
        closeConnection($connection);
        $_SESSION["main_message"] = "Please verify the email before signing in!";
        die(header("location: signin.php"));
    }

    // setting user data to the session
    $_SESSION["USER-UUID"] = $uuid;
    $_SESSION["USER-SIGNIN_ID"] = $signin_id;

    // closing connection
    closeConnection($connection);

    die(header("location: home.php"));
}
?>

<?php

$NavBarVisible = FALSE;

// setting header parameters
$PageTitle = "Signin";
$stylesheets = array("static/css/signin.css");

include("includes/header.php")

?>

<form class="form" action="signin.php" method="POST">
    <header class="header">Signin</header>

    <div class="message-main">
        <?php
            $message = printFlashMessage("main_message");
            if(!empty($message)){ echo "<p class='warn'>".$message."</p>"; }
        ?>
    </div>

    <input type="hidden" name="csrf-token" required value="<?= $_SESSION["CSRF-TOKEN"][1] ?>" />

    <div class="label">
        <label for="email">Your email</label>
        <input type="email" name="email" id="email" required="" placeholder="Email here" />
    </div>
    <div class="label">
        <label for="password">Your Password</label>
        <input type="password" name="password" id="password" required="" placeholder="Password here" />
    </div>
    
    <button type="submit">Signin</button>

    <div class="form-act">
        <a class="link" href="<?= url_for("register.php") ?>">Don't have an account? Click to register</a>
    </div>
    <div class="form-act">
        <a class="link" href="<?= url_for("forgot_password.php") ?>">Forgot Password? Click to reset</a>
    </div>
</form>

<?php include("includes/footer.php") ?>