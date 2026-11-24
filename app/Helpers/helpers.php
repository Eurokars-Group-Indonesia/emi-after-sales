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

/*
|--------------------------------------------------------------------------
| RBAC WRS Connect - After Sales
|--------------------------------------------------------------------------
*/
if (!function_exists('afsel_type')) {
    /** Tipe user After Sales yang sedang login: 'atpm' | 'dealer' | null */
    function afsel_type(): ?string
    {
        if (session('user.app') !== 'aftersales') {
            return null;
        }

        $type = session('user.loginAs');

        return isset(\App\Services\Afsel\AfselRbacService::TYPES[$type]) ? $type : null;
    }
}

if (!function_exists('afsel_can')) {
    /** Cek permission user After Sales yang sedang login (berdasarkan role aktif). */
    function afsel_can(string $slug): bool
    {
        if (config('afsel.auth_driver') !== 'local') {
            return true;
        }

        $type = afsel_type();
        if (!$type || !session()->has('user.id')) {
            return false;
        }

        return app(\App\Services\Afsel\AfselRbacService::class)
            ->userHasPermission($type, (string) session('user.id'), $slug);
    }
}

if (!function_exists('afsel_role')) {
    /** Role aktif user After Sales yang sedang login. */
    function afsel_role(): ?\Illuminate\Database\Eloquent\Model
    {
        $type = afsel_type();
        if (!$type || !session()->has('user.id')) {
            return null;
        }

        return app(\App\Services\Afsel\AfselRbacService::class)
            ->getActiveRole($type, (string) session('user.id'));
    }
}

/*
|--------------------------------------------------------------------------
| RBAC WRS Connect - Sales (terpisah dari After Sales)
|--------------------------------------------------------------------------
*/
if (!function_exists('sales_type')) {
    /** Tipe user Sales yang sedang login: 'atpm' | 'dealer' | null */
    function sales_type(): ?string
    {
        if (session('user.app') !== 'sales') {
            return null;
        }

        $type = session('user.loginAs');

        return isset(\App\Services\Sales\SalesRbacService::TYPES[$type]) ? $type : null;
    }
}

if (!function_exists('sales_can')) {
    /** Cek permission user Sales yang sedang login (berdasarkan role aktif). */
    function sales_can(string $slug): bool
    {
        if (config('sales.auth_driver') === 'api') {
            return true;
        }

        $type = sales_type();
        if (!$type || !session()->has('user.id')) {
            return false;
        }

        return app(\App\Services\Sales\SalesRbacService::class)
            ->userHasPermission($type, (string) session('user.id'), $slug);
    }
}

if (!function_exists('sales_role')) {
    /** Role aktif user Sales yang sedang login. */
    function sales_role(): ?\Illuminate\Database\Eloquent\Model
    {
        $type = sales_type();
        if (!$type || !session()->has('user.id')) {
            return null;
        }

        return app(\App\Services\Sales\SalesRbacService::class)
            ->getActiveRole($type, (string) session('user.id'));
    }
}
