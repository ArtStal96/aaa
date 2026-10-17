<?php
header("Content-Type: application/json");
require __DIR__ . "/bootstrap.php";

$aiKey = env("AI_SECRET_KEY");

// Получаем данные от фронта
$input = json_decode(file_get_contents("php://input"), true);

// Добавляем серверные данные
$input["ip"] = $_SERVER["REMOTE_ADDR"] ?? "unknown";
$input["ua"] = $_SERVER["HTTP_USER_AGENT"] ?? "unknown";

// Отправляем в твой ИИ
$ch = curl_init("http://localhost:8000/analyze"); // твой локальный ИИ
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $aiKey
]);

$response = curl_exec($ch);
curl_close($ch);

$res = json_decode($response, true);

// Если ИИ сказал "опасно"
if (!empty($res["risk"]) && $res["risk"] > 0.7) {
    echo json_encode(["block" => true, "risk" => $res["risk"]]);
    exit;
}

// Всё ок
echo json_encode(["block" => false, "risk" => $res["risk"] ?? 0]);
require __DIR__ . "/security-log.php";

if (!empty($res["risk"]) && $res["risk"] > 0.7) {
    logSecurity("blocked_by_ai", $input);
    echo json_encode(["block" => true, "risk" => $res["risk"]]);
    exit;
}

logSecurity("ai_pass", ["risk" => $res["risk"]]);
