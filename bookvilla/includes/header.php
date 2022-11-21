<?php
    require_once(__DIR__."/helpers.php")
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
    if(isset($PageDescription)){
    ?>
    <meta name="description" content="<?= substr(explode(".",$PageDescription)[0],0,150) ?>">
    <?php
    } else {
    ?>
    <meta name="description" content="Bookvilla is a social network to share books for free.">
    <?php
    }

?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="static/css/main.css">
    <link rel="stylesheet" href="static/css/header.css">
    <link rel="stylesheet" href="static/css/navbar.css">
    <link rel="stylesheet" href="static/css/footer.css">
    <link rel="stylesheet" href="static/css/popup.css">

    <?php
        if(isset($stylesheets)){
            foreach($stylesheets as $css){
                echo "<link rel=\"stylesheet\" href=".$css.">";
            }
        }
    ?>

    <title><?php echo $PageTitle." - "."BookVilla" ?></title>
</head>

<body>
    <header class="main-header">
        <div>
            <div><a href="home.php" style="color: var(--fg); text-decoration: none;">BookVilla</a></div>
            <?php
            if(isLoggedin()){
                require_once("navbar.php");
            } else {
                require_once("navbar_unsign.php");
            }
            ?>
        </div>
    </header>
    
    <div id="main-container">
        <?php
            $value = printFlashMessage("main-message");
            if($value){
                $type = printFlashMessage("main-message-type");
                if(!$type){
                    $type = "abnormal";
                }
                echo "<div class='$type-message'>$value</div>";
            }
        ?>
        <div id="popups">
            <?php
            if(isset($Popups)){
                foreach ($Popups as $popup) {
                    require_once("$popup.php");
                }
            }
            ?>
            <?php require_once("cookie_popup.php"); ?>
        </div>
        <div id="inner-container">