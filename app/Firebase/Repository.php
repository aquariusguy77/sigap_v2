<?php

namespace App\Firebase;

use App\Exceptions\FirebaseWriteException;
use App\Services\FirebaseRealtimeDatabaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Dasar penyimpanan data pada Firebase Realtime Database.
 *
 * Menggantikan peran model Eloquent: seluruh operasi baca dan tulis dilakukan
 * langsung ke Firebase, sehingga aplikasi tidak lagi memerlukan MySQL maupun
 * SQLite.
 */
abstract class Repository
{
    /** Nama node pada Firebase, misalnya "refugees". */
    protected string $node = '';

    /** Kolom yang perlu diubah menjadi objek tanggal saat dibaca. */
    protected array $dates = [];

    /** Kolom yang boleh disimpan, sekaligus menjaga urutan isi node. */
    protected array $fields = [];

    /** Kolom untuk mengurutkan daftar secara menurun. */
    protected string $sortBy = 'created_at';

    protected bool $sortDescending = true;

    public function __construct(
        protected FirebaseRealtimeDatabaseService $firebase
    ) {
    }

    public function node(): string
    {
        return $this->node;
    }

    /**
     * Seluruh isi node sebagai kumpulan Record.
     */
    public function all(): Collection
    {
        $snapshot = $this->firebase->fetchNode($this->firebase->path($this->node));

        if (! is_array($snapshot)) {
            return collect();
        }

        return collect($snapshot)
            ->map(function ($payload, $key) {
                if (! is_array($payload)) {
                    return null;
                }

                return $this->hydrate((string) $key, $payload);
            })
            ->filter()
            ->sortByDesc(fn (Record $record) => (string) ($record->attributes[$this->sortBy] ?? ''))
            ->when(! $this->sortDescending, fn (Collection $items) => $items->reverse())
            ->values();
    }

    public function find(string $id): ?Record
    {
        if (trim($id) === '') {
            return null;
        }

        $payload = $this->firebase->fetchNode($this->firebase->path($this->node) . '/' . $id);

        return is_array($payload) ? $this->hydrate($id, $payload) : null;
    }

    /**
     * Menambah data baru. Firebase yang menentukan kuncinya.
     *
     * @throws FirebaseWriteException
     */
    public function create(array $attributes): Record
    {
        $payload = $this->preparePayload($attributes);
        $payload['created_at'] = $payload['created_at'] ?? now()->toIso8601String();
        $payload['updated_at'] = now()->toIso8601String();

        $key = $this->firebase->pushNode($this->node, $payload);

        if ($key === null) {
            throw FirebaseWriteException::gagalMenyimpan($this->firebase->lastError());
        }

        return $this->hydrate($key, $payload);
    }

    /**
     * Menyimpan data pada kunci tertentu, membuat bila belum ada.
     *
     * @throws FirebaseWriteException
     */
    public function put(string $id, array $attributes): Record
    {
        $payload = $this->preparePayload($attributes);
        $payload['created_at'] = $payload['created_at'] ?? now()->toIso8601String();
        $payload['updated_at'] = now()->toIso8601String();

        if (! $this->firebase->putNode($this->firebase->path($this->node) . '/' . $id, $payload)) {
            throw FirebaseWriteException::gagalMenyimpan($this->firebase->lastError());
        }

        return $this->hydrate($id, $payload);
    }

    /**
     * @throws FirebaseWriteException
     */
    public function update(string $id, array $attributes): Record
    {
        $payload = $this->preparePayload($attributes);
        $payload['updated_at'] = now()->toIso8601String();

        if (! $this->firebase->patchNode($this->firebase->path($this->node) . '/' . $id, $payload)) {
            throw FirebaseWriteException::gagalMenyimpan($this->firebase->lastError());
        }

        $existing = $this->find($id);

        return $existing ?? $this->hydrate($id, $payload);
    }

    /**
     * @throws FirebaseWriteException
     */
    public function delete(string $id): void
    {
        if (! $this->firebase->deleteNode($this->firebase->path($this->node) . '/' . $id)) {
            throw FirebaseWriteException::gagalMenghapus($this->firebase->lastError());
        }
    }

    public function count(): int
    {
        return $this->all()->count();
    }

    /**
     * Menyaring isi payload sesuai kolom yang dikenali, sekaligus membuang
     * nilai yang tidak diisi supaya node Firebase tetap ringkas.
     */
    protected function preparePayload(array $attributes): array
    {
        $payload = [];

        foreach ($this->fields as $field) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }

            $value = $attributes[$field];

            if ($value instanceof Carbon) {
                $value = $value->toIso8601String();
            }

            $payload[$field] = $value === '' ? null : $value;
        }

        foreach (['created_at', 'updated_at'] as $stamp) {
            if (array_key_exists($stamp, $attributes)) {
                $payload[$stamp] = $attributes[$stamp];
            }
        }

        return $payload;
    }

    /**
     * Mengubah data mentah Firebase menjadi Record siap pakai.
     */
    protected function hydrate(string $key, array $payload): Record
    {
        $payload['id'] = $key;

        foreach ($this->dates as $field) {
            if (filled($payload[$field] ?? null)) {
                try {
                    $payload[$field] = Carbon::parse($payload[$field]);
                } catch (Throwable) {
                    // Biarkan nilai aslinya bila format tanggalnya tidak dikenali.
                }
            }
        }

        return new Record($this->decorate($payload));
    }

    /**
     * Kesempatan bagi turunan untuk menambahkan kolom turunan.
     */
    protected function decorate(array $payload): array
    {
        return $payload;
    }
}
