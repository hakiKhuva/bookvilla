<?php require_once("helpers.php") ?>

<script>
    function remove_cookie_popup(){
        const popup = document.getElementById("cookie-popup");
        if(popup){
            const parentElement = popup.parentElement;
            if(parentElement){
                parentElement.removeChild(popup);
                window.localStorage.setItem("cookie-popup",true)
            }
        }
        return false;
    }
</script>

<div id="cookie-popup" class="popup" style="display: none;">
    <div class="content">
        We use cookies to remember your preferences, and optimize your experience.
    </div>
    <div class="content small right">
        <a href="#" class="a-link" onclick="return remove_cookie_popup();">Accept and close</a>
    </div>
</div>

<script>
    if(window.localStorage.getItem("cookie-popup") !== "true") {
        const popup = document.getElementById("cookie-popup");
        if(popup){
            popup.style.display = "block";
        }
    }
</script>