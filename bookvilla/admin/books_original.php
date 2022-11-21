<?php
session_start();

require_once("includes/helpers.php");
require_once("../includes/config.php");
require_once("includes/connection.php");

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "delete" && isset($_POST["book"])){
    $Book = $_POST["book"];

    $connection = getConnection();

    $sql = $connection -> prepare("SELECT book_thumbnail FROM books WHERE book_filename=?");
    $sql -> bind_param("s", $Book);

    $sql -> execute();

    $result = $sql -> get_result();

    if($result->num_rows !== 1){
        $_SESSION["admin_messages"] = array("No book is associated with provided book id!");
        closeConnection($connection);
        die(header("Location: books.php"));
    } 
    $data = $result -> fetch_assoc();

    $book_name = BOOK_UPLOAD_CONFIG["BOOKS_FOLDER"]."\\".$Book.".pdf";
    $image_preview = BOOK_UPLOAD_CONFIG["BOOKS_PREVIEW_IMAGE_FOLDER"]."\\".$data["book_thumbnail"];

    unlink($book_name); unlink($image_preview);

    $sql = $connection -> prepare("DELETE FROM books WHERE book_filename=?");
    $sql -> bind_param("s", $Book);

    $res = $sql -> execute();
    closeConnection($connection);

    if($res === TRUE){
        $_SESSION["admin_messages"] = array("The books was successfully deleted.");
    } else {
        $_SESSION["admin_messages"] = array("Unable to delete book associated with book id, tryagain!");
    }


    die(header("Location: books.php"));
}

$Page = 1;
$BooksPerPage = 10;
$Pattern = NULL;

if (isset($_GET["page"])) {
    $Page = (int)$_GET["page"];
}

if(isset($_GET["pattern"])){
    $Pattern = $_GET["pattern"];
}

$LimitFrom = ($Page * $BooksPerPage) - $BooksPerPage;

$connection = getConnection();

$sql = $connection->query("SELECT COUNT(*) FROM books");
$count_all = $sql -> fetch_assoc()["COUNT(*)"];

if($Pattern !== NULL){
    $sql = $connection->prepare("SELECT * FROM books WHERE book_filename=?");
    $sql->bind_param("s", $Pattern);
} else {
    $sql = $connection->prepare("SELECT * FROM books LIMIT ?,?");
    $sql->bind_param("ii", $LimitFrom, $BooksPerPage);
}

$sql->execute();

$result = $sql->get_result();

$Pages = ceil($count_all / $BooksPerPage);

$PageTitle = "All Books";
$stylesheets = array("../static/css/book.css","../static/css/search.css", "static/css/users.css");
require_once("includes/header.php");

?>

<div>
    <div class="options">
        <h1 style="text-align: center;">Book stats</h1>

        <div class="options">
            <div>
                <strong>Total books : <?= $count_all ?></strong>
            </div>

            <details>
                <summary>Search book</summary>
                <form action="books.php" method="GET" class="form-w" style="max-width: 350px; position: absolute; border: 1px solid var(--fg);">
                    <div class="label">
                        <label for="pattern">Book Id</label>
                        <input type="text" name="pattern" id="pattern" required />
                    </div>

                    <button type="submit">Search</button>
                </form>
            </details>

            <form action="get" class="select-form" style="margin: 0px;padding: 0px;">
                <select name="page" id="page" class="round" onchange="page_change_jump(this)">
                    <option hidden>Jump To Page</option>
                    <?php
                        $i = 1;
                        while($i <= $Pages){
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

    <div class="books-colls">
        <?php
        if ($result->num_rows > 0) {
        ?>
            <?php
            while ($data = $result->fetch_assoc()) {
            ?>
                <a class="book" href="book_edit.php?book_id=<?= $data["book_filename"] ?>">
                    <div class="book-image">
                        <img src="<?= "../" . BOOK_UPLOAD_CONFIG["BOOKS_PREVIEW_IMAGE_STATIC"] . $data["book_thumbnail"] ?>" alt="Book image">
                    </div>
                    <div class="book-details">
                        <div>
                            <?php
                                if (strlen($data["name"]) <= 45) {
                                    echo $data["name"];
                                } else {
                                    echo substr($data["name"], 0, 42) . "...";
                                }
                            ?>
                        </div>
                        <div>
                            <div>
                                <?php
                                    if (strlen($data["author_name"]) <= 30) {
                                        echo $data["author_name"];
                                    } else {
                                        echo substr($data["author_name"], 0, 27) . "...";
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <form action="books.php" method="post" class="delete-form">
                            <input type="hidden" name="action" value="delete" required />
                            <input type="hidden" name="book" value="<?= $data["book_filename"] ?>" required />
                            <button type="submit">Delete this book</button>
                        </form>

                        <form action="../book.php" method="get" class="delete-form">
                            <input type="hidden" name="book" value="<?= $data["book_filename"] ?>" required />
                            <button type="submit">View book</button>
                        </form>
                    </div>
                </a>
            <?php
            }
            ?>
        <?php
        } else {
        ?>
            <div>
                <h1>No books found!</h1>
                <p>No books found, no books uploaded or the searched book id is not associated with any book!</p>
            </div>
        <?php
        }
        ?>

    </div>
</div>

<?php
closeConnection($connection);
require_once("includes/footer.php");
?>