<?php
// IMPOSSIBLE: allow viewing only your own profile (robust for DVWA)

$requestedId = $_GET['id'] ?? '';
$row = null;
$message = '';

// 1) Get current logged-in username from DVWA session
$currentUsername = $_SESSION['dvwa']['username'] ?? null;

if ($currentUsername === null) {
    $message = 'Invalid request.';
} else {
    // 2) Map username -> user_id from DB
    $safeUser = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $currentUsername);
    $q = "SELECT user_id FROM users WHERE user = '{$safeUser}' LIMIT 1;";
    $r = mysqli_query($GLOBALS["___mysqli_ston"], $q);
    $meRow = $r ? mysqli_fetch_assoc($r) : null;
    $currentUserId = $meRow['user_id'] ?? null;

    if ($requestedId !== '' && ctype_digit($requestedId) && $currentUserId !== null) {
        $req = (int)$requestedId;
        $me  = (int)$currentUserId;

        if ($req === $me) {
            $q2 = "SELECT user, first_name, last_name FROM users WHERE user_id = {$me} LIMIT 1;";
            $r2 = mysqli_query($GLOBALS["___mysqli_ston"], $q2);
            if ($r2) $row = mysqli_fetch_assoc($r2);
        } else {
            $message = 'Access denied: you can only view your own profile.';
        }
    } elseif ($requestedId !== '') {
        $message = 'Invalid request.';
    }
}

$html = '
<form action="#" method="GET">
    User ID:
    <input type="text" name="id" value="' . htmlspecialchars($requestedId, ENT_QUOTES) . '">
    <input type="submit" name="Submit" value="View Profile">
</form>

<p><b>' . htmlspecialchars($message, ENT_QUOTES) . '</b></p>

<pre>Username: ' . htmlspecialchars($row['user'] ?? 'N/A', ENT_QUOTES) . '
Name: ' . htmlspecialchars(($row['first_name'] ?? 'N/A') . ' ' . ($row['last_name'] ?? ''), ENT_QUOTES) . '</pre>
';