<?php
session_start();

require_once("includes/helpers.php");
require_once("includes/config.php");

LoginRequired();

if(isset($_GET["type"])){
    $CurrentPath = $_GET["type"];
    if($CurrentPath !== "uploaded" && $CurrentPath !== "saved"){
        $CurrentPath = "saved";
    }
} else {
    $CurrentPath = "saved";
}

$connection = getConnection();

if($_SERVER["REQUEST_METHOD"] === "POST"){
    if(isset($_POST["action"])){
        if($_POST["action"] === "delete" && isset($_POST["book"])){
            $book_id = $_POST["book"];
            $user_id = getCurrentUserId($connection);

            $sql = $connection -> prepare("SELECT * FROM books WHERE books.book_filename=? AND books.uploaded_by=? LIMIT 1");
            $sql -> bind_param("si", $book_id, $user_id);
            $sql -> execute();

            $result = $sql-> get_result();

            if($result -> num_rows !== 1){
                closeConnection($connection);
                $_SESSION["main-message"] = "Book does not exists to delete!";
                $_SESSION["main-message-type"] = "abnormal";
                die(header("Location: library.php?type=uploaded"));
            }

            $book_to_delete = $result -> fetch_assoc();
            $book_primary_key = $book_to_delete["id"];
            $book_thumbnail = $book_to_delete["book_thumbnail"];

            $sql = $connection -> prepare("DELETE FROM saved_books WHERE book_id=?");
            $sql -> bind_param("i", $book_primary_key);
            $sql -> execute();

            $sql = $connection -> prepare("DELETE FROM books WHERE id=? AND uploaded_by=?");
            $sql -> bind_param("ii", $book_primary_key, $user_id);
            $response = $sql -> execute();

            closeConnection($connection);

            if($response === TRUE){
                $book_name = BOOK_UPLOAD_CONFIG["BOOKS_FOLDER"]."\\".$book_id.".pdf";
                $image_preview = BOOK_UPLOAD_CONFIG["BOOKS_PREVIEW_IMAGE_FOLDER"]."\\".$book_thumbnail;

                unlink($book_name);
                unlink($image_preview);

                $_SESSION["main-message"] = "Your book was deleted successfully.";
                $_SESSION["main-message-type"] = "normal";
            } else {
                $_SESSION["main-message"] = "Something went wrong, while deleting book!";
                $_SESSION["main-message-type"] = "abnormal";
            }

            die(header("Location: library.php?type=uploaded"));
        }
    }
}

$sql = $connection->prepare("SELECT id FROM users WHERE users.uuid=?");
$sql -> bind_param("s",$_SESSION["USER-UUID"]);
$sql -> execute();

$result = $sql -> get_result();
$user_id = $result -> fetch_row()[0];

$sql -> close();

if($CurrentPath === "uploaded"){
    $sql = $connection -> prepare("SELECT books.id, books.name, books.author_name, books.book_filename, books.book_thumbnail FROM books INNER JOIN users ON users.id=books.uploaded_by WHERE users.id=? ORDER BY books.uploaded_at DESC");
    $sql->bind_param("i", $user_id);
    $sql -> execute();

    $result = $sql -> get_result();
} else {
    $sql = $connection -> prepare("SELECT * FROM books WHERE books.id IN (SELECT book_id FROM saved_books WHERE user_id=? ORDER BY books.id DESC)");
    $sql->bind_param("i", $user_id);
    $sql -> execute();

    $result = $sql -> get_result();
}

closeConnection($connection);

if($CurrentPath === "saved"){
    $PageTitle = "Saved books";
} else {
    $PageTitle = "Uploaded books";
}

$stylesheets = array("static/css/book.css");
include_once("includes/header.php");
?>

<section>
    <div class="section">
        <div class="navbar-inner">
            <div class="header-b <?= $CurrentPath === "saved" ? 'underline' : '' ?>">
                <a href="library.php?type=saved">Saved Books</a>
            </div>
            <div class="header-b <?= $CurrentPath === "uploaded" ? 'underline' : '' ?>">
                <a href="library.php?type=uploaded">Uploaded Books</a>
            </div>
        </div>

        <div class="books-colls">

            <?php
            if($result -> num_rows > 0){
                while($data = $result -> fetch_assoc()){
                    ?>
                    <a class="book" href="book.php?book=<?= $data["book_filename"] ?>" >
                        <div class="book-image">
                            <img src="<?= BOOK_UPLOAD_CONFIG["BOOKS_PREVIEW_IMAGE_STATIC"].$data["book_thumbnail"] ?>" alt="Book image">
                        </div>
                        <div class="book-details">
                            <div>
                                <?php
                                    if(strlen($data["name"]) <= 45){
                                        echo $data["name"];
                                    } else {
                                        echo substr($data["name"],0,42)."...";
                                    }
                                ?>
                            </div>
                            <div>
                                <div>
                                    <?php
                                        if(strlen($data["author_name"]) <= 30){
                                            echo $data["author_name"];
                                        } else {
                                            echo substr($data["author_name"],0,27)."...";
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                            if($CurrentPath !== "saved"){
                                ?>
                                <div>
                                    <form action="library.php" method="post" class="delete-form">
                                        <input type="hidden" name="action" value="delete" required />
                                        <input type="hidden" name="book" value="<?= $data["book_filename"] ?>" required />
                                        <button type="submit">Delete this book</button>
                                    </form>
                                </div>
                                <?php
                            }
                        ?>

                    </a>

                    <?php
                }
            ?>

            <?php
            } else {
                if($CurrentPath === "saved"){
                    echo "<div class='header-b' style='text-align:center;'>You have not saved any book.</div>";
                } else {
                    echo "<div class='header-b' style='text-align:center;'>You have not uploaded any book.</div>";
                }
            }
            ?>
        </div>
    </div>

    
</section>

<?php
include_once("includes/footer.php");
?>