<?php require_once("helpers.php") ?>

<script>
    function remove_signin_popup(){
        const popup = document.getElementById("signin-popup");
        if(popup){
            const parentElement = popup.parentElement;
            if(parentElement){
                parentElement.removeChild(popup);
                window.localStorage.setItem("signin-popup",true)
            }
        }
        return false;
    }
</script>

<div id="signin-popup" class="popup" style="display: none;">
    <div class="content">
        Signin to save books, read and download.
    </div>
    <div class="content small right">
        <a href="#" class="a-link" onclick="return remove_signin_popup();">Dismiss</a>
        <a href="<?= url_for("/signin.php") ?>" class="a-link">Click to Signin</a>
    </div>
</div>

<script>
    if(window.localStorage.getItem("signin-popup") !== "true") {
        const popup = document.getElementById("signin-popup");
        if(popup){
            popup.style.display = "block";
        }
    }
</script>