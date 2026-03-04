<?php
define('DVWA_WEB_PAGE_TO_ROOT', '');
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/casLogger.inc.php';

dvwaPageStartup(array('authenticated'));

$page = dvwaPageNewGrab();
$page['title']   = 'Cyber Attack Simulation - Lab' . $page['title_separator'] . $page['title'];
$page['page_id'] = 'cas_lab'; // ใช้เป็น id เฉพาะของหน้า

// -----------------------------
// Handle POST (Simulation)
// -----------------------------
$resultHtml = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cas_action'])) {

    $action = $_POST['cas_action'];

    // helper sanitize
    $s = function($x){ return trim((string)$x); };

    if ($action === 'verify') {
        $username = $s($_POST['username'] ?? '');
        if ($username === '') {
            $resultHtml .= "<div class='cas-alert cas-alert--warn'>กรุณากรอก Username</div>";
        } else {
            // Simulation logic (ไม่ทำ auth จริง)
            $ok = (strlen($username) >= 4);
            $detail = "username={$username}";
            cas_log_event('Account Verification (Simulation)', $detail, $ok ? 'info' : 'warn', array(
                'module' => 'verify',
                'username' => $username,
                'result' => $ok ? 'success' : 'failed'
            ));
            $resultHtml .= $ok
                ? "<div class='cas-alert cas-alert--ok'>Verification passed (Simulation): <b>" . htmlspecialchars($username) . "</b></div>"
                : "<div class='cas-alert cas-alert--bad'>Verification failed (Simulation): <b>" . htmlspecialchars($username) . "</b></div>";
        }
    }

    if ($action === 'search') {
        $q = $s($_POST['query'] ?? '');
        if ($q === '') {
            $resultHtml .= "<div class='cas-alert cas-alert--warn'>กรุณากรอก Query</div>";
        } else {
            // Simulation: ตรวจ pattern น่าสงสัยแบบ “เชิงเรียนรู้” (ไม่ใช่ payload สอนโจมตี)
            $isSusp = (preg_match('/\b(or|and)\b\s*\d+\s*=\s*\d+/i', $q) || stripos($q, 'union') !== false);
            cas_log_event('Suspicious Search (Simulation)', "query={$q}", $isSusp ? 'warn' : 'info', array(
                'module' => 'search',
                'query' => $q,
                'flag' => $isSusp ? 'suspicious' : 'normal'
            ));
            $resultHtml .= $isSusp
                ? "<div class='cas-alert cas-alert--warn'>SQL Injection pattern detected (Simulation): <b>" . htmlspecialchars($q) . "</b></div>"
                : "<div class='cas-alert cas-alert--ok'>Search logged (Simulation): <b>" . htmlspecialchars($q) . "</b></div>";
        }
    }

    if ($action === 'ransom') {
        $filename = $s($_POST['filename'] ?? '');
        if ($filename === '') $filename = 'example.txt';

        // Simulation: ไม่เข้ารหัสจริง แค่ “จำลองเหตุการณ์” แล้ว log
        cas_log_event('Ransomware (Simulation)', "file={$filename}", 'warn', array(
            'module' => 'ransom',
            'file' => $filename,
            'result' => 'encrypted(simulation)'
        ));
        $resultHtml .= "<div class='cas-alert cas-alert--bad'>File encrypted (Simulation): <b>" . htmlspecialchars($filename) . "</b></div>";
    }
}

// Read logs (latest)
$events = cas_read_last_events(25);

