<?php

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\Controller;

class User extends Controller
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Menampilkan form untuk menambah user baru.
     */
    public function new()
    {
        return view('pages/new', ['page_title' => 'Tambah Pengguna Baru']);
    }

    /**
     * Memproses pembuatan user baru dari form.
     */
    public function create()
    {
        $displayName = $this->request->getPost('displayName');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $role = $this->request->getPost('role'); // 'admin' atau 'user'

        if (empty($displayName) || empty($email) || empty($password) || empty($role)) {
            return redirect()->back()->withInput()->with('error', 'Semua field harus diisi.');
        }

        try {
            // Buat user di Firebase Auth
            $user = $this->authService->createUser([
                'email' => $email,
                'password' => $password,
                'displayName' => $displayName,
            ]);

            // Set custom claim untuk role
            $this->authService->setRole($user->uid, $role);

            return redirect()->to('users/new')->with('success', 'Pengguna berhasil ditambahkan!');

        } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
            return redirect()->back()->withInput()->with('error', 'Email sudah terdaftar.');
        } catch (\Exception $e) {
            log_message('error', '[UserCreate] ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal menambahkan pengguna. Coba lagi.');
        }
    }
}
