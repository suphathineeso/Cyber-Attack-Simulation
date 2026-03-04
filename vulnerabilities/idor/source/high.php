<?php
// HIGH: ID comes from session (via session-input.php). Still no auth check.

$id = $_SESSION['idor_id'] ?? '';
$row = null;

if ($id !== '' && ctype_digit((string)$id)) {
    $idInt = (int)$id;

    $query  = "SELECT user, first_name, last_name FROM users WHERE user_id = {$idInt} LIMIT 1;";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
    }
}

$html = '
<p>
    <button onclick="window.open(\'session-input.php\', \'idor_session_input\', \'width=420,height=220\'); return false;">
        Set ID in Session
    </button>
</p>
<p>Current Session ID: <b>' . htmlspecialchars((string)($id !== '' ? $id : 'not set'), ENT_QUOTES) . '</b></p>

<pre>Username: ' . htmlspecialchars($row['user'] ?? 'N/A', ENT_QUOTES) . '
Name: ' . htmlspecialchars(($row['first_name'] ?? 'N/A') . ' ' . ($row['last_name'] ?? ''), ENT_QUOTES) . '</pre>
';