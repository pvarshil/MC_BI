<?php
include 'auth.php';

function getRecords($endpoint, $params) {
    $MailChimpDataCenter = $params['data_center'];
    $MailChimpApiKey = $params['api_key'];

    $auth = base64_encode("user:$MailChimpApiKey");
    $url = "https://$MailChimpDataCenter.api.mailchimp.com/3.0$endpoint";

    $url_params = http_build_query($params);
    if (!empty($url_params)) {
        $url .= "?$url_params";
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . $auth,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $result = curl_exec($ch);

    if ($result === false) {
        die(json_encode(["error_code" => "500", "error_description" => "Curl error: " . curl_error($ch)]));
    }
    curl_close($ch);

    $result = json_decode($result);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die(json_encode(["error_code" => "500", "error_description" => "JSON decode error: " . json_last_error_msg()]));
    }

    $endpoint_method = basename($endpoint);

    return isset($result->$endpoint_method) ? $result->$endpoint_method : [];
}

$headers = getallheaders();
if (function_exists('Auth') && Auth(isset($headers['Authorization']) ? $headers['Authorization'] : '') == false) {
    die(json_encode(["error_code" => "401", "error_description" => "Unauthorized"]));
}

$params = [
    "data_center" => $_REQUEST['data_center'] ?? '',
    "api_key" => $_REQUEST['api_key'] ?? '',
    "offset" => 0,
    "count" => $_REQUEST['count'] ?? 10,
];

$result = null;
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "getRecords") {
    $result = getRecords($_REQUEST['endpoint'] ?? '', $params);
}

echo json_encode($result);
?>
