// Cyber Attack Simulation - cyber-theme.js
(function () {
  function getCookie(name) {
    const m = document.cookie.match(new RegExp("(^| )" + name + "=([^;]+)"));
    return m ? decodeURIComponent(m[2]) : null;
  }
  function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${d.toUTCString()}; path=/`;
  }
  function apply(theme) {
    document.body.classList.toggle("dark", theme === "dark");
  }

  // apply on load
  const saved = getCookie("cyber_theme") || "light";
  apply(saved);

  // expose for button
  window.toggleCyberTheme = function () {
    const cur = document.body.classList.contains("dark") ? "dark" : "light";
    const next = cur === "dark" ? "light" : "dark";
    setCookie("cyber_theme", next, 365);
    apply(next);
  };
})();