<?php
// Set content type to JSON
header('Content-Type: application/json');

// Include the authentication file
include 'auth.php';

// Function to get records from MailChimp API
function getRecords($endpoint, $params) {
    $MailChimpDataCenter = $params['data_center'];
    $MailChimpApiKey = $params['api_key'];

    $auth = base64_encode('user:' . $MailChimpApiKey);
    $url = "https://$MailChimpDataCenter.api.mailchimp.com/3.0" . $endpoint;
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

    // Check for curl errors
    if ($result === false) {
        return array("error" => curl_error($ch));
    }

    curl_close($ch);

    // Decode JSON result
    $result = json_decode($result);

    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array("error" => "Invalid JSON received");
    }

    $endpoint_method = end(explode("/", $endpoint));

    if (!in_array($endpoint_method, array("lists", "members", "segments", "campaigns", "reports"))) {
        return $result;
    }

    $total_items = isset($result->total_items) ? $result->total_items : 0;
    $result = isset($result->$endpoint_method) ? $result->$endpoint_method : array();

    $records = array();

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

// Authentication check
$headers = getallheaders();
if (function_exists('Auth') && !Auth($headers['Authorization'])) {
    echo json_encode(array("error_code" => "401", "error_description" => "Unauthorized"));
    exit;
}

// Parameters from request
$params = array(
    "data_center" => isset($_REQUEST['data_center']) ? $_REQUEST['data_center'] : '',
    "api_key" => isset($_REQUEST['api_key']) ? $_REQUEST['api_key'] : '',
    "offset" => 0,
    "count" => isset($_REQUEST['count']) ? $_REQUEST['count'] : 100, // Default to 100 if not provided
);

// Validate action
if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "getRecords") {
    $result = getRecords(isset($_REQUEST['endpoint']) ? $_REQUEST['endpoint'] : '', $params);
    echo json_encode($result);
} else {
    echo json_encode(array("error" => "Invalid action specified"));
}
?>
