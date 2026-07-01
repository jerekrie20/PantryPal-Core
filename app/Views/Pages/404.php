<?php
$errStatus  = 404;
$errTitle   = 'This page isn\'t on the menu';
$errBody    = 'The page you\'re looking for might have moved, been removed, or never existed.';
$errAccent  = 'brand';
$errActions = [
    ['text' => 'Back to home', 'href' => '/',         'variant' => 'cta'],
    ['text' => 'My pantry',    'href' => '/items',    'variant' => 'secondary'],
];
require VIEW_PATH . '/Components/error_page.php';
