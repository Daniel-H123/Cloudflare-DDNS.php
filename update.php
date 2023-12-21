<?php

// Request url: https://website.com/update.php?email=example@email.com&token=VerySecureToken&domain=example.domain.com&ip=127.0.0.1

// Get all GET information
$record = $_GET["domain"];
$ip = $_GET['ip'];
$token = $_GET['token'];
$email = $_GET['email'];
$proxied = $_GET['proxied'] ?? true; // Optional default true

// Check if ip is valid
if (filter_var($ip, FILTER_VALIDATE_IP)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid IP address.']));
}

// Extract domain if record domain is subdomain (example.domain.com => domail.com)
// NOTE: This does not work on example.co.uk domains!
$pattern = "/[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/";
$matches = array();
preg_match($pattern, $targetDomain, $matches);
$domain = $matches[0];

// Define custom token
define('TOKEN', 'Your custom password');
define('CLOUDFLARE_EMAIL', 'Your cloudflare email');
define('CLOUDFLARE_API_KEY', 'Your cloudflare api key');

// Validate custom set token and email with token in request (higly recommended)
if (TOKEN != $token || $email != CLOUDFLARE_EMAIL) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid authentication information.']));
}

// Get zone id and record id
$zoneId = getZoneId($domain);
$recordId = getRecordId($zoneId, $record);

// Check if IP is the same
$oldIp = $result['result'][0]['content'];
if ($ip == $oldIp) {
    http_response_code(200);
    die(json_encode(['success' => true, 'message' => 'IP is the same.']));
}

// Update record
updateRecord($zoneId, $recordId, $record, $ip, $proxied);

/**
 * Get zone id
 * 
 * @param string $domain 
 * @return void 
 */
function getZoneId($domain)
{
    // Get zone id
    $url = 'https://api.cloudflare.com/client/v4/zones?name=' . urlencode($domain);
    $result = request($url);
    $cloudflareZoneId = $result['result'][0]['id'] ?? null;

    if (empty($cloudflareZoneId)) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Zone not found.']));
    }
}

/**
 * Get record id
 * 
 * @param string $zoneId 
 * @param string $record 
 * @return void 
 */
function getRecordId($zoneId, $record)
{
    // Get record id
    $url = 'https://api.cloudflare.com/client/v4/zones/' . $zoneId . '/dns_records?type=A&name=' . urlencode($record);
    $result = request($url);
    $recordId = $result['result'][0]['id'] ?? null;

    if (empty($recordId)) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Record not found.']));
    }

    return $recordId;
}

/**
 * Update record
 * 
 * @param string $zoneId The zone id
 * @param string $recordId The record id
 * @param string $record The record name
 * @param string $ip The new IP address
 * @param bool $proxied Whether the record is being proxied
 * @return void 
 */
function updateRecord($zoneId, $recordId, $record, $ip, $proxied = true)
{
    // Update record
    $url = 'https://api.cloudflare.com/client/v4/zones/' . $zoneId . '/dns_records/' . $recordId;
    $data = [
        'type' => 'A',
        'name' => $record,
        'content' => $ip,
        'proxied' => $proxied
    ];
    $result = request($url, 'PUT', $data);

    if ($result['success']) {
        http_response_code(200);
        die(json_encode(['success' => true, 'message' => 'Record updated.']));
    }
}

/**
 * Send request to Cloudflare API
 * 
 * @param mixed $url The request url
 * @param string $type The request type
 * @param mixed $data The request body
 * @return array 
 */
function request($url, $type = 'GET', $data = null)
{
    $ch = curl_init();
    $headers = [
        'X-Auth-Email: ' . CLOUDFLARE_EMAIL,
        'X-Auth-Key: ' . CLOUDFLARE_API_KEY,
        'Content-Type: application/json'
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($type == 'POST' || $type == 'PUT') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    curl_close($ch);

    if (empty($response)) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Cloudflare API error.']));
    }

    return json_decode($response, true);
}