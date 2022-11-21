<?php
session_start();

require_once("includes/helpers.php");

LoginRequired();

require_once("includes/config.php");
require_once("includes/connection.php");

if($_SERVER["REQUEST_METHOD"] === "POST"){
    if(!isset($_POST["csrf"]) || !isset($_SESSION["DEL-ACC-CSRF"]) || getSHA1($_SESSION["DEL-ACC-CSRF"][0]) !== $_POST["csrf"]){
        $_SESSION["main-message"] = "Something went wrong, tryagain later.";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: delete_account.php"));
    }

    if(!isset($_POST["password"]) || empty($_POST["password"])){
        $_SESSION["main-message"] = "Please enter password to delete your account!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: delete_account.php"));
    }

    $password = $_POST["password"];

    $connection = getConnection();
    $uuid = $_SESSION["USER-UUID"];

    $sql = $connection -> prepare("SELECT id, u_role_type, u_password FROM users WHERE uuid=?");
    $sql -> bind_param("s", $uuid);
    $sql -> execute();

    $result = $sql->get_result();

    if($result -> num_rows < 1){
        closeConnection($connection);
        $_SESSION["main-message"] = "Unable to fetch account details, tryagain later.!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: delete_account.php"));
    }

    $data = $result -> fetch_row();

    $id = $data[0]; $role = $data[1]; $pwd = $data[2];

    if($role === "admin_user"){
        closeConnection($connection);
        $_SESSION["main-message"] = "Admin accounts can only be deleted from admin dashboard!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: delete_account.php"));
    }

    $hashed_pwd = hash("sha256", $password);

    if($hashed_pwd !== $pwd){
        closeConnection($connection);
        $_SESSION["main-message"] = "Incorrect password to delete this account!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: delete_account.php"));
    }

    $sql = $connection -> prepare("SELECT * FROM books WHERE uploaded_by=?");
    $sql -> bind_param("i", $id);

    $sql -> execute();

    $result = $sql -> get_result();

    if($result -> num_rows > 0){
        while ($data = $result -> fetch_assoc()) {
            $book_name = BOOK_UPLOAD_CONFIG["BOOKS_FOLDER"]."\\".$data["book_filename"].".pdf";
            $image_preview = BOOK_UPLOAD_CONFIG["BOOKS_PREVIEW_IMAGE_FOLDER"]."\\".$data["book_thumbnail"];

            if(file_exists($book_name)){
                unlink($book_name); 
            }
            if(file_exists($image_preview)){
                unlink($image_preview);
            }
        }
    }

    $sql = $connection -> prepare("DELETE FROM users WHERE uuid=?");
    $sql -> bind_param("s", $uuid);

    $response = $sql -> execute();
    closeConnection($connection);

    if($response !== TRUE){
        $_SESSION["main-message"] = "Unable to delete your account, tryagain later!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: delete_account.php"));
    }

    $_SESSION["main_message"] = "Your account was deleted successfully.";
    die(header("Location: signin.php"));
}

$_SESSION["DEL-ACC-CSRF"] = generate_csrf();

$PageTitle = "Delete account";
$stylesheets = array("static/css/main.css");
require_once("includes/header.php");
?>

<form action="delete_account.php" method="POST" class="form-w" style="width: 400px; margin: auto;" >
    <div class="header">Delete account</div>
    <input type="hidden" name="csrf" value="<?= $_SESSION["DEL-ACC-CSRF"][1] ?>" required />

    <div class="label">
        <label for="password">Your Password</label>
        <input type="password" name="password" id="password" required />
    </div>

    <div class="label">
        <p>
            *I know that by clicking below button("Delete account") my account will be deleted
            and all saved/uploaded books will be removed from the server.
        </p>
    </div>

    <button type="submit" style="font-weight: bold; border-width: 2px; border-radius: 25px;">Delete account</button>
</form>

<?php

require_once("includes/footer.php");
?>