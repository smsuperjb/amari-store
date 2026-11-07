<?php
// Copy to secrets.php on the server and fill in. Never commit secrets.php.
if (!defined('JWT_SECRET')) define('JWT_SECRET', 'CHANGE_ME');
if (!defined('RESET_SECRET')) define('RESET_SECRET', 'CHANGE_ME');
if (!defined('ADMIN_SECRET')) define('ADMIN_SECRET', 'CHANGE_ME');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'CHANGE_ME');
