<?php
/*********************************************************************
 * DVWA Page Include (Template / Layout Engine)
 * ไฟล์นี้เป็น “ตัวกลาง” ที่ DVWA ใช้สร้างหน้าเว็บส่วนใหญ่
 * - จัดการ session / security level / locale
 * - สร้าง layout (header, navbar, body, footer)
 * - ปุ่ม View Help / View Source / Compare (popup)
 * - เพิ่ม Learning Panel แบบ Template กลาง (แบบ A)
 *********************************************************************/

// -------------------- [1] Safety check: path root must be defined --------------------
if( !defined( 'DVWA_WEB_PAGE_TO_ROOT' ) ) {
	die( 'Cyber Attack Simulation System error- WEB_PAGE_TO_ROOT undefined' );
	exit;
}

// -------------------- [2] Config file must exist --------------------
if (!file_exists(DVWA_WEB_PAGE_TO_ROOT . 'config/config.inc.php')) {
	die ("Cyber Attack Simulation System error - config file not found. Copy config/config.inc.php.dist to config/config.inc.php and configure to your environment.");
}

// -------------------- [3] Load config --------------------
require_once DVWA_WEB_PAGE_TO_ROOT . 'config/config.inc.php';

require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/casLogger.inc.php';
// Declare the $html variable used by some modules
if( !isset( $html ) ) {
	$html = "";
}

// -------------------- [4] Ensure security cookie is valid --------------------
$security_levels = array('low', 'medium', 'high', 'impossible');
if( !isset( $_COOKIE[ 'security' ] ) || !in_array( $_COOKIE[ 'security' ], $security_levels ) ) {

	// Set security cookie to default (or impossible if invalid)
	if( in_array( $_DVWA[ 'default_security_level' ], $security_levels) ) {
		dvwaSecurityLevelSet( $_DVWA[ 'default_security_level' ] );
	} else {
		dvwaSecurityLevelSet( 'impossible' );
	}

	// If the cookie wasn't set then session flags need updating
	dvwa_start_session();
}

/*
 * -------------------- [5] Session configuration based on security level --------------------
 * - impossible: httponly=true, samesite=Strict, regenerate session id
 * - others: allow session fixation (intentionally), httponly=false
 */
function dvwa_start_session() {

	$security_level = dvwaSecurityLevelGet();
	if ($security_level == 'impossible') {
		$httponly = true;
		$samesite = "Strict";
	}
	else {
		$httponly = false;
		$samesite = "";
	}

	$maxlifetime = 86400;
	$secure = false;
	$domain = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST);

	// cannot change cookie params while session active
	if (session_status() == PHP_SESSION_ACTIVE) {
		session_write_close();
	}

	session_set_cookie_params([
		'lifetime' => $maxlifetime,
		'path' => '/',
		'domain' => $domain,
		'secure' => $secure,
		'httponly' => $httponly,
		'samesite' => $samesite
	]);

	// impossible: regenerate id (more secure)
	if ($security_level == 'impossible') {
		session_start();
		session_regenerate_id();
	}
	// lower levels: keep id if exists (to allow fixation by design)
	else {
		if (isset($_COOKIE[session_name()]))
			session_id($_COOKIE[session_name()]);
		session_start();
	}
}

// Start session when logging in, otherwise ensure session exists
if (array_key_exists ("Login", $_POST) && $_POST['Login'] == "Login") {
	dvwa_start_session();
} else {
	if (!session_id()) {
		session_start();
	}
}

// -------------------- [6] Locale default --------------------
if (!array_key_exists ("default_locale", $_DVWA)) {
	$_DVWA[ 'default_locale' ] = "en";
}
dvwaLocaleSet( $_DVWA[ 'default_locale' ] );

// -------------------- [7] Session helper functions --------------------
function &dvwaSessionGrab() {
	if( !isset( $_SESSION[ 'dvwa' ] ) ) {
		$_SESSION[ 'dvwa' ] = array();
	}
	return $_SESSION[ 'dvwa' ];
}

function dvwaPageStartup( $pActions ) {
	if (in_array('authenticated', $pActions)) {
		if( !dvwaIsLoggedIn()) {
			dvwaRedirect( DVWA_WEB_PAGE_TO_ROOT . 'login.php' );
		}
	}
}

function dvwaLogin( $pUsername ) {
	$dvwaSession =& dvwaSessionGrab();
	$dvwaSession[ 'username' ] = $pUsername;
}

function dvwaIsLoggedIn() {
	global $_DVWA;

	if (array_key_exists("disable_authentication", $_DVWA) && $_DVWA['disable_authentication']) {
		return true;
	}
	$dvwaSession =& dvwaSessionGrab();
	return isset( $dvwaSession[ 'username' ] );
}

function dvwaLogout() {
	$dvwaSession =& dvwaSessionGrab();
	unset( $dvwaSession[ 'username' ] );
}

function dvwaPageReload() {
	if  ( array_key_exists( 'HTTP_X_FORWARDED_PREFIX' , $_SERVER )) {
		dvwaRedirect( $_SERVER[ 'HTTP_X_FORWARDED_PREFIX' ] . $_SERVER[ 'PHP_SELF' ] );
	}
	else {
		dvwaRedirect( $_SERVER[ 'PHP_SELF' ] );
	}
}

function dvwaCurrentUser() {
	$dvwaSession =& dvwaSessionGrab();
	return ( isset( $dvwaSession[ 'username' ]) ? $dvwaSession[ 'username' ] : 'Unknown') ;
}

// -------------------- [8] Page object template --------------------
function &dvwaPageNewGrab() {
	$returnArray = array(
		'title'           => 'Cyber Attack Simulation',
		'title_separator' => ' :: ',
		'body'            => '',
		'page_id'         => '',
		'help_button'     => '',
		'source_button'   => '',
	);
	return $returnArray;
}

// -------------------- [9] Theme + Security level helpers --------------------
function dvwaThemeGet() {
	if (isset($_COOKIE['theme'])) {
		return $_COOKIE[ 'theme' ];
	}
	return 'light';
}

