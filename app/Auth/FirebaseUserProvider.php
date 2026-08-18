<?php

namespace App\Auth;

use App\Firebase\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Throwable;

/**
 * Menyediakan akun pengguna bagi sistem login Laravel, dibaca dari Firebase.
 *
 * Dengan provider ini, Auth::attempt() bekerja tanpa tabel users di database
 * relasional. Kata sandi tetap disimpan dalam bentuk hash bcrypt.
 */
class FirebaseUserProvider implements UserProvider
{
    public function __construct(
        protected UserRepository $users,
        protected Hasher $hasher
    ) {
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $record = $this->users->find((string) $identifier);

        return $record ? new FirebaseUser($record) : null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $record = $this->users->findByRememberToken((string) $identifier, (string) $token);

        return $record ? new FirebaseUser($record) : null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        try {
            $this->users->update((string) $user->getAuthIdentifier(), ['remember_token' => $token]);
        } catch (Throwable) {
            // Gagal menyimpan token "ingat saya" tidak boleh menggagalkan login.
        }
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (blank($credentials['email'] ?? null)) {
            return null;
        }

        $record = $this->users->findByEmail((string) $credentials['email']);

        return $record ? new FirebaseUser($record) : null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $password = (string) ($credentials['password'] ?? '');
        $hash = $user->getAuthPassword();

        if ($password === '' || $hash === '') {
            return false;
        }

        return $this->hasher->check($password, $hash);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        try {
            $this->users->update((string) $user->getAuthIdentifier(), [
                'password' => $this->hasher->make((string) ($credentials['password'] ?? '')),
            ]);
        } catch (Throwable) {
            // Pembaruan hash bersifat penyempurnaan, bukan syarat login.
        }
    }
}
