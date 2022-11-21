<?php

if (isset($_SERVER['HTTPS']) &&
    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $protocol = 'https://';
}
else {
  $protocol = 'http://';
}

define("APP_URL", $protocol.$_SERVER["HTTP_HOST"]."/");
define("EMAIL", "your_email_here");

define("SIGNIN_CONFIG", array(
    "NAME_MIN_LEN" => 6,
    "NAME_MAX_LEN" => 50,
    "EMAIL_REGEX" => '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
    "PASSWORD_MIN_LEN" => 8,
    "EMAIL_CODE_EXPIRE_TIME" => "15 minutes"
));

define("BOOK_UPLOAD_CONFIG", array(
  "BOOKS_FOLDER" => __DIR__."\\..\\static\\books\\",
  "BOOKS_PREVIEW_IMAGE_FOLDER" => __DIR__."\\..\\static\\books_images\\",
  "BOOKS_PREVIEW_IMAGE_STATIC" => "static\\books_images\\",
  "MAX_BOOK_SIZE" => 20480,
  "MAX_BOOK_PREVIEW_SIZE" => 4096
));

define("BOOK_LANGUAGES", array(
  "english" => "English",
  "hindi" => "Hindi",
  "gujarati" => "Gujarati",
  "tamil" => "Tamil",
  "telugu" => "Telugu"
));

define("BOOK_CATEGORIES", array(
  "business" => "Business",
  "money" => "Money",
  "personal-development" => "Personal Development",
  "software-development" => "Software Development",
  "programming" => "Programming",
  "technology" => "Tech",
  "science-math" => "Science and Math",
  "engineering" => "Engineering",
  "design-art" => "Design and Art",
  "entertainment" => "Entertainment",
  "history" => "History",
  "law" => "Law",
  "medicine" => "Medicine",
  "language" => "Language",
  "sports" => "Sports",
  "comedy" => "Comedy"
));

define("BOOK_CONFIG", array(
  "NAME_LENGTH" => array(10, 80),
  "AUTHOR_NAME_LENGTH" => array(10, 50),
  "BOOK_DESC_LENGTH" => array(100, 7500),
));

define("CONTACT_FORM_CONFIG", array(
  "SUBJECT_LENGTH" => array(10, 100),
  "MESSAGE_LENGTH" => array(50, 10000)
));

?>