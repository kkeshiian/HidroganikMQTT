<?php

namespace App\Controllers;

use App\Libraries\Firebase;
use CodeIgniter\HTTP\ResponseInterface;
use Kreait\Firebase\Exception\AuthException;
use App\Services\AuthService;

class AuthController extends BaseController
{
    protected $authService;
    protected $session;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->session = session();
    }

    /**
     * Menampilkan halaman login.
     * Jika sudah login, redirect ke dashboard.
     */
    public function index()
    {
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to('/');
        }
        // DIUBAH: Menyesuaikan dengan lokasi view Anda
        return view('pages/login');
    }

    /**
     * Memproses upaya login dari form.
     */
    public function loginProcess()
    {
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        try {
            $user = $this->authService->verifyPassword($email, $password);
            
            if ($user) {
                // Ambil detail user termasuk custom claims (role)
                $userDetails = $this->authService->getUserByEmail($email);
                
                $sessionData = [
                    'uid'         => $user->uid,
                    'email'       => $user->email,
                    'displayName' => $userDetails->displayName ?? 'User',
                    'role'        => $userDetails->customClaims['role'] ?? 'user', // Default role 'user'
                    'isLoggedIn'  => true,
                ];

                $this->session->set($sessionData);
                return redirect()->to('/');
            }
        // UBAH BAGIAN INI: Gabungkan semua error autentikasi ke dalam satu blok catch
        } catch (AuthException $e) {
            // Log pesan error asli dari Firebase untuk debugging
            log_message('error', '[AuthProcess] Firebase Auth Error: ' . $e->getMessage());
            // Tampilkan pesan yang ramah ke pengguna
            return redirect()->back()->with('error', 'Email atau password yang Anda masukkan salah.');
        } catch (\Exception $e) {
            // Tangani error umum lainnya
            log_message('error', '[AuthProcess] General Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan pada server. Coba lagi nanti.');
        }

        // Baris ini sebenarnya tidak akan pernah tercapai, tapi sebagai fallback
        return redirect()->back()->with('error', 'Login gagal.');
    }

    /**
     * Proses logout.
     */
    public function logout()
    {
        $this->session->destroy();
        // DIUBAH: Mengarahkan ke rute yang benar, yaitu /login
        return redirect()->to('/login');
    }
}
