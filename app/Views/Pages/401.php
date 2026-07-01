<?php
$errStatus  = 401;
$errTitle   = 'Log in to continue';
$errBody    = 'You need to be logged in to access that page.';
$errAccent  = 'warning';
$errActions = [
    ['text' => 'Log in', 'href' => '/login', 'variant' => 'cta'],
    ['text' => 'Home',   'href' => '/',      'variant' => 'secondary'],
];
require VIEW_PATH . '/Components/error_page.php';
