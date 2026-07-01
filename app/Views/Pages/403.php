<?php
$errStatus  = 403;
$errTitle   = 'You don\'t have access to this';
$errBody    = 'This page is off-limits for your account. If you think that\'s wrong, get in touch with an admin.';
$errAccent  = 'danger';
$errActions = [
    ['text' => 'Go home', 'href' => '/', 'variant' => 'cta'],
];
require VIEW_PATH . '/Components/error_page.php';
