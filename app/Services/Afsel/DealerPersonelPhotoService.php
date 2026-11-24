<?php

namespace App\Services\Afsel;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Foto Dealer Personel.
 * File foto ada di server WRS lama (kolom picture hanya nama file). Sumber diatur lewat
 * config('afsel.personel_photo_source'): template URL http(s) atau path folder, placeholder {id} {picture} {ext}.
 * Foto yang berhasil diambil disimpan di storage/app/afsel/personel-photos agar tidak mengambil ulang.
 *
 * Foto yang di-upload dealer disimpan di disk local: UPLOAD_DIR/{id}.{ext} dan selalu diutamakan
 * dibanding sumber di atas.
 */
class DealerPersonelPhotoService
{
    private const MIME = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp',
    ];

    private const CACHE_DIR = 'afsel/personel-photos';
    private const MAX_BYTES = 5 * 1024 * 1024;

    /** Folder foto upload (disk local = storage/app/private), sama dengan folder hasil salin dari WRS. */
    public const UPLOAD_DIR = 'afsel/dealer_personil_aftersales';
    public const UPLOAD_MAX_KB = 400;
    public const UPLOAD_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /**
     * Simpan foto upload sebagai UPLOAD_DIR/{id}.{ext}; foto lama personel ini (ekstensi apa pun) dihapus.
     *
     * @return array{picture:string, picture_ext:string} nilai kolom picture & picture_ext
     */
    public function store(string $id, UploadedFile $file): array
    {
        $disk   = Storage::disk('local');
        $digits = preg_replace('/\D/', '', $id);
        $ext    = strtolower($file->getClientOriginalExtension());

        foreach (array_keys(self::MIME) as $old) {
            foreach ([$old, strtoupper($old)] as $variant) {
                $disk->delete([self::UPLOAD_DIR . "/{$digits}.{$variant}", self::CACHE_DIR . "/{$digits}.{$variant}"]);
            }
        }

        $disk->putFileAs(self::UPLOAD_DIR, $file, "{$digits}.{$ext}");

        return ['picture' => "{$digits}.{$ext}", 'picture_ext' => ".{$ext}"];
    }

    /** Penanda versi foto untuk URL (cache browser ikut berganti saat foto diganti). */
    public function version(object $personel): string
    {
        $path = $this->uploaded($personel);

        return substr(md5($personel->picture . $personel->picture_ext . ($path ? filemtime($path) : '')), 0, 10);
    }

    /** Path absolut foto upload personel bila ada. */
    private function uploaded(object $personel): ?string
    {
        if ($this->extension($personel) === null) {
            return null;
        }

        $ext  = preg_replace('/[^A-Za-z0-9]/', '', (string) ($personel->picture_ext ?: pathinfo((string) $personel->picture, PATHINFO_EXTENSION)));
        $path = Storage::disk('local')->path(self::UPLOAD_DIR . '/' . preg_replace('/\D/', '', $personel->dealer_personel_id) . '.' . $ext);

        return is_file($path) ? $path : null;
    }

    /** Foto bisa ditampilkan: sumber sudah diatur & file berupa gambar (bukan pdf, dll). */
    public function available(object $personel): bool
    {
        if ($this->uploaded($personel)) {
            return true;
        }

        if (!config('afsel.personel_photo_source') || $this->extension($personel) === null) {
            return false;
        }

        // Folder lokal: pastikan filenya ada, supaya modal langsung memakai avatar inisial bila tidak ada
        [$source, $isUrl] = $this->resolveSource($personel);

        return $isUrl || is_file($source);
    }

    /** @return array{0:string,1:string}|null [isi file, mime] */
    public function get(object $personel): ?array
    {
        $ext = $this->extension($personel);

        if ($ext && ($path = $this->uploaded($personel)) && filesize($path) <= self::MAX_BYTES) {
            $content = file_get_contents($path);

            return $this->looksLikeImage($content) ? [$content, self::MIME[$ext]] : null;
        }

        if (!$ext || !config('afsel.personel_photo_source')) {
            return null;
        }

        [$source, $isUrl] = $this->resolveSource($personel);

        // Sumber folder lokal: baca langsung, tidak perlu disalin ke cache
        if (!$isUrl) {
            $content = is_file($source) && filesize($source) <= self::MAX_BYTES ? file_get_contents($source) : null;

            return $content !== null && $this->looksLikeImage($content) ? [$content, self::MIME[$ext]] : null;
        }

        // Sumber URL: ambil sekali lalu simpan di storage
        $cachePath = self::CACHE_DIR . '/' . preg_replace('/\D/', '', $personel->dealer_personel_id) . '.' . $ext;
        $disk = Storage::disk('local');

        if ($disk->exists($cachePath)) {
            return [$disk->get($cachePath), self::MIME[$ext]];
        }

        $content = $this->fetchUrl($source, $personel->dealer_personel_id);
        if ($content === null || !$this->looksLikeImage($content)) {
            return null;
        }

        $disk->put($cachePath, $content);

        return [$content, self::MIME[$ext]];
    }

    /**
     * Lokasi foto dari template. Path relatif dihitung dari root project
     * (mis. public/assets/upload/dealer_personil_aftersales/{id}{ext}).
     *
     * @return array{0:string,1:bool} [lokasi, apakah URL]
     */
    private function resolveSource(object $personel): array
    {
        $template = (string) config('afsel.personel_photo_source');
        $isUrl = preg_match('#^https?://#i', $template) === 1;
        $picture = basename(str_replace('\\', '/', (string) $personel->picture)); // cegah path traversal
        $ext = preg_replace('/[^A-Za-z0-9.]/', '', (string) $personel->picture_ext);

        $source = strtr($template, [
            '{id}'      => preg_replace('/\D/', '', $personel->dealer_personel_id),
            '{picture}' => $isUrl ? rawurlencode($picture) : $picture,
            '{ext}'     => $ext,
        ]);

        $isAbsolute = preg_match('#^([A-Za-z]:[\\\\/]|[\\\\/])#', $source) === 1;
        if (!$isUrl && !$isAbsolute) {
            $source = base_path($source);
        }

        return [$source, $isUrl];
    }

    private function fetchUrl(string $url, string $id): ?string
    {
        try {
            $res = Http::timeout(5)->connectTimeout(3)->get($url);

            return $res->successful() && strlen($res->body()) <= self::MAX_BYTES ? $res->body() : null;
        } catch (\Throwable $e) {
            Log::warning('[DealerPersonelPhoto] gagal mengambil foto ' . $id . ': ' . $e->getMessage());

            return null;
        }
    }

    private function extension(object $personel): ?string
    {
        $ext = strtolower(ltrim((string) ($personel->picture_ext ?: pathinfo((string) $personel->picture, PATHINFO_EXTENSION)), '.'));

        return isset(self::MIME[$ext]) ? $ext : null;
    }

    /** Pastikan isi file memang gambar (bukan halaman error HTML). */
    private function looksLikeImage(string $content): bool
    {
        $info = @getimagesizefromstring($content);

        return $info !== false;
    }
}
