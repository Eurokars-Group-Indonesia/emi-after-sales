<?php
if (!function_exists('validDate')) {
    function validDate($date)
    {
        if (!$date) return null;

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null; // jika error, skip
        }
    }
}


if (!function_exists('validDateTime')) {
    function validDateTime($dateTime)
    {
        if (!$dateTime) return null;

        try {
            // Hapus spasi ekstra
            $dateTime = trim($dateTime);

            // Cek format DATETIME dengan regex sederhana (YYYY-MM-DD HH:MM:SS)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $dateTime)) {
                return null; // format tidak sesuai → return null
            }

            // Parse dengan Carbon
            $dt = \Carbon\Carbon::parse($dateTime);

            // Filter tahun <1970
            if ($dt->year < 1970) {
                return null; // tahun terlalu lama → return null
            }

            // Return MySQL DATETIME
            return $dt->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            return null;
        }
    }
}


if (!function_exists('sanitize')) {
    function sanitizeString($value)
    {
        if ($value === null) {
            return null;
        }

        // NBSP -> spasi normal
        $value = str_replace("\xC2\xA0", " ", $value);

        // buang karakter control
        $value = preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $value);

        // remove invalid utf8
        $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return trim($value);
    }
}