<?php

function mg_firebase_pick_value(...$values)
{
    foreach ($values as $value) {
        if ($value === null || $value === false) {
            continue;
        }

        if (is_string($value)) {
            if (trim($value) === '') {
                continue;
            }
            return $value;
        }

        return $value;
    }

    return null;
}

function mg_firebase_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $localConfig = [];
    $localPath = __DIR__ . '/../includes/firebase.local.php';
    if (file_exists($localPath)) {
        $loaded = require $localPath;
        if (is_array($loaded)) {
            $localConfig = $loaded;
        }
    }

    $serviceAccount = [];
    $serviceAccountPath = trim((string)(mg_firebase_pick_value(
        $localConfig['service_account_json_path'] ?? null,
        getenv('FIREBASE_SERVICE_ACCOUNT_JSON_PATH')
    ) ?? ''));
    if ($serviceAccountPath !== '') {
        if (!preg_match('#^(?:[a-zA-Z]:[\\\\/]|/)#', $serviceAccountPath)) {
            $serviceAccountPath = realpath(__DIR__ . '/../includes/' . ltrim($serviceAccountPath, '/\\')) ?: $serviceAccountPath;
        }
        if (is_file($serviceAccountPath)) {
            $decoded = json_decode((string)file_get_contents($serviceAccountPath), true);
            if (is_array($decoded)) {
                $serviceAccount = $decoded;
            }
        }
    }

    $enabledValue = mg_firebase_pick_value(
        $localConfig['enabled'] ?? null,
        getenv('FIREBASE_ENABLED'),
        false
    );
    $enabled = filter_var($enabledValue, FILTER_VALIDATE_BOOLEAN);

    $privateKey = (string)(mg_firebase_pick_value(
        $localConfig['private_key'] ?? null,
        getenv('FIREBASE_PRIVATE_KEY'),
        $serviceAccount['private_key'] ?? null,
        ''
    ) ?? '');
    $privateKey = str_replace(["\r\n", '\n'], ["\n", "\n"], $privateKey);

    $config = [
        'enabled' => $enabled,
        'project_id' => trim((string)(mg_firebase_pick_value($localConfig['project_id'] ?? null, getenv('FIREBASE_PROJECT_ID'), $serviceAccount['project_id'] ?? null, '') ?? '')),
        'api_key' => trim((string)(mg_firebase_pick_value($localConfig['api_key'] ?? null, getenv('FIREBASE_API_KEY'), '') ?? '')),
        'app_id' => trim((string)(mg_firebase_pick_value($localConfig['app_id'] ?? null, getenv('FIREBASE_APP_ID'), '') ?? '')),
        'messaging_sender_id' => trim((string)(mg_firebase_pick_value($localConfig['messaging_sender_id'] ?? null, getenv('FIREBASE_MESSAGING_SENDER_ID'), $serviceAccount['project_number'] ?? null, '') ?? '')),
        'auth_domain' => trim((string)(mg_firebase_pick_value($localConfig['auth_domain'] ?? null, getenv('FIREBASE_AUTH_DOMAIN'), '') ?? '')),
        'storage_bucket' => trim((string)(mg_firebase_pick_value($localConfig['storage_bucket'] ?? null, getenv('FIREBASE_STORAGE_BUCKET'), '') ?? '')),
        'measurement_id' => trim((string)(mg_firebase_pick_value($localConfig['measurement_id'] ?? null, getenv('FIREBASE_MEASUREMENT_ID'), '') ?? '')),
        'vapid_key' => trim((string)(mg_firebase_pick_value($localConfig['vapid_key'] ?? null, getenv('FIREBASE_VAPID_KEY'), '') ?? '')),
        'service_account_email' => trim((string)(mg_firebase_pick_value($localConfig['service_account_email'] ?? null, getenv('FIREBASE_SERVICE_ACCOUNT_EMAIL'), $serviceAccount['client_email'] ?? null, '') ?? '')),
        'private_key' => trim($privateKey),
        'token_uri' => trim((string)(mg_firebase_pick_value($localConfig['token_uri'] ?? null, getenv('FIREBASE_TOKEN_URI'), $serviceAccount['token_uri'] ?? null, 'https://oauth2.googleapis.com/token') ?? '')),
        'service_account_json_path' => $serviceAccountPath,
    ];

    $config['web_ready'] = $config['enabled']
        && $config['project_id'] !== ''
        && $config['api_key'] !== ''
        && $config['app_id'] !== ''
        && $config['messaging_sender_id'] !== ''
        && $config['vapid_key'] !== '';

    $config['server_ready'] = $config['web_ready']
        && $config['service_account_email'] !== ''
        && $config['private_key'] !== '';

    return $config;
}

