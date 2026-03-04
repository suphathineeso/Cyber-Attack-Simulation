<?php
if (!defined('DVWA_WEB_PAGE_TO_ROOT')) {
    die('Direct access not permitted');
}

$url = $_GET['url'] ?? '';

if ($url) {
    header("Location: $url");
    exit();
}
?>

<div class="vulnerable_code_area">
    <h3>Open Redirect</h3>

    <form method="GET">
        Redirect to:
        <input type="text" name="url">
        <input type="submit" value="Go">
    </form>

    <hr>
    <b>Vulnerability:</b> Attacker can redirect user to malicious site.
</div>
