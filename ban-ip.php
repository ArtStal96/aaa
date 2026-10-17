<?php

function banIP($ip) {
    $file = __DIR__ . "/banned.txt";
    file_put_contents($file, $ip . PHP_EOL, FILE_APPEND);
}

function isBanned($ip) {
    $file = __DIR__ . "/banned.txt";
    if (!file_exists($file)) return false;

    $list = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return in_array($ip, $list);
}
