<?php
/**
 * Cyber Attack Simulation - Incident Timeline + Playbook (mini SOC)
 * Path: dvwa/vulnerabilities/ir/index.php
 *
 * เป้าหมายของหน้านี้ (สอนอะไร)
 * 1) Timeline (Latest Events): ฝึกอ่าน “Log → เหตุการณ์ตามเวลา” + Filter/Search เพื่อหาเบาะแส
 * 2) Incident (Active/All): ฝึก “รวมหลาย event → เป็น 1 incident” แล้วจัดการสถานะงานแบบเป็นระบบ
 * 3) Playbook (Checklist): ฝึกตอบสนองแบบเป็นขั้นตอน (ลดลืม/ลดมั่ว เหมือน SOC/IR จริง)
 * 4) Learning Panel (IR Mindset): สรุปแนวคิด IR ว่าทำไมต้องมี Log/Timeline/Playbook
 */

define('DVWA_WEB_PAGE_TO_ROOT', '../../');
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/casLogger.inc.php';

dvwaPageStartup(['authenticated']);

$page = dvwaPageNewGrab();
$page['title']   = 'Incident Timeline' . $page['title_separator'] . $page['title'];
$page['page_id'] = 'ir';

/* =========================================================
   [A] Incident storage (JSON file)
   ========================================================= */
