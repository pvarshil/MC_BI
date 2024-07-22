<?php

/* #########################
* This code was developed by:
* Audox Ingeniería SpA.
* website: www.audox.com
* email: info@audox.com
######################### */

include 'auth.php';

function getRecords($endpoint, $params) {
    $MailChimpDataCenter = $params['data_center'];
    $MailChimpApiKey = $params['api_key'];

    $auth = base64_encode('user:' . $MailChimpApiKey);
    $url = "https://$MailChimpDataCenter.api.mailchimp.com/3.0" . $endpoint;

    echo "Constructed URL: $url\n"; // Debugging URL

    $url_params = array();
    if (!empty($params['count'])) $url_params['count'] = $params['count'];
    if (!empty($params['offset'])) $url_params['offset'] = $params['offset'];
    if (!empty($url_params)) $url .= "?" . http_build_query($url_params);

    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . $auth
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);

    if ($result === false) {
        die(json_encode(array("error_code" => "500", "error_description" => "Curl error: " . curl_error($ch))));
    }
    curl_close($ch);

    echo "Raw response: $result\n"; // Debugging raw response

    $result = json_decode($result);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die(json_encode(array("error_code" => "500", "error_description" => "JSON decode error: " . json_last_error_msg())));
    }

    $endpoint_method = array_pop(explode("/", $endpoint));

    if (!in_array($endpoint_method, array("lists", "members", "segments", "campaigns", "reports"))) return $result;

    $total_items = isset($result->total_items) ? $result->total_items : 0;
    $result = isset($result->$endpoint_method) ? $result->$endpoint_method : [];

    $records = [];

    if (!empty($result)) {
        foreach ($result as $record) {
            $records[] = $record;
        }
    }

    if ($total_items > $params['offset'] + count($result)) {
        $params["offset"] = $params['offset'] + count($result);
        $records = array_merge($records, getRecords($endpoint, $params));
    }

    return $records;
}

$headers = getallheaders();
if (function_exists('Auth') && Auth(isset($headers['Authorization']) ? $headers['Authorization'] : '') == false) {
    die(json_encode(array("error_code" => "401", "error_description" => "Unauthorized")));
}

$params = array(
    "data_center" => isset($_REQUEST['data_center']) ? $_REQUEST['data_center'] : '',
    "api_key" => isset($_REQUEST['api_key']) ? $_REQUEST['api_key'] : '',
    "offset" => 0,
    "count" => isset($_REQUEST['count']) ? $_REQUEST['count'] : 10,
);

$result = null;
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "getRecords") {
    $result = getRecords(isset($_REQUEST['endpoint']) ? $_REQUEST['endpoint'] : '', $params);
}

echo json_encode($result);

?>