function dvwaSecurityLevelGet() {
	global $_DVWA;

	// cookie has priority
	if (isset($_COOKIE['security'])) {
		return $_COOKIE[ 'security' ];
	}

	// if auth disabled, use default
	if (array_key_exists("disable_authentication", $_DVWA) && $_DVWA['disable_authentication']) {
		return $_DVWA[ 'default_security_level' ];
	}

	// fallback
	return 'impossible';
}

function dvwaSecurityLevelSet( $pSecurityLevel ) {
	if( $pSecurityLevel == 'impossible' ) {
		$httponly = true;
	}
	else {
		$httponly = false;
	}

	setcookie( 'security', $pSecurityLevel, 0, "/", "", false, $httponly );
	$_COOKIE['security'] = $pSecurityLevel;
}

function dvwaLocaleGet() {
	$dvwaSession =& dvwaSessionGrab();
	return $dvwaSession[ 'locale' ];
}

function dvwaSQLiDBGet() {
	global $_DVWA;
	return $_DVWA['SQLI_DB'];
}

function dvwaLocaleSet( $pLocale ) {
	$dvwaSession =& dvwaSessionGrab();
	$locales = array('en', 'zh');
	if( in_array( $pLocale, $locales) ) {
		$dvwaSession[ 'locale' ] = $pLocale;
	} else {
		$dvwaSession[ 'locale' ] = 'en';
	}
}

// -------------------- [10] Message functions (flash messages) --------------------
function dvwaMessagePush( $pMessage ) {
	$dvwaSession =& dvwaSessionGrab();
	if( !isset( $dvwaSession[ 'messages' ] ) ) {
		$dvwaSession[ 'messages' ] = array();
	}
	$dvwaSession[ 'messages' ][] = $pMessage;
}

function dvwaMessagePop() {
	$dvwaSession =& dvwaSessionGrab();
	if( !isset( $dvwaSession[ 'messages' ] ) || count( $dvwaSession[ 'messages' ] ) == 0 ) {
		return false;
	}
	return array_shift( $dvwaSession[ 'messages' ] );
}


function messagesPopAllToHtml() {
	$messagesHtml = '';
	while( $message = dvwaMessagePop() ) {
		$messagesHtml .= "<div class=\"message\">{$message}</div>";
	}
	return $messagesHtml;
}

/* ========= Helper: insert Learning Panel OUTSIDE the input box =========
 * Priority:
 * 1) Put AFTER the main vulnerable box (.vulnerable_code_area)  ✅ สวยสุด
 * 2) Else put BEFORE "More Information"
 * 3) Else append to end
 */
function dvwaInsertLearningPanel($bodyHtml, $panelHtml) {

	// 1) After the main vulnerable box
	$patternBox = '/(<div[^>]*class="[^"]*vulnerable_code_area[^"]*"[^>]*>.*?<\/div>)/is';
	if (preg_match($patternBox, $bodyHtml)) {
		return preg_replace($patternBox, "$1\n" . $panelHtml, $bodyHtml, 1);
	}


	// 2) Before "More Information"
	$patternMore = '/(<h[1-6][^>]*>\s*More Information\s*<\/h[1-6]>)/i';
	if (preg_match($patternMore, $bodyHtml)) {
		return preg_replace($patternMore, $panelHtml . "\n$1", $bodyHtml, 1);
	}

	// 3) Fallback append
	return $bodyHtml . "\n" . $panelHtml;
}


/* =========================================================================
 * [11] Learning Meta (Template data)   แบบ A
 * - เพิ่ม/แก้ “สิ่งที่ต้องเรียนรู้” ที่นี่ “ครั้งเดียว”
 * - หน้าใดที่ page_id ตรงกัน จะมี Learning Panel โผล่อัตโนมัติ
 * - เพิ่มได้เรื่อย ๆ เช่น xss_r, csrf, upload, fi, brute, weak_id...
 * ========================================================================= */
