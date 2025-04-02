<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo 0;
    exit;
}

$count = getCartCount();
echo $count;
