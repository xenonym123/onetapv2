<?php

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', ['title' => 'Scan anytime, ride anywhere', 'page' => 'home']);
    }

    public function about(): void
    {
        $this->view('home/about', ['title' => 'About us', 'page' => 'about']);
    }

    public function qr(): void
    {
        $this->view('home/qr', ['title' => 'Pay with QR', 'page' => 'qr', 'methods' => config('reload.methods')]);
    }
}
