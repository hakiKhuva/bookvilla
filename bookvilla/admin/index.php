<?php
session_start();
require_once("includes/helpers.php");

AdminRequired();

require_once("includes/connection.php");

$users_count = 0;
$verified_users_count = 0;
$books_count = 0;
$downloads_count = 0;
$book_upload_data = array();
$users_account_data = array();

$connection = getConnection();

$response = $connection->query("SELECT COUNT(*) FROM users");
$users_count = $response->fetch_row()[0];

$response = $connection->query("SELECT COUNT(*), CAST( created_at AS DATE ) FROM users GROUP BY CAST( created_at AS DATE )");
$users_account_data = $response->fetch_all();

$response = $connection->query("SELECT COUNT(*) FROM users WHERE email_confirmed_at IS NOT NULL");
$verified_users_count = $response->fetch_row()[0];

$response = $connection->query("SELECT COUNT(*) FROM books");
$books_count = $response->fetch_row()[0];

$response = $connection->query("SELECT COUNT(*), CAST(uploaded_at AS DATE) FROM books GROUP BY CAST(uploaded_at AS DATE)");
$book_upload_data = $response->fetch_all();

$response = $connection->query("SELECT SUM(download_count) FROM books");
$downloads_count = $response->fetch_row()[0];

closeConnection($connection);

$PageTitle = "Dashboard - BookVilla";
// charts.js from "https://www.w3schools.com/js/js_graphics_chartjs.asp"
$scripts = array("static/charts.js");

require_once("includes/header.php");
?>

<div>
    <h1 style="text-align: center;">Dashboard</h1>
    <div class="cards">

        <div class="card">
            <div class="name">Total users</div>
            <div class="value"><?= $users_count ?></div>
        </div>

        <div class="card">
            <div class="name">Total Verified Users</div>
            <div class="value"><?= $verified_users_count ?></div>
        </div>

        <div class="card">
            <div class="name">Total books</div>
            <div class="value"><?= $books_count ?></div>
        </div>

        <div class="card">
            <div class="name">Total Downloads</div>
            <div class="value"><?= $downloads_count === NULL ? "0" : $downloads_count ?></div>
        </div>
    </div>

    <div class="graph-container">
        <div class="header">User account</div>
        <canvas class="graph" id="users-acc-graph"></canvas>
    </div>

    <div class="graph-container">
        <div class="header">Book Upload Data</div>
        <canvas class="graph" id="book-graph"></canvas>
    </div>

</div>

<script>
    const UsersData = <?= json_encode($users_account_data) ?>;
    const BookData = <?= json_encode($book_upload_data) ?>;

    let UsersXData = [];
    let UsersYData = [];

    UsersData.forEach(obj => {
        UsersYData.push(obj[0]);
        UsersXData.push(obj[1]);
    })
    
    new Chart("users-acc-graph", {
        type: "line",
        data: {
            labels: UsersXData,
            datasets: [{
                fill: false,
                lineTension: 0,
                backgroundColor: "rgba(230,120,55,1.0)",
                borderColor: "rgba(230,120,55,0.1)",
                data: UsersYData
            }]
        },
        options: {
            legend: {
                display: false
            },
        }
    });

    let BooksXData = [];
    let BooksYData = [];

    BookData.forEach(obj => {
        BooksYData.push(obj[0]);
        BooksXData.push(obj[1]);
    })

    new Chart("book-graph", {
        type: "line",
        data: {
            labels: BooksXData,
            datasets: [{
                fill: false,
                lineTension: 0,
                backgroundColor: "rgba(0,0,255,1.0)",
                borderColor: "rgba(0,0,255,0.1)",
                data: BooksYData
            }]
        },
        options: {
            legend: {
                display: false
            },
        }
    });

</script>

<?php
require_once("includes/footer.php");
?>