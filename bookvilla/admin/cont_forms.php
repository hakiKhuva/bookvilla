<?php
session_start();

require_once("includes/helpers.php");
AdminRequired();

require_once("includes/connection.php");

if($_SERVER["REQUEST_METHOD"] === "POST"){
    if(!isset($_POST["form_id"]) || empty($_POST["form_id"]) ){
        $_SESSION["admin_messages"] = array("No contact form found to update!");
        die(header("Location: cont_forms.php"));
    }

    $form_id = $_POST["form_id"];

    $connection = getConnection();
    
    date_default_timezone_set("Asia/Kolkata");
    $dt = date("Y-m-d H:i:s");

    $sql = $connection -> prepare("UPDATE contact_forms SET form_read=? WHERE id=? AND form_read IS NULL");
    $sql -> bind_param("si",$dt,$form_id);

    $response = $sql -> execute();
    closeConnection($connection);

    if($response === TRUE){
        $_SESSION["admin_messages"] = array("Form successfully set as read.");
    } else {
        $_SESSION["admin_messages"] = array("Failed to set form as read!");
    }

    die(header("Location: cont_forms.php?type=new"));
}

$Type = "NEW";
$FormPage = 1;
$FormPerPage = 10;

if(isset($_GET["type"])){
    if(!in_array($_GET["type"], array("new","read"))){
        $Type = "NEW";
    } else {
        $Type = strtoupper($_GET["type"]);
    }
}

if(isset($_GET["page"])){
    $FormPage = (int)$_GET["page"];
}

$connection = getConnection();

if($Type === "NEW"){
    $sql1 = $connection -> prepare("SELECT COUNT(*) FROM contact_forms WHERE form_read IS NULL");
} else {
    $sql1 = $connection -> prepare("SELECT COUNT(*) FROM contact_forms WHERE form_read IS NOT NULL");
}

$FROM = ($FormPage * $FormPerPage) - $FormPerPage;
$TO = $FormPerPage;

$sql1 -> execute();

$result = $sql1 -> get_result();

$count_all = $result ->fetch_assoc()["COUNT(*)"];

if($Type === "NEW"){
    $sql2 = $connection -> prepare("SELECT * FROM contact_forms WHERE form_read IS NULL ORDER BY form_date DESC LIMIT ?,?");
} else {
    $sql2 = $connection -> prepare("SELECT * FROM contact_forms WHERE form_read IS NOT NULL ORDER BY form_date DESC LIMIT ?,?");
}

$sql2 -> bind_param("ii",$FROM,$TO);

$sql2 -> execute();

$result = $sql2 -> get_result();

$FormPagesCount = ceil($count_all/$FormPerPage);

$PageTitle = "Contact forms";
$stylesheets = array("static/css/users.css","static/css/cont.css");

require_once("includes/header.php");

?>

<div>
    <div class="options">
    <h1>Contact forms</h1>
    <div class="navbar-inner">
        <div class="header-b <?= $Type === "NEW" ? "underline" : "" ?>">
            <a href="cont_forms.php?type=new">New forms</a>
        </div>
        <div class="header-b <?= $Type === "READ" ? "underline" : "" ?>">
            <a href="cont_forms.php?type=read">Read forms</a>
        </div>
    </div>
    </div>

    <div class="options">
        <div>
            <strong>Total forms : <?= $count_all ?></strong>
        </div>
        <div>
            <strong>Total pages : <?= $FormPagesCount ?></strong>
        </div>
        <div>
            <form action="get" class="select-form" style="margin: 0px;padding: 0px;">
                <select name="page" id="page" class="round" onchange="page_change_jump(this)">
                    <option hidden>Jump To Page</option>
                    <?php
                        $i = 1;
                        while($i <= $FormPagesCount){
                            ?>
                                <option name="page" value="<?=$i?>"><?= $i ?></option>
                            <?php
                            $i++;
                        }
                    ?>
                </select>
            </form>
        </div>
    </div>

    <div>
        <?php
            if($result->num_rows > 0){
                ?>
                <div class="c-forms-colls">
                    <?php
                    while($data = $result -> fetch_assoc()){
                        ?>
                        <div class="cont-form">
                            <div class="id">
                                Form id : <?= $data["id"] ?>
                            </div>
                            <div class="name">
                                <?= $data["u_name"] ?>
                            </div>
                            <div class="email">
                                <?= $data["u_email"] ?>
                            </div>
                            <div class="subject">
                                <?= $data["subject"] ?>
                            </div>
                            <div class="message">
                                <p><?= $data["message"] ?></p>
                            </div>
                            <div class="dt-tm">
                                <?= $data["form_date"] ?>
                            </div>
                            <?php
                            if($data["form_read"] === NULL){
                                ?>
                                <form action="cont_forms.php" method="POST" class="form-w">
                                    <input type="hidden" name="form_id" value="<?=$data["id"]?>" />
                                    <button type="submit">Update as read</button>
                                </form>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            } else {
                if($Type === "NEW"){
                    ?>
                    <h2 style="text-align: center;">No new forms available</h2>
                    <?php
                } else {
                    ?>
                    <h2 style="text-align: center;">No read forms available</h2>
                    <?php
                }
            }
        ?>
    </div>
</div>

<?php
require_once("includes/footer.php");
closeConnection($connection)

?>