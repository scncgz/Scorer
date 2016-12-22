<?php
require_once 'vendor/autoload.php';

// Database Connection Configuration
define('DB_SERVER', 'mongodb://127.0.0.1:27017');
define('DB_NAME', 'scncgz_score');

// Whether this site is closed
define('CLOSED', false);

define('ROOT', __DIR__.'/');
define('PAGE', ROOT.'page/');
if (CLOSED) {
    die('<h1>Site closed temporarily.</h1>');
}
require_once ROOT.'3L.php';
require_once ROOT.'router.php';