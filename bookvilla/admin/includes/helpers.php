<?php

require_once("connection.php");

function getSHA256($string_data){
    // return SHA256 Hash of string
    return hash("SHA256", $string_data);
}

function AdminRequired(){
    if(!isset($_SESSION["USER-UUID"]) || !isset($_SESSION["USER-SIGNIN_ID"])){
        http_response_code(403);
        echo("<h1>403 Forbidden</h1>");
        echo("You are not authorized to access this page.");
        die;
    }

    $uuid = $_SESSION["USER-UUID"];
    $signin_id = $_SESSION["USER-SIGNIN_ID"];
    $role = "admin_user";

    $connection = getConnection();
    
    $sql = $connection -> prepare("SELECT u_role_type FROM users WHERE uuid=? AND signin_id=?");
    $sql -> bind_param("ss",$uuid, $signin_id);

    $res = $sql -> execute();

    if($res !== TRUE){
        closeConnection($connection);
        http_response_code(403);
        echo("<h1>403 Forbidden</h1>");
        echo("You are not authorized to access this page.");
        die;
    }

    $result = $sql -> get_result();

    if($result -> num_rows === 0){
        closeConnection($connection);
        http_response_code(401);
        echo("<h1>401 Unauthorized</h1>");
        echo("You are not authorized to access this page.");
        die;
    }

    $data = $result -> fetch_assoc();

    if($data["u_role_type"] !== "admin_user"){
        closeConnection($connection);
        http_response_code(401);
        echo("<h1>401 Unauthorized</h1>");
        echo("You are not authorized to access this page.");
        die;
    }

    closeConnection($connection);
}
?>