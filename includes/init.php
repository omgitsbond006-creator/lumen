<?php
/**
 * Application bootstrap. Every page starts with:
 *   require_once __DIR__ . '/../includes/init.php';
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/auth.php';
