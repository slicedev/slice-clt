<?php
include("api_functions.php");

// USAGE: php slice-clt.php METHOD PATH USERNAME PARAM1 VALUE1 PARAM2 VALUE2 ...

// Initial settings (may need to be modified for specific use cases):

set_client_id('3934893');
set_base_url('https://api.slice.com');

$locale = "en_US";

// DO NOT MODIFY ANYTHING BELOW THIS LINE
// -----------------------------------------------------

function abort($errorMsg)
{
  echo "$errorMsg\n";
  die();
}

// 1. parse input params

if(count($argv) < 4)
  abort("Required Usage: php slice-clt.php METHOD PATH USERNAME [PARAM1 VALUE1 [PARAM2 VALUE2 ... ]]");

$n = 1;
$method = $argv[$n++];
$path = $argv[$n++];  // must be one of GET, POST, PUT, DELETE
$username = $argv[$n++];

$params = array();
while($n+1 < count($argv))
{
  $params[$argv[$n]] = $argv[$n+1];
  $n+=2;
}

//echo "$method\t$path\t$username\n" . var_dump($params);

// 2. prepare request

$params["locale"] = $locale;
$params["client"] = "p";

$http_params = http_build_query($params);

// 3. make request

$response = slice_request($method, $path, $username, $http_params);

// 4. echo output

echo "\n\nRequest: " . $response['method'] . " " . $response['url'] . "\n";
echo "Signature String: " . $response['signature'] . "\n";
echo "Response: \n" . json_encode($response['response']) . "\n\n";

?>
