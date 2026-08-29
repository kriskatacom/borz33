<?php

declare(strict_types=1);

namespace Store\Controllers;

class HomeController extends Controller
{
    public function index(): never
    {
        $this->view('home', [
            'title' => 'Borz33',
        ]);
    }
}