function mg_firebase_public_base_path(): string
{
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    if (preg_match('#/(api|includes|helpers|pages)$#', $scriptDir)) {
        $scriptDir = str_replace('\\', '/', dirname($scriptDir));
    }
    $basePath = trim($scriptDir, '/.');

    return $basePath === '' ? '' : '/' . $basePath;
}

function mg_firebase_base_url(): string
{
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '';
    }

    $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
    $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
    $basePath = trim(mg_firebase_public_base_path(), '/');

    return $scheme . '://' . $host . ($basePath !== '' ? '/' . $basePath : '');
}

function mg_firebase_public_config(): array
{
    $config = mg_firebase_config();
    $basePath = mg_firebase_public_base_path();

    return [
        'enabled' => $config['web_ready'],
        'project_id' => $config['project_id'],
        'api_key' => $config['api_key'],
        'app_id' => $config['app_id'],
        'messaging_sender_id' => $config['messaging_sender_id'],
        'auth_domain' => $config['auth_domain'],
        'storage_bucket' => $config['storage_bucket'],
        'measurement_id' => $config['measurement_id'],
        'vapid_key' => $config['vapid_key'],
        'service_worker_path' => $basePath . '/firebase-messaging-sw.php',
        'service_worker_scope' => ($basePath === '' ? '/' : $basePath . '/'),
        'api_endpoint' => $basePath . '/api/firebase.php',
    ];
}

function mg_firebase_normalize_url(?string $url): ?string
{
    $url = trim((string)$url);
    if ($url === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }

    $baseUrl = mg_firebase_base_url();
    if ($baseUrl === '') {
        return null;
    }

    if ($url[0] === '/') {
        $parts = parse_url($baseUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . $url;
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
}

function mg_firebase_table(string $name): string
{
    if (function_exists('mg_table')) {
        return mg_table($name);
    }

    return strtoupper(trim($name));
}

function mg_firebase_sequence(string $name): string
{
    if (function_exists('mg_sequence')) {
        return mg_sequence($name);
    }

    return strtoupper(trim($name));
}

function mg_firebase_base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function mg_firebase_http_post(string $url, string $body, array $headers): array
{
    $response = false;
    $httpCode = 0;
    $curlError = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = trim((string)curl_error($ch));
        curl_close($ch);
    }

    if ($response === false) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        foreach (($http_response_header ?? []) as $headerLine) {
            if (preg_match('/\s(\d{3})\s/', $headerLine, $match)) {
                $httpCode = (int)$match[1];
                break;
            }
        }

        if ($response === false) {
            return [
                'success' => false,
                'http_code' => $httpCode,
                'error' => $curlError !== '' ? $curlError : 'Falha na requisicao HTTP.',
            ];
        }
    }

    return [
        'success' => true,
        'http_code' => $httpCode,
        'body' => (string)$response,
    ];
}

