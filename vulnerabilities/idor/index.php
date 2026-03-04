<?php

define( 'DVWA_WEB_PAGE_TO_ROOT', '../../' );
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

dvwaPageStartup( array( 'authenticated' ) );

$page = dvwaPageNewGrab();
$page[ 'title' ]   = 'Vulnerability: IDOR' . $page[ 'title_separator' ] . $page[ 'title' ];
$page[ 'page_id' ] = 'idor';
$page[ 'help_button' ]   = 'idor';
$page[ 'source_button' ] = 'idor';

dvwaDatabaseConnect();

$html = '';
$vulnerabilityFile = '';

switch( dvwaSecurityLevelGet() ) {
    case 'low':
        $vulnerabilityFile = 'low.php';
        break;
    case 'medium':
        $vulnerabilityFile = 'medium.php';
        break;
    case 'high':
        $vulnerabilityFile = 'high.php';
        break;
    default:
        $vulnerabilityFile = 'impossible.php';
        break;
}

require_once DVWA_WEB_PAGE_TO_ROOT . "vulnerabilities/idor/source/{$vulnerabilityFile}";

$page[ 'body' ] .= '
<div class="body_padded">
    <h1>Vulnerability: IDOR</h1>
    <p>Try to access other users&#39; data by changing the ID reference. (Training lab)</p>
    <div class="vulnerable_code_area">
        ' . $html . '
    </div>
</div>';

dvwaHtmlEcho( $page );