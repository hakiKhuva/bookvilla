<?php
session_start();

require_once("includes/helpers.php");

RedirectIfLoggedin();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="title" content="BookVilla">
    <meta name="description" content="Bookvilla is a social network to share books for free.">
    <meta name="keywords" content="Bookvilla, books, new books, free, free books, download new books, download free books">
    <meta name="language" content="English">

    <!-- <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet"> -->
    <link rel="stylesheet" href="static/css/fonts.css">

    <link rel="stylesheet" href="static/css/main.css">
    <link rel="stylesheet" href="static/css/index.css">

    <title>BookVilla</title>
</head>

<body>

    <div id="main-container">
        <div class="custom-card">
            <div class="tag-card">
                <div class="tagLine">
                    BookVilla
                </div>
                <div class="tagLine-desc">
                    <p>A social network for books.</p>
                    <p>Share your books to people around the glob.</p>
                    <p>Download book shared by people.</p>
                </div>
            </div>

            <div class="link-continue">
                <a href="<?= url_for("/home.php") ?>">Get started, it's free</a>
            </div>
        </div>
    </div>

</body>

</html>