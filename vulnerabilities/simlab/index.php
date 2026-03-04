<?php
/**
 * DVWA - Simulation Lab (Cyber Attack Simulation)
 * File: dvwa/vulnerabilities/simlab/index.php
 *
 * หน้านี้ทำอะไร?
 * 1) มีฟอร์ม “จำลองเหตุการณ์” 3 แบบ (Verification / Suspicious Search / Ransomware)
 * 2) กด Simulate แล้วเรียก cas_log_event() เพื่อเขียนลง dvwa/logs/events.jsonl (JSONL = 1 เหตุการณ์/1 บรรทัด)
 * 3) แสดง Dashboard (ตาราง log ล่าสุด) จากไฟล์เดียวกัน
 * 4) แสดง Learning Panel (Why / Safe Lab / Fix) แบบ “กล่องใครกล่องมัน”
 * 5) มีปุ่ม Reset Event Log วางไว้ด้านขวาของหัวข้อ Event Log (ล่าสุด)
 */

define('DVWA_WEB_PAGE_TO_ROOT', '../../');
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/casLogger.inc.php';

dvwaPageStartup(['authenticated']);

$page = dvwaPageNewGrab();
$page['title']   = 'Simulation' . $page['title_separator'] . $page['title'];
$page['page_id'] = 'simlab';

/* =========================
   [0] CSRF token (ใช้ของ DVWA)
   ========================= */
if (!isset($_SESSION['session_token'])) {
    generateSessionToken();
}

/* =========================
   [A] Handle POST actions
   ========================= */
$msgHtml = '';

$flash = function($text) {
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    return "<div class='message'>{$safe}</div>";
};

// helper reset file
function cas_reset_log_file() {
    $file = cas_log_path(); // from casLogger.inc.php
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    // truncate / create new empty file
    $fp = @fopen($file, 'wb');
    if (!$fp) return false;
    fclose($fp);
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF check
    checkToken($_REQUEST['user_token'] ?? '', $_SESSION['session_token'] ?? '', 'index.php');

    $action = (string)$_POST['action'];

    if ($action === 'verify') {
        $username = trim($_POST['username'] ?? '');
        $ok = cas_log_event(
            'SIM_VERIFY',
            'simlab',
            'info',
            'Account verification simulated',
            ['username' => $username]
        );
        $msgHtml = $ok ? $flash("Saved: SIM_VERIFY (username={$username})")
                       : $flash("ERROR: cannot write events.jsonl (check dvwa/logs permission)");
    }

    if ($action === 'search') {
        $query = trim($_POST['query'] ?? '');
        $ok = cas_log_event(
            'SIM_SEARCH',
            'simlab',
            'warn',
            'Suspicious search simulated',
            ['query' => $query]
        );
        $msgHtml = $ok ? $flash("Saved: SIM_SEARCH (query={$query})")
                       : $flash("ERROR: cannot write events.jsonl (check dvwa/logs permission)");
    }

    if ($action === 'ransom') {
        $filename = trim($_POST['filename'] ?? 'example.txt');
        $ok = cas_log_event(
            'SIM_RANSOMWARE',
            'simlab',
            'high',
            'Ransomware simulated (no encryption)',
            ['file' => $filename]
        );
        $msgHtml = $ok ? $flash("Saved: SIM_RANSOMWARE (file={$filename})")
                       : $flash("ERROR: cannot write events.jsonl (check dvwa/logs permission)");
    }

    if ($action === 'reset_log') {
        $ok = cas_reset_log_file();
        $msgHtml = $ok ? $flash("Reset done: events.jsonl cleared")
                       : $flash("ERROR: cannot reset events.jsonl (check dvwa/logs permission)");
    }
}

/* =========================
   [B] Read latest events (Dashboard)
   ========================= */
$latestEvents = cas_log_tail(250);          // read tail from jsonl
$latestEvents = array_reverse($latestEvents); // newest first
$latestEvents = array_slice($latestEvents, 0, 25);

/* =========================
   [C] Page CSS
   ========================= */
