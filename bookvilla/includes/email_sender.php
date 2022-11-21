<?php

function send_email_verification($to, $name, $auth_token)
{
    $email = EMAIL;
    $headers  = "From: BookVilla - email verification <" . $email . ">\n";
    $headers .= "X-Priority: 1\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\n";

    $content = '<head>
    <style>
        body {
            font-family: "Gill Sans", "Gill Sans MT", Calibri, "Trebuchet MS", sans-serif;
        }

        div#section {
            max-width: 500px;
            margin: auto;
            border-width: 5px 0px;
            font-size: 15.5px;
            background-color: #034;
        }

        div#section>header,
        div#section>footer {
            text-align: center;
            background-color: #034;
            color: #fff;
            padding: 15px;
        }

        div#section>header {
            font-size: 23.5px;
            padding: 20px;
            font-weight: bold;
        }

        .link {
            display: block;
            font-size: 17px;
            margin: 30px auto;
            padding: 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            width: max-content;
            color: #034;
            text-decoration: underline;
        }

        #main {
            padding: 15px 10px;
            padding-bottom: 25px;
            margin: 0px 3.5px;
            background-color: #fff;
        }

        div#section>footer {
            padding: 20px;
        }
    </style>
    <title>Email verification</title>
</head>

<body>
    <div id="section">
        <header>BookVilla</header>
        <div id="main">
            <p>
                Hey, <b>'.$name.'</b>
                <br/>
                <br/>
                new account has been created using this email, if this was you then you can verify the email using below link to continue
            </p>
            
            <a class="link" href="'.APP_URL.'/email_verification.php?uid='.$auth_token.'">Click here to verify</a>
            
            <p>
                If this wasn\'t you then you can safely ignore this email.
            </p>

            <p>This link will expire in '.SIGNIN_CONFIG["EMAIL_CODE_EXPIRE_TIME"].' of sent.</p>

            <div class="regards">
                Regards, BookVilla
            </div>
        </div>
        <footer>
            <div>&copy;2022 BookVilla. All rights reserved.</div>
        </footer>
    </div>
</body>';

    return mail($to, "Email verification", $content, $headers);
}


function send_password_reset_link($to, $name, $reset_token){
    $email = EMAIL;
    $headers  = "From: BookVilla - Reset Password <" . $email . ">\n";
    $headers .= "X-Priority: 1\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\n";

    $content = '<head>
    <style>
        body {
            font-family: "Gill Sans", "Gill Sans MT", Calibri, "Trebuchet MS", sans-serif;
        }

        div#section {
            max-width: 500px;
            margin: auto;
            border-width: 5px 0px;
            font-size: 15.5px;
            background-color: #034;
        }

        div#section>header,
        div#section>footer {
            text-align: center;
            background-color: #034;
            color: #fff;
            padding: 15px;
        }

        div#section>header {
            font-size: 23.5px;
            padding: 20px;
            font-weight: bold;
        }

        .link {
            display: block;
            font-size: 17px;
            margin: 30px auto;
            padding: 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            width: max-content;
            color: #034;
            text-decoration: underline;
        }

        #main {
            padding: 15px 10px;
            padding-bottom: 25px;
            margin: 0px 3.5px;
            background-color: #fff;
        }

        div#section>footer {
            padding: 20px;
        }
    </style>
    <title>Reset Password</title>
</head>

<body>
    <div id="section">
        <header>BookVilla</header>
        <div id="main">
            <p>
                Hey, <b>'.$name.'</b>
                <br/>
                <br/>
                Password reset request was performed using this email, if you\'ve requested then you can reset your password by clicking below link.
            </p>
            
            <a class="link" href="'.APP_URL.'/reset_password.php?uid='.$reset_token.'">Click to reset password</a>
            
            <p>
                If this wasn\'t you then you can safely ignore this email.
            </p>

            <p>This link will expire in '.SIGNIN_CONFIG["EMAIL_CODE_EXPIRE_TIME"].' of sent.</p>

            <div class="regards">
                Regards, BookVilla
            </div>
        </div>
        <footer>
            <div>&copy;2022 BookVilla. All rights reserved.</div>
        </footer>
    </div>
</body>';

    return mail($to, "Reset Password", $content, $headers);
}

?>