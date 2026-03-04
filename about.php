<?php

define( 'DVWA_WEB_PAGE_TO_ROOT', '' );
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

dvwaPageStartup( array() );

/* =========================
   Cyber Attack Simulation - language toggle (About only)
   cookie: cas_lang = th|en
   ========================= */
$cas_lang = 'th';
if (isset($_COOKIE['cas_lang']) && in_array($_COOKIE['cas_lang'], array('th','en'))) {
	$cas_lang = $_COOKIE['cas_lang'];
}
if (isset($_GET['lang']) && in_array($_GET['lang'], array('th','en'))) {
	$cas_lang = $_GET['lang'];
	setcookie('cas_lang', $cas_lang, time() + 60*60*24*365, "/");
	$_COOKIE['cas_lang'] = $cas_lang;
	dvwaRedirect(DVWA_WEB_PAGE_TO_ROOT . 'about.php'); // reload กันค้างพารามิเตอร์
}

/* =========================
   Text content (TH/EN)
   ========================= */
if ($cas_lang === 'en') {

	$OUR_H2   = "About: Cyber Attack Simulation";
	$OUR_P1   = "Cyber threats are increasingly frequent and can impact systems, networks, and data at scale. Cyber Attack Simulation was created as a controlled learning environment to understand common web security failures through real system behavior.";
	$OUR_P2   = "Instead of memorizing payloads, the project focuses on the full security story: <em>cause → impact → remediation</em>. You learn where the risky pattern appears in the code path (input → processing → output) and how secure patterns prevent it.";
	$OUR_P3   = "The lab is designed to be used locally or in an isolated VM environment, enabling hands-on practice without touching real-world systems.";

	$OUR_OBJ  = "Project Objectives";
	$OBJ = array(
		"Simulate attack scenarios for learning and to build a clear mental model of how vulnerabilities happen.",
		"Study impacts on applications, networks, and data to understand risk and consequences.",
		"Reduce human error by practicing detection of risky patterns and safe coding habits.",
		"Support future security planning by documenting lessons learned and mitigation checklists.",
		"Strengthen technical and analytical skills as a foundation for cybersecurity study and work."
	);

	$OUR_SAFE = "Safe Environment (VM / Local Only)";
	$SAFE_P1  = "This application is intentionally vulnerable for education. Run it locally or inside an isolated VM (NAT) only.";
	$SAFE_P2  = "<strong>Do NOT expose this project to the public internet</strong>. Treat it as unsafe software and keep it private.";
	$SAFE_P3  = "Recommended: " . dvwaExternalLinkUrlGet('https://www.virtualbox.org/','VirtualBox') . " or " . dvwaExternalLinkUrlGet('https://www.vmware.com/','VMware') . " + " . dvwaExternalLinkUrlGet('https://www.apachefriends.org/','XAMPP') . ".";

	$LANG_LABEL = "Language:";
	$UPSTREAM_NOTE = "The following section is preserved from the original DVWA project for attribution and licensing.";
}
else {
	$OUR_H2   = "เกี่ยวกับโครงงาน: Cyber Attack Simulation";
	$OUR_P1   = "ปัจจุบันภัยคุกคามทางไซเบอร์เกิดขึ้นบ่อยและส่งผลกระทบต่อระบบสารสนเทศอย่างกว้างขวาง โครงงาน Cyber Attack Simulation จึงถูกพัฒนาขึ้นเพื่อจำลองสถานการณ์การโจมตีในสภาพแวดล้อมเสมือน ช่วยให้ผู้เรียนเข้าใจกระบวนการโจมตีและผลกระทบได้อย่างเป็นรูปธรรม";
	$OUR_P2   = "แนวทางของโครงงานคือเรียนจาก “เส้นทางการทำงานของระบบ” มากกว่าการท่องจำ payload โดยมองภาพรวม <em>สาเหตุ → ผลกระทบ → วิธีแก้</em> และชี้จุดเสี่ยงในโค้ด/กระบวนการให้ชัด (รับ input → ประมวลผล → แสดงผล)";
	$OUR_P3   = "เพื่อความปลอดภัย โครงงานถูกออกแบบให้ทดลองบนเครื่องตนเองหรือ VM ที่แยกจากระบบจริง ช่วยให้ฝึกได้อย่างปลอดภัยและไม่ขัดต่อจริยธรรม";

	$OUR_OBJ  = "วัตถุประสงค์ของโครงงาน";
	$OBJ = array(
		"เพื่อจำลองสถานการณ์การโจมตีเพื่อเป็นความรู้ในการศึกษาต่อ ๆ ไป และสร้างความเข้าใจเชิงระบบ",
		"เพื่อศึกษาผลกระทบของการโจมตีต่อระบบ เครือข่าย และข้อมูล ช่วยมองภาพรวมของความเสี่ยง",
		"เพื่อลดความเสี่ยงจากความผิดพลาดของมนุษย์ (Human Error) ด้วยการเห็นตัวอย่างจริง",
		"เพื่อใช้เป็นข้อมูลประกอบการวางแผนพัฒนาระบบรักษาความปลอดภัยในอนาคต",
		"เพื่อเสริมทักษะด้านเทคนิคและการวิเคราะห์ ซึ่งเป็นพื้นฐานสำคัญต่อการเรียน/ทำงานด้าน Cyber Security"
	);

	$OUR_SAFE = "สภาพแวดล้อมที่ปลอดภัย (VM / Local Only)";
	$SAFE_P1  = "ระบบนี้มีช่องโหว่ “โดยตั้งใจ” เพื่อการเรียนรู้ จึงควรรันบนเครื่องตนเองหรือ VM แบบ NAT เท่านั้น";
	$SAFE_P2  = "<strong>ห้ามนำขึ้นเซิร์ฟเวอร์ที่เข้าถึงจากอินเทอร์เน็ต</strong> เพราะถ้าเปิด public อาจถูกโจมตีได้จริง";
	$SAFE_P3  = "แนะนำใช้ " . dvwaExternalLinkUrlGet('https://www.virtualbox.org/','VirtualBox') . " หรือ " . dvwaExternalLinkUrlGet('https://www.vmware.com/','VMware') . " + " . dvwaExternalLinkUrlGet('https://www.apachefriends.org/','XAMPP') . " สำหรับ Apache/MySQL";

	$LANG_LABEL = "ภาษา:";
	$UPSTREAM_NOTE = "ส่วนด้านล่างคงไว้จากโปรเจกต์ DVWA ต้นฉบับ เพื่อการอ้างอิงเครดิตและลิขสิทธิ์ให้ครบถ้วน";
}

