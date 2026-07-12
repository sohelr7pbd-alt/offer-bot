<?php
$botToken = getenv('8623146278:AAGYRYs2zKaseNMK9w2342FRiJtNUD6PLZ4'); 
$chatId = getenv('8470477489'); 

$filePath = __DIR__. "/offers_bank.txt";
$logPath = __DIR__. "/telegram_log.txt";

$keywords = [
    "signup bonus", "bank bonus", "referral bonus", "checking bonus", "savings bonus",
    "chase", "sofi", "wells fargo", "bank of america", "citi", "discover", "capital one",
    "chime", "varo", "current", "upgrade", "credit card", "credit card bonus", "amex"
];

function logMsg($msg) { global $logPath; file_put_contents($logPath, date("Y-m-d H:i:s"). " - BANK: ". $msg. "\n", FILE_APPEND); }
function curlGet($url) { $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); curl_setopt($ch, CURLOPT_TIMEOUT, 30); curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); $data = curl_exec($ch); $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return $httpcode == 200? $data : false; }
function sendTelegram($message) { global $botToken, $chatId; $url = "https://api.telegram.org/bot$botToken/sendMessage"; $data = ["chat_id" => $chatId, "text" => $message, "disable_web_page_preview" => true]; $options = ["http" => ["header" => "Content-type: application/x-www-form-urlencoded\r\n", "method" => "POST", "content" => http_build_query($data)]]; @file_get_contents($url, false, stream_context_create($options)); logMsg("SMS Sent: ". substr($message, 0, 60)); }
function matchKeyword($text) { global $keywords; $text = strtolower($text); foreach ($keywords as $k) { if (stripos($text, strtolower($k))!== false) return true; } return false; }

function fetchFromRSS() {
    $feeds = [
        "https://www.doctorofcredit.com/feed/",
        "https://www.bankrate.com/rss/",
        "https://www.nerdwallet.com/blog/feed",
        "https://www.reddit.com/r/churning/.rss",
        "https://www.reddit.com/r/BankBonuses/.rss"
    ];
    $offers = [];
    foreach ($feeds as $feed) {
        $xml_string = curlGet($feed);
        if (!$xml_string) { logMsg("RSS BLOCK: $feed"); continue; }
        $xml = @simplexml_load_string($xml_string);
        if (!$xml) continue;
        foreach ($xml->channel->item as $item) {
            $title = (string)$item->title;
            if (matchKeyword($title)) { $offers[] = $title. "\n". (string)$item->link; }
        }
        sleep(1);
    }
    logMsg("RSS OK: ". count($offers));
    return $offers;
}

$seen = file_exists($filePath)? file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
logMsg("=== BANK RUN START ===");
$allOffers = fetchFromRSS();
$newCount = 0;
foreach ($allOffers as $offer) {
    $offer = trim($offer);
    if (empty($offer)) continue;
    $offerKey = explode("\n", $offer)[0];
    if (!in_array($offerKey, $seen) && matchKeyword($offer)) {
        sendTelegram("🏦 BANK OFFER ALERT 🏦\n\n". $offer);
        $seen[] = $offerKey;
        $newCount++;
        sleep(2);
    }
}
if (count($seen) > 2000) $seen = array_slice($seen, -2000);
file_put_contents($filePath, implode("\n", $seen));
logMsg("DONE. New: $newCount");
echo "Bank Done. New: $newCount";
?>
