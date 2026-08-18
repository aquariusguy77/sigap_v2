<?php

namespace App\Firebase;

use ArrayAccess;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Satu baris data yang berasal dari Firebase Realtime Database.
 *
 * Kelas ini menggantikan model Eloquent. Bentuknya sengaja dibuat sederhana
 * namun tetap dapat dipakai seperti objek di dalam Blade (contoh
 * $refugee->name) dan tetap bisa dijadikan parameter route.
 */
class Record implements ArrayAccess, Arrayable, UrlRoutable
{
    public function __construct(
        public array $attributes = []
    ) {
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    // --- Dukungan route model binding ---

    public function getRouteKey(): mixed
    {
        return $this->attributes['id'] ?? null;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        return null;
    }

    public function resolveChildRouteBinding($childType, $value, $field = null): ?static
    {
        return null;
    }
}
