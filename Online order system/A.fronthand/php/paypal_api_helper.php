<?php
require_once 'config.php';

function paypalApiRequest(string $method, string $endpoint, $body = null, ?string $accessToken = null): array
{
    $url = rtrim(PAYPAL_API_BASE, '/') . $endpoint;

    $ch = curl_init($url);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    if ($accessToken) {
        $headers[] = 'Authorization: Bearer ' . $accessToken;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        throw new Exception('PayPal cURL error: ' . $curlError);
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        throw new Exception('Invalid PayPal response: ' . $response);
    }

    return [
        'http_code' => $httpCode,
        'data' => $decoded
    ];
}

function getPayPalAccessToken(): string
{
    $url = rtrim(PAYPAL_API_BASE, '/') . '/v1/oauth2/token';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: en_US',
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        throw new Exception('PayPal token cURL error: ' . $curlError);
    }

    $decoded = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300 || !is_array($decoded) || empty($decoded['access_token'])) {
        throw new Exception('Failed to get PayPal access token: ' . $response);
    }

    return $decoded['access_token'];
}
?>