<?php

namespace App\Libraries;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

class Firebase
{
    private Auth $auth;

    public function __construct()
    {
        $serviceAccount = env('FIREBASE_CREDENTIALS');
        $factory = (new Factory())->withServiceAccount($serviceAccount);
        $this->auth = $factory->createAuth();
    }

    public function auth(): Auth
    {
        return $this->auth;
    }
}
