<?php
header("Content-Type: application/json");
require __DIR__ . "/bootstrap.php";

$merchantId = env("P24_MERCHANT_ID");
$posId      = env("P24_POS_ID");
$crc        = env("P24_CRC");
$apiKey     = env("P24_API_KEY");

$telegramToken = env("TELEGRAM_TOKEN");
$chatId        = env("TELEGRAM_CHAT_ID");

// Получаем данные от P24
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["error" => "no_input"]);
    exit;
}

// Подтверждаем транзакцию
$verifyData = [
    "merchantId" => $merchantId,
    "posId"      => $posId,
    "sessionId"  => $input["sessionId"],
    "amount"     => $input["amount"],
    "currency"   => "PLN"
];

$verifyData["sign"] = hash("sha384", json_encode($verifyData) . $crc);

$ch = curl_init("https://secure.przelewy24.pl/api/v1/transaction/verify");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verifyData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Basic " . base64_encode($posId . ":" . $apiKey)
]);

$response = curl_exec($ch);
curl_close($ch);

$res = json_decode($response, true);

// Если подтверждение успешно
if (!empty($res["data"]["status"]) && $res["data"]["status"] === "success") {

    // Формируем сообщение в Telegram
    $text  = "💳 *Оплата прошла успешно!*\n\n";
    $text .= "Сумма: " . ($input["amount"] / 100) . " PLN\n";
    $text .= "Session ID: " . $input["sessionId"] . "\n";
    $text .= "Order ID: " . ($input["orderId"] ?? "не указан") . "\n";

    // Отправляем в Telegram
    file_get_contents(
        "https://api.telegram.org/bot{$telegramToken}/sendMessage?chat_id={$chatId}&text=" . urlencode($text) . "&parse_mode=Markdown"
    );

    echo json_encode(["status" => "OK"]);
    exit;
}

echo json_encode(["error" => "verify_failed", "raw" => $res]);
