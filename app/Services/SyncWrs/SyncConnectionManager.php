<?php

namespace App\Services\SyncWrs;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Koneksi database sumber sync, dibaca dari tabel ms_sync_db_connection (bukan .env),
 * sehingga host / user / password bisa diganti dari menu tanpa redeploy.
 */
class SyncConnectionManager
{
    public const TABLE = 'ms_sync_db_connection';

    public const DRIVERS = ['pgsql' => 'PostgreSQL', 'mysql' => 'MySQL', 'sqlsrv' => 'SQL Server'];

    /** Port default per driver (dipakai form menu Koneksi Database Sync). */
    public const DEFAULT_PORTS = ['pgsql' => '5432', 'mysql' => '3306', 'sqlsrv' => '1433'];

    public function find(string $name): object
    {
        $row = DB::table(self::TABLE)->where('name', $name)->first();
        if (!$row) {
            throw new RuntimeException("Koneksi database '{$name}' belum diatur di menu Koneksi Database Sync.");
        }

        return $row;
    }

    /**
     * Daftarkan koneksi runtime dari baris ms_sync_db_connection dan pastikan bisa connect.
     *
     * @return string nama koneksi Laravel yang bisa dipakai DB::connection()
     */
    public function connect(string $name): string
    {
        $row = $this->find($name);
        if (!$row->is_active) {
            throw new RuntimeException("Koneksi database '{$name}' sedang non-aktif.");
        }

        $connection = 'sync_src_' . Str::lower(preg_replace('/\W+/', '_', $name));

        config(["database.connections.{$connection}" => $this->buildConfig(
            $row->driver, $row->hostname, $row->port, $row->database, $row->username, $this->decrypt($row->password)
        )]);
        DB::purge($connection);
        DB::connection($connection)->getPdo();

        return $connection;
    }

    public function disconnect(string $connection): void
    {
        DB::purge($connection);
    }

    /**
     * Uji koneksi tanpa menyimpan apa pun.
     *
     * @return array{ok: bool, message: string}
     */
    public function test(string $driver, string $host, string $port, string $database, string $username, ?string $password): array
    {
        $connection = 'sync_test_' . Str::random(8);

        // PDO::ATTR_TIMEOUT pdo_mysql hanya membatasi connect TCP. Jika port milik server lain (mis. 1433 SQL Server),
        // mysqlnd menunggu salam MySQL sampai net_read_timeout (default 86400 detik) → tombol Test menggantung.
        // Laravel mencoba ulang koneksi "gone away" (connect + query, total 4x), jadi batas per percobaan dibuat pendek.
        $readTimeout = ini_get('mysqlnd.net_read_timeout');
        if ($driver === 'mysql') {
            ini_set('mysqlnd.net_read_timeout', '5');
        }

        try {
            config(["database.connections.{$connection}" => $this->buildConfig($driver, $host, $port, $database, $username, $password)]);
            $sql     = $driver === 'sqlsrv' ? 'select @@VERSION as v' : 'select version() as v';
            $version = preg_replace('/\s+/', ' ', (string) DB::connection($connection)->selectOne($sql)->v);

            return ['ok' => true, 'message' => 'Koneksi berhasil. ' . Str::limit((string) $version, 90)];
        } catch (Throwable $e) {
            $hint = $this->driverHint($driver, $port);

            return ['ok' => false, 'message' => 'Koneksi gagal: ' . Str::limit($e->getMessage(), 300) . ($hint ? " {$hint}" : '')];
        } finally {
            DB::purge($connection);
            config(["database.connections.{$connection}" => null]);
            if ($readTimeout !== false) {
                ini_set('mysqlnd.net_read_timeout', $readTimeout);
            }
        }
    }

    /** Petunjuk bila port adalah port default driver lain (mis. driver MySQL dengan port 1433). */
    private function driverHint(string $driver, string $port): ?string
    {
        $owner = array_search((string) (int) $port, self::DEFAULT_PORTS, true);
        if ($owner === false || $owner === $driver) {
            return null;
        }

        return "Port {$port} adalah port default " . self::DRIVERS[$owner] . ', sedangkan driver yang dipilih '
            . (self::DRIVERS[$driver] ?? $driver) . '. Periksa pilihan Driver.';
    }

    public function encrypt(?string $password): ?string
    {
        return ($password === null || $password === '') ? null : Crypt::encryptString($password);
    }

    public function decrypt(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            throw new RuntimeException('Password koneksi tidak bisa dibaca (APP_KEY berubah?). Isi ulang password di menu Koneksi Database Sync.');
        }
    }

    private function buildConfig(string $driver, string $host, string $port, string $database, string $username, ?string $password): array
    {
        if ($driver === 'sqlsrv') {
            if (!extension_loaded('pdo_sqlsrv')) {
                throw new RuntimeException('Extension PHP pdo_sqlsrv belum terpasang di server ini (dibutuhkan untuk koneksi SQL Server).');
            }

            $opt = config('sync_msi.sqlsrv', []);

            // PDO::ATTR_TIMEOUT tidak didukung pdo_sqlsrv → pakai login_timeout (LoginTimeout di DSN)
            return [
                'driver'                   => 'sqlsrv',
                'host'                     => $host,
                'port'                     => $port,
                'database'                 => $database,
                'username'                 => $username,
                'password'                 => (string) $password,
                'charset'                  => 'utf8',
                'prefix'                   => '',
                'prefix_indexes'           => true,
                'encrypt'                  => $opt['encrypt'] ?? 'no',
                'trust_server_certificate' => (bool) ($opt['trust_server_certificate'] ?? true),
                'login_timeout'            => (int) ($opt['login_timeout'] ?? 15),
            ];
        }

        $base = [
            'driver'   => $driver,
            'host'     => $host,
            'port'     => $port,
            'database' => $database,
            'username' => $username,
            'password' => (string) $password,
            'prefix'   => '',
            'options'  => [PDO::ATTR_TIMEOUT => 10],
        ];

        if ($driver === 'mysql') {
            return $base + ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true];
        }

        return $base + ['charset' => 'utf8', 'prefix_indexes' => true, 'search_path' => 'public', 'sslmode' => 'prefer'];
    }
}
