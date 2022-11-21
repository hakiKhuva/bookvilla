<?php
session_start();

require_once("includes/helpers.php");
require_once("includes/config.php");

// LoginRequired();
require_once("includes/connection.php");

?>

<?php

if(!isLoggedin()){
    // require_once("includes/signin_popup.php");
    $Popups = array("signin_popup");
}

$Book = "";
$Category = NULL;
$BookPerPage = 10;

if(isset($_GET["search-term"])){
    if(isset($_GET["book-cat"]) && !empty($_GET["book-cat"]) && in_array($_GET["book-cat"], array_keys(BOOK_CATEGORIES))){
        $Category = $_GET["book-cat"];
    }

    if(isset($_GET["page"])){
        $currentSearchPage = (int)$_GET["page"];
    } else {
        $currentSearchPage = 1;
    }

    // page : 1
    // 1
    // 1*1 - 1 = 0
    // 1*1 = 1

    $From = ($currentSearchPage*$BookPerPage)-$BookPerPage;
    $To = $BookPerPage;

    $Book = htmlentities($_GET["search-term"]);

    if($Category === NULL && empty($Book)){
        $_SESSION["main-message"] = "Select a category or enter words for search!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: search.php"));
    }

    $book_i = $Book."%";

    $connection = getConnection();

    if($Category !== NULL){
        $sql = $connection -> prepare("SELECT COUNT(*) FROM books WHERE books.name LIKE ? AND books.book_category=?");
        $sql -> bind_param("ss",$book_i, $Category);
    } else {
        $sql = $connection -> prepare("SELECT COUNT(*) FROM books WHERE books.name LIKE ?");
        $sql -> bind_param("s",$book_i);
    }
    
    $sql -> execute();
    $res = $sql -> get_result();

    $count_all = $res -> fetch_row()[0];

    if($Category !== NULL){
        $sql = $connection -> prepare("SELECT * FROM books WHERE books.name LIKE ? AND books.book_category=? LIMIT ?,?");
        $sql -> bind_param("ssii",$book_i,$Category, $From, $To);
    } else {
        $sql = $connection -> prepare("SELECT * FROM books WHERE books.name LIKE ? LIMIT ?,?");
        $sql -> bind_param("sii",$book_i, $From, $To);
    }
    
    $sql -> execute();

    $result = $sql -> get_result();

    $TotalPages = ceil($count_all/$BookPerPage);
}

$PageTitle = "Search";
$stylesheets = array("static/css/search.css", "static/css/book.css");
include_once("includes/header.php");
?>

