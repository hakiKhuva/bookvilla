<?php
session_start();

require_once("includes/helpers.php");

AdminRequired();

require_once("includes/connection.php");
require_once("../includes/config.php");

$UUID = NULL;
$Is404 = TRUE;

if(isset($_GET["uuid"])){
    $UUID = $_GET["uuid"];
}

$connection = getConnection();

$sql = $connection -> prepare("SELECT * FROM users WHERE users.uuid=?");
$sql -> bind_param("s",$UUID);

$sql -> execute();

$result = $sql -> get_result();

if($result->num_rows > 0){
    $Is404 = FALSE;

    $user = $result -> fetch_assoc();
}

if($Is404 !== TRUE){
    if($_SERVER["REQUEST_METHOD"] === "POST"){
        if(isset($_POST["action"])){
            $action = $_POST["action"];
            if($action === "update-data"){
                $users_types = array(
                    "admin" => "admin_user",
                    "normal" => "normal_user",
                );

                $name = $_POST["u_name"];
                $email = $_POST["u_email"];
                $u_role = $users_types[$_POST["u_role"]];

                if(strlen($name) < SIGNIN_CONFIG["NAME_MIN_LEN"] || strlen($name) > SIGNIN_CONFIG["NAME_MAX_LEN"]){
                    closeConnection($connection);
                    $_SESSION["admin_messages"] = array("Name length must be between ".SIGNIN_CONFIG["NAME_MIN_LEN"]." and ".SIGNIN_CONFIG["NAME_MAX_LEN"].".");
                    die(header("Location: user.php?uuid=$UUID"));
                }

                if(!preg_match(SIGNIN_CONFIG["EMAIL_REGEX"], $email)){
                    closeConnection($connection);
                    $_SESSION["admin_messages"] = array("Enter a valid email!");
                    die(header("Location: user.php?uuid=$UUID"));
                }

                if($u_role === "normal_user"){
                    $sql = $connection -> prepare("SELECT u_role_type FROM users WHERE uuid=?");
                    $sql -> bind_param("s",$UUID);

                    $sql -> execute();

                    $data = $sql -> get_result() -> fetch_assoc()["u_role_type"];
                    $sql = $connection -> prepare("SELECT COUNT(*) FROM users WHERE u_role_type='admin_user'");
                    $sql -> execute();

                    $result = $sql -> get_result() -> fetch_assoc()["COUNT(*)"];

                    if($data === "admin_user" && $result === 1){
                        closeConnection($connection);
                        $_SESSION["admin_messages"] = array("Last admin account cannot be transferred to normal account!");
                        die(header("Location: user.php?uuid=$UUID"));
                    }
                }

                $sql = $connection -> prepare("UPDATE users SET u_name=?, u_email=?, u_role_type=? WHERE uuid=?");
                $sql -> bind_param("ssss",$name, $email, $u_role, $UUID);

                if($sql -> execute() === TRUE){
                    $_SESSION["admin_messages"] = array("User data updated successfully.");
                } else {
                    $_SESSION["admin_messages"] = array("Something went wrong, while updating data!");
                }

                closeConnection($connection);
                die(header("Location: user.php?uuid=$UUID"));
            }
            else if($action === "update-password"){
                if(isset($_POST["password"])){
                    $password = $_POST["password"];

                    if(strlen($password) < SIGNIN_CONFIG["PASSWORD_MIN_LEN"]){
                        $_SESSION["admin_messages"] = array("Password must contains ".SIGNIN_CONFIG["PASSWORD_MIN_LEN"]." characters!");

                        closeConnection($connection);
                        die(header("Location: user.php?uuid=$UUID"));
                    }

                    $password = getSHA256($password);

                    $sql = $connection -> prepare("UPDATE users SET u_password=? WHERE uuid=?");
                    $sql -> bind_param("ss",$password, $UUID);

                    if($sql -> execute() === TRUE){
                        $_SESSION["admin_messages"] = array("User password updated successfully.");
                    } else {
                        $_SESSION["admin_messages"] = array("Something went wrong, while updating password!");
                    }

                    closeConnection($connection);
                    die(header(("Location: user.php?uuid=$UUID")));
                }
            } else if($action === "delete-data"){
                $sql = $connection -> prepare("SELECT u_role_type FROM users WHERE uuid=?");
                $sql -> bind_param("s",$UUID);

                $sql -> execute();

                $data = $sql -> get_result() -> fetch_assoc()["u_role_type"];
                $sql = $connection -> prepare("SELECT COUNT(*) FROM users WHERE u_role_type='admin_user'");
                $sql -> execute();

                $result = $sql -> get_result() -> fetch_assoc()["COUNT(*)"];

                if($data === "admin_user" && $result === 1){
                    closeConnection($connection);
                    $_SESSION["admin_messages"] = array("Last admin account cannot be deleted!");
                    die(header("Location: user.php?uuid=$UUID"));
                }

                $sql = $connection -> prepare("SELECT id FROM users WHERE uuid=?");
                $sql -> bind_param("s", $UUID);
                $sql -> execute();
                $id = $sql -> get_result() -> fetch_assoc()["id"];

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
                $sql -> bind_param("s",$UUID);

                $res = $sql -> execute();
                if($res === TRUE){
                    $_SESSION["admin_messages"] = array("User account deleted successfully.");
                } else {
                    $_SESSION["admin_messages"] = array("Something went wrong, while deleting account!");
                }

                closeConnection($connection);
                if($res === TRUE){
                    die(header(("Location: users.php")));
                } else {
                    die(header(("Location: user.php?uuid=$UUID")));
                }
            } else if($action === "logout-all"){
                date_default_timezone_set("Asia/Kolkata");
                $dt = date("Y-m-d H:i:s");
                
                $sql = $connection -> prepare("UPDATE users SET signin_id=? WHERE uuid=?");
                $sql -> bind_param("ss",$dt, $UUID);

                $res = $sql -> execute();
                if($res === TRUE){
                    $_SESSION["admin_messages"] = array("Signed out from all devices successfully.");
                } else {
                    $_SESSION["admin_messages"] = array("Cannot signout from all devices!");
                }
                
                closeConnection($connection);
                die(header(("Location: user.php?uuid=$UUID")));

            } else if($action === "confirm-email"){
                date_default_timezone_set("Asia/Kolkata");
                $dt = date("Y-m-d H:i:s");
                
                $sql = $connection -> prepare("UPDATE users SET signin_id=?,email_confirmed_at=? WHERE uuid=? AND email_confirmed_at IS NULL");
                $sql -> bind_param("sss",$dt, $dt, $UUID);

                $res = $sql -> execute();
                if($res === TRUE){
                    $_SESSION["admin_messages"] = array("Email confirmed successfully.");
                } else {
                    $_SESSION["admin_messages"] = array("Cannot confirm the email!");
                }
                
                closeConnection($connection);
                die(header(("Location: user.php?uuid=$UUID")));
            } else if($action === "unconfirm-email"){
                date_default_timezone_set("Asia/Kolkata");
                $dt = date("Y-m-d H:i:s");
                
                $sql = $connection -> prepare("UPDATE users SET email_confirmed_at=NULL WHERE uuid=? AND email_confirmed_at IS NOT NULL");
                $sql -> bind_param("s", $UUID);

                $res = $sql -> execute();
                if($res === TRUE){
                    $_SESSION["admin_messages"] = array("Email unconfirmed successfully.");
                } else {
                    $_SESSION["admin_messages"] = array("Cannot unconfirm the email!");
                }
                
                closeConnection($connection);
                die(header(("Location: user.php?uuid=$UUID")));
            }
        }
    }
}

