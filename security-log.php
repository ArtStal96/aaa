<?php

function logSecurity($type, $data = []) {
    $logFile = __DIR__ . "/security.log";

    $entry = [
        "time" => date("Y-m-d H:i:s"),
        "type" => $type,
        "ip"   => $_SERVER["REMOTE_ADDR"] ?? "unknown",
        "ua"   => $_SERVER["HTTP_USER_AGENT"] ?? "unknown",
        "data" => $data
    ];

    file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
}
