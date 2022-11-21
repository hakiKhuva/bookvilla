<?php
session_start();

require_once("includes/config.php");
require_once("includes/connection.php");
require_once("includes/helpers.php");

$is404 = FALSE;
$book_GET = "";
$user_id = NULL;

if(!isset($_GET["book"])){
    $is404 = TRUE;
} else {
    $book_GET = $_GET["book"];
    $connection = getConnection();

    $sql = $connection -> prepare("SELECT books.name, books.author_name, books.book_filename, books.book_thumbnail, books.book_language, books.book_category, books.book_description, books.uploaded_at, books.download_count, users.u_name, books.id, users.confirm_token, books.book_filesize FROM books INNER JOIN users ON books.uploaded_by=users.id WHERE book_filename=?");
    $sql -> bind_param("s",$_GET["book"]);

    $sql -> execute();

    $result = $sql -> get_result();

    if($result -> num_rows > 0){
        $data = $result -> fetch_assoc();

        if(isLoggedin()){
            $book_id = $data["id"];
            $user_id = getCurrentUserId($connection);

            $sql = $connection -> prepare("SELECT COUNT(*) FROM saved_books WHERE book_id=? AND user_id=?");
            $sql -> bind_param("ii",$book_id, $user_id);

            $sql -> execute();

            $save_book_result = $sql -> get_result();
            $count_save = $save_book_result -> fetch_row();

            $is_book_saved = $count_save[0] > 0 ? TRUE : FALSE;

        }

    } else {
        $is404 = TRUE;
    }

    closeConnection($connection);
}

if($is404 === FALSE){
    if($_SERVER["REQUEST_METHOD"] === "POST"){
        if(isset($_POST["data"])){
            if(!isLoggedin()){
                $_SESSION["main-message"] = "Please signin to save this book!";
                $_SESSION["main-message-type"] = "abnormal";
                die(header("Location: book.php?book=$book_GET"));
            }

            $book_id = $_POST["data"];

            $connection = getConnection();
            $sql = $connection -> prepare("SELECT books.id FROM books WHERE book_filename=?");
            $sql -> bind_param("s", $book_id);

            $sql -> execute();

            $result1 = $sql -> get_result();

            $sql = $connection -> prepare("SELECT users.id FROM users WHERE uuid=?");
            $sql -> bind_param("s", $_SESSION["USER-UUID"]);

            $sql -> execute();

            $result2 = $sql -> get_result();

            if($result1 -> num_rows > 0 && $result2 -> num_rows > 0){
                $result1 = $result1 -> fetch_row();
                $result2 = $result2 -> fetch_row();
                $book_id = $result1[0];
                $user_id = $result2[0];

                $sql = $connection -> prepare("SELECT COUNT(*) FROM saved_books WHERE book_id=? AND user_id=?");
                $sql -> bind_param("ii", $book_id, $user_id);

                $exec_result = $sql -> execute();
                $result = $sql -> get_result();

                if($exec_result === TRUE){
                    $count_of_save = $result -> fetch_row()[0];

                    if($count_of_save > 0){
                        $sql = $connection -> prepare("DELETE FROM saved_books WHERE book_id=? AND user_id=?");
                        $sql -> bind_param("ii",$book_id, $user_id);

                        $res = $sql -> execute();
                        if($res === TRUE){
                            $_SESSION["main-message"] = "Book removed from saved books successfully.";
                            $_SESSION["main-message-type"] = "normal";
                        } else {
                            $_SESSION["main-message"] = "Cannot remove book from saved books, tryagain later!";
                            $_SESSION["main-message-type"] = "abnormal";
                        }
                    } else {
                        $sql = $connection -> prepare("INSERT INTO saved_books(book_id, user_id) VALUES(?,?)");
                        $sql -> bind_param("ii",$book_id, $user_id);

                        $res = $sql -> execute();
                        if($res === TRUE){
                            $_SESSION["main-message"] = "Book saved successfully.";
                            $_SESSION["main-message-type"] = "normal";
                        } else {
                            $_SESSION["main-message"] = "Cannot save the book, tryagain later!";
                            $_SESSION["main-message-type"] = "abnormal";
                        }
                    }
                }
            } else {
                $is404 = TRUE;
            }

            closeConnection($connection);
            die(header("Location: book.php?book=$book_GET"));
        } else {
            die(header("Location: book.php?book=$book_GET"));
        }
    }
}