<div>
    <div class="header" style="margin: auto; text-align: center;">Search Books</div>
    <form method="get" class="form-w" style="margin: 0px;">
        <div class="label" style="margin: auto; width:500px; max-width: 100%; margin-bottom: 15px;">
            <select name="book-cat" id="book-cat" class="input" style="border-radius: 5px; font-family: var(--font-normal);">
                <option hidden>Book Category</option>
                <?php
                    $BookCategoryKeys = array_keys(BOOK_CATEGORIES);
                    sort($BookCategoryKeys);

                    foreach($BookCategoryKeys as $BookCat){
                        ?>
                            <option value="<?= $BookCat ?>" <?= $BookCat === $Category ? "selected" : "" ?>><?= BOOK_CATEGORIES[$BookCat] ?></option>
                        <?php
                    }
                ?>
            </select>
        </div>
        
        <div class="label" style="margin: auto; width:500px; max-width: 100%;">
            <div id="search-icon-s">
            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" x="0" y="0" version="1.1" viewBox="0 0 29 29" xml:space="preserve"><circle cx="11.854" cy="11.854" r="9" fill="none" stroke="#000" stroke-miterlimit="10" stroke-width="2"/><path fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" d="M18.451 18.451l7.695 7.695"/></svg>
            </div>
            <input type="search" name="search-term" placeholder="Search books" id="search-term" value="<?= $Book ?>" />
        </div>
        
        <button hidden type="submit"></button>
    </form>
    <?php
        if(isset($result)){
            if($result -> num_rows === 0){
                ?>
                <div style="text-align: center; ">
                    <div class="header">Book not found!</div>
                    <p>Please use correct words and select correct category to find books. This error also raised when custom page number used.</p>
                </div>
                <?php
            } else {
                ?>
                <div style="text-align: center;">
                    <b>Total Books : <?= $count_all ?></b> | 
                    <b>Pages : <?= $TotalPages ?></b> | 
                    <b>Current Page : <?= $currentSearchPage ?></b>
                </div>
                <div class="books-colls">
                    <?php
                        while($data = $result -> fetch_assoc()){
                            ?>
                            <a class="book" href="book.php?book=<?= $data["book_filename"] ?>" >
                                <div class="book-image">
                                    <img src="<?= BOOK_UPLOAD_CONFIG["BOOKS_PREVIEW_IMAGE_STATIC"].$data["book_thumbnail"] ?>" alt="Book image">
                                </div>
                                <div class="book-details">
                                <div><?php
                                    if(strlen($data["name"]) <= 45){
                                        echo $data["name"];
                                    } else {
                                        echo substr($data["name"],0,42)."...";
                                    }
                                    ?></div>
                                    <div>
                                        <div><?php
                                        if(strlen($data["author_name"]) <= 30){
                                            echo $data["author_name"];
                                        } else {
                                            echo substr($data["author_name"],0,27)."...";
                                        }
                                        ?></div>
                                    </div>
                                </div>
                            </a>
                            <?php
                        }
                    ?>
                </div>
                <div class="buttons search-buttons-page">
                    <?php
                    
                    // if($currentSearchPage > 1){
                        ?>
                        <form action="search.php" method="GET">
                            <input type="hidden" name="book-cat" value="<?= $Category ?>" required />
                            <input type="hidden" name="search-term" value="<?= $Book ?>" required />
                            <input type="hidden" name="page" value="<?= $currentSearchPage-1 ?>" required />
                            <button type="submit" <?= $currentSearchPage > 1 ? "" : "disabled" ?> >Previous</button>
                        </form>
                        <?php
                    // }

                        if(isset($TotalPages) && $TotalPages > 1){
                            ?>
                                <script>
                                    function page_change_jump(e){
                                        if(e){
                                            if(e.value){
                                                const Page = e.value;
                                                const URL_N = new URL(window.location);

                                                URL_N.searchParams.set("page",Page);

                                                window.location = URL_N
                                            }
                                        }
                                    }
                                </script>

                                <form action="search.php" method="GET" class="select-form">
                                    <input type="hidden" name="book-cat" value="<?= $Category ?>" required />
                                    <input type="hidden" name="search-term" value="<?= $Book ?>" required />
                                    <select name="page" id="page" class="round" onchange="page_change_jump(this)">
                                        <option hidden>Jump to</option>
                                        <?php
                                            $p_c = 1;
                                            while($p_c <= $TotalPages){
                                                ?>
                                                    <option value="<?= $p_c ?>"><?= $p_c ?></option>
                                                <?php
                                                $p_c++;
                                            }
                                        ?>
                                    </select>
                                </form>
                            <?php
                        }

                    // if($currentSearchPage < ($count_all/$BookPerPage)){
                        ?>
                        <form action="search.php" method="GET">
                            <input type="hidden" name="book-cat" value="<?= $Category ?>" required />
                            <input type="hidden" name="search-term" value="<?= $Book ?>" required />
                            <input type="hidden" name="page" value="<?= $currentSearchPage+1 ?>" required />
                            <button type="submit" <?= $currentSearchPage < $TotalPages ? "" : "disabled" ?> >Next</button>
                        </form>
                        <?php
                    // }
                    ?>
                </div>
                <?php
            }
        }
    ?>
    </div>

<?php include_once("includes/footer.php") ?>