function mg_firebase_access_token(array $config): array
{
    if (empty($config['service_account_email']) || empty($config['private_key'])) {
        return ['success' => false, 'error' => 'Credenciais do Firebase nao configuradas para envio.'];
    }

    $header = mg_firebase_base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_UNESCAPED_UNICODE));
    $now = time();
    $claims = [
        'iss' => $config['service_account_email'],
        'sub' => $config['service_account_email'],
        'aud' => $config['token_uri'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'iat' => $now,
        'exp' => $now + 3600,
    ];
    $payload = mg_firebase_base64url_encode(json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $unsigned = $header . '.' . $payload;

    $privateKey = openssl_pkey_get_private($config['private_key']);
    if ($privateKey === false) {
        return ['success' => false, 'error' => 'Nao foi possivel carregar a chave privada do Firebase.'];
    }

    $signature = '';
    $signed = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    if (is_resource($privateKey) || is_object($privateKey)) {
        openssl_free_key($privateKey);
    }

    if (!$signed) {
        return ['success' => false, 'error' => 'Falha ao assinar o JWT do Firebase.'];
    }

    $assertion = $unsigned . '.' . mg_firebase_base64url_encode($signature);
    $body = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $assertion,
    ]);

    $http = mg_firebase_http_post($config['token_uri'], $body, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    if (empty($http['success'])) {
        return ['success' => false, 'error' => $http['error'] ?? 'Falha ao obter token OAuth do Firebase.'];
    }

    $decoded = json_decode((string)($http['body'] ?? ''), true);
    if (($http['http_code'] ?? 0) >= 400 || !is_array($decoded) || empty($decoded['access_token'])) {
        return [
            'success' => false,
            'error' => is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'Resposta invalida ao obter token OAuth do Firebase.',
        ];
    }

    return [
        'success' => true,
        'access_token' => (string)$decoded['access_token'],
    ];
}

function mg_firebase_save_token(PDO $conn, string $usuario, string $token, array $meta = []): array
{
    $usuario = trim($usuario);
    $token = trim($token);

    if ($usuario === '' || $token === '') {
        return ['success' => false, 'error' => 'Usuario e token sao obrigatorios.'];
    }

    $sqlExistingUser = "SELECT COUNT(*)
                          FROM " . mg_firebase_table('MEGAG_PUSH_TOKENS') . "
                         WHERE UPPER(USUARIO) = UPPER(:USUARIO)
                           AND ATIVO = 'S'";
    $stmtExistingUser = $conn->prepare($sqlExistingUser);
    $stmtExistingUser->bindValue(':USUARIO', $usuario, PDO::PARAM_STR);
    $stmtExistingUser->execute();
    $activeTokensBefore = (int)$stmtExistingUser->fetchColumn();

    $sqlExistingToken = "SELECT COUNT(*)
                           FROM " . mg_firebase_table('MEGAG_PUSH_TOKENS') . "
                          WHERE TOKEN = :TOKEN";
    $stmtExistingToken = $conn->prepare($sqlExistingToken);
    $stmtExistingToken->bindValue(':TOKEN', $token, PDO::PARAM_STR);
    $stmtExistingToken->execute();
    $tokenAlreadyKnown = (int)$stmtExistingToken->fetchColumn() > 0;

    $sql = "MERGE INTO " . mg_firebase_table('MEGAG_PUSH_TOKENS') . " T
            USING (SELECT :TOKEN_SRC AS TOKEN FROM DUAL) SRC
               ON (T.TOKEN = SRC.TOKEN)
            WHEN MATCHED THEN UPDATE SET
                T.USUARIO = :USUARIO_UPD,
                T.DEVICE_PLATFORM = :PLATFORM_UPD,
                T.USER_AGENT = :USER_AGENT_UPD,
                T.ULTIMO_ACESSO_EM = SYSDATE,
                T.ENDPOINT_ORIGEM = :ENDPOINT_UPD,
                T.ATIVO = 'S'
            WHEN NOT MATCHED THEN INSERT
                (ID, USUARIO, TOKEN, DEVICE_PLATFORM, USER_AGENT, ENDPOINT_ORIGEM, ATIVO, CRIADO_EM, ULTIMO_ACESSO_EM)
            VALUES
                (" . mg_firebase_sequence('SEQ_MEGAG_PUSH_TOKENS') . ".NEXTVAL, :USUARIO_INS, :TOKEN_INS, :PLATFORM_INS, :USER_AGENT_INS, :ENDPOINT_INS, 'S', SYSDATE, SYSDATE)";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':TOKEN_SRC', $token, PDO::PARAM_STR);
    $stmt->bindValue(':USUARIO_UPD', $usuario, PDO::PARAM_STR);
    $stmt->bindValue(':PLATFORM_UPD', trim((string)($meta['platform'] ?? 'web')), PDO::PARAM_STR);
    $stmt->bindValue(':USER_AGENT_UPD', substr((string)($meta['user_agent'] ?? ''), 0, 1000), PDO::PARAM_STR);
    $stmt->bindValue(':ENDPOINT_UPD', substr((string)($meta['endpoint'] ?? ''), 0, 500), PDO::PARAM_STR);
    $stmt->bindValue(':USUARIO_INS', $usuario, PDO::PARAM_STR);
    $stmt->bindValue(':TOKEN_INS', $token, PDO::PARAM_STR);
    $stmt->bindValue(':PLATFORM_INS', trim((string)($meta['platform'] ?? 'web')), PDO::PARAM_STR);
    $stmt->bindValue(':USER_AGENT_INS', substr((string)($meta['user_agent'] ?? ''), 0, 1000), PDO::PARAM_STR);
    $stmt->bindValue(':ENDPOINT_INS', substr((string)($meta['endpoint'] ?? ''), 0, 500), PDO::PARAM_STR);
    $stmt->execute();

    return [
        'success' => true,
        'is_first_activation' => $activeTokensBefore === 0,
        'is_new_token' => !$tokenAlreadyKnown,
    ];
}

