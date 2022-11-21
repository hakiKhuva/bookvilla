<?php require_once(__DIR__."/helpers.php")  ?>

        </div>
        <footer>
            <div>
                <div class="link-coll">
                    <div class="name">Developers</div>
                    <a href="https://www.linkedin.com/in/harkishan-khuva-85b2a51ba/" target="_blank">Harkishan Khuva</a>
                    <a href="https://www.linkedin.com/in/nayan-karodiya-6a0459250/" target="_blank">Nayan Karodiaya</a>
                </div>

                <div class="link-coll">
                    <div class="name">Company</div>
                    <a href="<?= url_for("about_us.php") ?>">About Us</a>
                    <a href="<?= url_for("contact_us.php") ?>">Contact Us</a>
                </div>

                <div class="footer-desc">
                    <div class="name">BookVilla</div>
                    <div class="desc">Copyright &copy; 2022, All rights reserved.</div>
                </div>
            </div>
        </footer>
    </body>
</html>