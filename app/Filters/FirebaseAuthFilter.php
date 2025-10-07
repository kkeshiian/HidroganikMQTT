<?php
namespace App\Filters;

use App\Libraries\Firebase;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class FirebaseAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        $uid = $session->get('uid');
        $role = $session->get('role');

        if (!$uid) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) {
                $idToken = trim($m[1]);
                try {
                    $fb = new Firebase();
                    $auth = $fb->auth();
                    $verified = $auth->verifyIdToken($idToken);
                    $uid = $verified->claims()->get('sub');

                    $user = $auth->getUser($uid);
                    $c = $user->customClaims ?? [];
                    $role = $c['role'] ?? 'user';
                    $active = (bool)($c['active'] ?? false);

                    if (!$active && $role !== 'admin') {
                        return service('response')->setStatusCode(403, 'Forbidden');
                    }

                    $session->set([
                        'uid'   => $uid,
                        'email' => $user->email,
                        'name'  => $user->displayName ?? ($user->email ?? ''),
                        'role'  => $role,
                    ]);
                } catch (FailedToVerifyToken $e) {
                    return redirect()->to('/login');
                }
            } else {
                return redirect()->to('/login');
            }
        }

        if (!empty($arguments)) {
            $requiredRole = $arguments[0];
            if (($role ?? 'user') !== $requiredRole) {
                return service('response')->setStatusCode(403, 'Forbidden');
            }
        }
        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
