<?php

// Request url: https://website.com/update.php?email=example@email.com&token=VerySecureToken&domain=example.domain.com&ip=127.0.0.1

// Get all GET information
$targetDomain = $_GET["domain"];
$ip = $_GET['ip'];
$token = $_GET['token'];
$email = $_GET['email'];

// Extract domain if record domain is subdomain (example.domain.com => domail.com)
// NOTE: This does not work on example.co.uk domains!
$pattern = "/[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/";
$matches = array();
preg_match($pattern, $targetDomain, $matches);
$domain = $matches[0];

define('TOKEN', 'Your random password');
define('CLOUDFLARE_EMAIL', 'Your cloudflare email');
define('CLOUDFLARE_API_KEY', 'Your cloudflare api key');
define('DOMAIN', $domain);
define('CLOUDFLARE_RECORD', $targetDomain);
define('CLOUDFLARE_RECORD_PROXIED', true);

// Validate token and email (higly recommended)
if (TOKEN != $token || $email != CLOUDFLARE_EMAIL) {
    die("Invalid authentication information!");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.cloudflare.com/client/v4/zones?name=' . urlencode(DOMAIN));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
$headers = array();
$headers[] = 'X-Auth-Email: ' . CLOUDFLARE_EMAIL;
$headers[] = 'X-Auth-Key: ' . CLOUDFLARE_API_KEY;
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = json_decode(curl_exec($ch), true);
curl_close($ch);
if (isset($result['result'][0]['id'])) {
    define('CLOUDFLARE_ID', $result['result'][0]['id']);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,
        'https://api.cloudflare.com/client/v4/zones/' . CLOUDFLARE_ID . '/dns_records?type=A&name=' . urlencode(CLOUDFLARE_RECORD));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    $headers = array();
    $headers[] = 'X-Auth-Email: ' . CLOUDFLARE_EMAIL;
    $headers[] = 'X-Auth-Key: ' . CLOUDFLARE_API_KEY;
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if (isset($result['result'][0]['id'])) {
        define('CLOUDFLARE_RECORD_ID', $result['result'][0]['id']);
        $oldIp = $result['result'][0]['content'];
            if (filter_var($ip, FILTER_VALIDATE_IP) and $ip !== $oldIp) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,
                    'https://api.cloudflare.com/client/v4/zones/' . CLOUDFLARE_ID . '/dns_records/' . CLOUDFLARE_RECORD_ID);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
                    'type' => 'A',
                    'name' => CLOUDFLARE_RECORD,
                    'content' => $ip,
                    'proxied' => CLOUDFLARE_RECORD_PROXIED
                )));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $headers = array();
                $headers[] = 'X-Auth-Email: ' . CLOUDFLARE_EMAIL;
                $headers[] = 'X-Auth-Key: ' . CLOUDFLARE_API_KEY;
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = json_decode(curl_exec($ch), true);
                curl_close($ch);
                if ($result['success']) {
                    echo "\nRecord updated.\nOld IP: $oldIp\nNew IP: $ip\n";
                    $oldIp = $ip;
                }
            }
    } else {
        die('Record not found.' . PHP_EOL);
    }
} else {
    die('Error.' . PHP_EOL);
}
