<?php
$client_id = '';
$key_file = 'myprivatekey.pem';
$base_url = '';
$use_proxy = false;
$oauth_token = '';

$elbcookie = '';
$content_type = '';
$content_encoding = '';
$content_length = 0;
$connect_timeout = 2;
$request_timeout = 10;

function set_client_id($passed_client_id) {
	global $client_id;
	$client_id = $passed_client_id;
}

function set_key_file($passed_key_file) {
	global $key_file;
	$key_file = $passed_key_file;
}

function set_base_url($passed_baseurl) {
	global $base_url;
	$base_url = $passed_baseurl;
}

function set_use_proxy($passed_use_proxy) {
	global $use_proxy;
	$use_proxy = $passed_use_proxy;
}

function set_oauth_token($passed_oauth_token) {
	global $oauth_token;
	$oauth_token = $passed_oauth_token;
}

function set_connect_timeout($passed_connect_timeout) {
	global $connect_timeout;
	$connect_timeout = $passed_connect_timeout;
}

function set_request_timeout($passed_request_timeout) {
	global $request_timeout;
	$request_timeout = $passed_request_timeout;
}

function curl_header_callback($resURL, $strHeader) {
	global $content_type, $content_encoding, $content_length;
	if (preg_match('/^set-cookie/i', $strHeader) && preg_match('/AWSELB=/', $strHeader)) {
		$regex_pattern = "/AWSELB=([^;]+);/";
		preg_match_all($regex_pattern, $strHeader, $cookievalues);
		error_log('Received header:' . $cookievalues[1][0]);
		// $jsessionid = $cookievalues[1][0];
	}
	if (preg_match('/^content-type/i', $strHeader)) {
		echo $strHeader . "\n";
		list($key, $val) = explode(":", $strHeader);
		$content_type = $val;
	}
	if (preg_match('/^content-encoding/i', $strHeader)) {
		echo $strHeader . "\n";
		list($key, $val) = explode(":", $strHeader);
		$content_encoding = $val;
	}
	if (preg_match('/^content-length/i', $strHeader)) {
		echo $strHeader . "\n";
		list($key, $val) = explode(":", $strHeader);
		$content_length = intval($val);
	}
	return strlen($strHeader);
}

function get_param_value($paramValue) {
	if ($paramValue == '') {
		return $paramValue;
	}
	if (strpos($paramValue, 'file:') === 0) {
		$filename = substr($paramValue, 5);
		if ($paramValue == '') {
			return $paramValue;
		}
		$content = file_get_contents($filename);
		// echo "CONTENT:" . $content . "\n";
		return $content;
	} else {
		return $paramValue;
	}
}

function appendUrlParameters($url, $request_params) {
	if ($request_params == '') {
		return $url;
	}
	if (strpos($url, '?') !== false) {
		$url .= '&' . $request_params;
	} else {
		$url .= '?' . $request_params;
	}
	return $url;
}