// -----------------------------
// Page body (UI)
// -----------------------------
$page['body'] .= "
<div class='cas-lab body_padded'>

  <h1>Cyber Attack Simulation - Lab</h1>
  <p class='cas-sub'>
    หน้านี้เป็นการ <b>จำลองเหตุการณ์</b> เพื่อการเรียนรู้: กดปุ่ม → สร้าง event → บันทึกลง <code>dvwa/logs/events.jsonl</code> และแสดงผลด้านล่าง
  </p>

  <hr class='cas-hr' />

  <div class='cas-grid-2'>

    <!-- 1) Account Verification -->
    <section class='cas-box'>
      <h2>1) Account Verification <span class='cas-tag'>(Simulation)</span></h2>

      <form method='post' class='cas-form'>
        <input type='hidden' name='cas_action' value='verify' />
        <label class='cas-label'>Username</label>
        <div class='cas-row'>
          <input class='cas-input' type='text' name='username' placeholder='taweb68' />
          <button class='cas-btn' type='submit'>Simulate</button>
        </div>
      </form>

      <div class='cas-learn'>
        <div class='cas-learn__title'>สิ่งที่ต้องเรียนรู้ (Why / Safe Lab / Fix)</div>
        <div class='cas-learn__flow'>Input(username) → Validation → Decision → Log</div>
        <div class='cas-learn__cols'>
          <div class='cas-card'>
            <div class='cas-card__h'>Why vulnerable?</div>
            <ul class='cas-ul'>
              <li>ระบบยืนยันตัวตนที่ไม่มี rate limit/lockout ทำให้เดารหัสซ้ำได้</li>
              <li>ข้อความ error ต่างกันมาก → ช่วยเดาได้ง่ายขึ้น</li>
              <li>ไม่มีการ log/alert → ทีมไม่รู้ว่ากำลังโดนลองผิดลองถูก</li>
            </ul>
          </div>
          <div class='cas-card'>
            <div class='cas-card__h'>Safe Lab</div>
            <ul class='cas-ul'>
              <li>ทดลองกรอก username หลายรูปแบบ แล้วดูผลลัพธ์ที่ระบบแสดง</li>
              <li>สังเกตว่าเราบันทึก event อะไรลง log บ้าง (เวลา/ผู้ใช้/รายละเอียด)</li>
              <li>ดูว่า “สัญญาณเตือน” ควรเป็นแบบไหนถึงพอดี ไม่รบกวนผู้ใช้</li>
            </ul>
          </div>
          <div class='cas-card'>
            <div class='cas-card__h'>Fix checklist</div>
            <ul class='cas-ul'>
              <li>Rate limit + progressive delay</li>
              <li>Lockout ชั่วคราว + แจ้งเตือน</li>
              <li>Log + alert + audit trail</li>
            </ul>
          </div>
        </div>
      </div>

    </section>

    <!-- 2) Search / Suspicious Activity -->
    <section class='cas-box'>
      <h2>2) Search / Suspicious Activity <span class='cas-tag'>(Simulation)</span></h2>

      <form method='post' class='cas-form'>
        <input type='hidden' name='cas_action' value='search' />
        <label class='cas-label'>Query</label>
        <div class='cas-row'>
          <input class='cas-input' type='text' name='query' placeholder='type a search query...' />
          <button class='cas-btn' type='submit'>Simulate</button>
        </div>
      </form>

      <div class='cas-learn'>
        <div class='cas-learn__title'>สิ่งที่ต้องเรียนรู้ (Why / Safe Lab / Fix)</div>
        <div class='cas-learn__flow'>Input(query) → Pattern check (simulation) → Log → Response</div>
        <div class='cas-learn__cols'>
          <div class='cas-card'>
            <div class='cas-card__h'>Why vulnerable?</div>
            <ul class='cas-ul'>
              <li>ถ้ารับ query ไปประกอบ SQL ตรง ๆ จะเสี่ยง Injection</li>
              <li>ขาด validation/parameterization ทำให้ query structure เปลี่ยนได้</li>
              <li>ขาด monitoring/log ทำให้ตรวจจับพฤติกรรมผิดปกติไม่ได้</li>
            </ul>
          </div>
          <div class='cas-card'>
            <div class='cas-card__h'>Safe Lab</div>
            <ul class='cas-ul'>
              <li>ลอง query แบบปกติและแบบ “น่าสงสัย” แล้วดูว่า flag ต่างกันยังไง</li>
              <li>เช็ค log ว่าบันทึก severity เป็น info/warn อย่างถูกต้องไหม</li>
              <li>คิดต่อ: ถ้าเป็นระบบจริง จะทำ response ยังไงไม่ให้บอกข้อมูลมากเกิน</li>
            </ul>
          </div>
          <div class='cas-card'>
            <div class='cas-card__h'>Fix checklist</div>
            <ul class='cas-ul'>
              <li>Prepared statements + bind parameters</li>
              <li>Input validation ตาม context</li>
              <li>Security logging + alert rules</li>
            </ul>
          </div>
        </div>
      </div>

    </section>

    <!-- 3) Ransomware -->
    <section class='cas-box'>
      <h2>3) Ransomware <span class='cas-tag'>(Simulation)</span></h2>

      <form method='post' class='cas-form'>
        <input type='hidden' name='cas_action' value='ransom' />
        <label class='cas-label'>Filename</label>
        <div class='cas-row'>
          <input class='cas-input' type='text' name='filename' value='example.txt' />
          <button class='cas-btn' type='submit'>Simulate</button>
        </div>
      </form>

      <div class='cas-learn'>
        <div class='cas-learn__title'>สิ่งที่ต้องเรียนรู้ (Why / Safe Lab / Fix)</div>
        <div class='cas-learn__flow'>Action(trigger) → Impact(simulated) → Log → Response</div>
        <div class='cas-learn__cols'>
          <div class='cas-card'>
            <div class='cas-card__h'>Why vulnerable?</div>
            <ul class='cas-ul'>
              <li>ถ้าไม่มี backup/segmentation เมื่อเกิดเหตุจะกระทบวงกว้าง</li>
              <li>สิทธิ์ไฟล์/บัญชีสูงเกิน → ransomware ทำลายได้มากขึ้น</li>
              <li>ไม่มี incident log → ตอบสนองช้าและหาสาเหตุยาก</li>
            </ul>
          </div>
          <div class='cas-card'>
            <div class='cas-card__h'>Safe Lab</div>
            <ul class='cas-ul'>
              <li>เน้น “ลำดับเหตุการณ์” ใน log: เริ่มต้น → ผลกระทบ → การตอบสนอง</li>
              <li>กำหนดว่า event ไหนควรเป็น warn/critical</li>
              <li>วางแนว incident response แบบสั้น ๆ จากข้อมูลใน log</li>
            </ul>
          </div>
          <div class='cas-card'>
            <div class='cas-card__h'>Fix checklist</div>
            <ul class='cas-ul'>
              <li>Offline backup + restore drill</li>
              <li>Least privilege + segmentation</li>
              <li>Monitoring + incident response playbook</li>
            </ul>
          </div>
        </div>
      </div>

    </section>

  </div>

  <!-- (ผลลัพธ์) แสดงด้านล่างตามที่ขอ -->
  <div class='cas-spacer'></div>
  <h2>Result</h2>
  <div class='cas-result'>
    " . ($resultHtml ? $resultHtml : "<div class='cas-muted'>ยังไม่มีการจำลอง — ลองกรอกแล้วกด Simulate เพื่อให้มีผลลัพธ์</div>") . "
  </div>

  <div class='cas-spacer'></div>
  <h2>Event Log (ล่าสุด)</h2>
  <p class='cas-muted'>เปิดไฟล์: <code>dvwa/logs/events.jsonl</code> เพื่อดูหลักฐานทั้งหมด (ทำ dashboard ทีหลังได้)</p>

  <div class='cas-table-wrap'>
    <table class='cas-table'>
      <thead>
        <tr>
          <th>Time</th>
          <th>Event</th>
          <th>Severity</th>
          <th>Detail</th>
          <th>User</th>
        </tr>
      </thead>
      <tbody>";

if (!empty($events)) {
    foreach ($events as $e) {
        $ts = htmlspecialchars($e['ts'] ?? '');
        $ev = htmlspecialchars($e['event'] ?? '');
        $sv = htmlspecialchars($e['severity'] ?? '');
        $dt = htmlspecialchars($e['detail'] ?? '');
        $us = htmlspecialchars($e['user'] ?? '');

        $page['body'] .= "
        <tr>
          <td class='mono'>{$ts}</td>
          <td>{$ev}</td>
          <td><span class='sev sev--{$sv}'>{$sv}</span></td>
          <td class='mono'>{$dt}</td>
          <td>{$us}</td>
        </tr>";
    }
} else {
    $page['body'] .= "
        <tr><td colspan='5' class='cas-muted'>ยังไม่มี log — ลองกด Simulate สัก 1 ครั้ง</td></tr>";
}

$page['body'] .= "
      </tbody>
    </table>
  </div>

</div>
";

dvwaHtmlEcho($page);