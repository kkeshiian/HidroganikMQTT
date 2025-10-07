<?php

namespace App\Services;

use Kreait\Firebase\Factory;

class AuthService
{
    protected $auth;
    protected $database;

    public function __construct()
    {
        $credentialsPath = WRITEPATH . '/keys/firebase-admin.json';

        $factory = (new Factory)
            ->withServiceAccount($credentialsPath)
            ->withDatabaseUri(getenv('FIREBASE_DATABASE_URL'));

        $this->auth = $factory->createAuth();
        $this->database = $factory->createDatabase();
    }

    /**
     * Memverifikasi email dan password user.
     * @return \Kreait\Firebase\Auth\UserRecord
     */
    public function verifyPassword(string $email, string $password)
    {
        $signInResult = $this->auth->signInWithEmailAndPassword($email, $password);
        return $this->auth->getUser($signInResult->firebaseUserId());
    }

    /**
     * Membuat user baru.
     */
    public function createUser(array $properties)
    {
        return $this->auth->createUser($properties);
    }
    
    /**
     * Mengambil data user berdasarkan email.
     */
    public function getUserByEmail(string $email)
    {
        return $this->auth->getUserByEmail($email);
    }

    /**
     * Menetapkan role ke user menggunakan Custom Claims.
     */
    public function setRole(string $uid, string $role)
    {
        $this->auth->setCustomUserClaims($uid, ['role' => $role]);
    }
}