function mg_firebase_deactivate_token(PDO $conn, string $token): void
{
    $token = trim($token);
    if ($token === '') {
        return;
    }

    $sql = "UPDATE " . mg_firebase_table('MEGAG_PUSH_TOKENS') . "
               SET ATIVO = 'N',
                   ULTIMO_ACESSO_EM = SYSDATE
             WHERE TOKEN = :TOKEN";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':TOKEN', $token, PDO::PARAM_STR);
    $stmt->execute();
}

function mg_firebase_unregister_token(PDO $conn, string $usuario, string $token): array
{
    $usuario = trim($usuario);
    $token = trim($token);

    if ($usuario === '' || $token === '') {
        return ['success' => false, 'error' => 'Usuario e token sao obrigatorios.'];
    }

    $sql = "UPDATE " . mg_firebase_table('MEGAG_PUSH_TOKENS') . "
               SET ATIVO = 'N',
                   ULTIMO_ACESSO_EM = SYSDATE
             WHERE UPPER(USUARIO) = UPPER(:USUARIO)
               AND TOKEN = :TOKEN";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':USUARIO', $usuario, PDO::PARAM_STR);
    $stmt->bindValue(':TOKEN', $token, PDO::PARAM_STR);
    $stmt->execute();

    return ['success' => true];
}