/* ========= Learning Meta (Template A: 10 core modules) ========= */
function dvwaLearnMetaGet($page_id){

	$meta = array(

		/* 1) SQL Injection */
		'sqli' => array(
			'title' => 'SQL Injection',
			'why' => array(
				'รับค่าจากผู้ใช้แล้วนำไปต่อเป็น SQL ตรง ๆ (untrusted input → query)',
				'ไม่มี prepared statement ทำให้โครงสร้าง query ถูกเปลี่ยนได้',
			),
			'safe' => array(
				'ทดลองกรอกเฉพาะ “ตัวเลข ID” เช่น 1, 2, 3 เพื่อดูผลลัพธ์ query',
				'เปิด View Source แล้วไฮไลต์จุด: รับ input → สร้าง query → แสดงผล',
			),
			'fix' => array(
				'ใช้ prepared statement + bind parameter',
				'จำกัดชนิดข้อมูล (is_numeric / intval)',
				'จำกัดผลลัพธ์ (LIMIT 1) และไม่ echo error ลึก ๆ',
			),
			'flow' => 'Browser → Request(id) → PHP reads input → Database query → Response'
		),

		/* 2) Command Injection */
		'exec' => array(
			'title' => 'Command Injection',
			'why' => array(
				'นำ input ไปประกอบคำสั่งระบบ (OS command) เช่น ping',
				'ถ้าไม่ตรวจรูปแบบ input อาจทำให้คำสั่งทำงานเกินกว่าที่ตั้งใจ',
			),
			'safe' => array(
				'ทดลองกรอก IP ที่ถูกต้อง เช่น 127.0.0.1 หรือ 8.8.8.8',
				'Compare Low vs Impossible แล้วจดว่า Impossible “กันอะไรเพิ่ม” บ้าง',
			),
			'fix' => array(
				'ตรวจ input แบบ allow-list (เช่น IPv4 เท่านั้น)',
				'หลีกเลี่ยงประกอบคำสั่งจาก string ดิบ ถ้าเลี่ยงไม่ได้ต้อง validate เข้ม',
				'ลดการเปิดเผย error/รายละเอียดระบบ',
			),
			'flow' => 'Browser → Request(ip) → PHP validates → OS command → Output'
		),

		/* 3) CSRF */
		'csrf' => array(
			'title' => 'CSRF',
			'why' => array(
				'ผู้ใช้ล็อกอินค้างอยู่ → เว็บอื่นหลอกให้ยิง request แทนได้',
				'ถ้าไม่มี token/การยืนยันซ้ำ การกระทำสำคัญอาจถูกสั่งโดยไม่รู้ตัว',
			),
			'safe' => array(
				'ดูว่า form มี user_token ไหม และ token ถูกตรวจตรงไหน',
				'Compare Low vs Impossible แล้วสรุป “token เพิ่มความปลอดภัยยังไง”',
			),
			'fix' => array(
				'ใส่ CSRF token ทุก action สำคัญ + ตรวจ server-side',
				'ใช้ SameSite cookie ให้เหมาะสม + ยืนยันซ้ำสำหรับ action เสี่ยง',
			),
			'flow' => 'Victim logged-in → Malicious page triggers request → Server executes action'
		),

		/* 4) XSS (Reflected) */
		'xss_r' => array(
			'title' => 'XSS (Reflected)',
			'why' => array(
				'เอาค่าจาก request ไปแสดงใน HTML ทันที',
				'ถ้าไม่ encode output อาจทำให้โค้ดฝั่ง client ถูกตีความผิดบริบท',
			),
			'safe' => array(
				'สังเกตตำแหน่งที่ “พิมพ์อะไรแล้วถูกสะท้อนกลับ”',
				'Compare แล้วดูว่า Impossible ใช้ htmlspecialchars/encoding ตรงไหน',
			),
			'fix' => array(
				'ทำ output encoding ตามบริบท (HTML/attr/JS/URL)',
				'ทำ allow-list input + ใช้ template engine ที่ escape อัตโนมัติ',
			),
			'flow' => 'Browser → Request(param) → Server echoes into HTML → Browser renders'
		),

		/* 5) XSS (Stored) */
		'xss_s' => array(
			'title' => 'XSS (Stored)',
			'why' => array(
				'ข้อมูลถูกเก็บใน DB แล้วถูกแสดงให้คนอื่นเห็นภายหลัง',
				'ถ้าไม่ encode ตอนแสดงผล จะกระทบผู้ใช้หลายคน (impact สูงกว่า reflected)',
			),
			'safe' => array(
				'สังเกตส่วนที่ “โพสต์แล้วไปแสดงซ้ำ” (เช่น guestbook/comment)',
				'Compare แล้วชี้ว่า encode/validate ถูกเพิ่มตรงไหน',
			),
			'fix' => array(
				'Encode ตอน render เสมอ (สำคัญสุด)',
				'Validate/normalize input และกำหนด allowed content',
			),
			'flow' => 'User submits → Stored in DB → Another user views page → Browser renders'
		),

		/* 6) File Upload */
		'upload' => array(
			'title' => 'File Upload',
			'why' => array(
				'ถ้ารับไฟล์โดยไม่ตรวจชนิด/นามสกุล/การจัดเก็บ อาจนำไปสู่ไฟล์อันตราย',
				'ความเสี่ยงมักมาจาก “ยอมให้ไฟล์รันได้” หรือ “path/สิทธิ์ผิด”',
			),
			'safe' => array(
				'เรียนรู้กระบวนการ: รับไฟล์ → ตรวจ → เก็บ → แสดงลิงก์',
				'Compare Low vs Impossible แล้วดูว่ามีการตรวจอะไรเพิ่ม',
			),
			'fix' => array(
				'Allow-list MIME/type + ตรวจซ้ำ server-side',
				'เปลี่ยนชื่อไฟล์ + เก็บนอก webroot + ปิด execute permission',
				'สแกน/จำกัดขนาด/จำกัดนามสกุล และตรวจ path traversal',
			),
			'flow' => 'Browser upload → Server validates → Store safely → Serve as download'
		),

		/* 7) File Inclusion */
		'fi' => array(
			'title' => 'File Inclusion',
			'why' => array(
				'ใช้พารามิเตอร์กำหนดไฟล์ที่จะ include',
				'ถ้าไม่จำกัด allow-list อาจถูกชี้ไปไฟล์อื่นที่ไม่ควรเข้าถึง',
			),
			'safe' => array(
				'ดู parameter ที่ใช้เลือกไฟล์ (เช่น page=...)',
				'Compare แล้วชี้ว่า Impossible จำกัดอะไร (allow-list/realpath) มากขึ้น',
			),
			'fix' => array(
				'ใช้ allow-list ของหน้า/ไฟล์ที่อนุญาตเท่านั้น',
				'ใช้ realpath + ตรวจว่าอยู่ใน directory ที่กำหนด',
				'ปิด allow_url_include และหลีกเลี่ยง include จาก input ตรง ๆ',
			),
			'flow' => 'Browser → Request(page) → Server maps allowed file → include → Response'
		),

		/* 8) Brute Force */
		'brute' => array(
			'title' => 'Brute Force',
			'why' => array(
				'การเดารหัสผ่านซ้ำ ๆ ถ้าไม่มี rate limit/lockout จะเดาได้ง่ายขึ้น',
				'ระบบ login ที่ตอบสนองต่างกันมาก อาจช่วยให้เดาง่ายขึ้น',
			),
			'safe' => array(
				'สังเกตพฤติกรรมระบบ: จำกัดจำนวนครั้งไหม? มี delay ไหม?',
				'Compare แล้วจดมาตรการ: lockout / throttling / captcha / error msg',
			),
			'fix' => array(
				'Rate limit + เพิ่ม delay แบบ progressive',
				'ล็อกบัญชีชั่วคราว/แจ้งเตือนเมื่อพยายามผิดปกติ',
				'ใช้ MFA และทำ password policy ที่เหมาะสม',
			),
			'flow' => 'Attacker attempts many logins → Server throttles/locks → Prevent guessing'
		),

		/* 9) Weak Session IDs */
		'weak_id' => array(
			'title' => 'Weak Session IDs',
			'why' => array(
				'Session id ที่เดาง่าย/มีรูปแบบ → ถูกยึด session ได้',
				'ถ้าไม่ regenerate หลัง login อาจโดน session fixation',
			),
			'safe' => array(
				'ดูความต่างของระดับ: regenerate id ไหม? cookie flag เป็นยังไง?',
				'สังเกต HttpOnly/SameSite/secure มีผลยังไง (ฝั่ง cookie)',
			),
			'fix' => array(
				'ใช้ session id แบบสุ่มแข็งแรง + regenerate หลัง login',
				'ตั้ง cookie flags: HttpOnly, SameSite, Secure (เมื่อใช้ https)',
			),
			'flow' => 'Browser stores session id cookie → Server trusts it → Protect cookie + regenerate'
		),

		/* 10) IDOR */
		'idor' => array(
			'title' => 'IDOR (Insecure Direct Object Reference)',
			'why' => array(
				'เข้าถึง resource ด้วย id ตรง ๆ (เช่น user_id/order_id)',
				'ถ้าไม่ตรวจสิทธิ์ฝั่ง server ผู้ใช้หนึ่งอาจเข้าถึงข้อมูลของอีกคนได้',
			),
			'safe' => array(
				'ดูว่าหน้าใช้ id ใดในการดึงข้อมูล และมีการตรวจสิทธิ์หรือไม่',
				'Compare แล้วชี้ว่า Impossible เพิ่ม “authorization check” ตรงไหน',
			),
			'fix' => array(
				'ตรวจสิทธิ์ทุกครั้ง server-side (เจ้าของ/บทบาท/นโยบาย)',
				'ใช้ indirect reference หรือ random IDs เมื่อเหมาะสม',
				'บันทึก audit log สำหรับการเข้าถึง resource สำคัญ',
			),
			'flow' => 'Browser requests object id → Server checks authorization → Returns only allowed data'
		),
	);

	return isset($meta[$page_id]) ? $meta[$page_id] : null;
}

