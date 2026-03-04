<?php
// MEDIUM: validate numeric only, still no auth check (still vulnerable)

$id = $_GET['id'] ?? '';
$row = null;

if ($id !== '' && ctype_digit($id)) {
    $idInt = (int)$id;

    $query  = "SELECT user, first_name, last_name FROM users WHERE user_id = {$idInt} LIMIT 1;";
    $result = mysqli_query($GLOBALS["___mysqli_ston"], $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
    }
}

$html = '
<form action="#" method="GET">
    User ID (numbers only):
    <input type="text" name="id" value="' . htmlspecialchars($id, ENT_QUOTES) . '">
    <input type="submit" name="Submit" value="View Profile">
</form>
<br />
<pre>Username: ' . htmlspecialchars($row['user'] ?? 'N/A', ENT_QUOTES) . '
Name: ' . htmlspecialchars(($row['first_name'] ?? 'N/A') . ' ' . ($row['last_name'] ?? ''), ENT_QUOTES) . '</pre>
';