<?php
/**
 * =====================================================================
 * FILE KONEKSI DATABASE
 * Universitas Semantik - Hosting: InfinityFree
 * =====================================================================
 */

// ----------------------------------------------------------------------
// Konfigurasi Database (dari panel InfinityFree -> MySQL Databases)
// ----------------------------------------------------------------------
$host     = "sql301.infinityfree.com";
$username = "if0_42941925";
$password = "234kc2lh6EA4FRF";
$database = "if0_42941925_UniversitasSemantic";

// Matikan mode exception mysqli (PHP 8.1+) agar error query/koneksi
// tidak membuat halaman blank; halaman lain sudah menangani fallback sendiri.
mysqli_report(MYSQLI_REPORT_OFF);

// ----------------------------------------------------------------------
// Membuat koneksi menggunakan MySQLi
// ----------------------------------------------------------------------
$koneksi = @new mysqli($host, $username, $password, $database);

// Jika gagal, jangan hentikan halaman (index/login punya mode fallback).
// Untuk melihat penyebab error saat testing, ubah false -> true.
$DEBUG_KONEKSI = false;
if ($koneksi->connect_errno) {
    if ($DEBUG_KONEKSI) {
        die("Koneksi database gagal: " . $koneksi->connect_error);
    }
} else {
    $koneksi->set_charset("utf8mb4");
}
?>
