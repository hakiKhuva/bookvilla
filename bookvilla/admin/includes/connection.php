<?php

function getConnection(){
    $hostname = "localhost";
    $username = "root";
    $password = "";
    $database = "bookvilla";
    
    $conn = @new mysqli($hostname, $username, $password, $database);
    if($conn->connect_error){
        echo "<h1>Error occured</h1><p>Something went wrong, refresh the page or tryagain later.</p><p>If error persists please contact to admin.</p>";
        die;
    }

    if($conn -> connect_error){
        $_SESSION["main_message"] = "Something went wrong!";
        die(header($_SERVER['HTTP_REFERER']));
    }

    return $conn;
}

function closeConnection($conn){
    try{
        if($conn){
            $conn -> close();
        } else {
            echo "<script>alert('conn | connection');</script>";
        }
    } catch(Exception $e) {
        return;
    }
}

?>