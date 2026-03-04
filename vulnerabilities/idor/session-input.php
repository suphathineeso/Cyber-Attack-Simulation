<?php

define( 'DVWA_WEB_PAGE_TO_ROOT', '../../' );
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

dvwaPageStartup( array( 'authenticated' ) );

$page = dvwaPageNewGrab();
$page[ 'title' ] = 'IDOR Session Input' . $page[ 'title_separator' ] . $page[ 'title' ];

if( isset( $_POST[ 'id' ] ) ) {
    $_SESSION['idor_id'] = $_POST['id'];
    $page['body'] .= "Session ID set: <b>" . htmlspecialchars($_SESSION['idor_id'], ENT_QUOTES) . "</b><br /><br />";
    $page['body'] .= "<script>window.opener.location.reload(true);</script>";
}

$page['body'] .= '
<form action="#" method="POST">
    <label>User ID:</label>
    <input type="text" size="15" name="id">
    <input type="submit" name="Submit" value="Submit">
</form>
<hr />
<button onclick="self.close();">Close</button>
';

dvwaSourceHtmlEcho( $page );