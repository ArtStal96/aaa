<?php
header("Content-Type: application/json");
require __DIR__ . "/bootstrap.php";

$telegramToken = env("TELEGRAM_TOKEN");
$chatId        = env("TELEGRAM_CHAT_ID");

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["error" => "no_input"]);
    exit;
}
require __DIR__ . "/security-log.php";
require __DIR__ . "/ban-ip.php";

$ip = $_SERVER["REMOTE_ADDR"];

if (isBanned($ip)) {
    logSecurity("blocked_banned_ip", ["ip" => $ip]);
    echo json_encode(["error" => "banned"]);
    exit;
}

if (!is_array($input["items"]) || count($input["items"]) === 0) {
    banIP($ip);
    logSecurity("fraud_empty_cart_order", $input);
    echo json_encode(["error" => "fraud_detected"]);
    exit;
}


// Формируем сообщение
$text  = "🛒 *Новый заказ*\n\n";
$text .= "👤 Имя: " . $input["name"] . "\n";
$text .= "📞 Телефон: " . $input["phone"] . "\n";
$text .= "📍 Страна: " . $input["country"] . "\n";
$text .= "🏙 Город: " . $input["city"] . "\n";
$text .= "📮 Индекс: " . $input["zip"] . "\n";
$text .= "🏠 Адрес: " . $input["address"] . "\n";
$text .= "🚚 Доставка: " . $input["delivery"] . "\n";
$text .= "💰 Оплата: " . $input["payment"] . "\n";
$text .= "📅 Дата: " . $input["date"] . "\n\n";

$text .= "📦 *Товары:*\n";

foreach ($input["items"] as $item) {
    $text .= "- {$item["name"]} x{$item["quantity"]} ({$item["price"]} PLN)\n";
}

// Отправляем в Telegram
file_get_contents(
    "https://api.telegram.org/bot{$telegramToken}/sendMessage?chat_id={$chatId}&text=" . urlencode($text) . "&parse_mode=Markdown"
);

echo json_encode(["status" => "ok"]);
