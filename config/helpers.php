<?php
// config/helpers.php

function sanitize_filename($name) {
    $name = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $name);
    return trim($name, '_');
}

function req_json() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function now_db() {
    return date('Y-m-d H:i:s');
}
?>
