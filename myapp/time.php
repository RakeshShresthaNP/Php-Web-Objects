<?php
// Set to UTC to match your server environment
date_default_timezone_set('UTC');

$serverTime = time();
$formattedServer = date('Y-m-d H:i:s', $serverTime);

// Fetch "True Time" from a public API
$apiResponse = file_get_contents('http://worldtimeapi.org/api/timezone/Etc/UTC');
$apiData = json_decode($apiResponse, true);
$trueTime = $apiData['unixtime'];

$diff = $serverTime - $trueTime;

echo "<h2>Time Sync Debug</h2>";
echo "Server Time (UTC): " . $formattedServer . " (Timestamp: $serverTime)<br>";
echo "Real World UTC: " . date('Y-m-d H:i:s', $trueTime) . " (Timestamp: $trueTime)<br>";

if (abs($diff) > 5) {
    echo "<b style='color:red'>OFFSET DETECTED: Your server is " . abs($diff) . " seconds " . ($diff > 0 ? "ahead" : "behind") . "</b><br>";
    echo "This is why your OTP is failing. Please sync your OS Clock.";
} else {
    echo "<b style='color:green'>TIME IS SYNCED: Difference is only $diff seconds.</b><br>";
}