/* =========================================================================
 * [12] dvwaHtmlEcho (Main Template Renderer)
 * - สร้างเมนู/navbar
 * - แทรก Learning Panel (ถ้า meta มี)
 * - แสดง body/messages/system info/footer
 * ========================================================================= */
function dvwaHtmlEcho( $pPage ) {

	// -------------------- [12.1] Build menu blocks --------------------
	$menuBlocks = array();

	$menuBlocks[ 'home' ] = array();
	if( dvwaIsLoggedIn() ) {
		$menuBlocks[ 'home' ][] = array( 'id' => 'home', 'name' => 'Home', 'url' => '.' );
		$menuBlocks[ 'home' ][] = array( 'id' => 'instructions', 'name' => 'Instructions', 'url' => 'instructions.php' );
		$menuBlocks[ 'home' ][] = array( 'id' => 'setup', 'name' => 'Setup / Reset DB', 'url' => 'setup.php' );
	}
	else {
		$menuBlocks[ 'home' ][] = array( 'id' => 'setup', 'name' => 'Setup DVWA', 'url' => 'setup.php' );
		$menuBlocks[ 'home' ][] = array( 'id' => 'instructions', 'name' => 'Instructions', 'url' => 'instructions.php' );
	}

	if( dvwaIsLoggedIn() ) {
		$menuBlocks[ 'vulnerabilities' ] = array();
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'brute', 'name' => 'Brute Force', 'url' => 'vulnerabilities/brute/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'exec', 'name' => 'Command Injection', 'url' => 'vulnerabilities/exec/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'csrf', 'name' => 'CSRF', 'url' => 'vulnerabilities/csrf/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'fi', 'name' => 'File Inclusion', 'url' => 'vulnerabilities/fi/.?page=include.php' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'upload', 'name' => 'File Upload', 'url' => 'vulnerabilities/upload/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'captcha', 'name' => 'Insecure CAPTCHA', 'url' => 'vulnerabilities/captcha/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'sqli', 'name' => 'SQL Injection', 'url' => 'vulnerabilities/sqli/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'sqli_blind', 'name' => 'SQL Injection (Blind)', 'url' => 'vulnerabilities/sqli_blind/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'weak_id', 'name' => 'Weak Session IDs', 'url' => 'vulnerabilities/weak_id/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'xss_d', 'name' => 'XSS (DOM)', 'url' => 'vulnerabilities/xss_d/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'xss_r', 'name' => 'XSS (Reflected)', 'url' => 'vulnerabilities/xss_r/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'xss_s', 'name' => 'XSS (Stored)', 'url' => 'vulnerabilities/xss_s/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'csp', 'name' => 'CSP Bypass', 'url' => 'vulnerabilities/csp/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'javascript', 'name' => 'JavaScript Attacks', 'url' => 'vulnerabilities/javascript/' );
		

		if (dvwaCurrentUser() == "admin") {
			$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'authbypass', 'name' => 'Authorisation Bypass', 'url' => 'vulnerabilities/authbypass/' );
		}
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'open_redirect', 'name' => 'Open HTTP Redirect', 'url' => 'vulnerabilities/open_redirect/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'encryption', 'name' => 'Cryptography', 'url' => 'vulnerabilities/cryptography/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'api', 'name' => 'API', 'url' => 'vulnerabilities/api/' );
		$menuBlocks[ 'vulnerabilities' ][] = array('id' => 'idor','name' => 'IDOR','url' => 'vulnerabilities/idor/');
		//$menuBlocks['vulnerabilities'][] = array('id' => 'openredirect','name' => 'Open Redirect','url' => 'vulnerabilities/openredirect/'); //ยังไม่ได้แก้
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'simlab', 'name' => 'Simulation', 'url' => 'vulnerabilities/simlab/' );
		$menuBlocks[ 'vulnerabilities' ][] = array( 'id' => 'ir', 'name' => 'Incident Timeline', 'url' => 'vulnerabilities/ir/' );
		
	}

	$menuBlocks[ 'meta' ] = array();
	if( dvwaIsLoggedIn() ) {
		$menuBlocks[ 'meta' ][] = array( 'id' => 'security', 'name' => 'DVWA Security', 'url' => 'security.php' );
		$menuBlocks[ 'meta' ][] = array( 'id' => 'phpinfo', 'name' => 'PHP Info', 'url' => 'phpinfo.php' );
	}
	$menuBlocks[ 'meta' ][] = array( 'id' => 'about', 'name' => 'About', 'url' => 'about.php' );
	

	// -------------------- [12.2] Security label text --------------------
	$securityText = '';
	switch( dvwaSecurityLevelGet() ) {
		case 'low': $securityText = 'low'; break;
		case 'medium': $securityText = 'medium'; break;
		case 'high': $securityText = 'high'; break;
		default: $securityText = 'impossible'; break;
	}

	/* -------------------- [12.3] Learning Panel injection (Template A) --------------------
	 * จะทำงานเฉพาะ page_id ที่มีใน dvwaLearnMetaGet()
	 * แทรกบนสุดของ $pPage['body'] (เฉพาะหน้าหลักของบท)
	 */
	$learn = dvwaLearnMetaGet($pPage['page_id']);
	if ($learn) {
		$whyLis = '';
		foreach($learn['why'] as $w){ $whyLis .= '<li>' . htmlspecialchars($w) . '</li>'; }

		$safeLis = '';
		foreach($learn['safe'] as $s){ $safeLis .= '<li>' . htmlspecialchars($s) . '</li>'; }

		$fixLis = '';
		foreach($learn['fix'] as $f){ $fixLis .= '<li>' . htmlspecialchars($f) . '</li>'; }

		$panel = "
		<div class='cas-learn'>
		  <div class='cas-learn__top'>
		    <div class='cas-learn__title'>Learning Panel: " . htmlspecialchars($learn['title']) . "</div>
		    <div class='cas-learn__flow'>" . htmlspecialchars($learn['flow']) . "</div>
		  </div>

		  <div class='cas-learn__grid'>
		    <div class='cas-card'>
		      <div class='cas-card__h'>Why vulnerable?</div>
		      <ul class='cas-ul'>{$whyLis}</ul>
		    </div>

		    <div class='cas-card'>
		      <div class='cas-card__h'>Safe Lab</div>
		      <ul class='cas-ul'>{$safeLis}</ul>
		      <div class='cas-note'>หมายเหตุ: โหมดนี้เน้น “เข้าใจระบบ/โค้ด” ไม่เน้น payload โจมตี</div>
		    </div>

		    <div class='cas-card'>
		      <div class='cas-card__h'>Fix checklist</div>
		      <ul class='cas-ul'>{$fixLis}</ul>
		    </div>
		  </div>
		</div>
		";

		$pPage['body'] = dvwaInsertLearningPanel($pPage['body'], $panel);
	}

	// -------------------- [12.4] System info (bottom) --------------------
	$userInfoHtml = '<em>Username:</em> ' . ( dvwaCurrentUser() );
	$securityLevelHtml = "<em>Security Level:</em> {$securityText}";
	$localeHtml = '<em>Locale:</em> ' . ( dvwaLocaleGet() );
	$sqliDbHtml = '<em>SQLi DB:</em> ' . ( dvwaSQLiDBGet() );

	$messagesHtml = messagesPopAllToHtml();
	if( $messagesHtml ) {
		$messagesHtml = "<div class=\"body_padded\">{$messagesHtml}</div>";
	}

	$systemInfoHtml = "";
	if( dvwaIsLoggedIn() )
		$systemInfoHtml = "<div align=\"left\">{$userInfoHtml}<br />{$securityLevelHtml}<br />{$localeHtml}<br />{$sqliDbHtml}</div>";

	// Buttons: Compare + Source (ต้องมี source_button ก่อน)
	if( $pPage[ 'source_button' ] ) {
		$systemInfoHtml =
			dvwaButtonCompareHtmlGet( $pPage[ 'source_button' ] ) . " " .
			dvwaButtonSourceHtmlGet( $pPage[ 'source_button' ] ) . " " .
			$systemInfoHtml;
	}
	// Help button
	if( $pPage[ 'help_button' ] ) {
		$systemInfoHtml = dvwaButtonHelpHtmlGet( $pPage[ 'help_button' ] ) . " " . $systemInfoHtml;
	}

	// -------------------- [12.5] Dropdown helper --------------------
	$buildItems = function($items) use ($pPage) {
		$html = "";
		foreach($items as $it) {
			$active = ($it['id'] == $pPage['page_id']) ? " active" : "";
			$url = DVWA_WEB_PAGE_TO_ROOT . $it['url'];
			$name = htmlspecialchars($it['name']);
			$html .= "<li><a class=\"dropdown-item{$active}\" href=\"{$url}\">{$name}</a></li>";
		}
		return $html;
	};

	// -------------------- [12.6] Split vulnerabilities into categories --------------------
	$inj = [];
	$client = [];
	$auth = [];
	$files = [];
	$misc = [];

	if (dvwaIsLoggedIn()) {
		foreach ($menuBlocks['vulnerabilities'] as $v) {
			switch ($v['id']) {
				case 'sqli':
				case 'sqli_blind':
				case 'exec':
				case 'api':
					$inj[] = $v; break;

				case 'xss_r':
				case 'xss_s':
				case 'xss_d':
				case 'csrf':
				case 'csp':
				case 'javascript':
					$client[] = $v; break;

				case 'brute':
				case 'captcha':
				case 'weak_id':
				case 'authbypass':
					$auth[] = $v; break;

				case 'fi':
				case 'upload':
					$files[] = $v; break;

				case 'open_redirect':
				case 'openredirect':
				case 'idor':
				case 'encryption':
					$misc[] = $v; break;

				default:
					$misc[] = $v; break;
			}
		}
	}

	$homeDrop = $buildItems($menuBlocks['home']);
	$injDrop = $buildItems($inj);
	$clientDrop = $buildItems($client);
	$authDrop = $buildItems($auth);
	$filesDrop = $buildItems($files);
	$miscDrop = $buildItems($misc);
	$toolsDrop = $buildItems($menuBlocks['meta']);

	$currentUserSafe = htmlspecialchars(dvwaCurrentUser());

	// -------------------- [12.7] Inline navbar CSS (your current style) --------------------
	$inlineCss = '
