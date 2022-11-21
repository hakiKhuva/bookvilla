<?php
session_start();
// setting title
$PageTitle = "About";
include_once("includes/header.php")
?>

<style>
    table {
        width: 100%;
    }
    table, tr, td, th {
        border: 1px solid var(--fg);
        border-collapse: collapse;
        padding: 10px 20px;
        text-align: left;
    }

    p {
        font-size: 18px;
    }
</style>

<div class="company-page">
    <header><h1>About BookVilla</h1></header>
    <div>
        <div>
            <h2>Who we are</h2>
            <p>
                BookVilla is a social network to share books with other people on the Internet.
                However many users prefer videos for each category, there are more users who prefer books over videos.
                Our aim is to have all type of books so user can easily get any type of book without any hustle.
            </p>
        </div>
        <div>
            <h2>How it was started</h2>
            <p>
                In 2022 we started this project(BookVilla) as our Academic project, but we thought
                to make it live to public so people can interact with each other to share books, after this thought we changed many
                parts and functions of website so user have more ease of access on it. Our main aim is that people can get
                knowledge, information on same website without any cost and other users can also distribute their work and information
                without any cost.
            </p>
        </div>
        <div>
            <h2>Our Team</h2>
            <table class="table">
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                </tr>
                <tr>
                    <td>Harkishan Khuva</td>
                    <td>CEO & Co-founder</td>
                </tr>
                <tr>
                    <td>Nayan Karodiya</td>
                    <td>CFO & Co-founder</td>
                </tr>
            </table>
        </div>

    </div>
</div>

<?php include_once("includes/footer_full.php") ?>