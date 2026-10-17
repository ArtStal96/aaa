<?php
header("Content-Type: application/json");
require __DIR__ . "/bootstrap.php";

// Загружаем ключи из .env
$merchantId = env("P24_MERCHANT_ID");
$posId      = env("P24_POS_ID");
$crc        = env("P24_CRC");
$apiKey     = env("P24_API_KEY");
require __DIR__ . "/security-log.php";
require __DIR__ . "/ban-ip.php";

$ip = $_SERVER["REMOTE_ADDR"];

// Автобан
if (isBanned($ip)) {
    logSecurity("blocked_banned_ip", ["ip" => $ip]);
    echo json_encode(["error" => "banned"]);
    exit;
}

// Проверка суммы
if ($input["total"] < 10) {
    banIP($ip);
    logSecurity("fraud_low_total", $input);
    echo json_encode(["error" => "fraud_detected"]);
    exit;
}

// Проверка корзины
if (!is_array($input["items"]) || count($input["items"]) === 0) {
    banIP($ip);
    logSecurity("fraud_empty_cart", $input);
    echo json_encode(["error" => "fraud_detected"]);
    exit;
}


// Получаем данные заказа от фронтенда
$input = json_decode(file_get_contents("php://input"), true);

$sessionId = time(); // уникальный ID
$amount    = intval($input["total"] * 100); // PLN → grosze

// Формируем данные для P24
$data = [
    "merchantId" => $merchantId,
    "posId"      => $posId,
    "sessionId"  => $sessionId,
    "amount"     => $amount,
    "currency"   => "PLN",
    "description"=> "Оплата заказа Timber Loft Design",
    "email"      => $input["email"] ?? "client@example.com",
    "country"    => "PL",
    "language"   => "pl",

    // Локальные пути для XAMPP
    "urlReturn"  => "http://localhost/api/return.php",
    "urlStatus"  => "http://localhost/api/status.php"
];

// Подпись
$sign = hash("sha384", json_encode($data) . $crc);
$data["sign"] = $sign;

// Отправляем запрос в P24
$ch = curl_init("https://secure.przelewy24.pl/api/v1/transaction/register");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Basic " . base64_encode($posId . ":" . $apiKey)
]);

$response = curl_exec($ch);
curl_close($ch);

$res = json_decode($response, true) ?: [];

// Если токен есть → отдаём ссылку на оплату
if (!empty($res["data"]["token"])) {
    echo json_encode([
        "redirectUrl" => "https://secure.przelewy24.pl/trnRequest/" . $res["data"]["token"]
    ]);
    exit;
}

// Ошибка
echo json_encode([
    "error" => "payment_register_failed",
    "raw"   => $res
]);
