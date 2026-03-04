<?php
/**
 * Cyber Attack Simulation - JSONL Logger
 * Path: dvwa/includes/casLogger.inc.php
 *
 * Logs to: dvwa/logs/events.jsonl (1 JSON per line)
 * Safe for concurrent writes (flock)
 */

if (!defined('DVWA_WEB_PAGE_TO_ROOT')) {
    // If someone calls this directly, avoid errors
    define('DVWA_WEB_PAGE_TO_ROOT', '');
}

/**
 * Return absolute path to log file (events.jsonl)
 */
function cas_log_path() {
    // DVWA_WEB_PAGE_TO_ROOT points to web root relative path.
    // On filesystem, we can derive from current file location:
    // this file is in dvwa/includes -> go up one -> dvwa/ -> logs/events.jsonl
    $base = dirname(__FILE__);              // .../dvwa/includes
    $dvwaDir = realpath($base . DIRECTORY_SEPARATOR . '..'); // .../dvwa
    $logDir = $dvwaDir . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    return $logDir . DIRECTORY_SEPARATOR . 'events.jsonl';
}

/**
 * Minimal sanitization so logs don't break UI
 */
function cas_log_clean($v) {
    if (is_null($v)) return null;
    if (is_bool($v)) return $v;
    if (is_int($v) || is_float($v)) return $v;

    // stringify
    $s = (string)$v;
    // trim very long values (avoid huge file)
    if (strlen($s) > 500) $s = substr($s, 0, 500) . '...';
    // remove newlines (JSONL expects 1 line per event)
    $s = str_replace(["\r", "\n"], [' ', ' '], $s);
    return $s;
}

/**
 * Write 1 event line to events.jsonl
 *
 * $type:    e.g. "SQLI_ATTEMPT_SIM", "BRUTE_FORCE_SIM", "LOGIN", "RESET_DB"
 * $module:  e.g. "sqli", "exec", "brute", "home"
 * $severity: "low" | "medium" | "high" | "info"
 * $detail:  short message
 * $extra:   associative array for extra fields
 */
function cas_log_event($type, $module, $severity, $detail, $extra = []) {
    // Basic context
    $event = [
        'ts'       => gmdate('c'), // ISO time in UTC
        'type'     => cas_log_clean($type),
        'module'   => cas_log_clean($module),
        'severity' => cas_log_clean($severity),
        'detail'   => cas_log_clean($detail),
        'user'     => function_exists('dvwaIsLoggedIn') && dvwaIsLoggedIn() ? cas_log_clean(dvwaCurrentUser()) : 'guest',
        'security' => function_exists('dvwaSecurityLevelGet') ? cas_log_clean(dvwaSecurityLevelGet()) : null,
        'ip'       => cas_log_clean($_SERVER['REMOTE_ADDR'] ?? null),
        'ua'       => cas_log_clean($_SERVER['HTTP_USER_AGENT'] ?? null),
        'path'     => cas_log_clean($_SERVER['REQUEST_URI'] ?? null),
    ];

    // Merge extra (clean)
    if (is_array($extra)) {
        foreach ($extra as $k => $v) {
            $event[cas_log_clean($k)] = cas_log_clean($v);
        }
    }

    $line = json_encode($event, JSON_UNESCAPED_UNICODE) . "\n";

    $file = cas_log_path();
    $fp = @fopen($file, 'ab'); // append binary
    if (!$fp) return false;

    // Lock for concurrent requests
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $line);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return true;
}

/**
 * Read last N events from jsonl (tail-like)
 */
function cas_log_tail($maxLines = 200) {
    $file = cas_log_path();
    if (!file_exists($file)) return [];

    $lines = [];
    $fp = @fopen($file, 'rb');
    if (!$fp) return [];

    // Read line by line (OK for small/medium logs)
    while (!feof($fp)) {
        $line = trim(fgets($fp));
        if ($line === '') continue;
        $lines[] = $line;
        if (count($lines) > $maxLines) {
            array_shift($lines);
        }
    }
    fclose($fp);

    $events = [];
    foreach ($lines as $l) {
        $j = json_decode($l, true);
        if (is_array($j)) $events[] = $j;
    }
    return $events;
}