<style>
  .cas-nav { background:#0b1220; border-bottom:2px solid rgba(30,136,229,.55); }
  .cas-brand { font-weight:900; letter-spacing:.35px; text-transform:uppercase; }
  .cas-tag { color:rgba(234,241,255,.65); font-size:11px; font-weight:700; letter-spacing:.25px; }
  .navbar-dark .navbar-nav .nav-link { color: rgba(234,241,255,.86); font-weight:800; letter-spacing:.2px; text-transform:uppercase; font-size:12px; }
  .navbar-dark .navbar-nav .nav-link:hover { color:#fff; }
  .dropdown-menu { border-radius:0; border:1px solid rgba(30,136,229,.25); background:#0b1220; }
  .dropdown-item, .dropdown-item-text { color: rgba(234,241,255,.92); font-weight:800; }
  .dropdown-item:hover, .dropdown-item.active { background: rgba(30,136,229,.18); color:#fff; }
  .dropdown-divider { border-top:1px solid rgba(30,136,229,.20); }
  .cas-menu-scroll { max-height: 55vh; overflow:auto; }
  .cas-user-pill { font-weight:900; letter-spacing:.15px; }
  @media (max-width:768px){ #main_body{ padding:16px !important; } }
</style>
';

	// -------------------- [12.8] Output page HTML --------------------
	Header( 'Cache-Control: no-cache, must-revalidate');
	Header( 'Content-Type: text/html;charset=utf-8' );
	Header( 'Expires: Tue, 23 Jun 2009 12:00:00 GMT' );

	echo "<!DOCTYPE html>
<html lang=\"en-GB\">
<head>
  <meta charset=\"UTF-8\" />
  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />

  <title>{$pPage[ 'title' ]} | Cyber Attack Simulation</title>

  <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css\" rel=\"stylesheet\" />
  <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css\" rel=\"stylesheet\" />

  <link rel=\"stylesheet\" type=\"text/css\" href=\"" . DVWA_WEB_PAGE_TO_ROOT . "dvwa/css/main.css?v=cas_final_full\" />
  <link rel=\"icon\" type=\"image/x-icon\" href=\"" . DVWA_WEB_PAGE_TO_ROOT . "favicon.ico\" />

  <script type=\"text/javascript\" src=\"" . DVWA_WEB_PAGE_TO_ROOT . "dvwa/js/dvwaPage.js\"></script>
  {$inlineCss}
</head>

<body class=\"home " . dvwaThemeGet() . "\">
  <div id=\"container\">

    <div id=\"header\" class=\"d-flex justify-content-between align-items-center\">
      <div class=\"cas-header-logo-container\">
        <h1 style=\"margin: 0; font-size: 28px; font-weight: 800; letter-spacing: 1px; color: #ffffff; text-transform: uppercase; display: flex; align-items: center; gap: 12px;\">
          <i class=\"fa-solid fa-shield-virus\" style=\"font-size: 32px; color: #ffffff;\"></i>
          <span>Cyber Attack Simulation</span>
        </h1>
      </div>
      <a href=\"#\" onclick=\"javascript:toggleTheme();\" class=\"theme-icon\" title=\"Toggle theme between light and dark.\">
        <img src=\"" . DVWA_WEB_PAGE_TO_ROOT . "dvwa/images/theme-light-dark.png\" alt=\"Toggle theme\" />
      </a>
    </div>

    <nav class=\"navbar navbar-expand-lg navbar-dark cas-nav\">
      <div class=\"container-fluid\">

        <a class=\"navbar-brand cas-brand d-flex flex-column\" href=\"" . DVWA_WEB_PAGE_TO_ROOT . "\">
          <span style=\"font-weight: 700;\">Cyber Attack Simulation</span>
          <span class=\"cas-tag\" style=\"font-size: 11px; opacity: 0.8; font-weight: normal;\">Secure learning environment • hands-on labs</span>
        </a>

        <button class=\"navbar-toggler\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#navbarMain\" aria-controls=\"navbarMain\" aria-expanded=\"false\" aria-label=\"Toggle navigation\">
          <span class=\"navbar-toggler-icon\"></span>
        </button>

        <div class=\"collapse navbar-collapse\" id=\"navbarMain\">
          <ul class=\"navbar-nav ms-auto mb-2 mb-lg-0\">
            <li class=\"nav-item dropdown\">
              <a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Home</a>
              <ul class=\"dropdown-menu\">
                {$homeDrop}
              </ul>
            </li>";

	if (dvwaIsLoggedIn()) {
		echo "
			<li class=\"nav-item dropdown\">
			  <a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Injection</a>
			  <ul class=\"dropdown-menu cas-menu-scroll\">
				{$injDrop}
			  </ul>
			</li>

			<li class=\"nav-item dropdown\">
			  <a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Client</a>
			  <ul class=\"dropdown-menu cas-menu-scroll\">
				{$clientDrop}
			  </ul>
			</li>

			<li class=\"nav-item dropdown\">
			  <a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Auth</a>
			  <ul class=\"dropdown-menu cas-menu-scroll\">
				{$authDrop}
			  </ul>
			</li>

			<li class=\"nav-item dropdown\">
			  <a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Files</a>
			  <ul class=\"dropdown-menu cas-menu-scroll\">
				{$filesDrop}
			  </ul>
			</li>

			<li class=\"nav-item dropdown\">
			  <a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Misc</a>
			  <ul class=\"dropdown-menu cas-menu-scroll\">
				{$miscDrop}
			  </ul>
			</li>";
	}

	echo "
			<li class=\"nav-item dropdown\">
			  <a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Tools</a>
			  <ul class=\"dropdown-menu\">
				{$toolsDrop}
			  </ul>
			</li>
		  </ul>";

	// User profile dropdown on the right
	if (dvwaIsLoggedIn()) {
		echo "
		  <ul class=\"navbar-nav ms-3\">
			<li class=\"nav-item dropdown\">
			  <a class=\"nav-link dropdown-toggle cas-user-pill\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">
				{$currentUserSafe}
			  </a>
			  <ul class=\"dropdown-menu dropdown-menu-end\">
				<li><span class=\"dropdown-item-text text-light\">Security: {$securityText}</span></li>
				<li><span class=\"dropdown-item-text text-light\">Locale: " . htmlspecialchars(dvwaLocaleGet()) . "</span></li>
				<li><span class=\"dropdown-item-text text-light\">SQLi DB: " . htmlspecialchars(dvwaSQLiDBGet()) . "</span></li>
				<li><hr class=\"dropdown-divider\"></li>
				<li><a class=\"dropdown-item\" href=\"" . DVWA_WEB_PAGE_TO_ROOT . "logout.php\">Logout</a></li>
			  </ul>
			</li>
		  </ul>";
	}

	echo "
		</div>
	  </div>
	</nav>

	<!-- Main content -->
	<div id=\"main_body\" class=\"container mt-4\">
	  {$pPage[ 'body' ]}
	  <br /><br />
	  {$messagesHtml}
	</div>

	<div class=\"clear\"></div>

	<!-- System info (bottom) -->
	<div id=\"system_info\">
	  {$systemInfoHtml}
	</div>

	<!-- Footer -->
	<div id=\"footer\">
	  <p>Cyber Attack Simulation Secure learning environment • hands-on labs</p>
	  <script src='" . DVWA_WEB_PAGE_TO_ROOT . "dvwa/js/add_event_listeners.js'></script>
	</div>

  </div>

  <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";
}

/* =========================================================================
 * [13] Help/Source pages (ไม่ใส่ Learning Panel)
 * ========================================================================= */
function dvwaHelpHtmlEcho( $pPage ) {
	Header( 'Cache-Control: no-cache, must-revalidate');
	Header( 'Content-Type: text/html;charset=utf-8' );
	Header( 'Expires: Tue, 23 Jun 2009 12:00:00 GMT' );

	echo "<!DOCTYPE html>
<html lang=\"en-GB\">
<head>
  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
  <title>{$pPage[ 'title' ]}</title>
  <link rel=\"stylesheet\" type=\"text/css\" href=\"" . DVWA_WEB_PAGE_TO_ROOT . "dvwa/css/help.css\" />
  <link rel=\"icon\" type=\"\image/ico\" href=\"" . DVWA_WEB_PAGE_TO_ROOT . "favicon.ico\" />
</head>
<body class=\"" . dvwaThemeGet() . "\">
  <div id=\"container\">
	{$pPage[ 'body' ]}
  </div>
</body>
</html>";
}

function dvwaSourceHtmlEcho( $pPage ) {
	Header( 'Cache-Control: no-cache, must-revalidate');
	Header( 'Content-Type: text/html;charset=utf-8' );
	Header( 'Expires: Tue, 23 Jun 2009 12:00:00 GMT' );

	echo "<!DOCTYPE html>
<html lang=\"en-GB\">
<head>
  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
  <title>{$pPage[ 'title' ]}</title>
  <link rel=\"stylesheet\" type=\"text/css\" href=\"" . DVWA_WEB_PAGE_TO_ROOT . "dvwa/css/source.css\" />
  <link rel=\"icon\" type=\"\image/ico\" href=\"" . DVWA_WEB_PAGE_TO_ROOT . "favicon.ico\" />
</head>
<body class=\"" . dvwaThemeGet() . "\">
  <div id=\"container\">
	{$pPage[ 'body' ]}
  </div>
</body>
</html>";
}

// -------------------- [14] External link helper --------------------
function dvwaExternalLinkUrlGet( $pLink,$text=null ) {
	if(is_null( $text ) || $text == "") {
		return '<a href="' . $pLink . '" target="_blank">' . $pLink . '</a>';
	}
	else {
		return '<a href="' . $pLink . '" target="_blank">' . $text . '</a>';
	}
}

// -------------------- [15] Help/Source/Compare buttons (popup triggers) --------------------
function dvwaButtonHelpHtmlGet( $pId ) {
	$security = dvwaSecurityLevelGet();
	$locale = dvwaLocaleGet();
	return "<input type=\"button\" value=\"View Help\" class=\"popup_button\" id='help_button' data-help-url='" . DVWA_WEB_PAGE_TO_ROOT . "vulnerabilities/view_help.php?id={$pId}&security={$security}&locale={$locale}' )\">";
}

function dvwaButtonSourceHtmlGet( $pId ) {
	$security = dvwaSecurityLevelGet();
	return "<input type=\"button\" value=\"View Source\" class=\"popup_button\" id='source_button' data-source-url='" . DVWA_WEB_PAGE_TO_ROOT . "vulnerabilities/view_source.php?id={$pId}&security={$security}' )\">";
}

function dvwaButtonCompareHtmlGet( $pId ) {
	// Compare low vs impossible source side-by-side (your custom page)
	return "<input type=\"button\" value=\"Compare\" class=\"popup_button\" id='compare_button' data-compare-url='" .
		DVWA_WEB_PAGE_TO_ROOT . "vulnerabilities/view_compare.php?id={$pId}' )\">";
}

// -------------------- [16] Database Management (keep original behavior) --------------------
if( $DBMS == 'MySQL' ) {
	$DBMS = htmlspecialchars(strip_tags( $DBMS ));
}
elseif( $DBMS == 'PGSQL' ) {
	$DBMS = htmlspecialchars(strip_tags( $DBMS ));
}
else {
	$DBMS = "No DBMS selected.";
}

function dvwaDatabaseConnect() {
	global $_DVWA;
	global $DBMS;
	global $db;
	global $sqlite_db_connection;

	if( $DBMS == 'MySQL' ) {
		if( !@($GLOBALS["___mysqli_ston"] = mysqli_connect( $_DVWA[ 'db_server' ],  $_DVWA[ 'db_user' ],  $_DVWA[ 'db_password' ], "", $_DVWA[ 'db_port' ] ))
		|| !@((bool)mysqli_query($GLOBALS["___mysqli_ston"], "USE " . $_DVWA[ 'db_database' ])) ) {
			dvwaLogout();
			dvwaMessagePush( 'Unable to connect to the database.<br />' . mysqli_error($GLOBALS["___mysqli_ston"]));
			dvwaRedirect( DVWA_WEB_PAGE_TO_ROOT . 'setup.php' );
		}
		$db = new PDO('mysql:host=' . $_DVWA[ 'db_server' ].';dbname=' . $_DVWA[ 'db_database' ].';port=' . $_DVWA['db_port'] . ';charset=utf8', $_DVWA[ 'db_user' ], $_DVWA[ 'db_password' ]);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}
	elseif( $DBMS == 'PGSQL' ) {
		dvwaMessagePush( 'PostgreSQL is not currently supported.' );
		dvwaPageReload();
	}
	else {
		die ( "Unknown {$DBMS} selected." );
	}

	if ($_DVWA['SQLI_DB'] == SQLITE) {
		$location = DVWA_WEB_PAGE_TO_ROOT . "database/" . $_DVWA['SQLITE_DB'];
		$sqlite_db_connection = new SQLite3($location);
		$sqlite_db_connection->enableExceptions(true);
	}
}

// -- END (Database Management)

function dvwaRedirect( $pLocation ) {
	session_commit();
	header( "Location: {$pLocation}" );
	exit;
}

// -------------------- [17] XSS Stored guestbook helper --------------------
function dvwaGuestbook() {
	$query  = "SELECT name, comment FROM guestbook";
	$result = mysqli_query($GLOBALS["___mysqli_ston"],  $query );

	$guestbook = '';

	while( $row = mysqli_fetch_row( $result ) ) {
		if( dvwaSecurityLevelGet() == 'impossible' ) {
			$name    = htmlspecialchars( $row[0] );
			$comment = htmlspecialchars( $row[1] );
		}
		else {
			$name    = $row[0];
			$comment = $row[1];
		}

		$guestbook .= "<div id=\"guestbook_comments\">Name: {$name}<br />" . "Message: {$comment}<br /></div>\n";
	}
	return $guestbook;
}

// -------------------- [18] Token (CSRF) functions --------------------
function checkToken( $user_token, $session_token, $returnURL ) {
	global $_DVWA;

	if (array_key_exists("disable_authentication", $_DVWA) && $_DVWA['disable_authentication']) {
		return true;
	}

	if( $user_token !== $session_token || !isset( $session_token ) ) {
		dvwaMessagePush( 'CSRF token is incorrect' );
		dvwaRedirect( $returnURL );
	}
}

function generateSessionToken() {
	if( isset( $_SESSION[ 'session_token' ] ) ) {
		destroySessionToken();
	}
	$_SESSION[ 'session_token' ] = md5( uniqid() );
}

function destroySessionToken() {
	unset( $_SESSION[ 'session_token' ] );
}

function tokenField() {
	return "<input type='hidden' name='user_token' value='{$_SESSION[ 'session_token' ]}' />";
}

// -------------------- [19] Setup check variables (DVWA status page uses these) --------------------
$PHPUploadPath    = realpath( getcwd() . DIRECTORY_SEPARATOR . DVWA_WEB_PAGE_TO_ROOT . "hackable" . DIRECTORY_SEPARATOR . "uploads" ) . DIRECTORY_SEPARATOR;
$PHPCONFIGPath       = realpath( getcwd() . DIRECTORY_SEPARATOR . DVWA_WEB_PAGE_TO_ROOT . "config");

$phpDisplayErrors = 'PHP function display_errors: <span class="' . ( ini_get( 'display_errors' ) ? 'success">Enabled' : 'failure">Disabled' ) . '</span>';
$phpDisplayStartupErrors = 'PHP function display_startup_errors: <span class="' . ( ini_get( 'display_startup_errors' ) ? 'success">Enabled' : 'failure">Disabled' ) . '</span>';
$phpDisplayErrors = 'PHP function display_errors: <span class="' . ( ini_get( 'display_errors' ) ? 'success">Enabled' : 'failure">Disabled' ) . '</span>';
$phpURLInclude    = 'PHP function allow_url_include: <span class="' . ( ini_get( 'allow_url_include' ) ? 'success">Enabled' : 'failure">Disabled' ) . '</span> - Feature deprecated in PHP 7.4, see lab for more information';
$phpURLFopen      = 'PHP function allow_url_fopen: <span class="' . ( ini_get( 'allow_url_fopen' ) ? 'success">Enabled' : 'failure">Disabled' ) . '</span>';
$phpGD            = 'PHP module gd: <span class="' . ( ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) ? 'success">Installed' : 'failure">Missing - Only an issue if you want to play with captchas' ) . '</span>';
$phpMySQL         = 'PHP module mysql: <span class="' . ( ( extension_loaded( 'mysqli' ) && function_exists( 'mysqli_query' ) ) ? 'success">Installed' : 'failure">Missing' ) . '</span>';
$phpPDO           = 'PHP module pdo_mysql: <span class="' . ( extension_loaded( 'pdo_mysql' ) ? 'success">Installed' : 'failure">Missing' ) . '</span>';
$DVWARecaptcha    = 'reCAPTCHA key: <span class="' . ( ( isset( $_DVWA[ 'recaptcha_public_key' ] ) && $_DVWA[ 'recaptcha_public_key' ] != '' ) ? 'success">' . $_DVWA[ 'recaptcha_public_key' ] : 'failure">Missing' ) . '</span>';

$DVWAUploadsWrite = 'Writable folder ' . $PHPUploadPath . ': <span class="' . ( is_writable( $PHPUploadPath ) ? 'success">Yes' : 'failure">No' ) . '</span>';
$bakWritable = 'Writable folder ' . $PHPCONFIGPath . ': <span class="' . ( is_writable( $PHPCONFIGPath ) ? 'success">Yes' : 'failure">No' ) . '</span>';

$DVWAOS           = 'Operating system: <em>' . ( strtoupper( substr (PHP_OS, 0, 3)) === 'WIN' ? 'Windows' : '*nix' ) . '</em>';
$SERVER_NAME      = 'Web Server SERVER_NAME: <em>' . $_SERVER[ 'SERVER_NAME' ] . '</em>';

$MYSQL_USER       = 'Database username: <em>' . $_DVWA[ 'db_user' ] . '</em>';
$MYSQL_PASS       = 'Database password: <em>' . ( ($_DVWA[ 'db_password' ] != "" ) ? '******' : '*blank*' ) . '</em>';
$MYSQL_DB         = 'Database database: <em>' . $_DVWA[ 'db_database' ] . '</em>';
$MYSQL_SERVER     = 'Database host: <em>' . $_DVWA[ 'db_server' ] . '</em>';
$MYSQL_PORT       = 'Database port: <em>' . $_DVWA[ 'db_port' ] . '</em>';

?>