<?php

namespace Controllers;

use Helpers\View;

class DashboardController
{

    public function index(): string {
        return View::render('/Users/dashboard', ['title' => 'Dashboard']);
    }

}