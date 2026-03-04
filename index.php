<?php

define( 'DVWA_WEB_PAGE_TO_ROOT', '' );
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

dvwaPageStartup( array( 'authenticated' ) );

/* =========================
   Cyber Attack Simulation - language toggle (Home only)
   ใช้ cookie ชื่อ cas_lang: th | en
   ========================= */
$cas_lang = 'th';
if (isset($_COOKIE['cas_lang']) && in_array($_COOKIE['cas_lang'], array('th','en'))) {
	$cas_lang = $_COOKIE['cas_lang'];
}
if (isset($_GET['lang']) && in_array($_GET['lang'], array('th','en'))) {
	$cas_lang = $_GET['lang'];
	setcookie('cas_lang', $cas_lang, time() + 60*60*24*365, "/");
	$_COOKIE['cas_lang'] = $cas_lang;

	// reload without query param (กัน refresh แล้วค้าง ?lang=)
	dvwaRedirect(DVWA_WEB_PAGE_TO_ROOT . 'index.php');
}

/* =========================
   Text content (TH/EN)
   ========================= */
if ($cas_lang === 'en') {

	$H1 = "Welcome to Cyber Attack Simulation!";
	$P1 = "Cyber Attack Simulation is a local, educational PHP/MySQL web application built for learning web security through real behavior — not theory only.";
	$P2 = "This project helps you see how vulnerabilities happen in the code path: input → processing → output → impact.";
	$P3 = "You will learn defensive patterns by comparing insecure code (Low) and secure code (Impossible) in the same module.";
	$P4 = "Focus is on understanding the root cause, recognizing risky patterns, and applying a clear fix checklist you can reuse in real projects.";

	$H2A = "How to use this project";
	$PA1 = "1) Choose a module you want to learn (SQLi, XSS, CSRF, IDOR, etc.) and keep the security level consistent while you explore.";
	$PA2 = "2) Use the Learning Panel below the input area: it explains why it is vulnerable, a Safe Lab path, and how to fix it.";
	$PA3 = "3) Use Compare Low vs Impossible to see the difference in validation, escaping, tokens, and authorization checks.";
	$PA4 = "4) The goal is not scoring — the goal is to explain: what went wrong, what the impact is, and what the fix is.";

	$H2B = "WARNING";
	$PB1 = "This application is intentionally vulnerable for learning purposes.";
	$PB2 = "<strong>Do NOT deploy this project to any public / internet-facing server.</strong> If exposed, it can be compromised.";
	$PB3 = "Run it locally or inside a VM (NAT) and treat it as unsafe software (because it is).";
	$PB4 = "Recommended: " . dvwaExternalLinkUrlGet('https://www.virtualbox.org/','VirtualBox') . " or " . dvwaExternalLinkUrlGet('https://www.vmware.com/','VMware') . " + " . dvwaExternalLinkUrlGet('https://www.apachefriends.org/','XAMPP') . ".";

	$H3  = "Disclaimer";
	$PD1 = "This project is for education and security awareness only.";
	$PD2 = "Do not use it for unauthorized testing or any malicious activity.";
	$PD3 = "The authors/maintainers are not responsible for misuse or insecure deployment by users.";
	$PD4 = "You are responsible for keeping this environment private, local, and properly isolated.";

	$H2C = "More resources";
	$PC1 = "If you want more practice environments or harder challenges, you may explore these projects:";
	$PC2 = "Each project has different modules and difficulty levels — use them to widen your security coverage.";
	$PC3 = "Always keep your environment isolated and never expose vulnerable apps to the public internet.";

	$BTN1 = "Go to Security Level";
	$BTN2 = "Start with SQL Injection";
	$BTN3 = "Reset Database";
	$LANG_LABEL = "Language:";

} else {

	$H1 = "ยินดีต้อนรับสู่ Cyber Attack Simulation!";
	$P1 = "Cyber Attack Simulation คือเว็บแอป PHP/MySQL สำหรับฝึกความเข้าใจด้านความปลอดภัยเว็บแบบ “เห็นภาพจริงจากการทำงานของระบบ” ไม่ใช่แค่ท่องจำทฤษฎี";
	$P2 = "แนวคิดหลักคือมองเส้นทางของข้อมูล: รับ input → ประมวลผล → แสดงผล → เกิดผลกระทบ แล้วชี้จุดเสี่ยงให้ชัด";
	$P3 = "ในแต่ละบทจะมี Learning Panel ช่วยสรุป “สาเหตุที่เกิดช่องโหว่” + วิธีฝึกแบบ Safe Lab + เช็กลิสต์การแก้";
	$P4 = "โปรเจกต์นี้ไม่เน้นคะแนน แต่เน้นให้คุณอธิบายได้ว่า: มันพังตรงไหน, กระทบอะไร, และแก้แบบไหนถึงถูกต้อง";

	$H2A = "วิธีใช้งาน (แนวทางเรียนรู้)";
	$PA1 = "1) เลือกบทที่สนใจ (SQLi, XSS, CSRF, IDOR ฯลฯ) แล้วตั้ง Security Level ให้เหมาะกับการเรียนช่วงนั้น";
	$PA2 = "2) ทดลองกรอก/กดตามฟอร์ม แล้วอ่าน Learning Panel ที่อยู่ด้านล่าง เพื่อจับจุดรับค่า/จุดประมวลผล/จุดแสดงผล";
	$PA3 = "3) กด Compare Low vs Impossible เพื่อดู “รูปแบบโค้ดที่ปลอดภัย” เช่น validation, escaping, token, authorization";
	$PA4 = "4) สรุป Fix checklist ของบทนั้นเป็นโน้ตสั้น ๆ เพื่อเอาไปใช้กับงานจริงได้ (เช่น โปรเจกต์/งานพัฒนาเว็บ)";

	$H2B = "คำเตือน";
	$PB1 = "ระบบนี้ถูกออกแบบให้มีช่องโหว่ “โดยตั้งใจ” เพื่อการเรียนรู้";
	$PB2 = "<strong>ห้ามนำขึ้นเซิร์ฟเวอร์ที่เข้าถึงจากอินเทอร์เน็ต</strong> เพราะถ้าเปิด public อาจถูกโจมตีได้จริง";
	$PB3 = "ควรรันบนเครื่องตัวเอง หรือ VM แบบ NAT เท่านั้น และถือว่าเป็นซอฟต์แวร์ไม่ปลอดภัยเสมอ";
	$PB4 = "แนะนำใช้ " . dvwaExternalLinkUrlGet('https://www.virtualbox.org/','VirtualBox') . " หรือ " . dvwaExternalLinkUrlGet('https://www.vmware.com/','VMware') . " + " . dvwaExternalLinkUrlGet('https://www.apachefriends.org/','XAMPP') . " ในการรัน";

	$H3  = "ข้อสงวนสิทธิ์";
	$PD1 = "โปรเจกต์นี้จัดทำเพื่อการศึกษาและการตระหนักรู้ด้านความปลอดภัยเท่านั้น";
	$PD2 = "ห้ามนำไปใช้เพื่อทดสอบ/โจมตีระบบที่ไม่ได้รับอนุญาต";
	$PD3 = "ผู้จัดทำไม่รับผิดชอบต่อการใช้งานผิดวัตถุประสงค์ หรือการนำไปติดตั้งในสภาพแวดล้อมที่ไม่ปลอดภัย";
	$PD4 = "ผู้ใช้ต้องรับผิดชอบในการแยกสภาพแวดล้อมให้ปลอดภัยและไม่เปิดสู่สาธารณะ";

	$H2C = "แหล่งฝึกเพิ่มเติม";
	$PC1 = "ถ้าอยากฝึกกับโปรเจกต์อื่นหรืออยากได้โจทย์ที่หลากหลายขึ้น สามารถดูแหล่งต่อไปนี้:";
	$PC2 = "แต่ละโปรเจกต์มีโมดูลและความยากต่างกัน ช่วยขยายมุมมองเรื่องช่องโหว่ได้ดี";
	$PC3 = "อย่าลืมหลักสำคัญ: รันในเครื่อง/VM และไม่เปิด public เสมอ";

	$BTN1 = "ไปตั้งค่า Security Level";
	$BTN2 = "เริ่มที่ SQL Injection";
	$BTN3 = "Reset ฐานข้อมูล";
	$LANG_LABEL = "ภาษา:";
}

