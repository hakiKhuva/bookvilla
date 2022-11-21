<?php
require_once(__DIR__."/config.php");
require_once(__DIR__."/connection.php");

function generate_csrf(){
    // generate CSRF token
    // returns original string and hashed string
    define("LETTERS","1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz");

    $string = "";

    for($i=0; $i < 20; $i++){
        $string = $string . LETTERS[rand(0,strlen(LETTERS)) - 1];
    }

    return array($string, hash("SHA1",$string));
}

function generate_verification_token(){
    // generate a verification code
    // mainly used to authenticate the user
    define("HEX_CHARS", "1234567890ABCDEFabcdef");

    $string = "";
    for($i=0; $i < 25; $i++){
        $string .= HEX_CHARS[rand(0, strlen(HEX_CHARS)-1)];
    }

    return $string;
}

function getSHA1($string_data){
    // return SHA1 Hash of string
    return hash("SHA1", $string_data);
}

function getSHA256($string_data){
    // return SHA256 Hash of string
    return hash("SHA256", $string_data);
}

function isLoggedin(){
    // check the whether user is loggedin or not
    
    if ( isset($_SESSION["USER-UUID"]) && isset($_SESSION["USER-SIGNIN_ID"])) {
        $conn = getConnection();
        $sql = $conn -> prepare("SELECT signin_id FROM users WHERE uuid=?");
        $sql -> bind_param("s", $_SESSION["USER-UUID"]);
        if($sql -> execute() !== TRUE){
            closeConnection($conn);
            return FALSE;
        }

        $result = $sql -> get_result();
        if($result -> num_rows !== 1 ){
            closeConnection($conn);
            return FALSE;
        }
        $data = $result -> fetch_row();

        if($data[0] !== $_SESSION["USER-SIGNIN_ID"]){
            closeConnection($conn);
            return FALSE;
        }

        closeConnection($conn);
        return TRUE;
    }
    return FALSE;
}

function LoginRequired(){
    // Redirect to signin if not loggedin
    // if( !isset($_SESSION["USER-UUID"]) || !isset($_SESSION["USER-SIGNIN_ID"]) ){
    if(!isLoggedin()){
        $_SESSION["main_message"] = "You need to signin to access the page.";
        header("Location: signin.php");
        die();
    }
}

function RedirectIfLoggedin(){
    // Redirect to home if loggedin
    // if ( isset($_SESSION["SIGNIN-TOKEN"]) || isset($_SESSION["USER-SIGNIN_ID"])) {
    if(isLoggedin()){
        header("Location: home.php");
        die();
    }
}

function printFlashMessage($name){
    if(isset($_SESSION[$name])){
        if(!empty($_SESSION[$name])){
            $val = $_SESSION[$name];
            unset($_SESSION[$name]);
            return $val;
        }
    }
}


function url_for($string_url){
    // function to create full URL with current HOST
    return rtrim(APP_URL,"/")."/".ltrim($string_url, "/");
}


function getCurrentUserId($connection){
    $sql = $connection->prepare("SELECT id FROM users WHERE users.uuid=?");
    $sql -> bind_param("s",$_SESSION["USER-UUID"]);
    $sql -> execute();

    $result = $sql -> get_result();
    $user_id = $result -> fetch_row()[0];

    $sql -> close();
    
    return $user_id;
}

?>