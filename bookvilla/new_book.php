<?php
session_start();

require_once("includes/helpers.php");
LoginRequired();

require_once("includes/config.php");
require_once("includes/connection.php");

$book_name = "";
$author_name = "";
$book_desc = "";

if($_SERVER["REQUEST_METHOD"] === "POST"){
    date_default_timezone_set("Asia/Kolkata");

    if(
        !isset($_POST["name"]) ||
        !isset($_POST["author-name"]) ||
        !isset($_FILES["book-file"]) || 
        !isset($_FILES["book-preview-image"]) ||
        !isset($_POST["book-lang"]) ||
        !isset($_POST["book-cat"]) ||
        !isset($_POST["book-description"])
    ){
        $_SESSION["main-message"] = "Something went wrong, make sure file is not too large!";
        die(header("Location: new_book.php"));
    }

    $book_name = htmlentities(ltrim(rtrim($_POST["name"])));
    $author_name = htmlentities(ltrim(rtrim($_POST["author-name"])));
    $book_file = $_FILES["book-file"];
    $book_preview_image = $_FILES["book-preview-image"];
    $book_lang = $_POST["book-lang"];
    $book_category = $_POST["book-cat"];
    $book_desc = htmlentities(ltrim(rtrim($_POST["book-description"])));
    $book_unique_name = getSHA1(generate_csrf()[0].getSHA1($book_name."-".$author_name));

    if(!isset($book_file["type"]) || empty($book_file["type"]) || strtolower($book_file["type"]) !== "application/pdf"){
        $_SESSION["main-message"] = "Book file must be type of PDF.";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    $NAME_LENGTH = BOOK_CONFIG["NAME_LENGTH"];
    if(strlen($book_name) < $NAME_LENGTH[0] || strlen($book_name) > $NAME_LENGTH[1]){
        $_SESSION["main-message"] = "Book name length must be between $NAME_LENGTH[0] and $NAME_LENGTH[1]!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    $AUTHOR_NAME_LENGTH = BOOK_CONFIG["AUTHOR_NAME_LENGTH"];
    if(strlen($author_name) < $AUTHOR_NAME_LENGTH[0] || strlen($author_name) > $AUTHOR_NAME_LENGTH[1]){
        $_SESSION["main-message"] = "Author name length must be between $AUTHOR_NAME_LENGTH[0] and $AUTHOR_NAME_LENGTH[1]!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    $BOOK_DESC_LENGTH = BOOK_CONFIG["BOOK_DESC_LENGTH"];
    if(strlen($book_desc) < $BOOK_DESC_LENGTH[0] || strlen($book_desc) > $BOOK_DESC_LENGTH[1]){
        $_SESSION["main-message"] = "Book description length must be between $BOOK_DESC_LENGTH[0] and $BOOK_DESC_LENGTH[1]!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    if(!in_array($book_category, array_keys(BOOK_CATEGORIES))){
        $_SESSION["main-message"] = "Book category must be from given options only!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    if(!in_array($book_lang, array_keys(BOOK_LANGUAGES))){
        $_SESSION["main-message"] = "Book languages must be from given options only!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    $ext = explode(".",$book_preview_image["name"]);

    if(count($ext) > 1){
        $ext = ".".$ext[count($ext)-1];
    } else {
        $ext = "";
    }
    
    $image_types = array("image/png","image/jpeg");

    if(!isset($book_preview_image["type"]) || empty($book_preview_image["type"]) || !in_array(strtolower($book_preview_image["type"]), $image_types)){
        $_SESSION["main-message"] = "Book Preview Image file must be type of PNG or JPEG.";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    if(!isset($book_file["type"]) || empty($book_file["type"]) || $book_file["type"] !== "application/pdf"){
        $_SESSION["main-message"] = "Book file must be in PDF format!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    $name_book = BOOK_UPLOAD_CONFIG["BOOKS_FOLDER"]."\\".$book_unique_name.".pdf";
    $image_preview_book = BOOK_UPLOAD_CONFIG["BOOKS_PREVIEW_IMAGE_FOLDER"]."\\".$book_unique_name;

    move_uploaded_file($book_file["tmp_name"], $name_book);
    move_uploaded_file($book_preview_image["tmp_name"], $image_preview_book.$ext);
    
    $connection = getConnection();

    $sql = $connection -> prepare("SELECT id from users WHERE uuid=?");
    $sql -> bind_param("s",$_SESSION["USER-UUID"]);

    if($sql -> execute() !== TRUE){
        closeConnection($connection);
        $_SESSION["main-message"] = "Something went wrong, tryagain later!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    $result = $sql -> get_result();

    $data = $result -> fetch_row();
    $user_id = $data[0];
    $book_file_size = filesize($name_book);

    $thumbnail_image_name = $book_unique_name.$ext;

    $sql = $connection -> prepare("INSERT INTO books (name, author_name, book_filename, book_filesize, book_thumbnail, book_language, book_category, book_description, uploaded_by) VALUES(?,?,?,?,?,?,?,?,?)");
    $sql -> bind_param("sssdssssi", $book_name, $author_name, $book_unique_name, $book_file_size, $thumbnail_image_name, $book_lang, $book_category, $book_desc, $user_id);

    if($sql -> execute() !== TRUE){
        closeConnection($connection);
        $_SESSION["main-message"] = "Something went wrong, tryagain later!";
        $_SESSION["main-message-type"] = "abnormal";
        die(header("Location: new_book.php"));
    }

    closeConnection($connection);

    $_SESSION["main-message"] = "Book uploaded successfully.";
    $_SESSION["main-message-type"] = "normal";
    die(header("Location: new_book.php"));
}

$PageTitle = "Upload New Book";
$stylesheets = array("static/css/new_book.css");
require_once("includes/header.php");
?>

<form action="" class="form-w book-form" method="POST" enctype="multipart/form-data">
    <h1 style="margin: 10px;margin-top: -10px; padding: 0px;text-align: center;">Upload Book</h1>

    <div class="label">
        <label for="name">Book name</label>
        <input type="text" name="name" id="name" required />
        <small>Book name length should be between <?= BOOK_CONFIG["NAME_LENGTH"][0] ?> and <?= BOOK_CONFIG["NAME_LENGTH"][1] ?>.</small>
    </div>
    <div class="label">
        <label for="author-name">Author name</label>
        <input type="text" name="author-name" id="author-name" required />
        <small>Author name length should be between <?= BOOK_CONFIG["AUTHOR_NAME_LENGTH"][0] ?> and <?= BOOK_CONFIG["AUTHOR_NAME_LENGTH"][1] ?>.</small>
    </div>
    <div class="label">
        <label for="book-file">Book file</label>
        <input type="file" name="book-file" id="book-file" accept="application/pdf" required />
        <div style="display: none; cursor: pointer; border: 1px solid var(--fg);padding: 13.5px 20px;  width: max-content;" id="file-descriptor" class="input">
            <div class="label">
                <span id="filename-h">Click to add PDF File</span>
                <span id="filename"></span>
            </div>
        </div>
    </div>
    <div class="label">
        <label for="book-preview-image">Book Preview Image</label>
        <input type="file" name="book-preview-image" id="book-preview-image" accept="image/png, image/jpeg" required />
        <div style="display: none; cursor: pointer; border: 1px solid var(--fg);padding: 13.5px 20px;  width: max-content;" id="book-preview-image-descriptor" class="input">
            <div class="label">
                <span id="book-image-h">Click to Add Preview Image</span>
                <span id="book-image-name"></span>
            </div>
        </div>
        <small>You can use <a href="https://pdftoimage.com/" target="_blank">pdftoimage.com</a> to convert pdf to image.</small>
    </div>
    <div class="label">
        <label for="book-lang">Book Language</label>
        <select name="book-lang" id="book-lang">
            <option hidden>Book Language</option>
            <?php
            $LanguageKeys = array_keys(BOOK_LANGUAGES);

            foreach($LanguageKeys as $lang){
                ?>
                    <option value="<?= $lang ?>"><?= BOOK_LANGUAGES[$lang] ?></option>
                <?php
            }
            ?>

        </select>
    </div>
    <div class="label">
        <label for="book-cat">Book Category</label>
        <select name="book-cat" id="book-cat">
            <option hidden>Book Category</option>
            <?php
                $BookCategoryKeys = array_keys(BOOK_CATEGORIES);
                sort($BookCategoryKeys);

                foreach($BookCategoryKeys as $BookCat){
                    ?>
                        <option value="<?= $BookCat ?>"><?= BOOK_CATEGORIES[$BookCat] ?></option>
                    <?php
                }
            ?>
        </select>
    </div>

    <div class="label">
        <label for="book-description">Book Description</label>
        <textarea name="book-description" id="book-description" cols="30" rows="12"></textarea>
        <small>Book description length should be between <?= BOOK_CONFIG["BOOK_DESC_LENGTH"][0] ?> and <?= BOOK_CONFIG["BOOK_DESC_LENGTH"][1] ?>.</small>
    </div>

    <button type="submit">Upload Book</button>
</form>

<script>
    document.getElementById("book-file").style.display = "none"
    document.getElementById("file-descriptor").style.display = "block";

    document.getElementById("file-descriptor").onclick = () => document.getElementById("book-file").click()

    document.getElementById("book-file").onchange = e => {
        if(e.target.value){
            document.getElementById("filename-h").innerText = "Selected file : ";
            document.getElementById("filename").innerText = `${e.target.files[0].name}`;
        } else {
            document.getElementById("filename").innerText = "";
            document.getElementById("filename-h").innerText = "Click to add PDF File";
        }
    }

    document.getElementById("book-preview-image").style.display = "none"
    document.getElementById("book-preview-image-descriptor").style.display = "block";

    document.getElementById("book-preview-image-descriptor").onclick = () => document.getElementById("book-preview-image").click()

    document.getElementById("book-preview-image").onchange = e => {
        if(e.target.value){
            document.getElementById("book-image-h").innerText = "Selected file : ";
            document.getElementById("book-image-name").innerText = `${e.target.files[0].name}`;
        } else {
            document.getElementById("book-image-h").innerText = "Click to Add Book Image";
            document.getElementById("book-image-name").innerText = "";
        }
    }

    if(document.getElementById("book-preview-image").files.length > 0){
        let e = document.getElementById("book-preview-image");
        document.getElementById("book-image-h").innerText = "Selected file : ";
        document.getElementById("book-image-name").innerText = `${e.files[0].name}`;
    }

    if(document.getElementById("book-file").files.length > 0){
        let e = document.getElementById("book-file");
        document.getElementById("filename-h").innerText = "Selected file : ";
        document.getElementById("filename").innerText = `${e.files[0].name}`;
    }
</script>

<?php
require_once("includes/footer.php");
?>