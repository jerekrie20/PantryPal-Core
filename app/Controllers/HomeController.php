<?php

namespace Controllers;

use Helpers\View;

class HomeController
{
        public function index(): string {
            return View::render('home', ['title' => 'Home']);
        }
}