<?php
require __DIR__.'/vendor/autoload.php';

$clientId = '1060036047205-fpj77o44oobuhd3uio4d48ikjg1vf1c8.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost:8000/auth/google/callback';

$provider = new \League\OAuth2\Client\Provider\Google([
    'clientId'     => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri'  => $redirectUri,
]);

if (!isset($_GET['code'])) {
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => ['https://www.googleapis.com/auth/gmail.send'],
        'access_type' => 'offline',
        'prompt' => 'consent',
    ]);
    echo "Allez sur <a href='$authUrl'>$authUrl</a> et connectez-vous.";
} else {
    $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
    echo 'Refresh Token : ' . $token->getRefreshToken();
}