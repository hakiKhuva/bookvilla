<?php

session_start();

require_once("includes/helpers.php");
require_once("includes/config.php");
require_once("includes/connection.php");

if (isLoggedin() !== TRUE) {
    $_SESSION["main_message"] = "You need to signin to download book!";
    die(header("Location: signin.php"));
}

$is404 = FALSE;

if (isset($_GET["data"])) {
    $book_id = $_GET["data"];

    $connection = getConnection();

    $sql = $connection->prepare("SELECT id, book_filename, name FROM books WHERE book_filename=?");
    $sql->bind_param("s", $book_id);

    $sql->execute();

    $result = $sql->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_row();
    } else {
        $is404 = TRUE;
    }
} else {
    $is404 = TRUE;
}

if(isset($connection)){
    closeConnection($connection);
}

if ($is404 === TRUE) {
    http_response_code(404);
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Download Book</title>
    </head>

    <body>
        <div>
            <h1>
                Not found!
            </h1>
            <p>Book you are looking for is not available to download, please check the url and tryagain.</p>
        </div>
    </body>

    </html>
<?php
} else {
    $id = $data[0];
    $file = $data[1];
    $c_disposition = $data[2];

    $connection = getConnection();
    $sql = $connection->prepare("UPDATE books SET download_count = download_count + 1 WHERE id=?");
    $sql->bind_param("i", $id);

    $sql->execute();

    closeConnection($connection);

    $fname = BOOK_UPLOAD_CONFIG["BOOKS_FOLDER"] . $file . ".pdf";
    $size = filesize($fname);
    $f = fopen($fname, "r");
    $data = fread($f, $size);
    fclose($f);

    $name = $c_disposition . ".pdf";

    header("Content-Type: application/pdf");
    header("Content-Length: $size");
    header("Content-Disposition: attachment; filename=$name");
    echo $data;
    die;
}

?>