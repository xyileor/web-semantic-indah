<?php
/**
 * Pengalihan otomatis: Role Dosen telah diganti menjadi Program Studi (Prodi)
 * Sesuai spesifikasi tugas: Role Mahasiswa, Prodi, dan Admin.
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
header("Location: dashboard_prodi.php");
exit;
