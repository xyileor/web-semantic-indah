<?php
/**
 * =========================================================================
 * ROUTER UTAMA DASHBOARD PORTAL AKADEMIK & WEB SEMANTIK
 * Mengarahkan otomatis ke dashboard spesifik berdasarkan Peran (Role):
 * 1. Mahasiswa     -> dashboard_mahasiswa.php
 * 2. Dosen         -> dashboard_dosen.php (Pak Hary, M.Kom)
 * 3. Administrator -> dashboard_admin.php
 * =========================================================================
 */
// Inisialisasi session yang kompatibel dengan hosting/shared hosting.
// Cookie hanya dikirim melalui HTTP(S), dan session ID diregenerasi setelah login.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure_cookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
error_reporting(0);

// Cek autentikasi
if (!isset($_SESSION['user'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}


// Redirect otomatis sesuai sesi user yang login
$current_role = strtolower($_SESSION['user']['role'] ?? 'mahasiswa');

if ($current_role === 'prodi') {
    header("Location: dashboard_prodi.php");
    exit;
} elseif ($current_role === 'dosen') {
    // Pengalihan peran dosen lama ke prodi
    header("Location: dashboard_prodi.php");
    exit;
} elseif ($current_role === 'administrator' || $current_role === 'admin') {
    header("Location: dashboard_admin.php");
    exit;
} else {
    header("Location: dashboard_mahasiswa.php");
    exit;
}
