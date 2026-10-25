<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Membaca titik koordinat dari teks atau tautan Google Maps yang ditempel petugas.
 *
 * Yang paling sering ditempel dari HP adalah tautan bagikan maps.app.goo.gl.
 * Tautan itu tidak memuat angka apa pun; titiknya baru muncul setelah
 * tautannya diikuti. Karena itu tautan pendek diikuti lebih dulu di sisi
 * server, tetapi hanya selama tujuannya tetap domain Google, supaya fitur ini
 * tidak dapat dipakai untuk menjangkau alamat lain.
 */
class GoogleMapsLinkResolver
{
    protected const SHORT_HOSTS = ['maps.app.goo.gl', 'goo.gl', 'g.co'];

    /**
     * @return array{0: string, 1: string}|null  lintang dan bujur
     */
    public function coordinates(string $value): ?array
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $found = $this->extract($value);

        if ($found !== null || ! $this->isShortLink($value)) {
            return $found;
        }

        return $this->followShortLink($value);
    }

    /**
     * Mencari titik pada teks, dari yang paling tepat.
     *
     * Pada tautan tempat, "!3d…!4d…" adalah letak pin tempatnya, sedangkan
     * "@…" hanya pusat tampilan peta saat tautan dibuat. Keduanya bisa
     * berselisih puluhan meter, jadi pin didahulukan.
     *
     * Sepasang angka polos hanya dicari pada teks yang ditempel petugas,
     * tidak pada isi halaman Google yang penuh angka lain.
     *
     * @return array{0: string, 1: string}|null
     */
    public function extract(string $text, bool $acceptBarePair = true): ?array
    {
        $text = rawurldecode($text);
        $angka = '(-?\d{1,3}\.\d+)';

        $patterns = [
            '/!3d' . $angka . '!4d' . $angka . '/',
            '/@' . $angka . ',\s*' . $angka . '/',
            '/[?&](?:q|query|ll|center|destination|daddr)=' . $angka . ',\s*\+?' . $angka . '/',
        ];

        if ($acceptBarePair) {
            $patterns[] = '/' . $angka . '\s*,\s*' . $angka . '/';
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m) && $this->valid($m[1], $m[2])) {
                return [$m[1], $m[2]];
            }
        }

        return null;
    }

    public function isShortLink(string $value): bool
    {
        $host = Str::lower((string) parse_url(trim($value), PHP_URL_HOST));

        return in_array($host, self::SHORT_HOSTS, true);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected function followShortLink(string $url): ?array
    {
        $visited = [];

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (SIGAP; pembaca tautan peta)'])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 6,
                        'on_redirect' => function ($request, $response, $uri) use (&$visited) {
                            if (! $this->isGoogleHost((string) $uri->getHost())) {
                                throw new \RuntimeException('Tautan mengarah ke luar Google Maps.');
                            }

                            $visited[] = (string) $uri;
                        },
                    ],
                ])
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        foreach (array_reverse($visited) as $step) {
            if ($found = $this->extract($step)) {
                return $found;
            }
        }

        // Sebagian tautan berakhir di halaman yang memuat titiknya di dalam isi halaman.
        return $this->extract(Str::limit((string) $response->body(), 200000, ''), false);
    }

    protected function isGoogleHost(string $host): bool
    {
        $host = Str::lower($host);

        return in_array($host, self::SHORT_HOSTS, true)
            || preg_match('/(^|\.)google\.(com|co\.id|[a-z]{2,3}(\.[a-z]{2})?)$/', $host) === 1;
    }

    protected function valid(string $lat, string $lng): bool
    {
        return abs((float) $lat) <= 90 && abs((float) $lng) <= 180;
    }
}