$page['body'] .= "
<style>
  /* layout */
  .cas-wrap { max-width: 1020px; margin: 0 auto; }
  .cas-sub  { color: var(--muted); margin-top:-6px; }
  .cas-hr   { margin: 18px 0; }

  /* form blocks */
  .cas-block { margin-top: 14px; }
  .cas-form-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .cas-form-row label { min-width: 96px; font-weight:700; }
  .cas-form-row input[type=text] { min-width: 320px; }
  @media (max-width:640px){
    .cas-form-row label { min-width: 100%; }
    .cas-form-row input[type=text] { min-width: 100%; width:100%; }
  }

  /* dashboard table */
  .cas-table { width:100%; border-collapse: collapse; background: var(--surface); }
  .cas-table th, .cas-table td {
    border:1px solid var(--border);
    padding:8px 10px;
    text-align:left;
    vertical-align: top;
  }
  .cas-table th { background: rgba(30,136,229,.10); }
  body.dark .cas-table th { background: rgba(96,165,250,.10); }
  .cas-note { font-size:12px; color: var(--muted); margin-top: 6px; }

  /* severity pills */
  .cas-pill {
    display:inline-block;
    padding:2px 8px;
    border:1px solid var(--border);
    background: var(--bg);
    font-weight:800;
    text-transform: lowercase;
  }
  .cas-pill.info { border-color: rgba(30,136,229,.35); }
  .cas-pill.warn { border-color: rgba(245,158,11,.45); }
  .cas-pill.high { border-color: rgba(239,68,68,.50); }
  .cas-pill.critical { border-color: rgba(239,68,68,.75); }

  /* Event Log Header with Reset Button */
  .cas-log-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 18px;
  }
  .cas-log-header h2 { margin: 0; }

  /* Learning Panel */
  .cas-learn { margin-top: 16px; }
  .cas-learn__top { margin-bottom: 10px; }
  .cas-learn__title { font-weight: 900; letter-spacing:.3px; text-transform: uppercase; }
  .cas-learn__flow { color: var(--muted); margin-top: 4px; }

  .cas-learn__grid {
    display:grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 12px;
  }
  @media (max-width:900px){
    .cas-learn__grid { grid-template-columns: 1fr; }
  }

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

  /* legend under table */
  .cas-legend {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 10px 12px;
    margin-top: 10px;
  }
</style>
";

/* =========================
   [D] Body
   ========================= */
$page['body'] .= "
<div class='body_padded cas-wrap'>

  <h1>Cyber Attack Simulation</h1>
  <p class='cas-sub'>
    หน้านี้คือ “หน้าจำลองเหตุการณ์” (Simulation only) เพื่อฝึกแนวคิดเรื่อง <b>การบันทึกเหตุการณ์ (Event Logging)</b>:
    กรอกข้อมูล → กด Simulate → บันทึก → แสดงใน Dashboard ด้านล่าง
  </p>

  {$msgHtml}

  <hr class='cas-hr' />

  <div class='vulnerable_code_area cas-block'>
    <h2>1) Account Verification (Simulation)</h2>
    <form method='post' class='cas-form-row'>
      <input type='hidden' name='action' value='verify'>
      " . tokenField() . "
      <label>Username:</label>
      <input type='text' name='username' placeholder='testuser' required>
      <input type='submit' value='Simulate' class='popup_button'>
    </form>
    <div class='cas-note'>
      ใช้เพื่อจำลอง “การยืนยันตัวตน/ตรวจสอบบัญชี” แล้วบันทึก event: <b>SIM_VERIFY</b>
    </div>
  </div>

  <div class='vulnerable_code_area cas-block'>
    <h2>2) Search / Suspicious Activity (Simulation)</h2>
    <form method='post' class='cas-form-row'>
      <input type='hidden' name='action' value='search'>
      " . tokenField() . "
      <label>Query:</label>
      <input type='text' name='query' placeholder=\"' OR 1=1\" required>
      <input type='submit' value='Simulate' class='popup_button'>
    </form>
    <div class='cas-note'>
      ใช้เพื่อจำลอง “พฤติกรรมค้นหาที่น่าสงสัย” แล้วบันทึก event: <b>SIM_SEARCH</b>
    </div>
  </div>

  <div class='vulnerable_code_area cas-block'>
    <h2>3) Ransomware (Simulation)</h2>
    <form method='post' class='cas-form-row'>
      <input type='hidden' name='action' value='ransom'>
      " . tokenField() . "
      <label>Filename:</label>
      <input type='text' name='filename' value='example.txt'>
      <input type='submit' value='Simulate' class='popup_button'>
    </form>
    <div class='cas-note'>
      จำลองเหตุการณ์ “ไฟล์ถูกเข้ารหัส” แต่ <b>ไม่มีการเข้ารหัสจริง</b> (แค่บันทึก event): <b>SIM_RANSOMWARE</b>
    </div>
  </div>

  <hr class='cas-hr' />

  <div class='cas-log-header'>
    <h2>Event Log (ล่าสุด)</h2>
    <form method='post' style='margin:0;'>
      <input type='hidden' name='action' value='reset_log'>
      " . tokenField() . "
      <input type='submit' value='Reset Event Log' class='popup_button'
             onclick=\"return confirm('Reset Event Log? This will clear events.jsonl');\">
    </form>
  </div>
  <br>
  

";

/* =========================
   [E] Render Dashboard Table
   ========================= */
