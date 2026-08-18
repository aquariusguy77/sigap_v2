<?php

namespace App\Auth;

use App\Firebase\Record;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Pengguna yang datanya berasal dari node /users di Firebase.
 */
class FirebaseUser implements Authenticatable
{
    public function __construct(
        public Record $record
    ) {
    }

    public function __get(string $key): mixed
    {
        return $this->record->{$key};
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->record->id;
    }

    public function getAuthPassword(): string
    {
        return (string) $this->record->password;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return $this->record->remember_token;
    }

    public function setRememberToken($value): void
    {
        $this->record->remember_token = $value;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}
