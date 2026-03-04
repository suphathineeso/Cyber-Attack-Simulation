<?php
// dvwa/vulnerabilities/view_compare.php
// Compare Low vs Impossible source for a vulnerability in a safe, read-only way.

define('DVWA_WEB_PAGE_TO_ROOT', '../');
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

dvwaPageStartup(array('authenticated'));

$id = isset($_GET['id']) ? $_GET['id'] : '';
// allow only safe folder names
if (!preg_match('/^[a-z0-9_]+$/i', $id)) {
	die('Invalid id');
}

$lowPath = DVWA_WEB_PAGE_TO_ROOT . "vulnerabilities/{$id}/source/low.php";
$impPath = DVWA_WEB_PAGE_TO_ROOT . "vulnerabilities/{$id}/source/impossible.php";

if (!file_exists($lowPath) || !file_exists($impPath)) {
	die('Compare source not found for this module.');
}

$low = file($lowPath, FILE_IGNORE_NEW_LINES);
$imp = file($impPath, FILE_IGNORE_NEW_LINES);

function esc($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Simple diff marker: highlight lines that differ by position.
// (พอสำหรับ “เรียนรู้” และอ่านง่าย ไม่เน้น diff algorithm หนัก)
$max = max(count($low), count($imp));

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Compare: <?php echo esc($id); ?> (Low vs Impossible)</title>
  <style>
    :root{
      --bg:#0b1220; --panel:#121a2b; --text:#eaf1ff; --muted:#9fb0d1;
      --line:#26324a; --accent:#60a5fa; --warn:#ff6b6b;
    }
    body{margin:0;background:var(--bg);color:var(--text);font:13px/18px Arial,Helvetica,sans-serif;}
    .top{padding:14px 16px;border-bottom:1px solid rgba(96,165,250,.25);}
    .top h1{margin:0;font-size:14px;letter-spacing:.4px;text-transform:uppercase;}
    .top .sub{margin-top:4px;color:var(--muted);font-size:12px;}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:12px;max-width:1400px;margin:0 auto;}
    .col{background:var(--panel);border:1px solid rgba(96,165,250,.18);}
    .col .head{padding:10px 12px;border-bottom:1px solid rgba(96,165,250,.15);display:flex;justify-content:space-between;align-items:center;}
    .badge{font-weight:900;letter-spacing:.25px;text-transform:uppercase;font-size:12px;}
    .badge.low{color:var(--warn);}
    .badge.imp{color:var(--accent);}
    .code{font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size:12px;}
    .row{display:grid;grid-template-columns:56px 1fr;border-bottom:1px solid rgba(38,50,74,.55);}
    .ln{padding:6px 10px;color:rgba(234,241,255,.55);text-align:right;border-right:1px solid rgba(38,50,74,.55);}
    .tx{padding:6px 10px;white-space:pre;overflow:auto;}
    .diff .tx{background:rgba(255,107,107,.08);}
    .note{max-width:1400px;margin:0 auto;padding:0 12px 14px;color:var(--muted);}
    @media (max-width: 980px){ .grid{grid-template-columns:1fr;} }
  </style>
</head>
<body>
  <div class="top">
    <h1>Compare Source — <?php echo esc($id); ?></h1>
    <div class="sub">Left: Low (unsafe patterns) • Right: Impossible (safer patterns)</div>
  </div>

  <div class="grid">
    <div class="col">
      <div class="head"><span class="badge low">Low</span><span class="sub"><?php echo esc($lowPath); ?></span></div>
      <div class="code">
        <?php for($i=0;$i<$max;$i++):
          $l = $low[$i] ?? '';
          $r = $imp[$i] ?? '';
          $diff = ($l !== $r);
        ?>
          <div class="row <?php echo $diff ? 'diff':''; ?>">
            <div class="ln"><?php echo $i+1; ?></div>
            <div class="tx"><?php echo esc($l); ?></div>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <div class="col">
      <div class="head"><span class="badge imp">Impossible</span><span class="sub"><?php echo esc($impPath); ?></span></div>
      <div class="code">
        <?php for($i=0;$i<$max;$i++):
          $l = $low[$i] ?? '';
          $r = $imp[$i] ?? '';
          $diff = ($l !== $r);
        ?>
          <div class="row <?php echo $diff ? 'diff':''; ?>">
            <div class="ln"><?php echo $i+1; ?></div>
            <div class="tx"><?php echo esc($r); ?></div>
          </div>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <div class="note">
    Tip: สีแดงอ่อนคือบรรทัดที่ต่างกัน (เทียบตำแหน่งเดียวกัน) เพื่อให้ “เห็นจุดเปลี่ยน” ได้เร็ว
  </div>
</body>
</html>