/* Build list HTML */
$OBJ_HTML = "";
foreach($OBJ as $x){
	$OBJ_HTML .= "<li>{$x}</li>";
}

$page = dvwaPageNewGrab();
$page[ 'title' ]   = 'About' . $page[ 'title_separator' ] . $page[ 'title' ];
$page[ 'page_id' ] = 'about';

/* Small style for language switch (optional) */
$page['body'] .= "
<style>
  .cas-lang { float:right; font-size:12px; margin-top:4px; }
  .cas-lang a { font-weight:800; text-decoration:none; }
  .cas-lang a:hover { text-decoration:underline; }
  .cas-upstream-note { color:#666; font-size:12px; margin-top:6px; }
  body.dark .cas-upstream-note { color: var(--muted); }
</style>
";

$page[ 'body' ] .= "
<div class=\"body_padded\">

	<!-- Language toggle -->
	<div class=\"cas-lang\">
		{$LANG_LABEL}
		<a href=\"?lang=th\">TH</a> |
		<a href=\"?lang=en\">EN</a>
	</div>

	<!-- ===================== -->
	<!-- OUR PROJECT (NEW)      -->
	<!-- ===================== -->
	<h2>{$OUR_H2}</h2>
	<p>{$OUR_P1}</p>
	<p>{$OUR_P2}</p>
	<p>{$OUR_P3}</p>

	<h3>{$OUR_OBJ}</h3>
	<ul>
		{$OBJ_HTML}
	</ul>

	<h3>{$OUR_SAFE}</h3>
	<p>{$SAFE_P1}</p>
	<p>{$SAFE_P2}</p>
	<p>{$SAFE_P3}</p>

	<hr />
	<p class=\"cas-upstream-note\">{$UPSTREAM_NOTE}</p>
	<br />

	<!-- ===================== -->
	<!-- ORIGINAL DVWA (KEEP)  -->
	<!-- ===================== -->
	<h2>About</h2>
	<p>Damn Vulnerable Web Application (DVWA) is a PHP/MySQL web application that is damn vulnerable. Its main goals are to be an aid for security professionals to test their skills and tools in a legal environment, help web developers better understand the processes of securing web applications and aid teachers/students to teach/learn web application security in a class room environment</p>
	<p>Pre-August 2020, All material is copyright 2008-2015 RandomStorm & Ryan Dewhurst.</p>
	<p>Ongoing, All material is copyright Robin Wood and probably Ryan Dewhurst.</p>

	<h2>Links</h2>
	<ul>
		<li>Project Home: " . dvwaExternalLinkUrlGet( 'https://github.com/digininja/DVWA' ) . "</li>
		<li>Bug Tracker: " . dvwaExternalLinkUrlGet( 'https://github.com/digininja/DVWA/issues' ) . "</li>
		<li>Wiki: " . dvwaExternalLinkUrlGet( 'https://github.com/digininja/DVWA/wiki' ) . "</li>
	</ul>

	<h2>Credits</h2>
	<ul>
		<li>Brooks Garrett: " . dvwaExternalLinkUrlGet( 'http://brooksgarrett.com/','www.brooksgarrett.com' ) . "</li>
		<li>Craig</li>
		<li>g0tmi1k: " . dvwaExternalLinkUrlGet( 'https://blog.g0tmi1k.com/','g0tmi1k.com' ) . "</li>
		<li>Jamesr: " . dvwaExternalLinkUrlGet( 'https://www.creativenucleus.com/','www.creativenucleus.com' ) . "</li>
		<li>Jason Jones</li>
		<li>RandomStorm</li>
		<li>Ryan Dewhurst: " . dvwaExternalLinkUrlGet( 'https://wpscan.com/','wpscan.com' ) . "</li>
		<li>Shinkurt: " . dvwaExternalLinkUrlGet( 'http://www.paulosyibelo.com/','www.paulosyibelo.com' ) . "</li>
		<li>Tedi Heriyanto: " . dvwaExternalLinkUrlGet( 'http://tedi.heriyanto.net/','tedi.heriyanto.net' ) . "</li>
		<li>Tom Mackenzie</li>
		<li>Robin Wood: " . dvwaExternalLinkUrlGet( 'https://digi.ninja/','digi.ninja' ) . "</li>
		<li>Zhengyang Song: " . dvwaExternalLinkUrlGet( 'https://github.com/songzy12/','songzy12' ) . "</li>
	</ul>

	<h2>License</h2>
	<p>Damn Vulnerable Web Application (DVWA) is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.</p>

	<h2>Development</h2>
	<p>Everyone is welcome to contribute and help make DVWA as successful as it can be. All contributors can have their name and link (if they wish) placed in the credits section. To contribute pick an Issue from the Project Home to work on or submit a patch to the Issues list.</p>

</div>\n";

dvwaHtmlEcho( $page );
exit;

?>