function slice_request($method, $path, $username, $request_params, $requestId) {
	global $base_url, $client_id, $content_type, $content_encoding, $content_length, $oauth_token;

	//error_log("----- start slice request -----");

	$url = $base_url . $path;

	// We need this so that it's easier to track the requests in server access log
	$reqIdStr = "reqId=" . $requestId;
	$url = appendUrlParameters($url, $reqIdStr);
	if ($method != 'POST' && $method != 'PUT') {
		$url = appendUrlParameters($url, $request_params);
	}

	$ch = curl_init();

	$hdrs = array();
	$auth_hdr = '';
	if ($oauth_token != '') {
		echo "OAuth token authorization\n";
		$auth_hdr = 'Authorization: Bearer ' . $oauth_token;
		$hdrs[] = $auth_hdr;
	} else {
		$auth_hdr = get_signature_header($method, $path, $username);
		if (!isset($auth_hdr)) {
			error_log("Can't compute signature");
			return NULL;
		}
		$auth_hdr = 'X-Slice-API-Signature: ' . $auth_hdr;
		$hdrs[] = $auth_hdr;
	}

	if (count($hdrs) > 0) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
	}

	set_common_curlopts($ch, $method, $url, $auth_hdr);

	if ($method == 'POST' || $method == 'PUT') {
		echo "HTTP PARAMS IN BODY : " . $request_params . "\n";
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
	} else {
		echo "HTTP PARAMS : " . $request_params . "\n";
	}

	$response = curl_exec($ch);
	handle_response($method, $url, $ch, $response);

	# The HTTP response body contains a JSON object, decode it to convert it to PHP object
	$response_object = json_decode($response);
	$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	//error_log("----- end slice request -----");

	$out = array();
	$out['url'] = $url;
	$out['method'] = $method;
	$out['auth_hdr'] = $auth_hdr;
	$out['response'] = $response_object;
	$out['response_code'] = $response_code;
	$out['response_text'] = $response;
	$out['content_type'] = $content_type;
	$out['content_encoding'] = $content_encoding;

	if ($content_length == 0) {
		$content_length = strlen($response);
	}
	$out['content_length'] = $content_length;

	return $out;
}

function set_common_curlopts($ch, $method, $url, $auth_hdr) {
	global $use_proxy, $userAgent, $elbcookie, $connect_timeout, $request_timeout;

	$hostname = php_uname('n');
	date_default_timezone_set('America/Los_Angeles');
	$user_agent = $hostname . '-' . date('Y-m-d-H-i-s');
	if (isset($userAgent)) {
		$user_agent = $user_agent . " " . $userAgent;
	}

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if ($use_proxy) {
		echo "===== Using Proxy =====\n";
		curl_setopt($ch, CURLOPT_PROXY, "http://localhost:8888");
		curl_setopt($ch, CURLOPT_PROXYPORT, 8888);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($ch, CURLOPT_ENCODING, "");

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'curl_header_callback');
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout); //timeout in seconds
	curl_setopt($ch, CURLOPT_TIMEOUT, $request_timeout); //timeout in seconds

	if (isset($elbcookie) && $elbcookie != '') {
		error_log('SETTING ELB COOKIE');
		$cookie_str = 'AWSELB=' . $elbcookie;
		curl_setopt($ch, CURLOPT_COOKIE, $cookie_str);
	}
}

function handle_response($method, $url, $ch, $response) {
	// error_log("got response for : " . $method . "," . $url, 0);
	$info = curl_getinfo($ch);
	// error_log("Response code was : ".$info['http_code'], 0);
	// error_log("response was : " . $response, 0);
}

function get_private_key() {
	global $key_file;
	$private_key = NULL;
	if (file_exists($key_file)) {
		error_log("Private Key File :" . $key_file);
		$private_key = file_get_contents($key_file);
	}

	return $private_key;
}

function get_signature_header($method, $path, $username) {
	global $client_id;
	$timestamp = get_current_timestamp();
	$request_signature = compute_signature($method, $path, $client_id, $timestamp, $username);
	if (!isset($request_signature)) {
		return NULL;
	}

	$auth_hdr =	"client_id=" . $client_id .
			"&timestamp=" . $timestamp .
			"&request_signature=" . urlencode($request_signature);
	if (isset($username) && $username != '') {
		$auth_hdr = $auth_hdr . "&username=" . $username;
	}
	return $auth_hdr;
}

function compute_signature($method, $path, $client_id, $timestamp, $username) {
	$private_key = get_private_key();
	if (!isset($private_key)) {
		return NULL;
	}

	$data = $method . $path . $client_id . $timestamp;
	if (isset($username)) {
		$data = $data . $username;
	}
	$binary_signature = "";
	openssl_sign($data, $binary_signature, $private_key, OPENSSL_ALGO_DSS1);
	return  base64_encode($binary_signature);
}

function get_current_timestamp() {
	return round(microtime(true) * 1000) . "";
}

function get_timestamp($hours_back) {
	return (round(microtime(true) * 1000) - ($hours_back*3600000)) . "";
}
?>
