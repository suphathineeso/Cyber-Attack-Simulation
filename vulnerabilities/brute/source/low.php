<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== RATE LIMIT =====
if (!isset($_SESSION['attempt'])) {
    $_SESSION['attempt'] = 0;
}

if ($_SESSION['attempt'] >= 5) {
    die("<pre>Too many login attempts. Try again later.</pre>");
}

// ======================

if( isset( $_GET[ 'Login' ] ) ) {

    // Get username
    $user = $_GET[ 'username' ];

    // Get password
    $pass = $_GET[ 'password' ];
    $pass = md5( $pass );

    // Check the database
    $query  = "SELECT * FROM `users` WHERE user = '$user' AND password = '$pass';";
    $result = mysqli_query(
        $GLOBALS["___mysqli_ston"],
        $query
    ) or die( '<pre>' . mysqli_error($GLOBALS["___mysqli_ston"]) . '</pre>' );

    if( $result && mysqli_num_rows( $result ) == 1 ) {

        // ===== RESET ATTEMPTS =====
        $_SESSION['attempt'] = 0;
        // ==========================

        $row    = mysqli_fetch_assoc( $result );
        $avatar = $row["avatar"];

        // Login successful
        $html .= "<p>Welcome to the password protected area {$user}</p>";
        $html .= "<img src=\"{$avatar}\" />";

    }
    else {

        // ===== INCREASE ATTEMPTS =====
        $_SESSION['attempt']++;
        // =============================

        $html .= "<pre><br />Username and/or password incorrect.</pre>";
        $html .= "<pre>Attempts: " . $_SESSION['attempt'] . "</pre>";
    }

    mysqli_close($GLOBALS["___mysqli_ston"]);
}

?>
