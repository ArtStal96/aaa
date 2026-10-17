<?php
// Общий загрузчик env для всех API

function env($key, $default = null) {
    static $env = null;

    if ($env === null) {
        $path = __DIR__ . "/../.env";
        if (file_exists($path)) {
            $env = parse_ini_file($path);
        } else {
            $env = [];
        }
    }

    return $env[$key] ?? $default;
}