closeConnection($connection);


$PageTitle = "Specific User Details";
require_once("includes/header.php");
?>

<div>
    <?php
        if($Is404 === TRUE){
            ?>
                <div class="header">No user found!</div>
            <?php
        } else {
            ?>

                <form method="POST" class="form-w" style="max-width: 500px;">
                    <div class="header">User details for <?= $UUID ?></div>
                    
                    <div class="label">
                        <label for="u_name">Username</label>
                        <input type="text" name="u_name" id="u_name" value="<?= $user["u_name"] ?>" class="input" required />
                    </div>
                    
                    <div class="label">
                        <label for="u_email">User email</label>
                        <input type="email" name="u_email" id="u_email" value="<?= $user["u_email"] ?>" class="input" required />
                    </div>
                    
                    <div class="label">
                        <label for="u_role">User role</label>
                        <ul>
                            <li>
                                <input type="radio" name="u_role" id="u_role_admin" value="admin" <?= $user["u_role_type"] === "admin_user" ? "checked" : "" ?>>
                                <label for="u_role_admin">Admin user</label>
                            </li>
                            <li>
                                <input type="radio" name="u_role" id="u_role_normal" value="normal" <?= $user["u_role_type"] === "admin_user" ? "" : "checked" ?>>
                                <label for="u_role_normal">Normal user</label>
                            </li>
                        </ul>
                    </div>
                    <button type="submit" name="action" value="update-data">UPDATE ACCOUNT</button>

                    <?php
                    if($user["email_confirmed_at"]){
                        ?>
                        <div class="label">
                            <label for="u_name">Email confirmed at</label>
                            <input type="datetime-local" value="<?= $user["email_confirmed_at"] ?>" disabled>
                        </div>
                        <button type="submit" name="action" value="unconfirm-email">UNCONFIRM EMAIL</button>
                        <?php
                    } else {
                        ?>
                        <div class="label">
                            <label for="u_name">Email confirmed at</label>
                            <input type="text" disabled value="Not confirmed yet">
                        </div>
                        <button type="submit" name="action" value="confirm-email">CONFIRM EMAIL</button>
                        <?php
                    }
                    ?>

                </form>

                <form method="POST" class="form-w" style="max-width: 500px;">
                    <div class="header">Update password</div>
                    <div class="label">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="input" required />
                    </div>

                    <button type="submit" name="action" value="update-password">UPDATE PASSWORD</button>
                </form>

                <form method="post" class="form-w" style="max-width: 500px; padding: 0px 25px;">
                    <button type="submit" name="action" value="delete-data">DELETE ACCOUNT</button>
                </form>

                <form method="post" class="form-w" style="max-width: 500px; padding: 0px 25px;">
                    <button type="submit" name="action" value="logout-all">SIGNOUT FROM ALL DEVICES</button>
                </form>

            <?php
        }
    ?>
</div>

<?php
require_once("includes/footer.php");
?>