function cas_incident_path() {
    $base = dirname(__FILE__);
    $dvwaDir = realpath($base . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..');
    $logDir = $dvwaDir . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    return $logDir . DIRECTORY_SEPARATOR . 'incidents.json';
}

function cas_incident_read_all() {
    $file = cas_incident_path();
    if (!file_exists($file)) return [];
    $raw = @file_get_contents($file);
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function cas_incident_write_all($data) {
    $file = cas_incident_path();
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $fp = @fopen($file, 'cb');
    if (!$fp) return false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return true;
}

function cas_incident_new_id() {
    return 'INC-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function cas_incident_open_or_create($title, $severity, $trigger, $eventRef = []) {
    $all = cas_incident_read_all();

    // Reuse non-resolved incident to simulate continuous workflow
    foreach ($all as &$inc) {
        if (($inc['status'] ?? '') !== 'resolved') {
            $rank = ['info'=>1,'low'=>1,'warn'=>2,'warning'=>2,'medium'=>2,'high'=>3,'critical'=>4];
            $old = strtolower((string)($inc['severity'] ?? 'info'));
            $new = strtolower((string)$severity);
            if (($rank[$new] ?? 1) > ($rank[$old] ?? 1)) $inc['severity'] = $new;

            $inc['triggers'][] = $trigger;
            if (!empty($eventRef)) $inc['events'][] = $eventRef;

            cas_incident_write_all($all);
            return $inc['id'];
        }
    }
    unset($inc);

    $id = cas_incident_new_id();
    $all[] = [
        'id' => $id,
        'created_ts' => gmdate('c'),
        'title' => $title,
        'severity' => strtolower((string)$severity),
        'status' => 'new',
        'triggers' => [$trigger],
        'events' => empty($eventRef) ? [] : [$eventRef],
        'playbook' => [
            'identify' => false,
            'contain' => false,
            'eradicate' => false,
            'recover' => false,
            'lessons' => false
        ],
    ];
    cas_incident_write_all($all);
    return $id;
}

function cas_incident_get_by_id($id) {
    $all = cas_incident_read_all();
    foreach ($all as $inc) {
        if (($inc['id'] ?? '') === $id) return $inc;
    }
    return null;
}

function cas_incident_update($id, $patchFn) {
    $all = cas_incident_read_all();
    for ($i=0; $i<count($all); $i++) {
        if (($all[$i]['id'] ?? '') === $id) {
            $all[$i] = $patchFn($all[$i]);
            cas_incident_write_all($all);
            return true;
        }
    }
    return false;
}

/* =========================================================
   [B] Read events from JSONL + timeline logic
   ========================================================= */
$events = cas_log_tail(800); 
$events = array_reverse($events);

// 🔥 ดึงรายชื่อ Module ทั้งหมดที่มีใน Log จริงมาแสดงในตัวเลือก
$moduleSet = [];
foreach ($events as $e) {
    $m = strtolower((string)($e['module'] ?? ''));
    if ($m !== '') $moduleSet[$m] = true;
}
$availableModules = array_keys($moduleSet);
sort($availableModules);

/* =========================================================
   [C] Auto-create incident rule (SIEM Logic Simulation)
   ========================================================= */
$autoMsg = '';
if (!empty($events)) {
    $top = $events[0];
    $sev = strtolower((string)($top['severity'] ?? 'info'));
    $type = (string)($top['type'] ?? '');
    
    // กฎการสร้าง Incident อัตโนมัติ: ถ้าความรุนแรงสูง หรือเป็น Ransomware
    $isAuto = in_array($sev, ['warn','warning','high','critical'], true) || ($type === 'SIM_RANSOMWARE');

    if ($isAuto) {
        $title = ($type === 'SIM_RANSOMWARE') ? 'Ransomware Simulation Alert' : 'Suspicious Activity Detected';
        $trigger = $type . ' detected via ' . (string)($top['module'] ?? 'unknown');
        $ref = [
            'ts' => $top['ts'] ?? '',
            'type' => $top['type'] ?? '',
            'module' => $top['module'] ?? '',
            'severity' => $top['severity'] ?? '',
        ];
        $newId = cas_incident_open_or_create($title, $sev, $trigger, $ref);
        $autoMsg = "<div class='message' style='background:rgba(239,68,68,0.1); border-left:4px solid #ef4444; padding:10px; margin-bottom:15px;'>
            <b>SOC Correlation Engine:</b> ตรวจพบเหตุการณ์ผิดปกติและเปิด Incident หมายเลข <b>" . htmlspecialchars($newId, ENT_QUOTES, 'UTF-8') . "</b> อัตโนมัติ
        </div>";
    }
}

/* =========================================================
   [D] Filters (GET)
   ========================================================= */
$f_sev = strtolower(trim($_GET['sev'] ?? 'all'));
$f_mod = strtolower(trim($_GET['mod'] ?? 'all'));
$f_q   = trim($_GET['q'] ?? '');

$filtered = [];
foreach ($events as $e) {
    $sev = strtolower((string)($e['severity'] ?? 'info'));
    $mod = strtolower((string)($e['module'] ?? ''));
    $type = (string)($e['type'] ?? '');
    $detail = (string)($e['detail'] ?? '');

    if ($f_sev !== 'all' && $sev !== $f_sev) continue;
    if ($f_mod !== 'all' && $mod !== $f_mod) continue;

    if ($f_q !== '') {
        $hay = strtolower($type . ' ' . $detail . ' ' . $mod);
        if (strpos($hay, strtolower($f_q)) === false) continue;
    }
    $filtered[] = $e;
}
$filtered = array_slice($filtered, 0, 30);

/* =========================================================
   [E] Handle POST
   ========================================================= */
generateSessionToken();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkToken($_REQUEST['user_token'] ?? '', $_SESSION['session_token'] ?? '', 'index.php');
    $action = (string)($_POST['do'] ?? '');

    if ($action === 'set_active') {
        $id = trim($_POST['incident_id'] ?? '');
        setcookie('cas_active_incident', $id, time()+60*60*24*30, '/');
        $_COOKIE['cas_active_incident'] = $id;
    }
    if ($action === 'set_status') {
        $id = trim($_POST['incident_id'] ?? '');
        $status = trim($_POST['status'] ?? 'new');
        cas_incident_update($id, function($inc) use ($status) {
            $inc['status'] = $status;
            return $inc;
        });
    }
    if ($action === 'toggle_step') {
        $id = trim($_POST['incident_id'] ?? '');
        $step = trim($_POST['step'] ?? '');
        cas_incident_update($id, function($inc) use ($step) {
            if (isset($inc['playbook'][$step])) {
                $inc['playbook'][$step] = !$inc['playbook'][$step];
            }
            return $inc;
        });
    }
    if ($action === 'reset_incidents') {
        cas_incident_write_all([]);
    }
    if ($action === 'reset_events') {
        $log = cas_log_path();
        $fp = @fopen($log, 'cb');
        if ($fp && flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        if ($fp) fclose($fp);
    }
    dvwaRedirect('index.php');
}

/* =========================================================
   [F] Load incidents
   ========================================================= */
$incidents = cas_incident_read_all();
usort($incidents, function($a,$b){
    return strcmp((string)($b['created_ts'] ?? ''), (string)($a['created_ts'] ?? ''));
});

$activeId = $_COOKIE['cas_active_incident'] ?? '';
if ($activeId === '' && !empty($incidents)) $activeId = $incidents[0]['id'];
$active = $activeId ? cas_incident_get_by_id($activeId) : null;

/* =========================================================
   [G] UI (CSS) - ห้ามแก้ตามคำขอ
   ========================================================= */
$page['body'] .= "
<style>
  .cas-wrap{max-width:1100px;margin:0 auto;}
  .cas-sub{color:var(--muted);margin-top:-6px;}
  .cas-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:12px 14px;box-shadow:var(--shadow); margin-top:12px;}
  .cas-card h2{margin:0 0 8px 0;}
  .cas-card h3{margin:10px 0 6px 0;}
  .cas-help{margin-top:8px;font-size:12px;color:var(--muted);}
  .cas-form{display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
  .cas-form label{font-weight:800;min-width:70px;}
  .cas-form input[type=text], .cas-form select{min-width:220px;}
  .cas-table{width:100%;border-collapse:collapse;background:var(--surface);}
  .cas-table th,.cas-table td{border:1px solid var(--border);padding:8px 10px;text-align:left;vertical-align:top;}
  .cas-table th{background:rgba(30,136,229,.10);}
  body.dark .cas-table th{background:rgba(96,165,250,.10);}
  .cas-pill{display:inline-block;padding:2px 8px;border:1px solid var(--border);background:var(--bg);font-weight:900;text-transform:lowercase;}
  .cas-pill.info{border-color:rgba(30,136,229,.35);}
  .cas-pill.warn{border-color:rgba(245,158,11,.45);}
  .cas-pill.high{border-color:rgba(239,68,68,.55);}
  .cas-pill.critical{border-color:rgba(239,68,68,.85);}
  .cas-note{font-size:12px;color:var(--muted);margin-top:6px;}
  .cas-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;}
  .cas-steps{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;}
  @media (max-width:640px){.cas-steps{grid-template-columns:1fr;}}
  .cas-step{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 10px;border:1px solid var(--border);background:var(--bg);}
  .cas-step b{font-size:12px;text-transform:uppercase;}
  .cas-mini{background:var(--bg);border:1px solid var(--border);padding:10px 12px;border-radius:var(--radius);}
  .cas-note-list{margin:8px 0 0 0;padding-left:18px;}
  .cas-note-list li{margin:6px 0;}
  .cas-headline{display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;}
  .cas-headline__right{display:flex; gap:8px; align-items:center; flex-wrap:wrap;}
  .cas-headline__right form{margin:0;}
</style>
";

/* =========================================================
   [H] UI (HTML)
   ========================================================= */
$page['body'] .= "
<div class='body_padded cas-wrap'>
  <h1>Incident Timeline + Playbook (mini SOC)</h1>
  <p class='cas-sub'>
    วิเคราะห์ <b>Event Log</b> → ระบุ <b>Incident</b> → ดำเนินการตาม <b>Playbook</b> เพื่อกู้คืนระบบ
  </p>

  {$autoMsg}

  <div class='cas-card'>
    <h2>How to use (สรุปการใช้งาน)</h2>
    <div class='cas-mini'>
      <ol style='margin:0;padding-left:18px;'>
        <li>ไปที่ <b>Simulation Lab</b> เพื่อสร้างเหตุการณ์โจมตีจำลอง</li>
        <li>กลับมาหน้านี้เพื่อตรวจสอบ <b>Timeline</b> หา Root Cause ของปัญหา</li>
        <li>เมื่อพบสิ่งผิดปกติ ให้ทำการ Update <b>Playbook</b> จนกระทั่งสถานะเป็น Resolved</li>
      </ol>
    </div>
  </div>

  <div class='cas-card'>
    <div class='cas-headline'>
      <h2 style='margin:0;'>Timeline (Latest Events)</h2>
      <div class='cas-headline__right'>
        <form method='post' onsubmit=\"return confirm('ลบประวัติ Incident ทั้งหมด?');\">
          ".tokenField()."
          <input type='hidden' name='do' value='reset_incidents'>
          <input type='submit' value='Reset Incidents' class='popup_button'>
        </form>
        <form method='post' onsubmit=\"return confirm('ล้าง Timeline ทั้งหมด?');\">
          ".tokenField()."
          <input type='hidden' name='do' value='reset_events'>
          <input type='submit' value='Clear Events' class='popup_button'>
        </form>
      </div>
    </div>

    <form class='cas-form' method='get' style='margin-top:10px;'>
      <label>Severity</label>
      <select name='sev'>
        <option value='all'>All Levels</option>
        <option value='info' ".($f_sev==='info'?'selected':'').">Info - กิจกรรมปกติ</option>
        <option value='warn' ".($f_sev==='warn'?'selected':'').">Warning - ผิกปกติเล็กน้อย</option>
        <option value='high' ".($f_sev==='high'?'selected':'').">High - อันตราย</option>
        <option value='critical' ".($f_sev==='critical'?'selected':'').">Critical - วิกฤต</option>
      </select>

      <label>Module</label>
      <select name='mod'>
        <option value='all'>All Modules</option>
";

// 1. กำหนดรายการ Module พื้นฐาน
$standard_options = [
    'auth'   => 'Authentication',
    'system' => 'System Audit',
    'sqli'   => 'SQL Injection',
    'brute'  => 'Brute Force',
    'xss'    => 'XSS',
    'upload' => 'File Upload',
    'simlab' => 'Simulation Lab'
];

// 2. วนลูปสร้าง Option พื้นฐาน ใส่ลงใน $page['body']
foreach ($standard_options as $val => $label) {
    $sel = ($f_mod === $val) ? 'selected' : '';
    $page['body'] .= "<option value='{$val}' {$sel}>{$label}</option>";
}

// 3. ส่วนดึงจาก Log จริง (กรณีเจอ Module ใหม่ๆ)
$standard_keys = array_keys($standard_options);
foreach ($availableModules as $m) {
    if (in_array($m, $standard_keys)) continue;
    $sel = ($f_mod === $m) ? 'selected' : '';
    $page['body'] .= "<option value='".htmlspecialchars($m,ENT_QUOTES,'UTF-8')."' {$sel}>".htmlspecialchars($m,ENT_QUOTES,'UTF-8')." (Detected)</option>";
}

// 4. ปิด Tag Select
$page['body'] .= "
      </select>

      <label>Search</label>
      <input type='text' name='q' value='".htmlspecialchars($f_q,ENT_QUOTES,'UTF-8')."' placeholder='ค้นหา Event / รายละเอียด...' />

      <input type='submit' value='Filter' class='popup_button' />
      <a class='cas-note' href='index.php' style='text-decoration:none; margin-left:5px;'>Clear</a>
    </form>

    <div style='margin-top:10px;' class='vulnerable_code_area'>
      <table class='cas-table'>
        <thead>
          <tr>
            <th style='width:185px;'>Time (UTC)</th>
            <th style='width:110px;'>Severity</th>
            <th style='width:160px;'>Event ID</th>
            <th style='width:120px;'>Module</th>
            <th>Evidence / Message</th>
          </tr>
        </thead>
        <tbody>
";

if (empty($filtered)) {
    $page['body'] .= "<tr><td colspan='5' style='text-align:center;'>ยังไม่พบข้อมูลเหตุการณ์ (ลองไปที่ Simulation Lab ก่อน)</td></tr>";
} else {
    foreach ($filtered as $e) {
        $time = htmlspecialchars((string)($e['ts'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sevRaw = strtolower((string)($e['severity'] ?? 'info'));
        $sev = htmlspecialchars($sevRaw, ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars((string)($e['type'] ?? ''), ENT_QUOTES, 'UTF-8');
        $mod  = htmlspecialchars((string)($e['module'] ?? ''), ENT_QUOTES, 'UTF-8');
        $detail = htmlspecialchars((string)($e['detail'] ?? ''), ENT_QUOTES, 'UTF-8');

        // ข้อมูลทางเทคนิคที่ SOC ต้องใช้ (Forensics Data)
        $skip = ['ts','type','module','severity','detail','user','security','ip','ua','path'];
        $extraPairs = [];
        if (!empty($e['ip'])) $extraPairs[] = "IP: " . htmlspecialchars($e['ip'], ENT_QUOTES, 'UTF-8');
        
        foreach ($e as $k => $v) {
            if (in_array($k, $skip, true)) continue;
            if (is_scalar($v) && (string)$v !== '') {
                $extraPairs[] = htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            }
        }
        $extraTxt = implode(' | ', $extraPairs);

        $pill = ($sevRaw === 'critical' || $sevRaw === 'high') ? $sevRaw : (($sevRaw === 'warn' || $sevRaw === 'warning') ? 'warn' : 'info');
        $msg = "<b>{$detail}</b>";
        if ($extraTxt !== '') $msg .= "<div class='cas-note' style='background:rgba(0,0,0,0.05); padding:2px 5px; border-radius:3px;'> {$extraTxt}</div>";

        $page['body'] .= "
          <tr>
            <td style='font-family:monospace;'>{$time}</td>
            <td><span class='cas-pill {$pill}'>{$sev}</span></td>
            <td style='font-weight:bold;'>{$type}</td>
            <td><code>{$mod}</code></td>
            <td>{$msg}</td>
          </tr>";
    }
}

$page['body'] .= "
        </tbody>
      </table>
    </div>
  </div>

  <div class='cas-card'>
    <h2>Active Incident Management</h2>
";

if (!$active) {
    $page['body'] .= "
    <div class='vulnerable_code_area' style='margin-top:10px; text-align:center; padding:20px;'>
      <p style='font-size:1.2em;'> <b>ไม่มี Incident ที่กำลังดำเนินการ</b></p>
      <p class='cas-note'>ระบบจะเปิด Case ให้อัตโนมัติเมื่อตรวจพบความเสี่ยงระดับ <b>High/Critical</b></p>
    </div>";
} else {
    $aid = htmlspecialchars($active['id'], ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($active['title'] ?? '', ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars($active['status'] ?? 'new', ENT_QUOTES, 'UTF-8');
    $created = htmlspecialchars($active['created_ts'] ?? '', ENT_QUOTES, 'UTF-8');
    $pb = $active['playbook'] ?? [];
    
    $steps = [
        'identify' => ['Identify', 'ตรวจสอบร่องรอยและยืนยันการโจมตี (หลักฐานจาก Log)'],
        'contain' => ['Contain', 'จำกัดขอบเขตความเสียหาย (เช่น ตัดการเชื่อมต่อเครื่องที่ติดเชื้อ)'],
        'eradicate' => ['Eradicate', 'กำจัดต้นตอ (เช่น ลบ Malware หรือแก้ไขช่องโหว่โค้ด)'],
        'recover' => ['Recover', 'กู้คืนระบบกลับสู่สภาวะปกติและเฝ้าระวัง'],
        'lessons' => ['Lessons Learned', 'สรุปรายงานเหตุการณ์และแนวทางป้องกันในอนาคต']
    ];

    $page['body'] .= "
    <div class='vulnerable_code_area' style='margin-top:10px;'>
      <div style='display:flex; justify-content:space-between;'>
        <span style='font-size:1.4em; font-weight:bold;'>Case: {$aid}</span>
        <span class='cas-pill info' style='padding:5px 15px;'>สถานะ: {$status}</span>
      </div>
      <div class='cas-note'>ชื่อเหตุการณ์: {$title} | เริ่มบันทึกเมื่อ: {$created} (UTC)</div>

      <hr />
      <h3>1. Incident Status</h3>
      <form method='post' class='cas-form'>
        ".tokenField()."
        <input type='hidden' name='do' value='set_status'>
        <input type='hidden' name='incident_id' value='{$aid}'>
        <label>Update Status</label>
        <select name='status'>
          <option value='new' ".($status==='new'?'selected':'').">New (รับเรื่อง)</option>
          <option value='investigating' ".($status==='investigating'?'selected':'').">Investigating (กำลังวิเคราะห์)</option>
          <option value='contained' ".($status==='contained'?'selected':'').">Contained (ควบคุมได้แล้ว)</option>
          <option value='resolved' ".($status==='resolved'?'selected':'').">Resolved (แก้ไขเสร็จสิ้น)</option>
        </select>
        <input type='submit' value='บันทึกสถานะ' class='popup_button'>
      </form>

      <hr />
      <h3>2. Response Playbook (IR Steps)</h3>
      <div class='cas-steps'>";
    foreach ($steps as $key => $arr) {
        $done = !empty($pb[$key]);
        $page['body'] .= "
        <form method='post' class='cas-step' style='".($done?'opacity:0.6; background:#e8f5e9;':'')."'>
          ".tokenField()."
          <input type='hidden' name='do' value='toggle_step'>
          <input type='hidden' name='incident_id' value='{$aid}'>
          <input type='hidden' name='step' value='".htmlspecialchars($key,ENT_QUOTES,'UTF-8')."'>
          <div>
            <b style='color:var(--primary);'>{$arr[0]}</b>
            <div class='cas-note'>{$arr[1]}</div>
          </div>
          <input type='submit' class='popup_button' value='".($done?' Complete':'Mark Done')."'>
        </form>";
    }
    $page['body'] .= "</div></div>";
}
$page['body'] .= "</div>";

/* =========================================================
   [I] Incidents list
   ========================================================= */
$page['body'] .= "
  <div class='cas-card'>
    <h2>Case Archive (คลังข้อมูลเหตุการณ์)</h2>
    <div class='vulnerable_code_area' style='margin-top:10px;'>";
if (empty($incidents)) {
    $page['body'] .= "<p class='cas-note'>ยังไม่มีประวัติการเปิด Case</p>";
} else {
    foreach ($incidents as $inc) {
        $iid = htmlspecialchars($inc['id'], ENT_QUOTES, 'UTF-8');
        $it  = htmlspecialchars($inc['title'], ENT_QUOTES, 'UTF-8');
        $is  = htmlspecialchars($inc['status'], ENT_QUOTES, 'UTF-8');
        $page['body'] .= "
        <form method='post' style='margin-bottom:8px; border-bottom:1px solid var(--border); padding-bottom:5px;'>
          ".tokenField()."
          <input type='hidden' name='do' value='set_active'>
          <input type='hidden' name='incident_id' value='{$iid}'>
          <span class='cas-pill info' style='min-width:80px; text-align:center;'>{$is}</span>
          <b style='margin-left:10px;'>{$iid}</b> — {$it}
          <input type='submit' value='เรียกดูข้อมูล' class='popup_button' style='float:right;'>
          <div style='clear:both;'></div>
        </form>";
    }
}
$page['body'] .= "</div></div>";

/* =========================================================
   [J] Learning Panel
   ========================================================= */
$page['body'] .= "
  <div class='cas-card'>
    <h2>Learning Panel (IR Mindset)</h2>
    <div class='cas-mini'>
      <b>ทำไมทักษะนี้ถึงสำคัญ?</b>
      <ul class='cas-note-list'>
        <li><b>Incident ≠ Event:</b> ไม่ใช่ทุก Log คือภัยคุกคาม หน้าที่ของ SOC คือแยกแยะ Noise ออกจาก Incident จริง</li>
        <li><b>Evidence Chain:</b> การทำ Timeline ช่วยให้เห็นภาพรวมว่าคนร้ายเริ่มเจาะจากจุดไหน (Initial Access)</li>
        <li><b>Standardization:</b> การใช้ Playbook ช่วยให้การตอบโต้มีมาตรฐาน ลดความผิดพลาดจากตัวบุคคล (Human Error)</li>
      </ul>
    </div>
  </div>
</div>";

dvwaHtmlEcho($page);
?>