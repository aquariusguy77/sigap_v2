<?php

namespace App\Firebase;

use Illuminate\Support\Collection;

class UserRepository extends Repository
{
    protected string $node = 'users';

    protected array $fields = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'remember_token',
    ];

    protected string $sortBy = 'name';

    protected bool $sortDescending = false;

    public function findByEmail(string $email): ?Record
    {
        $email = mb_strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        return $this->all()->first(
            fn (Record $user) => mb_strtolower((string) $user->email) === $email
        );
    }

    public function findByRememberToken(string $id, string $token): ?Record
    {
        $user = $this->find($id);

        return $user && filled($user->remember_token) && hash_equals((string) $user->remember_token, $token)
            ? $user
            : null;
    }

    /**
     * Daftar akun tanpa kata sandi, untuk ditampilkan di halaman pengaturan.
     */
    public function listing(): Collection
    {
        return $this->all()->map(function (Record $user) {
            $data = $user->toArray();
            unset($data['password'], $data['remember_token']);

            return new Record($data);
        });
    }

    protected function decorate(array $payload): array
    {
        $payload['role'] = $payload['role'] ?? 'supervisor';
        $payload['status'] = $payload['status'] ?? 'Aktif';

        return $payload;
    }
}
