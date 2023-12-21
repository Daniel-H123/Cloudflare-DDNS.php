<?php

// Request url: https://website.com/update.php?email=example@email.com&token=VerySecureToken&domain=example.domain.com&ip=127.0.0.1

// Get all GET information
$cloudflareRecord = $_GET["domain"];
$ip = $_GET['ip'];
$token = $_GET['token'];
$email = $_GET['email'];
$proxied = $_GET['proxied'] ?? true; // Optional default true

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

define('TOKEN', 'Your random password');
define('CLOUDFLARE_EMAIL', 'Your cloudflare email');
define('CLOUDFLARE_API_KEY', 'Your cloudflare api key');

// Validate token and email (higly recommended)
if (TOKEN != $token || $email != CLOUDFLARE_EMAIL) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Invalid authentication information.']));
}

// Get zone id
$url = 'https://api.cloudflare.com/client/v4/zones?name=' . urlencode($domain);
$headers = [
    'X-Auth-Email: ' . CLOUDFLARE_EMAIL,
    'X-Auth-Key: ' . CLOUDFLARE_API_KEY,
    'Content-Type: application/json'
];

$result = request($url, 'GET', null, $headers);
$cloudflareZoneId = $result['result'][0]['id'] ?? null;

if (empty($cloudflareZoneId)) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Zone not found.']));
}


// Get record id
$url = 'https://api.cloudflare.com/client/v4/zones/' . $cloudflareZoneId . '/dns_records?type=A&name=' . urlencode($cloudflareRecord);
$result = request($url, 'GET', null, $headers);
$cloudflareRecordId = $result['result'][0]['id'] ?? null;

if (empty($cloudflareRecordId)) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Record not found.']));
}


// Check if IP is the same
$oldIp = $result['result'][0]['content'];
if ($ip == $oldIp) {
    http_response_code(200);
    die(json_encode(['success' => true, 'message' => 'IP is the same.']));
}

// Update record
$url = 'https://api.cloudflare.com/client/v4/zones/' . $cloudflareZoneId . '/dns_records/' . $cloudflareRecordId;
$data = [
    'type' => 'A',
    'name' => $cloudflareRecord,
    'content' => $ip,
    'proxied' => $proxied
];
$result = request($url, 'PUT', $data, $headers);

if ($result['success']) {
    http_response_code(200);
    die(json_encode(['success' => true, 'message' => 'Record updated.']));
}

function request($url, $type = 'GET', $data, $headers)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($type == 'POST' || $type == 'PUT') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
