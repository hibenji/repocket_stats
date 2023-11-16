<?php

// show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// SQLite connection
$dbfile = "config/repocket.db";

$conn = new SQLite3($dbfile);

if (!$conn) {
    die("Connection failed: " . $conn->lastErrorMsg());
}

// Create a table if it doesn't exist
$query = "CREATE TABLE IF NOT EXISTS repocket (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp INTEGER,
    total_balance REAL,
    balance_json TEXT,
    devices_count TEXT
)";

$result = $conn->exec($query);

if (!$result) {
    die("Table creation failed: " . $conn->lastErrorMsg());
}

include 'config.php';

$curl = curl_init();


curl_setopt_array($curl, [
  CURLOPT_URL => "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=AIzaSyBJf6hyw47O-5TrAwQszkwvDEh-Ri6q6SU",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => "{\"returnSecureToken\":true,\"email\":\"$email\",\"password\":\"$password\",\"clientType\":\"CLIENT_TYPE_WEB\"}",
  CURLOPT_HTTPHEADER => [
    "accept: */*",
    "accept-language: en-US,en;q=0.9,ru;q=0.8,de;q=0.7",
    "authority: identitytoolkit.googleapis.com",
    "content-type: application/json",
    "origin: https://app.repocket.co",
    "sec-ch-ua: ^\^Not/A",
    "sec-ch-ua-mobile: ?0",
    "sec-ch-ua-platform: ^\^Windows^^",
    "sec-fetch-dest: empty",
    "sec-fetch-mode: cors",
    "sec-fetch-site: cross-site",
    "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
    "x-client-data: CKe1yQEIhrbJAQiitskBCKmdygEI0/jKAQiVocsBCIWgzQEIrr3NAQjDyM0BCLnKzQEY9abNAQ==",
    "x-client-version: Chrome/JsCore/10.0.0/FirebaseCore-web",
    "x-firebase-gmpid: 1:71243431928:web:13341974dd1de7b61ba00d"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
  exit;
} else {
  // echo $response;
}

$response = json_decode($response, true);

// get the idToken
$idToken = $response["idToken"];

$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.repocket.co/api/reports/current?withReferralBonusesFix=true",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_HTTPHEADER => [
    "accept: application/json, text/plain, */*",
    "accept-language: en-US,en;q=0.9,ru;q=0.8,de;q=0.7",
    "auth-token: $idToken",
    "authority: api.repocket.co",
    "device-os: web",
    "origin: https://app.repocket.co",
    "referer: https://app.repocket.co/",
    "sec-ch-ua: ^\^Not/A",
    "sec-ch-ua-mobile: ?0",
    "sec-ch-ua-platform: ^\^Windows^^",
    "sec-fetch-dest: empty",
    "sec-fetch-mode: cors",
    "sec-fetch-site: same-site",
    "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
    "x-app-version: web"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  // echo $response;
}

$balance_response = $response;


$balance_json = json_decode($response, true);


$current_balance = $balance_json["centsCredited"];
$pending_balance = $balance_json["centsEarned"];

$total = $current_balance + $pending_balance;



$curl = curl_init();

curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.repocket.co/api/users/devices",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_POSTFIELDS => "",
  CURLOPT_HTTPHEADER => [
    "accept: application/json, text/plain, */*",
    "accept-language: en-US,en;q=0.9,ru;q=0.8,de;q=0.7",
    "auth-token: $idToken",
    "authority: api.repocket.co",
    "device-os: web",
    "origin: https://app.repocket.co",
    "referer: https://app.repocket.co/",
    "sec-ch-ua: ^\^Not/A",
    "sec-ch-ua-mobile: ?0",
    "sec-ch-ua-platform: ^\^Windows^^",
    "sec-fetch-dest: empty",
    "sec-fetch-mode: cors",
    "sec-fetch-site: same-site",
    "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
    "x-app-version: web"
  ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  // echo $response;
}

$devices_response = $response;

// count the number of devices
$devices_json = json_decode($response, true);

$devices_count = count($devices_json);



// Insert data into the SQLite database
$timestamp = time();

$query = "INSERT INTO repocket (timestamp, total_balance, balance_json, devices_count) 
          VALUES ($timestamp, $total, '$balance_response', '$devices_count')";

$result = $conn->exec($query);

if ($result) {
    echo "New record created successfully";
} else {
    echo "Error: " . $conn->lastErrorMsg();
}

// Close the SQLite connection
$conn->close();

?>
