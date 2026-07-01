<?php
$errStatus  = 500;
$errTitle   = 'Something went wrong on our end';
$errBody    = 'That wasn\'t supposed to happen. Try again in a moment — if it keeps failing, let us know.';
$errAccent  = 'danger';
$errActions = [
    ['text' => 'Go home', 'href' => '/', 'variant' => 'cta'],
];
require VIEW_PATH . '/Components/error_page.php';
