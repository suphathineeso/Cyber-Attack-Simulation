<?php

define('DVWA_WEB_PAGE_TO_ROOT', '../../');
require_once DVWA_WEB_PAGE_TO_ROOT . 'dvwa/includes/dvwaPage.inc.php';

dvwaPageStartup(array('authenticated'));

$page = dvwaPageNewGrab();
$page['title'] = 'Help - IDOR' . $page['title_separator'] . $page['title'];

$page['body'] .= <<<HTML
<div class="body_padded">
  <h1>Help - IDOR</h1>

  <div id="code">
    <table width="100%" bgcolor="white" style="border:2px #C0C0C0 solid">
      <tr><td>

        <h3>About</h3>
        <p><b>IDOR</b> (Insecure Direct Object Reference) เกิดเมื่อระบบใช้ค่าอ้างอิง เช่น user_id / file_id
        แล้ว “ไม่ตรวจสอบสิทธิ์” ทำให้ผู้ใช้เข้าถึงข้อมูลของคนอื่นได้</p>

        <br /><hr /><br />

        <h3>Objective</h3>
        <p>มีผู้ใช้ 5 คน (ID 1-5) ให้ลองเข้าถึงโปรไฟล์ที่ไม่ใช่ของตัวเองโดยการเปลี่ยนค่า ID</p>

        <br /><hr /><br />

        <h3>Low</h3>
        <p>รับค่า ID จาก GET ตรงๆ ไม่มีการเช็คสิทธิ์</p>

        <br />

        <h3>Medium</h3>
        <p>มีการตรวจรูปแบบข้อมูลบ้าง (ตัวเลข) แต่ยังไม่เช็คสิทธิ์</p>

        <br />

        <h3>High</h3>
        <p>ส่งค่า ID ผ่าน Session (ตั้งค่าจากหน้า session-input.php)</p>

        <br />

        <h3>Impossible</h3>
        <p>ตรวจ Authorization ถูกต้อง (อนุญาตให้ดูได้เฉพาะข้อมูลของตัวเอง)</p>

      </td></tr>
    </table>
  </div>
</div>
HTML;

dvwaHtmlEcho($page);