/* =========================
   Page setup
   ========================= */
$page = dvwaPageNewGrab();
$page[ 'title' ]   = 'Home' . $page[ 'title_separator' ] . 'Cyber Attack Simulation';
$page[ 'page_id' ] = 'home';

/* =========================
   Minimal CSS (Home only)
   ========================= */
$page['body'] .= "
<style>
  .cas-lang { float:right; font-size:12px; margin-top:4px; }
  .cas-lang a { font-weight:800; text-decoration:none; }
  .cas-lang a:hover { text-decoration:underline; }
  .cas-actions a { display:inline-block; margin-right:10px; }
  .cas-muted { color:#666; font-size:12px; }
  body.dark .cas-muted { color: var(--muted); }
</style>
";

/* =========================
   Body (DVWA-like layout)
   ========================= */
$page[ 'body' ] .= "
<div class=\"body_padded\">

  <div class=\"cas-lang\">
    {$LANG_LABEL}
    <a href=\"?lang=th\">TH</a> |
    <a href=\"?lang=en\">EN</a>
  </div>

  <h1>{$H1}</h1>

  <p>{$P1}</p>
  <p>{$P2}</p>
  <p>{$P3}</p>
  <p>{$P4}</p>

  <div class=\"cas-actions\">
    <a href=\"" . DVWA_WEB_PAGE_TO_ROOT . "security.php\">{$BTN1}</a>
    <a href=\"" . DVWA_WEB_PAGE_TO_ROOT . "vulnerabilities/sqli/\">{$BTN2}</a>
    <a href=\"" . DVWA_WEB_PAGE_TO_ROOT . "setup.php\">{$BTN3}</a>
  </div>

  <hr />
  <br />

  <h2>{$H2A}</h2>
  <p>{$PA1}</p>
  <p>{$PA2}</p>
  <p>{$PA3}</p>
  <p>{$PA4}</p>

  <hr />
  <br />

  <h2>{$H2B}</h2>
  <p>{$PB1}</p>
  <p>{$PB2}</p>
  <p>{$PB3}</p>
  <p>{$PB4}</p>

  <br />
  <h3>{$H3}</h3>
  <p>{$PD1}</p>
  <p>{$PD2}</p>
  <p>{$PD3}</p>
  <p>{$PD4}</p>

  <hr />
  <br />

  <h2>{$H2C}</h2>
  <p>{$PC1}</p>
  <p>{$PC2}</p>
  <p>{$PC3}</p>
  <ul>
    <li>" . dvwaExternalLinkUrlGet( 'https://github.com/webpwnized/mutillidae', 'Mutillidae') . "</li>
    <li>" . dvwaExternalLinkUrlGet( 'https://owasp.org/www-project-vulnerable-web-applications-directory', 'OWASP Vulnerable Web Applications Directory') . "</li>
  </ul>

  <p class=\"cas-muted\">Cyber Attack Simulation • local learning environment</p>

</div>";

dvwaHtmlEcho( $page );

?>