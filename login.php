<?php

define( 'DVWA_WEB_PAGE_TO_ROOT', '' );
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

dvwaPageStartup( array( ) );

dvwaDatabaseConnect();

if( isset( $_POST[ 'Login' ] ) ) {
    // Anti-CSRF
    if (array_key_exists ("session_token", $_SESSION)) {
        $session_token = $_SESSION[ 'session_token' ];
    } else {
        $session_token = "";
    }

    checkToken( $_REQUEST[ 'user_token' ], $session_token, 'login.php' );

    $user = $_POST[ 'username' ];
    $user = stripslashes( $user );
    $user = ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"],  $user ) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""));

    $pass = $_POST[ 'password' ];
    $pass = stripslashes( $pass );
    $pass = ((isset($GLOBALS["___mysqli_ston"]) && is_object($GLOBALS["___mysqli_ston"])) ? mysqli_real_escape_string($GLOBALS["___mysqli_ston"],  $pass ) : ((trigger_error("[MySQLConverterToo] Fix the mysql_escape_string() call! This code does not work.", E_USER_ERROR)) ? "" : ""));
    $pass = md5( $pass );

    $query = ("SELECT table_schema, table_name, create_time
                FROM information_schema.tables
                WHERE table_schema='{$_DVWA['db_database']}' AND table_name='users'
                LIMIT 1");
    $result = @mysqli_query($GLOBALS["___mysqli_ston"],  $query );
    if( mysqli_num_rows( $result ) != 1 ) {
        dvwaMessagePush( "First time using DVWA.<br />Need to run 'setup.php'." );
        dvwaRedirect( DVWA_WEB_PAGE_TO_ROOT . 'setup.php' );
    }

    $query  = "SELECT * FROM `users` WHERE user='$user' AND password='$pass';";
    $result = @mysqli_query($GLOBALS["___mysqli_ston"],  $query ) or die( '<pre>' . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)) . '.<br />Try <a href="setup.php">installing again</a>.</pre>' );
    if( $result && mysqli_num_rows( $result ) == 1 ) {    // Login Successful...
        dvwaMessagePush( "You have logged in as '{$user}'" );
        dvwaLogin( $user );
        dvwaRedirect( DVWA_WEB_PAGE_TO_ROOT . 'index.php' );
    }

    // Login failed
    dvwaMessagePush( 'Login failed' );
    dvwaRedirect( 'login.php' );
}

$messagesHtml = messagesPopAllToHtml();

Header( 'Cache-Control: no-cache, must-revalidate');    // HTTP/1.1
Header( 'Content-Type: text/html;charset=utf-8' );      // TODO- proper XHTML headers...
Header( 'Expires: Tue, 23 Jun 2009 12:00:00 GMT' );     // Date in the past

// Anti-CSRF
generateSessionToken();

echo "<!DOCTYPE html>
<html lang=\"en-GB\">
<head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Login :: Cyber Attack Simulation</title>
    
    <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css\" rel=\"stylesheet\" />
    <style>
        :root {
            --bg-color: #f0f2f5;
            --card-bg: #ffffff;
            --primary-blue: rgb(11, 39, 100);
            --text-dark: #202124;
            --text-muted: #5f6368;
            --border-color: #dadce0;
        }
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        #wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 48px 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            text-align: center;
        }
        .logo-container {
            margin-bottom: 24px;
        }
        .logo-icon {
            font-size: 48px;
            color: var(--primary-blue);
            margin-bottom: 12px;
        }
        .logo-text {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary-blue);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .tagline {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 32px;
        }
        fieldset { border: none; padding: 0; margin: 0; }
        .input-group {
            text-align: left;
            margin-bottom: 24px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
        }
        .loginInput {
            width: 100%;
            padding: 13px 15px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-dark);
            font-size: 15px;
            box-sizing: border-box;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .loginInput:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px rgba(26,115,232,0.2);
            outline: none;
        }
        input[type=\"submit\"] {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.2s;
            margin-top: 8px;
        }
        input[type=\"submit\"]:hover {
            background-color: rgb(11, 39, 100);
            box-shadow: 0 1px 3px rgba(60,64,67,0.3);
        }
        .messages {
            margin-top: 24px;
            color: #d93025;
            font-size: 14px;
            min-height: 20px;
        }
        #footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }
        #footer a { color: var(--primary-blue); text-decoration: none; }
        #footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div id=\"wrapper\">
    <div class=\"login-card\">
        <div class=\"logo-container\">
            <div class=\"logo-icon\">
                <i class=\"fa-solid fa-shield-virus\"></i>
            </div>
            <div class=\"logo-text\">Cyber Attack Simulation</div>
        </div>
        <div class=\"tagline\">Learning Management System</div>

        <form action=\"login.php\" method=\"post\">
            <fieldset>
                <div class=\"input-group\">
                    <label for=\"user\">Username</label>
                    <input type=\"text\" class=\"loginInput\" name=\"username\" id=\"user\" required autofocus>
                </div>
                
                <div class=\"input-group\">
                    <label for=\"pass\">Password</label>
                    <input type=\"password\" class=\"loginInput\" name=\"password\" id=\"pass\" AUTOCOMPLETE=\"off\" required>
                </div>

                <div class=\"submit-container\">
                    <input type=\"submit\" value=\"Login\" name=\"Login\">
                </div>
            </fieldset>
            " . tokenField() . "
        </form>

        <div class=\"messages\">
            {$messagesHtml}
        </div>
    </div>

    <div id=\"footer\">
        <p>" . dvwaExternalLinkUrlGet( 'https://github.com/digininja/DVWA/', 'Credit : Damn Vulnerable Web Application (DVWA)' ) . "</p>
    </div>
</div>

</body>
</html>";

?>