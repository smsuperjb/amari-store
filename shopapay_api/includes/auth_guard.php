<?php
// includes/auth_guard.php
//
// Works out which customer an app request is for, from the login token
// (the JWT that api/auth/login.php and register.php hand out).
//
// The app sends it as:   Authorization: Bearer <token>
//
// Rules:
//  - Valid token  -> the token's user id is used. If the request also names a
//                    different user_id, it is refused (403).
//  - Bad/expired  -> refused (401) when APP_AUTH_REQUIRED is true, so the app
//                    can send the person to log in. Ignored while it is false.
//  - No token     -> refused (401) when APP_AUTH_REQUIRED is true.
//                    While it is false (the default), the old behaviour is kept
//                    and the user_id from the request is used, so the current
//                    Amari Online app keeps working until the new app ships.
//                    Set APP_AUTH_REQUIRED to true in config/secrets.php then.

require_once __DIR__ . '/jwt.php';

function api_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if ($header === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $header = $value;
                break;
            }
        }
    }

    if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function api_auth_fail(int $code, string $message): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $message, 'auth' => false]);
    exit;
}

/**
 * Returns the user id this request may act for.
 *
 * @param mixed $claimedUserId user_id sent by the app, if any
 */
function api_user_id($claimedUserId = null): int
{
    $claimed  = (int) ($claimedUserId ?? 0);
    $token    = api_bearer_token();
    $required = defined('APP_AUTH_REQUIRED') && APP_AUTH_REQUIRED;

    if ($token !== null) {
        $payload = verifyJWT($token);
        if (!$payload || empty($payload['user_id'])) {
            if (!$required) {
                // Legacy mode: the old app may resend an expired token. Behave as before.
                return $claimed;
            }
            api_auth_fail(401, 'Your session has expired. Please log in again.');
        }
        $tokenUserId = (int) $payload['user_id'];
        if ($claimed > 0 && $claimed !== $tokenUserId) {
            api_auth_fail(403, 'Not allowed.');
        }
        return $tokenUserId;
    }

    if ($required) {
        api_auth_fail(401, 'Please log in.');
    }

    return $claimed; // legacy mode: trust the request, as before
}
