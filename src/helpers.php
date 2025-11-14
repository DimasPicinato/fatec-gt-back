<?php
$config = require __DIR__ . '/config.php';

function uuid_v4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data),4));
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_bearer_token(): ?string {
    $h = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $h = trim($_SERVER['HTTP_AUTHORIZATION']);
    elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $h = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    if (!$h) return null;
    if (stripos($h,'bearer ') === 0) return substr($h,7);
    return null;
}

function jwt_encode(array $payload, int $expSeconds = 3600): string {
    global $config;
    $header = ['alg'=>'HS256','typ'=>'JWT'];
    $now = time();
    $payload['iat'] = $now;
    $payload['exp'] = $now + $expSeconds;
    $b64 = function($v){ return rtrim(strtr(base64_encode(json_encode($v)), '+/', '-_'), '='); };
    $header_b = $b64($header);
    $payload_b = $b64($payload);
    $signature = hash_hmac('sha256', "$header_b.$payload_b", $config->jwt_secret, true);
    $sig_b = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    return "$header_b.$payload_b.$sig_b";
}

function jwt_decode(string $token) {
    global $config;
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header_b, $payload_b, $sig_b] = $parts;
    $sig = base64_decode(strtr($sig_b, '-_', '+/'));
    $expected = hash_hmac('sha256', "$header_b.$payload_b", $config->jwt_secret, true);
    if (!hash_equals($expected, $sig)) return null;
    $payload = json_decode(base64_decode(strtr($payload_b, '-_', '+/')), true);
    if (!$payload) return null;
    if (isset($payload['exp']) && time() > $payload['exp']) return null;
    return $payload;
}

function auth_user_or_401() {
    $token = get_bearer_token();
    if (!$token) json_response(['error'=>'missing token'],401);
    $payload = jwt_decode($token);
    if (!$payload || empty($payload['uuid'])) json_response(['error'=>'invalid token'],401);
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id,name,photo FROM users WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$payload['uuid']]);
    $user = $stmt->fetch();
    if (!$user) json_response(['error'=>'user not found or deleted'],401);
    return $user;
}
