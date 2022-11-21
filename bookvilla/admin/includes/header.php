<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Baloo+2&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="static/css/main.css">
    <link rel="stylesheet" href="../static/css/main.css">

    <script src="static/main.js"></script>

    <?php
    if (isset($stylesheets)) {
        foreach ($stylesheets as $css) {
            echo "<link rel=\"stylesheet\" href=" . $css . ">";
        }
    }
    ?>
    <?php
    if (isset($scripts)) {
        foreach ($scripts as $script) {
            echo "<script src=\"$script\"></script>";
        }
    }
    ?>

    <title><?= $PageTitle ?> - Bookvilla</title>
</head>

<body>
    <header id="main-header">
        <div>
            <div class="header">BookVilla</div>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="users.php">Users</a>
                <a href="books.php">Books</a>
                <a href="cont_forms.php">Contact forms</a>
                <a href="../home.php">View website</a>
            </nav>
        </div>
    </header>

    <script>
        function hide_main() {
            document.getElementById("messages-flashs").style.display = 'none';

        }
    </script>
    <?php
    if (isset($_SESSION["admin_messages"])) {
    ?>
        <div id="messages-flashs" title="Click to dismiss" onclick="hide_main();">
            <div class="header">Messages from server</div>
            <ul>
                <?php
                foreach ($_SESSION["admin_messages"] as $screen) {
                ?>
                    <li><?= $screen ?></li>
                <?php
                }
                ?>
            </ul>
        </div>
    <?php
        unset($_SESSION["admin_messages"]);
    }
    ?>

    <div id="main-container">