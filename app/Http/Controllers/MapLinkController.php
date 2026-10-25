<?php

namespace App\Http\Controllers;

use App\Services\GoogleMapsLinkResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dipanggil pemilih titik saat petugas menempel tautan yang titiknya tidak
 * dapat dibaca langsung di peramban, terutama tautan bagikan maps.app.goo.gl.
 * Hasilnya dipakai untuk memindahkan pin sebelum formulir disimpan.
 */
class MapLinkController extends Controller
{
    public function resolve(Request $request, GoogleMapsLinkResolver $resolver): JsonResponse
    {
        $this->ensureAnyAbility(['manage-refugees', 'manage-placements']);

        $validated = $request->validate(['link' => ['required', 'string', 'max:500']]);
        $titik = $resolver->coordinates($validated['link']);

        if ($titik === null) {
            return response()->json([
                'message' => 'Titik lokasi tidak ditemukan pada tautan ini. Di Google Maps, tekan lama titik rumahnya, '
                    . 'lalu salin angka koordinat yang muncul dan tempel di sini, atau ketuk titiknya langsung di peta.',
            ], 422);
        }

        return response()->json(['latitude' => $titik[0], 'longitude' => $titik[1]]);
    }
}
