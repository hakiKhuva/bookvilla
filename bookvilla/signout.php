<?php
session_start();

require_once(__DIR__."/includes/helpers.php");

LoginRequired();

unset($_SESSION["USER-UUID"]);
unset($_SESSION["USER-SIGNIN_ID"]);

die(header("Location: signin.php"));
?>