if($is404 === TRUE){
    $PageTitle = "Book not found!";
} else {
    $PageTitle = $data["name"]." by ".$data["author_name"];
}

if($is404 !== TRUE){
    $PageDescription = $data["book_description"];
}
$stylesheets = array("static/css/book.css");
require_once("includes/header.php");
?>

<div>
    <?php
        if($is404 === TRUE){
            ?>
                <div class="full-center-page">
                    <div class="header">Oops!</div>
                    <p class="description">
                        Book that you are looking for not found! Please check the URL if you've typed it manually and tryagain.
                    </p>
                    <div>
                        <form action="home.php" class="form" style="width: max-content; margin: 0px; padding: 0px;" >
                            <button type="submit" style="margin: 20px; width: max-content;padding-left: 25px;padding-right: 25px;">Back to Home</button>
                        </form>
                    </div>
                </div>
            <?php
        }
        else {
            ?>
                <div id="book">
                    <div id="book-image">
                        <img src="<?= BOOK_UPLOAD_CONFIG["BOOKS_PREVIEW_IMAGE_STATIC"].$data["book_thumbnail"] ?>" alt="Book image">
                    </div>

                    <div id="details">
                        <div id="name">
                            <span>
                                <?= $data["name"] ?>
                            </span>
                        </div>

                        <div id="author">
                            <span>
                                by <?= $data["author_name"] ?>
                            </span>
                        </div>

                        <table id="other-details">
                            <tr>
                                <td>
                                    Language
                                </td>
                                <td>
                                    <?= BOOK_LANGUAGES[$data["book_language"]] ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Category
                                </td>
                                <td>
                                    <?= BOOK_CATEGORIES[$data["book_category"]] ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Filesize
                                </td>
                                <td>
                                    <?= round($data["book_filesize"]/1024/1024,2) ?> MB(s)
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Uploaded Date
                                </td>
                                <td>
                                    <?php
                                        $dt = strtotime($data["uploaded_at"]);
                                        print_r(date("Y-m-d",$dt));
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Uploaded by
                                </td>
                                <td style="word-break: break-all;">
                                    <?= $data["u_name"] ?>(<?= $data["confirm_token"] ?>)
                                </td>
                            </tr>
                        </table>

                        <div class="buttons">
                            <form action="book.php?book=<?= $data["book_filename"] ?>" method="post" class="book-form-btn">
                                <input type="hidden" name="data" value="<?= $data["book_filename"] ?>" required />
                                <button type="submit" class="button book-btn">
                                    <?php 
                                    if(isset($is_book_saved) && $is_book_saved){
                                        echo "Remove from saved";
                                    } else {
                                        echo "Save Book";
                                    } 
                                    ?>
                                </button>
                            </form>

                            <form action="download.php" method="get" class="book-form-btn">
                                <input type="hidden" name="data" value="<?= $data["book_filename"] ?>" required />
                                <button type="submit" class="button book-btn" id="download-btn">
                                    Download Book(<?= $data["download_count"] ?>)
                                </button>
                            </form>

                        </div>

                        <?php
                            if($data["book_description"]){
                                ?>
                                    <div id="description">
                                        <div class="header-b">Book description</div>
                                        <div class="content" style="max-height: 300px; overflow:hidden; transition: 200s;">
                                            <p><?= nl2br($data["book_description"]) ?></p>
                                        </div>
                                        <button style="display: none;" id="read-more" type="button" onclick="expand_description();">Read more</button>
                                    </div>
                                <?php
                            }
                        ?>

                        <div style="word-break: break-all; word-wrap: break-word;">
                            <span><b>Book Id</b></span>
                            <span><?= $data["book_filename"] ?> </span>
                        </div>
                    </div>
                </div>
            <?php
        }
    ?>
</div>


<script>
    const desc = document.getElementById("description");
    if(desc){
        const Height = desc.children[1].scrollHeight;
        if(Height > 300){
            desc.children[2].style.display = "block";
        }
    }

    function expand_description(){
        if(desc){
            const cont = desc.children[1];
            if(cont){
                if(cont.style["max-height"] === "300px"){
                    cont.style["max-height"] = "max-content";
                    desc.children[2].innerText = "Read less";
                } else {
                    cont.style["max-height"] = "300px";
                    desc.children[2].innerText = "Read more";
                }
            }
        }
    }
</script>

<?php
    require_once("includes/footer_full.php");
?>