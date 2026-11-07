<?php
// Copy to secrets.php on the server and fill in. Never commit secrets.php.
if (!defined('JWT_SECRET')) define('JWT_SECRET', 'CHANGE_ME');
if (!defined('RESET_SECRET')) define('RESET_SECRET', 'CHANGE_ME');
if (!defined('ADMIN_SECRET')) define('ADMIN_SECRET', 'CHANGE_ME');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'CHANGE_ME');
// Set to true once the new app (which sends the login token) is live, to stop
// accepting requests that only name a user_id. See includes/auth_guard.php.
if (!defined('APP_AUTH_REQUIRED')) define('APP_AUTH_REQUIRED', false);