function mg_firebase_fetch_tokens(PDO $conn, array $usuarios): array
{
    $usuarios = array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $usuarios))));

    if (!$usuarios) {
        return [];
    }

    $placeholders = [];
    $params = [];
    foreach ($usuarios as $index => $usuario) {
        $key = ':U' . $index;
        $placeholders[] = $key;
        $params[$key] = strtoupper($usuario);
    }

    $sql = "SELECT DISTINCT TOKEN
              FROM " . mg_firebase_table('MEGAG_PUSH_TOKENS') . "
             WHERE ATIVO = 'S'
               AND UPPER(USUARIO) IN (" . implode(', ', $placeholders) . ")";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->execute();

    return array_values(array_filter(array_map('trim', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
}

function mg_firebase_normalize_data(array $data): array
{
    $normalized = [];
    foreach ($data as $key => $value) {
        if ($value === null) {
            continue;
        }

        if (is_bool($value)) {
            $normalized[(string)$key] = $value ? 'true' : 'false';
            continue;
        }

        if (is_scalar($value)) {
            $normalized[(string)$key] = (string)$value;
            continue;
        }

        $normalized[(string)$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    return $normalized;
}

function mg_firebase_send_message(string $projectId, string $accessToken, string $token, string $title, string $message, array $extra = []): array
{
    $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';
    $link = mg_firebase_normalize_url((string)($extra['url'] ?? ''));

    $payload = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $message,
            ],
            'data' => mg_firebase_normalize_data(array_merge(
                ['title' => $title, 'body' => $message],
                is_array($extra['data'] ?? null) ? $extra['data'] : [],
                $link ? ['url' => $link] : []
            )),
            'webpush' => [
                'headers' => [
                    'Urgency' => 'high',
                ],
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                ],
            ],
        ],
    ];

    if ($link !== null) {
        $payload['message']['webpush']['fcm_options'] = ['link' => $link];
    }

    $http = mg_firebase_http_post(
        $url,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer ' . $accessToken,
        ]
    );

    if (empty($http['success'])) {
        return ['success' => false, 'error' => $http['error'] ?? 'Falha ao enviar push via Firebase.'];
    }

    $decoded = json_decode((string)($http['body'] ?? ''), true);
    if (($http['http_code'] ?? 0) >= 400) {
        return [
            'success' => false,
            'error' => is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'Erro ao enviar push via Firebase.',
        ];
    }

    return [
        'success' => true,
        'data' => $decoded,
    ];
}

function mg_firebase_should_deactivate_token(string $errorMessage): bool
{
    $errorMessage = strtoupper($errorMessage);
    return strpos($errorMessage, 'UNREGISTERED') !== false
        || strpos($errorMessage, 'REGISTRATION_TOKEN_NOT_REGISTERED') !== false
        || strpos($errorMessage, 'INVALID_ARGUMENT') !== false;
}

function mg_firebase_send_to_users(PDO $conn, array $usuarios, string $title, string $message, array $extra = []): array
{
    $config = mg_firebase_config();
    if (!$config['server_ready']) {
        return ['success' => false, 'error' => 'Firebase nao configurado para envio.'];
    }

    $tokens = mg_firebase_fetch_tokens($conn, $usuarios);
    if (!$tokens) {
        return ['success' => false, 'error' => 'Nenhum dispositivo registrado para os usuarios informados.'];
    }

    $auth = mg_firebase_access_token($config);
    if (empty($auth['success'])) {
        return ['success' => false, 'error' => $auth['error'] ?? 'Falha ao autenticar no Firebase.'];
    }

    $successCount = 0;
    $errors = [];

    foreach ($tokens as $token) {
        $result = mg_firebase_send_message($config['project_id'], $auth['access_token'], $token, $title, $message, $extra);
        if (!empty($result['success'])) {
            $successCount++;
            continue;
        }

        $error = (string)($result['error'] ?? 'Falha no envio do push.');
        $errors[] = $error;
        if (mg_firebase_should_deactivate_token($error)) {
            mg_firebase_deactivate_token($conn, $token);
        }
    }

    return [
        'success' => $successCount > 0,
        'sent' => $successCount,
        'errors' => $errors,
        'tokens' => count($tokens),
    ];
}

function mg_firebase_notify_user(PDO $conn, ?string $usuario, string $title, string $message, array $extra = []): array
{
    $usuario = trim((string)$usuario);
    if ($usuario === '') {
        return ['success' => false, 'error' => 'Usuario de push vazio.'];
    }

    return mg_firebase_send_to_users($conn, [$usuario], $title, $message, $extra);
}
