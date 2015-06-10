<?php
$client_id = '';
$baseurl = '';

$elbcookie = '';
$connect_timeout = 2;
$request_timeout = 10;

$useProxy = false;

function set_client_id($passed_client_id) {
	global $client_id;
	$client_id = $passed_client_id;
}

function set_base_url($passed_baseurl) {
	global $baseurl;
	$baseurl = $passed_baseurl;
}

function curl_header_callback($resURL, $strHeader) { 
	if (preg_match('/^set-cookie/i', $strHeader) && preg_match('/AWSELB=/', $strHeader)) { 
		$regex_pattern = "/AWSELB=([^;]+);/";
		preg_match_all($regex_pattern, $strHeader, $cookievalues);
		error_log('Received header:' . $cookievalues[1][0]); 
		// $jsessionid = $cookievalues[1][0];
	} 
	return strlen($strHeader); 
} 

function slice_request($method, $path, $username, $request_params) {
	global $baseurl;

	//error_log("----- start slice request -----");

	$url = $baseurl . $path;
	if (strpos($url, '?') !== false) {
		$url .= '&' . $request_params;
	} else {
		$url .= '?' . $request_params;
	}

	$ch = curl_init();
	$auth_hdr = get_signature_header($method, $path, $username);
	if (!isset($auth_hdr)) {
		error_log("Can't compute signature");
		return NULL;
	}
	set_common_curlopts($ch, $method, $url, $auth_hdr);

	$response = curl_exec($ch);
	handle_response($method, $url, $ch, $response);

	# The HTTP response body contains a JSON object, decode it to convert it to PHP object
	$response_object = json_decode($response);
	curl_close($ch);

	//error_log("----- end slice request -----");
	
	$out = array();
	$out['url'] = $url;
	$out['method'] = $method;
	$out['signature'] = $auth_hdr;
	$out['response'] = $response_object;
	return $out;
}

function slice_post_request($path, $username, $request_params) {
	global $baseurl;

	error_log("----- start slice request -----");

	$url = $baseurl . $path;

	$ch = curl_init();
	$method = 'POST';
        $auth_hdr = get_signature_header($method, $path, $username);
	set_common_curlopts($ch, $method, $url, $auth_hdr);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);

	$response = curl_exec($ch);
	handle_response($method, $url, $ch, $response);

	# The HTTP response body contains a JSON object, decode it to convert it to PHP object
	$response_object = json_decode($response);
	curl_close($ch);

	error_log("----- end slice request -----");

	return $response_object;
}

function slice_put_request($path, $username, $request_params) {
	global $baseurl;

	error_log("----- start slice request -----");

	$url = $baseurl . $path;

	$ch = curl_init();
	$method = 'PUT';
        $auth_hdr = get_signature_header($method, $path, $username);
	if (!isset($auth_hdr)) {
		error_log("Can't compute signature");
		return NULL;
	}
	set_common_curlopts($ch, $method, $url, $auth_hdr);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);

	$response = curl_exec($ch);
	handle_response($method, $url, $ch, $response);

	# The HTTP response body contains a JSON object, decode it to convert it to PHP object
	$response_object = json_decode($response);
	curl_close($ch);

	error_log("----- end slice request -----");

	return $response_object;
}

function slice_get_request($path, $username, $request_params) {
	global $baseurl;

	error_log("----- start slice request -----");

	$url = $baseurl . $path;
	if (strpos($url, '?') !== false) {
		$url .= '&' . $request_params;
	} else {
		$url .= '?' . $request_params;
	}

	$ch = curl_init();
	$method = 'GET';
        $auth_hdr = get_signature_header($method, $path, $username);
	if (!isset($auth_hdr)) {
		error_log("Can't compute signature");
		return NULL;
	}
	set_common_curlopts($ch, $method, $url, $auth_hdr);

	$response = curl_exec($ch);
	handle_response($method, $url, $ch, $response);

	# The HTTP response body contains a JSON object, decode it to convert it to PHP object
	$response_object = json_decode($response);
	curl_close($ch);

	error_log("----- end slice request -----");

	return $response_object;
}

function slice_delete_request($path, $username, $request_params) {
	global $baseurl;

	error_log("----- start slice request -----");

	$url = $baseurl . $path;
	if (strpos($url, '?') !== false) {
		$url .= '&' . $request_params;
	} else {
		$url .= '?' . $request_params;
	}

	$ch = curl_init();
	$method = 'DELETE';
        $auth_hdr = get_signature_header($method, $path, $username);
	if (!isset($auth_hdr)) {
		error_log("Can't compute signature");
		return NULL;
	}
	set_common_curlopts($ch, $method, $url, $auth_hdr);

	$response = curl_exec($ch);
	handle_response($method, $url, $ch, $response);

	# The HTTP response body contains a JSON object, decode it to convert it to PHP object
	$response_object = json_decode($response);
	curl_close($ch);

	error_log("----- end slice request -----");

	return $response_object;
}

function set_common_curlopts($ch, $method, $url, $auth_hdr) {
	global $useProxy, $elbcookie, $connect_timeout, $request_timeout;

	$hostname = php_uname('n');
	date_default_timezone_set('America/Los_Angeles');
	$user_agent = $hostname . '-' . date('Y-m-d-H-i-s');

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if ($useProxy) {
		curl_setopt($ch, CURLOPT_PROXY, "http://localhost:8888"); 
		curl_setopt($ch, CURLOPT_PROXYPORT, 8888); 
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Slice-API-Signature: ' . $auth_hdr));

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
	error_log("got response for : " . $method . "," . $url, 0);
	$info = curl_getinfo($ch);
	error_log("Response code was : ".$info['http_code'], 0);
	// error_log("response was : " . $response, 0);
}

function get_private_key() {
	$private_key = NULL;
	$filename = "myprivatekey.pem";
	if (file_exists($filename)) {
		error_log("Private Key File :" . $filename);
		$private_key = file_get_contents($filename);
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
	if (isset($username)) {
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
