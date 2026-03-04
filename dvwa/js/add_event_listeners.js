// These functions need to be called after the content they reference
// has been added to the page otherwise they will fail.

function openUrl(url) {
  // fallback ถ้า popUp ไม่มีหรือพัง
  try {
    if (typeof popUp === "function") {
      popUp(url);
      return;
    }
  } catch (e) {}
  // สุดท้ายเปิดแท็บใหม่แบบชัวร์
  window.open(url, "_blank");
}

function addEventListeners() {
  var source_button = document.getElementById("source_button");
  if (source_button) {
    source_button.addEventListener("click", function () {
      openUrl(source_button.dataset.sourceUrl);
    });
  }

  var help_button = document.getElementById("help_button");
  if (help_button) {
    help_button.addEventListener("click", function () {
      openUrl(help_button.dataset.helpUrl);
    });
  }

  var compare_button = document.getElementById("compare_button");
  if (compare_button) {
    compare_button.addEventListener("click", function () {
      openUrl(compare_button.dataset.compareUrl);
    });
  }
}

// ชัวร์สุด: รอ DOM เสร็จก่อน
document.addEventListener("DOMContentLoaded", addEventListeners);