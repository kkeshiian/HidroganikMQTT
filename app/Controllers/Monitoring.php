<?php
namespace App\Controllers;

class Monitoring extends BaseController
{
    public function index(): string
    {
        $data['title'] = 'Dashboard - Hidroganik Alfa Monitoring';
        // UBAH DI SINI: tambahkan 'pages/'
        return view('pages/dashboard', $data);
    }

    public function kalibrasi(): string
    {
        $data['title'] = 'Kalibrasi - Hidroganik Alfa Monitoring';
        // UBAH DI SINI: tambahkan 'pages/'
        return view('pages/kalibrasi', $data);
    }

    public function log(): string
    {
        $data['title'] = 'Log Data - Hidroganik Alfa Monitoring';
        // UBAH DI SINI: tambahkan 'pages/'
        return view('pages/log', $data);
    }
}