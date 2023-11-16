<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Repocket Stats</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" type="text/css" href="https://bench.benji.link/assets/bulma-prefers-dark.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  </head>
  <?php

  // get variables from config.php
  include 'config.php';


  ?>
  <body>
  <section class="section">
    <div class="container">
      <h1 class="title">
        Repocket Stats - <?php echo $name; ?>
      </h1>
      <p class="subtitle">
        A simple dashboard for your Repocket stats.
      </p>


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

// function to say hello world
function get_idtoken() {

  global $email;
  global $password;

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
      "x-client-version: Chrome/JsCore/10.0.0/FirebaseCore-web",
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

  return $idToken;
  
}


// check how many rows are in the table
$sql = "SELECT COUNT(*) FROM $table";

$result = $conn->query($sql);

// Check if query was successful and if there are results
if ($result) {
    // Initialize a variable to check if any rows were fetched
    $rowsFetched = false;

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { // Fetch rows as associative array
        $count = $row["COUNT(*)"];

        // echo "Total Balance: " . $balance / 100 . "\n";

        $rowsFetched = true; // Mark that rows were fetched
    }

    if (!$rowsFetched) {
        echo "0 results";
    }
} else {
    echo "Error in SQL query";
}

// if there are less than 1440 rows, we know it hasnt been running for 24 hours
if ($count < 1440) {
  echo "<b>The script has not been running for 24 hours yet.</b><br><br>";
  $balance = "Has not been running for 24 Hours Yet";
  $cents_earned = "Has not been running for 24 Hours Yet";
  $json_balance = "Has not been running for 24 Hours Yet";

} else {
  
  // get the last two rows
  $sql = "SELECT total_balance, balance_json, timestamp FROM $table ORDER BY `id` DESC LIMIT 1 OFFSET 1439;";

  $result = $conn->query($sql);

  // Check if query was successful and if there are results
  if ($result) {
      // Initialize a variable to check if any rows were fetched
      $rowsFetched = false;

      while ($row = $result->fetchArray(SQLITE3_ASSOC)) { // Fetch rows as associative array
          $balance = $row["total_balance"];
          $json = json_decode($row["balance_json"], true);
          $cents_earned = $json["centsEarned"];
          $json_balance = $json["centsCredited"];

          // echo "Total Balance: " . $balance / 100 . "\n";

          $rowsFetched = true; // Mark that rows were fetched
      }

      if (!$rowsFetched) {
          echo "0 results";
      }
  } else {
      echo "Error in SQL query";
  }

}

$hours_24_balance = $balance;


// get the last 120 rows
$sql = "SELECT * FROM $table ORDER BY `id` DESC LIMIT 120";

$result = $conn->query($sql);

$balance = array();
$cents_earned = array();
$device_count = array();

// Check if the query was successful
if ($result) {
    $i = 0;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { // Fetch rows as associative array
        $device_count[$i] = $row["devices_count"];
        $balance[$i] = $row["total_balance"];
        $json = json_decode($row["balance_json"], true);
        $cents_earned[$i] = $json["centsEarned"];
        $i++;
    }

    // Check if any rows were fetched
    if ($i == 0) {
        echo "0 results";
    }
} else {
    echo "Error in SQL query";
}

$row_count = $i;


$minutely = array();

for ($i = 1; $i < count($cents_earned); $i++) {
  $minutely[$i] = $cents_earned[$i -1] - $cents_earned[$i];
}


// set the timezone to Vienna
date_default_timezone_set('Europe/Vienna');
$current_time = time();
// make the labels in time, each label is 1 minute ago
$labels_new  = array();

for ($i = 0; $i < 120; $i++) {
  $labels_new[$i] = date("H:i", $current_time - ($i * 60));
}


// flip the arraya
$minutely = array_reverse($minutely);
$labels_new = array_reverse($labels_new);
$cents_earned = array_reverse($cents_earned);
$device_count_array = array_reverse($device_count);



$data_json = json_encode($cents_earned);

$change = $balance[0] - $balance[1];
$change = $change / 100;

$theoretical = $change * 1440;


if ($row_count < 10) {
  $theoretical_10 = "Not enough data yet, hasnt been running for 10 minutes yet";
} else {
  $change_10 = $balance[0] - $balance[10];
  $change_10 = $change_10 / 100;
  $theoretical_10 = $change_10 * 144;
  $theoretical_10 = $theoretical_10 . "$";
}

if ($row_count < 60) {
  $theoretical_60 = "Not enough data yet, hasnt been running for 60 minutes yet";
} else {
  $change_60 = $balance[0] - $balance[60];
  $change_60 = $change_60 / 100;
  $theoretical_60 = $change_60 * 24;
  $theoretical_60 = $theoretical_60 . "$";
}


echo "The change in the last minute is: $change$";
echo "<br>";
echo "The theoretical 24h change (1m average) will be: $theoretical$";
echo "<br>";
echo "The theoretical 24h change (10m average) will be: $theoretical_10";
echo "<br>";
echo "The theoretical 24h change (1h average) will be: $theoretical_60";
echo "<br>";

$idToken = get_idtoken();

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


$devices = json_decode($response, true);


// count the number of devices
$device_count = count($devices);


$current_balance = $balance_json["centsCredited"];
$pending_balance = $balance_json["centsEarned"];

$total = $current_balance + $pending_balance;

$total = $total / 100;

echo "Current  Total Balance: " . $total . "$\n";
echo "<br>";
echo "Current Pending Balance: " . $pending_balance / 100 . "$\n";
echo "<br>";

if($hours_24_balance == "Has not been running for 24 Hours Yet") {
  echo "24 Hour Total Balance change: " . $hours_24_balance . "\n";
} else {
  echo "24 Hour Total Balance change: " . $total - $hours_24_balance / 100 . "$\n";
}
echo "<br>";
echo "Current active devices: " . $device_count . "\n";


?>

<canvas id="perminute"></canvas>


<!-- make two coloulmns -->
<div class="columns">
  <div class="column">
    <canvas id="device_count"></canvas>
  </div>
  <div class="column">
    <canvas id="balance"></canvas>

  </div>
</div>

<hr>

<canvas id="device_count"></canvas>


<script>

  var data_point = <?php echo json_encode($minutely); ?>;
  var labels = <?php echo json_encode($labels_new); ?>;

  
  var ctx = document.getElementById('perminute').getContext('2d');

  const data = {
    labels: labels,
    datasets: [{
      label: 'Earnings per minute',
      data: data_point,
      fill: false,
      borderColor: 'rgb(75, 192, 192)',
      tension: 0.1
    }]
  };

  var myChart = new Chart(ctx, {
    type: 'line',
    data: data,
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  var data_point2 = <?php echo json_encode($cents_earned); ?>;
  var ctx2 = document.getElementById('balance').getContext('2d');

  const data2 = {
    labels: labels,
    datasets: [{
      label: 'Pending balance',
      data: data_point2,
      fill: false,
      borderColor: 'rgb(52, 235, 137)',
      tension: 0.1
    }]
  };

  var myChart2 = new Chart(ctx2, {
    type: 'line',
    data: data2,
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  var data_point3 = <?php echo json_encode($device_count_array); ?>;

  var ctx3 = document.getElementById('device_count').getContext('2d');

  const data3 = {
    labels: labels,
    datasets: [{
      label: 'Device Count',
      data: data_point3,
      fill: false,
      borderColor: 'rgb(235, 64, 52)',
      tension: 0.1
    }]
  };

  var myChart3 = new Chart(ctx3, {
    type: 'line',
    data: data3,
    options: {
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });


</script>
