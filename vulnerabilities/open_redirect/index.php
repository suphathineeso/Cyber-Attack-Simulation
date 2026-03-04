<?php

define( 'DVWA_WEB_PAGE_TO_ROOT', '../../' );
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

dvwaPageStartup( array( 'authenticated' ) );

$page = dvwaPageNewGrab();
$page[ 'title' ]   = 'Vulnerability: Open HTTP Redirect' . $page[ 'title_separator' ].$page[ 'title' ];
$page[ 'page_id' ] = 'open_redirect';
$page[ 'help_button' ]   = 'open_redirect';
$page[ 'source_button' ] = 'open_redirect';

dvwaDatabaseConnect();

$html = ''; // กัน Notice หากไม่มีตัวแปร

switch( dvwaSecurityLevelGet() ) {
	case 'low':
		$link1 = "source/low.php?redirect=info.php?id=1";
		$link2 = "source/low.php?redirect=info.php?id=2";
		break;
	case 'medium':
		$link1 = "source/medium.php?redirect=info.php?id=1";
		$link2 = "source/medium.php?redirect=info.php?id=2";
		break;
	case 'high':
		$link1 = "source/high.php?redirect=info.php?id=1";
		$link2 = "source/high.php?redirect=info.php?id=2";
		break;
	default:
		$link1 = "source/impossible.php?redirect=1";
		$link2 = "source/impossible.php?redirect=2";
		break;
}

$page['body'] .= "
<style>
  .cas-note { color: var(--muted); font-size: 12px; margin-top: 6px; }
  .cas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
  @media (max-width: 900px){ .cas-grid { grid-template-columns: 1fr; } }
  .cas-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 12px 14px;
    box-shadow: var(--shadow);
  }
  .cas-card__h { font-weight: 900; text-transform: uppercase; margin-bottom: 8px; }
  .cas-ul { margin: 0; padding-left: 18px; }
  .cas-ul li { margin: 4px 0; }
  .cas-kv { display:flex; gap:10px; flex-wrap:wrap; margin-top: 8px; }
  .cas-pill {
    display:inline-block;
    padding:2px 8px;
    border:1px solid var(--border);
    background: var(--bg);
    font-weight:800;
    text-transform: uppercase;
    font-size: 12px;
  }
</style>
";

$page[ 'body' ] .= "
<div class=\"body_padded\">

	<h1>Vulnerability: Open HTTP Redirect</h1>
	<p class='cas-note'>
		<strong>Cyber Attack Simulation</strong> • เป้าหมายของบทนี้คือเข้าใจว่า “การ redirect ที่ไม่ตรวจสอบ” ถูกใช้หลอกผู้ใช้ได้อย่างไร และควรแก้ด้วย allow-list/validation แบบไหน
	</p>

	<div class=\"vulnerable_code_area\">
		<h2>Open HTTP Redirect</h2>
		<p>
			ด้านล่างคือ link ตัวอย่าง 2 อัน (ตาม Security Level ที่ตั้งไว้) เพื่อให้คุณทดลองดูพฤติกรรมการ redirect
			แล้วสังเกตว่า parameter <code>redirect</code> ส่งผลต่อปลายทางอย่างไร
		</p>

		<div class='cas-kv'>
			<span class='cas-pill'>Try: change redirect</span>
			<span class='cas-pill'>Observe: url destination</span>
			<span class='cas-pill'>Think: validation</span>
		</div>

		<ul style='margin-top:10px;'>
			<li><a href='{$link1}'>Quote 1</a> <span class='cas-note'>({$link1})</span></li>
			<li><a href='{$link2}'>Quote 2</a> <span class='cas-note'>({$link2})</span></li>
		</ul>

		<div class='cas-note'>
			Hint: บทนี้ไม่เน้น “payload” แต่เน้น <b>เข้าใจ flow</b> และวิธีป้องกันที่ถูกต้อง
		</div>

		{$html}
	</div>

	<br />

	<h2>Learning Panel: Open Redirect</h2>
	<p class='cas-note'>
		<b>Flow:</b> User clicks trusted link → App redirects by user input → Victim lands on attacker site
	</p>

	<div class='cas-grid'>
		<div class='cas-card'>
			<div class='cas-card__h'>Why vulnerable?</div>
			<ul class='cas-ul'>
				<li> ระบบรับ URL/ปลายทางจากผู้ใช้แล้ว redirect ทันที โดยไม่ตรวจสอบ</li>
				<li> ผู้โจมตีใช้ “ชื่อเว็บที่น่าเชื่อถือ” เป็นตัวล่อ (trust abuse)</li>
				<li> Redirect destination is controlled by user input without validation.</li>
				<li> Attackers leverage trusted domains to increase click-through for phishing.</li>
			</ul>
		</div>

		<div class='cas-card'>
			<div class='cas-card__h'>Impact</div>
			<ul class='cas-ul'>
				<li> ใช้ทำ phishing / หลอกให้ login / เก็บข้อมูล</li>
				<li> ใช้พาไปหน้า malware หรือเว็บอันตราย</li>
				<li> Phishing, credential theft, malware delivery, reputation damage.</li>
			</ul>
		</div>

		<div class='cas-card'>
			<div class='cas-card__h'>Safe Lab</div>
			<ul class='cas-ul'>
				<li> สังเกตว่า redirect ถูกสร้างจากค่าไหน (query param / form)</li>
				<li> เปรียบเทียบ Low/Medium/High/Impossible ว่า “ตรวจอะไรเพิ่ม”</li>
				<li> Compare levels to see where allow-list / parsing / checks are applied.</li>
			</ul>
			<div class='cas-note'>หมายเหตุ: โหมดนี้เน้น “อ่านโค้ดและอธิบายเหตุผล” ให้เป็น</div>
		</div>

		<div class='cas-card'>
			<div class='cas-card__h'>Fix checklist</div>
			<ul class='cas-ul'>
				<li><b>Allow-list</b> เฉพาะ path/หน้าภายในที่อนุญาต (เช่น info.php, home.php)</li>
				<li><b>Block external URL</b> ห้าม redirect ไป domain ภายนอก (http/https)</li>
				<li><b>Use ID mapping</b> ใช้ id=1,2 แล้ว map ไปหน้าแทนการรับ URL ตรง ๆ</li>
				<li><b>Log</b> บันทึกเหตุการณ์ redirect ที่ผิดปกติ เพื่อทำ timeline/incident ต่อได้</li>
			</ul>
		</div>
	</div>

	<br />

	<h2>More Information</h2>
	<ul>
		<li>" . dvwaExternalLinkUrlGet( 'https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html', "OWASP Unvalidated Redirects and Forwards Cheat Sheet" ) . "</li>
		<li>" . dvwaExternalLinkUrlGet( 'https://owasp.org/www-project-web-security-testing-guide/stable/4-Web_Application_Security_Testing/11-Client-side_Testing/04-Testing_for_Client-side_URL_Redirect', "WSTG - Testing for Client-side URL Redirect") . "</li>
		<li>" . dvwaExternalLinkUrlGet( 'https://cwe.mitre.org/data/definitions/601.html', "Mitre - CWE-601: URL Redirection to Untrusted Site ('Open Redirect')" ) . "</li>
	</ul>

</div>\n";

dvwaHtmlEcho( $page );

?>