if (empty($latestEvents)) {
    $page['body'] .= "
    <div class='vulnerable_code_area'>
      <p><b>ยังไม่มี event</b></p>
      <p class='cas-note'>
        กรอกข้อมูล เพื่อจำลองเหตุการณ์
      </p>
    </div>";
} else {

    $page['body'] .= "
    <div class='vulnerable_code_area'>
      <table class='cas-table'>
        <thead>
          <tr>
            <th style='width:185px;'>Time</th>
            <th style='width:110px;'>Severity</th>
            <th style='width:160px;'>Event</th>
            <th style='width:120px;'>Module</th>
            <th>Message / Detail</th>
          </tr>
        </thead>
        <tbody>
    ";

    foreach ($latestEvents as $e) {

        $time   = htmlspecialchars((string)($e['ts'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sevRaw = strtolower((string)($e['severity'] ?? 'info'));
        $sev    = htmlspecialchars($sevRaw, ENT_QUOTES, 'UTF-8');
        $event  = htmlspecialchars((string)($e['type'] ?? ''), ENT_QUOTES, 'UTF-8');
        $module = htmlspecialchars((string)($e['module'] ?? ''), ENT_QUOTES, 'UTF-8');
        $msgTxt = htmlspecialchars((string)($e['detail'] ?? ''), ENT_QUOTES, 'UTF-8');

        $skip = ['ts','type','module','severity','detail','user','security','ip','ua','path'];
        $extraPairs = [];
        foreach ($e as $k => $v) {
            if (in_array($k, $skip, true)) continue;
            if (is_scalar($v) && (string)$v !== '') {
                $extraPairs[] = htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            }
        }
        $extraTxt = implode(' • ', $extraPairs);

        $pillClass = 'info';
        if ($sevRaw === 'warn' || $sevRaw === 'warning') $pillClass = 'warn';
        if ($sevRaw === 'high') $pillClass = 'high';
        if ($sevRaw === 'critical') $pillClass = 'critical';

        $finalMsg = $msgTxt;
        if ($extraTxt !== '') {
            $finalMsg .= "<br><span class='cas-note'>{$extraTxt}</span>";
        }

        $page['body'] .= "
          <tr>
            <td>{$time}</td>
            <td><span class='cas-pill {$pillClass}'>{$sev}</span></td>
            <td>{$event}</td>
            <td>{$module}</td>
            <td>{$finalMsg}</td>
          </tr>
        ";
    }

    $page['body'] .= "
        </tbody>
      </table>

      <div class='cas-legend'>
        <div><b>หมายเหตุคอลัมน์</b></div>
        <div class='cas-note'>
          <b>Time</b> = เวลาเกิดเหตุการณ์ (UTC) •
          <b>Severity</b> = ระดับความรุนแรง •
          <b>Event</b> = รหัสเหตุการณ์ •
          <b>Module</b> = หน้าที่สร้างเหตุการณ์ •
          <b>Message/Detail</b> = ข้อความสรุป + รายละเอียดเสริม
        </div>
      </div>
    </div>
    ";
}

/* =========================
   [F] Learning Panel (3 กล่อง)
   ========================= */
$page['body'] .= "
  <hr class='cas-hr' />

  <div class='cas-learn'>
    <div class='cas-learn__top'>
      <div class='cas-learn__title'>Learning Panel: IDOR (Insecure Direct Object Reference)</div>
      <div class='cas-learn__flow'>Browser requests object id → Server checks authorization → Returns only allowed data</div>
    </div>

    <div class='cas-learn__grid'>
      <div class='cas-card'>
        <div class='cas-card__h'>Why vulnerable?</div>
        <ul class='cas-ul'>
          <li>เข้าถึง resource ด้วย id ตรง ๆ (เช่น user_id / order_id)</li>
          <li>ถ้าไม่ตรวจสิทธิ์ฝั่ง server ผู้ใช้หนึ่งอาจเข้าถึงข้อมูลของอีกคนได้</li>
        </ul>
      </div>

      <div class='cas-card'>
        <div class='cas-card__h'>Safe Lab</div>
        <ul class='cas-ul'>
          <li>ดูว่าหน้าใช้ id ใดในการดึงข้อมูล และมีการตรวจสิทธิ์หรือไม่</li>
          <li>Compare แล้วชี้ว่า Impossible เพิ่ม “authorization check” ตรงไหน</li>
        </ul>
        <div class='cas-note'>หมายเหตุ: โหมดนี้เน้น “เข้าใจระบบ/โค้ด” ไม่เน้น payload โจมตี</div>
      </div>

      <div class='cas-card'>
        <div class='cas-card__h'>Fix checklist</div>
        <ul class='cas-ul'>
          <li>ตรวจสิทธิ์ทุกครั้ง server-side (เจ้าของ/บทบาท/นโยบาย)</li>
          <li>ใช้ indirect reference หรือ random IDs เมื่อเหมาะสม</li>
          <li>บันทึก audit log สำหรับการเข้าถึง resource สำคัญ</li>
        </ul>
      </div>
    </div>
  </div>

</div>
";

dvwaHtmlEcho($page);
?>