<?php
/**
 * Portal Akademik & Data Mahasiswa Berbasis Web Semantik
 * Universitas Contoh (Universitas Muhammadiyah Bengkulu)
 */
session_start();
error_reporting(0);
ini_set('display_errors', 0);

$db_connected = false;
$koneksi = null;

// Cek dan hubungkan ke database secara aman
if (file_exists(__DIR__ . '/koneksi.php')) {
    try {
        @include_once __DIR__ . '/koneksi.php';
        if (isset($koneksi) && $koneksi instanceof mysqli && !mysqli_connect_errno()) {
            $db_connected = true;
        }
    } catch (Throwable $e) {
        $db_connected = false;
    }
}

// Data default Program Studi (sesuai gambar desain referensi)
$prodi_default = [
    [
        'kode' => 'MJ',
        'nama' => 'Manajemen',
        'jumlah' => 325,
        'icon' => 'fa-chart-column',
        'color' => '#db2777',
        'bg_color' => '#fce7f3',
        'fakultas' => 'Ekonomi'
    ],
    [
        'kode' => 'TI',
        'nama' => 'Teknik Informatika',
        'jumlah' => 412,
        'icon' => 'fa-laptop-code',
        'color' => '#ec4899',
        'bg_color' => '#fce7f3',
        'fakultas' => 'Teknik'
    ],
    [
        'kode' => 'ES',
        'nama' => 'Ekonomi Syariah',
        'jumlah' => 286,
        'icon' => 'fa-book-open',
        'color' => '#a21caf',
        'bg_color' => '#fae8ff',
        'fakultas' => 'Ekonomi'
    ],
    [
        'kode' => 'KP',
        'nama' => 'Keperawatan',
        'jumlah' => 198,
        'icon' => 'fa-stethoscope',
        'color' => '#e11d48',
        'bg_color' => '#ffe4e6',
        'fakultas' => 'Ilmu Kesehatan'
    ],
    [
        'kode' => 'PD',
        'nama' => 'Pendidikan',
        'jumlah' => 276,
        'icon' => 'fa-user-group',
        'color' => '#ea580c',
        'bg_color' => '#ffedd5',
        'fakultas' => 'Keguruan'
    ],
    [
        'kode' => 'AG',
        'nama' => 'Agronomi',
        'jumlah' => 154,
        'icon' => 'fa-leaf',
        'color' => '#16a34a',
        'bg_color' => '#dcfce7',
        'fakultas' => 'Pertanian'
    ],
    [
        'kode' => 'TM',
        'nama' => 'Teknik Mesin',
        'jumlah' => 231,
        'icon' => 'fa-gear',
        'color' => '#ec4899',
        'bg_color' => '#fdf2f8',
        'fakultas' => 'Teknik'
    ],
    [
        'kode' => 'OTHER',
        'nama' => 'Program Studi Lainnya',
        'jumlah' => 'dan berbagai program studi lainnya',
        'is_text' => true,
        'icon' => 'fa-ellipsis',
        'color' => '#8a6b7c',
        'bg_color' => '#fdeef5',
        'fakultas' => 'Semua Fakultas'
    ]
];

// Ambil data dari database jika terhubung
$mahasiswa_list = [];
$total_mhs_db = 0;
$total_prodi_db = 0;
$total_fakultas_db = 0;

if ($db_connected) {
    $q_mhs = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM mahasiswa");
    if ($q_mhs && $row = mysqli_fetch_assoc($q_mhs)) {
        $total_mhs_db = (int)$row['total'];
    }

    $q_prd = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM program_studi");
    if ($q_prd && $row = mysqli_fetch_assoc($q_prd)) {
        $total_prodi_db = (int)$row['total'];
    }

    $q_fak = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM fakultas");
    if ($q_fak && $row = mysqli_fetch_assoc($q_fak)) {
        $total_fakultas_db = (int)$row['total'];
    }

    $q_list = mysqli_query($koneksi, "
        SELECT m.*, p.nama_prodi, f.nama_fakultas 
        FROM mahasiswa m 
        LEFT JOIN program_studi p ON m.kode_prodi = p.kode_prodi 
        LEFT JOIN fakultas f ON p.kode_fakultas = f.kode_fakultas
        ORDER BY m.npm ASC
    ");
    if ($q_list) {
        while ($row = mysqli_fetch_assoc($q_list)) {
            $mahasiswa_list[] = $row;
        }
    }
}

// Fallback dummy data jika data mahasiswa kosong
if (empty($mahasiswa_list)) {
    $mahasiswa_list = [
        ['npm' => '2023010001', 'nama_mahasiswa' => 'Raka Pratama', 'jenis_kelamin' => 'L', 'nama_prodi' => 'Teknik Informatika', 'nama_fakultas' => 'Teknik', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Melati No. 21, Bengkulu'],
        ['npm' => '2023010002', 'nama_mahasiswa' => 'Nadia Putri', 'jenis_kelamin' => 'P', 'nama_prodi' => 'Teknik Informatika', 'nama_fakultas' => 'Teknik', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Anggrek No. 7, Argamakmur'],
        ['npm' => '2023010003', 'nama_mahasiswa' => 'Fikri Hakim', 'jenis_kelamin' => 'L', 'nama_prodi' => 'Sistem Informasi', 'nama_fakultas' => 'Teknik', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Kenanga No. 15, Kepahiang'],
        ['npm' => '2023010004', 'nama_mahasiswa' => 'Salsabila Azzahra', 'jenis_kelamin' => 'P', 'nama_prodi' => 'Manajemen', 'nama_fakultas' => 'Ekonomi', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Cempaka No. 4, Bengkulu'],
        ['npm' => '2023010005', 'nama_mahasiswa' => 'Rahmat Hidayat', 'jenis_kelamin' => 'L', 'nama_prodi' => 'Teknik Mesin', 'nama_fakultas' => 'Teknik', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Adam Malik No. 12, Bengkulu'],
        ['npm' => '2023010006', 'nama_mahasiswa' => 'Nurlaila Sari', 'jenis_kelamin' => 'P', 'nama_prodi' => 'Ekonomi Syariah', 'nama_fakultas' => 'Ekonomi', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Danau Dendam No. 7, Bengkulu'],
        ['npm' => '2023010007', 'nama_mahasiswa' => 'Fajar Pratama', 'jenis_kelamin' => 'L', 'nama_prodi' => 'Agronomi', 'nama_fakultas' => 'Pertanian', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Suprapto No. 44, Bengkulu'],
        ['npm' => '2023010008', 'nama_mahasiswa' => 'Putri Ayu', 'jenis_kelamin' => 'P', 'nama_prodi' => 'Keperawatan', 'nama_fakultas' => 'Ilmu Kesehatan', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Salak No. 19, Bengkulu']
    ];
}

$is_logged_in = isset($_SESSION['user']);
$logged_user = $is_logged_in ? $_SESSION['user'] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa Per Program Studi | Universitas Contoh</title>
    <meta name="description" content="Akses, eksplorasi, dan manfaatkan data mahasiswa secara terbuka, terstruktur, dan terhubung berbasis Web Semantik untuk mendukung tata kelola universitas yang lebih baik.">
    
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #db2777;
            --primary-hover: #be185d;
            --primary-light: #fce7f3;
            --secondary-navy: #500724;
            --text-heading: #3b0a24;
            --text-body: #5b4756;
            --text-muted: #8a6b7c;
            --bg-body: #ffffff;
            --bg-alt: #fff7fb;
            --border-color: #f8dbe8;
            --card-shadow: 0 4px 20px -2px rgba(45, 16, 36, 0.05);
            --card-shadow-hover: 0 14px 30px -4px rgba(219, 39, 119, 0.12);
            --transition-smooth: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-full: 9999px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-body);
            background-color: var(--bg-body);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Notice Toast / Alert Banner */
        .alert-bar {
            padding: 12px 20px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .alert-bar.success {
            background: #dcfce7;
            color: #166534;
            border-bottom: 1px solid #bbf7d0;
        }
        .alert-bar.danger {
            background: #fee2e2;
            color: #991b1b;
            border-bottom: 1px solid #fecaca;
        }
        .alert-bar.info {
            background: #fdf2f8;
            color: #9d174d;
            border-bottom: 1px solid #fce7f3;
        }

        /* ========================================================
           HEADER & NAVIGATION BAR
           ======================================================== */
        .site-header {
            background: #ffffff;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid #fdeef5;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .emblem-wrapper {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, #db2777 0%, #be185d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(219, 39, 119, 0.25);
            position: relative;
            flex-shrink: 0;
        }

        .emblem-wrapper svg {
            width: 30px;
            height: 30px;
        }

        .brand-text h2 {
            font-size: 1.18rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.3px;
            line-height: 1.2;
        }

        .brand-text p {
            font-size: 0.76rem;
            font-weight: 500;
            color: #8a6b7c;
            letter-spacing: 0.2px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            list-style: none;
        }

        .nav-link {
            font-size: 0.92rem;
            font-weight: 500;
            color: #5b4756;
            transition: var(--transition-smooth);
            position: relative;
            padding: 8px 0;
        }

        .nav-link:hover {
            color: var(--primary-blue);
        }

        .nav-link.active {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2.5px;
            background: var(--primary-blue);
            border-radius: 2px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-login {
            background: var(--primary-blue);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 9px 24px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition-smooth);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(219, 39, 119, 0.25);
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(219, 39, 119, 0.35);
        }

        .btn-user-logged {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition-smooth);
        }

        .btn-user-logged:hover {
            background: #dcfce7;
            transform: translateY(-1px);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--text-heading);
            cursor: pointer;
        }

        /* ========================================================
           HERO SECTION
           ======================================================== */
        .hero-section {
            padding: 56px 0 60px;
            background: radial-gradient(circle at top right, rgba(252, 231, 243, 0.45) 0%, rgba(255, 255, 255, 0) 65%);
            position: relative;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            align-items: center;
            gap: 40px;
        }

        .hero-title {
            font-size: 2.85rem;
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1.15;
            letter-spacing: -0.8px;
            margin-bottom: 20px;
        }

        .hero-title .text-primary {
            color: var(--primary-blue);
            display: block;
        }

        .hero-subtitle {
            font-size: 1.02rem;
            color: #5b4756;
            line-height: 1.65;
            margin-bottom: 32px;
            max-width: 530px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 0.94rem;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px;
            transition: var(--transition-smooth);
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--primary-blue);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(219, 39, 119, 0.28);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1.5px);
            box-shadow: 0 8px 20px rgba(219, 39, 119, 0.35);
        }

        .btn-outline {
            background: #ffffff;
            color: var(--primary-blue);
            border: 1.5px solid #fbcfe8;
        }

        .btn-outline:hover {
            background: #fdf2f8;
            border-color: var(--primary-blue);
            transform: translateY(-1.5px);
        }

        .hero-quote {
            font-style: italic;
            color: #8a6b7c;
            font-size: 0.92rem;
        }

        /* HERO RIGHT: LAPTOP MOCKUP & RDF GRAPH */
        .hero-visual-wrapper {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-building-bg {
            position: absolute;
            top: -20px;
            right: -10px;
            width: 100%;
            height: 115%;
            background: radial-gradient(circle at 60% 30%, rgba(251, 207, 232, 0.4) 0%, rgba(255,255,255,0) 70%);
            z-index: 0;
            pointer-events: none;
            opacity: 0.85;
            display: flex;
            justify-content: flex-end;
            align-items: flex-start;
        }

        .hero-building-bg svg {
            width: 90%;
            height: auto;
            opacity: 0.22;
        }

        .semantic-laptop-composition {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 520px;
        }

        .floating-badge {
            position: absolute;
            top: 25px;
            right: -10px;
            background: #ffffff;
            padding: 12px 18px 14px;
            border-radius: 10px;
            box-shadow: 0 10px 25px -4px rgba(219, 39, 119, 0.15);
            border: 1px solid #fce7f3;
            z-index: 10;
            text-align: left;
            animation: floatSlow 5s ease-in-out infinite;
        }

        @keyframes floatSlow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        .floating-badge h4 {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--primary-blue);
            line-height: 1.25;
            margin-bottom: 6px;
        }

        .floating-badge .badge-line {
            width: 24px;
            height: 3px;
            background: var(--primary-blue);
            border-radius: 2px;
        }

        .laptop-container {
            width: 82%;
            margin-right: auto;
            perspective: 1000px;
        }

        .laptop-screen {
            background: #4a1d38;
            border-radius: 12px 12px 0 0;
            padding: 8px 8px 0;
            box-shadow: 0 18px 38px rgba(45, 16, 36, 0.22);
            border: 2px solid #4a3341;
            border-bottom: none;
        }

        .laptop-notch {
            width: 5px;
            height: 5px;
            background: #5b4756;
            border-radius: 50%;
            margin: 2px auto 6px;
        }

        .screen-display {
            background: #ffffff;
            border-radius: 6px 6px 0 0;
            padding: 14px 10px;
            height: 230px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            background: radial-gradient(circle at center, #fff7fb 0%, #ffffff 100%);
        }

        .rdf-graph-container {
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rdf-svg-lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .rdf-node {
            position: absolute;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transform: translate(-50%, -50%);
            transition: transform 0.2s;
        }

        .rdf-node:hover {
            transform: translate(-50%, -50%) scale(1.08);
        }

        .rdf-node .node-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.92rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            margin-bottom: 3px;
        }

        .rdf-node .node-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: #4a1d38;
            white-space: nowrap;
        }

        .node-mahasiswa { top: 25%; left: 24%; }
        .node-mahasiswa .node-icon { background: #ec4899; }

        .node-prodi { top: 25%; left: 76%; }
        .node-prodi .node-icon { background: #c026d3; }

        .node-matkul { top: 75%; left: 24%; }
        .node-matkul .node-icon { background: #10b981; }

        .node-univ { top: 75%; left: 76%; }
        .node-univ .node-icon { background: #ea580c; }

        .rdf-hub {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 3;
            background: #ffffff;
            padding: 4px 10px;
            border-radius: 20px;
            border: 2px solid #ec4899;
            box-shadow: 0 2px 8px rgba(236, 72, 153, 0.2);
            font-size: 0.72rem;
            font-weight: 800;
            color: #be185d;
            letter-spacing: 0.5px;
        }

        .laptop-base {
            height: 10px;
            background: #f0c2d6;
            border-radius: 0 0 16px 16px;
            position: relative;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .laptop-base::after {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 4px;
            background: #b59aa8;
            border-radius: 0 0 4px 4px;
        }

        .books-stack {
            position: absolute;
            bottom: 0px;
            right: 0px;
            width: 135px;
            z-index: 5;
            display: flex;
            flex-direction: column-reverse;
            gap: 2px;
        }

        .book-item {
            height: 24px;
            border-radius: 2px 5px 5px 2px;
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.6px;
            box-shadow: -2px 3px 6px rgba(0, 0, 0, 0.2);
            border-left: 4px solid rgba(255, 255, 255, 0.35);
            transition: var(--transition-smooth);
        }

        .book-item:hover {
            transform: translateX(-4px);
        }

        .book-linked { background: linear-gradient(90deg, #831843, #9d174d); }
        .book-sparql { background: linear-gradient(90deg, #831843, #a21c5a); }
        .book-owl    { background: linear-gradient(90deg, #4a1d38, #6b1140); }
        .book-rdf    { background: linear-gradient(90deg, #831843, #9d174d); }

        /* ========================================================
           FEATURE CARDS
           ======================================================== */
        .features-section {
            padding: 20px 0 50px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .feature-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 30px 20px;
            border: 1px solid var(--border-color);
            text-align: center;
            transition: var(--transition-smooth);
            box-shadow: var(--card-shadow);
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: #fbcfe8;
            box-shadow: var(--card-shadow-hover);
        }

        .feature-icon-circle {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin: 0 auto 18px;
            transition: var(--transition-smooth);
        }

        .feature-card:hover .feature-icon-circle {
            background: var(--primary-blue);
            color: #ffffff;
            transform: scale(1.08);
        }

        .feature-card h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* ========================================================
           PROGRAM STUDI SECTION
           ======================================================== */
        .prodi-section {
            padding: 40px 0 60px;
            background: #ffffff;
        }

        .section-header-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.4px;
            margin-bottom: 4px;
        }

        .section-subtitle {
            font-size: 0.94rem;
            color: var(--text-muted);
        }

        .link-view-all {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary-blue);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition-smooth);
            cursor: pointer;
        }

        .link-view-all:hover {
            color: var(--primary-hover);
            transform: translateX(3px);
        }

        .prodi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .prodi-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .prodi-card:hover {
            border-color: #f9a8d4;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(219, 39, 119, 0.12);
        }

        .prodi-info-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .prodi-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: var(--transition-smooth);
        }

        .prodi-card:hover .prodi-icon-wrap {
            transform: scale(1.05);
        }

        .prodi-text h4 {
            font-size: 0.94rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 2px;
        }

        .prodi-text span {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .prodi-arrow {
            color: #ec4899;
            font-size: 0.88rem;
            transition: var(--transition-smooth);
        }

        .prodi-card:hover .prodi-arrow {
            transform: translateX(4px);
            color: var(--primary-hover);
        }

        /* ========================================================
           STATISTICS SECTION
           ======================================================== */
        .stats-section {
            background: linear-gradient(180deg, #fdf2f8 0%, #fdf2f8 100%);
            padding: 50px 0;
            position: relative;
            border-top: 1px solid #fce7f3;
            border-bottom: 1px solid #fce7f3;
        }

        .stats-bg-watermark {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.06;
            background-image: radial-gradient(#db2777 1px, transparent 1px);
            background-size: 18px 18px;
            pointer-events: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            position: relative;
            z-index: 1;
        }

        .stat-item {
            text-align: center;
            padding: 0 20px;
            position: relative;
        }

        .stat-item:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 15%;
            height: 70%;
            width: 1px;
            background: #f0c2d6;
        }

        .stat-icon {
            font-size: 1.8rem;
            color: var(--primary-blue);
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.5px;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.88rem;
            font-weight: 600;
            color: #5b4756;
        }

        /* ========================================================
           ECOSYSTEM SECTION
           ======================================================== */
        .ecosystem-section {
            padding: 70px 0;
            background: #ffffff;
        }

        .ecosystem-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 48px;
            align-items: center;
        }

        .ecosystem-content h2 {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-heading);
            margin-bottom: 16px;
            letter-spacing: -0.4px;
        }

        .ecosystem-content p {
            font-size: 0.98rem;
            color: #5b4756;
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .quote-box {
            background: #ffffff;
            border-left: 4px solid var(--primary-blue);
            padding: 16px 20px;
        }

        .quote-box blockquote {
            font-style: italic;
            color: #5b4756;
            font-size: 0.96rem;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .quote-box cite {
            display: block;
            font-style: normal;
            font-size: 0.86rem;
            font-weight: 600;
            color: #4a3341;
        }

        /* ========================================================
           DATA EXPLORER
           ======================================================== */
        .data-explorer-section {
            padding: 50px 0 70px;
            background: #fff7fb;
            border-top: 1px solid var(--border-color);
        }

        .data-card-box {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .data-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .search-filter-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .search-input-box {
            position: relative;
        }

        .search-input-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #b59aa8;
            font-size: 0.88rem;
        }

        .search-input-box input {
            padding: 9px 16px 9px 38px;
            border: 1px solid #f0c2d6;
            border-radius: 8px;
            font-size: 0.88rem;
            width: 260px;
            outline: none;
            transition: var(--transition-smooth);
            font-family: inherit;
        }

        .search-input-box input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.15);
        }

        .filter-select {
            padding: 9px 16px;
            border: 1px solid #f0c2d6;
            border-radius: 8px;
            font-size: 0.88rem;
            outline: none;
            background-color: #ffffff;
            color: #4a3341;
            font-family: inherit;
            cursor: pointer;
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        .custom-table th {
            background: #fff7fb;
            color: #5b4756;
            font-weight: 700;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .custom-table td {
            padding: 14px 20px;
            border-bottom: 1px solid #fdeef5;
            color: #4a3341;
            vertical-align: middle;
        }

        .custom-table tr:hover td {
            background-color: #fff7fb;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 600;
        }

        .badge-blue { background: #fce7f3; color: #be185d; }
        .badge-purple { background: #fae8ff; color: #86198f; }

        .db-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .db-status-badge.online {
            background: #dcfce7;
            color: #166534;
        }
        .db-status-badge.demo {
            background: #fef3c7;
            color: #92400e;
        }

        /* ========================================================
           FOOTER
           ======================================================== */
        .site-footer {
            background: var(--secondary-navy);
            color: #b59aa8;
            padding: 60px 0 30px;
            border-top: 1px solid #4a1d38;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.3fr 0.9fr 0.9fr 1.3fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }

        .footer-brand .emblem-wrapper {
            background: linear-gradient(135deg, #be185d, #831843);
        }

        .footer-brand-text h3 {
            font-size: 1.15rem;
            color: #ffffff;
            font-weight: 700;
        }

        .footer-brand-text p {
            font-size: 0.76rem;
            color: #b59aa8;
        }

        .footer-desc {
            font-size: 0.85rem;
            line-height: 1.6;
            color: #b59aa8;
            max-width: 320px;
        }

        .footer-col h4 {
            color: #ffffff;
            font-size: 0.98rem;
            font-weight: 700;
            margin-bottom: 18px;
            letter-spacing: -0.2px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 10px;
        }

        .footer-col ul li a {
            font-size: 0.86rem;
            color: #b59aa8;
            transition: var(--transition-smooth);
        }

        .footer-col ul li a:hover {
            color: #ffffff;
            padding-left: 4px;
        }

        .contact-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.86rem;
            color: #b59aa8;
            margin-bottom: 14px;
        }

        .contact-list li i {
            color: #ec4899;
            margin-top: 4px;
            font-size: 0.95rem;
        }

        .footer-bottom {
            padding-top: 30px;
            border-top: 1px solid #4a1d38;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.84rem;
            flex-wrap: wrap;
            gap: 16px;
        }

        .social-icons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .social-icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #500724;
            color: #f0c2d6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-smooth);
            font-size: 0.9rem;
        }

        .social-icon-btn:hover {
            background: var(--primary-blue);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* ========================================================
           MODALS
           ======================================================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 24, 46, 0.65);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: #ffffff;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 480px;
            padding: 32px;
            position: relative;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.3);
            transform: scale(0.95);
            transition: transform 0.25s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #fdeef5;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8a6b7c;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .modal-close-btn:hover {
            background: #f8dbe8;
            color: #2d1024;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 0.86rem;
            font-weight: 600;
            color: #4a3341;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #f0c2d6;
            border-radius: 8px;
            font-size: 0.92rem;
            outline: none;
            font-family: inherit;
            transition: var(--transition-smooth);
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.15);
        }

        /* Mobile drawer */
        .mobile-drawer {
            position: fixed;
            top: 0;
            right: 0;
            width: 280px;
            height: 100%;
            background: #ffffff;
            z-index: 2500;
            box-shadow: -6px 0 25px rgba(0,0,0,0.15);
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            visibility: hidden;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s;
        }

        .mobile-drawer.open {
            transform: translateX(0);
            visibility: visible;
        }

        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(11, 24, 46, 0.5);
            backdrop-filter: blur(2px);
            z-index: 2400;
            display: none;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        .drawer-overlay.open {
            display: block;
            opacity: 1;
        }

        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .hero-subtitle {
                margin: 0 auto 32px;
            }
            .hero-actions {
                justify-content: center;
            }
            .hero-visual-wrapper {
                margin-top: 20px;
            }
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .prodi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }
            .stat-item:nth-child(2)::after {
                display: none;
            }
            .ecosystem-grid {
                grid-template-columns: 1fr;
            }
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-links, .nav-actions .btn-login, .nav-actions .btn-user-logged {
                display: none;
            }
            .mobile-menu-btn {
                display: block;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .prodi-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .stat-item::after {
                display: none !important;
            }
            .footer-grid {
                grid-template-columns: 1fr;
            }
            .hero-title {
                font-size: 2.2rem;
            }
            .footer-bottom {
                flex-direction: column-reverse;
                text-align: center;
            }
        }
    </style>
</head>
<body>

    <!-- Notification Alert Bar if redirected -->
    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'logout'): ?>
        <div class="alert-bar success">
            <div class="container" style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-check"></i>
                <span>Anda telah berhasil keluar dari sistem. Terima kasih!</span>
            </div>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert-bar danger">
            <div class="container" style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Gagal masuk. Mohon periksa kembali NPM dan kata sandi Anda.</span>
            </div>
        </div>
    <?php elseif ($is_logged_in): ?>
        <div class="alert-bar info">
            <div class="container" style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Halo <strong><?= htmlspecialchars($logged_user['nama']) ?></strong>, Anda sedang aktif di sistem.</span>
                </div>
                <a href="dashboard.php" style="font-weight: 700; text-decoration: underline;">
                    Buka Dashboard Portal Mahasiswa &rarr;
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================================
         HEADER / NAVBAR
         ======================================================== -->
    <header class="site-header">
        <div class="container">
            <nav class="navbar">
                <!-- University Logo & Identity -->
                <a href="index.php" class="brand-logo">
                    <div class="emblem-wrapper">
                        <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="50" cy="50" r="45" stroke="#fcd34d" stroke-width="4" stroke-dasharray="3 3"/>
                            <polygon points="50,15 61,38 85,38 66,54 73,78 50,63 27,78 34,54 15,38 39,38" fill="#fcd34d" opacity="0.95"/>
                            <circle cx="50" cy="50" r="16" fill="#9d174d"/>
                            <path d="M44 48L49 53L56 44" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="brand-text">
                        <h2>Universitas Contoh</h2>
                        <p>Unggul • Islam • Berkemajuan</p>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <ul class="nav-links">
                    <li><a href="#beranda" class="nav-link active">Beranda</a></li>
                    <li><a href="#data-mahasiswa" class="nav-link">Data Mahasiswa</a></li>
                    <li><a href="#program-studi" class="nav-link">Program Studi</a></li>
                    <li><a href="#tentang" class="nav-link">Tentang</a></li>
                    <li><a href="javascript:void(0)" onclick="openSemanticModal()" class="nav-link">Web Semantik</a></li>
                    <li><a href="#kontak" class="nav-link">Kontak</a></li>
                </ul>

                <!-- Login / Dashboard User Button -->
                <div class="nav-actions">
                    <?php if ($is_logged_in): ?>
                        <a href="dashboard.php" class="btn-user-logged">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </a>
                        <a href="logout.php" style="font-size: 0.84rem; color: #dc2626; padding: 6px 10px; border-radius: 6px;" title="Keluar">
                            <i class="fa-solid fa-power-off"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn-login" onclick="openLoginModal()">
                            <i class="fa-solid fa-user"></i> Login
                        </button>
                    <?php endif; ?>
                    <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Buka Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <!-- Mobile Navigation Drawer -->
    <div class="drawer-overlay" id="drawerOverlay" onclick="toggleMobileMenu()"></div>
    <div class="mobile-drawer" id="mobileDrawer">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div class="brand-text">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading);">Menu Utama</h3>
            </div>
            <button onclick="toggleMobileMenu()" style="background: none; border: none; font-size: 1.3rem; color: #8a6b7c; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 16px;">
            <li><a href="#beranda" onclick="toggleMobileMenu()" style="font-weight: 600; color: var(--primary-blue);">Beranda</a></li>
            <li><a href="#data-mahasiswa" onclick="toggleMobileMenu()" style="color: #5b4756;">Data Mahasiswa</a></li>
            <li><a href="#program-studi" onclick="toggleMobileMenu()" style="color: #5b4756;">Program Studi</a></li>
            <li><a href="#tentang" onclick="toggleMobileMenu()" style="color: #5b4756;">Tentang</a></li>
            <li><a href="javascript:void(0)" onclick="toggleMobileMenu(); openSemanticModal();" style="color: #5b4756;">Web Semantik</a></li>
            <li><a href="#kontak" onclick="toggleMobileMenu()" style="color: #5b4756;">Kontak</a></li>
        </ul>
        <div style="margin-top: auto; padding-top: 24px;">
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn-user-logged" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard Mahasiswa
                </a>
            <?php else: ?>
                <button class="btn-login" onclick="toggleMobileMenu(); openLoginModal();" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-user"></i> Login
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================
         HERO SECTION
         ======================================================== -->
    <section class="hero-section" id="beranda">
        <div class="container">
            <div class="hero-grid">
                <!-- Left Column -->
                <div class="hero-content">
                    <h1 class="hero-title">
                        Data Mahasiswa
                        <span class="text-primary">Per Program Studi</span>
                    </h1>
                    <p class="hero-subtitle">
                        Akses, eksplorasi, dan manfaatkan data mahasiswa secara terbuka, terstruktur, dan terhubung untuk mendukung tata kelola universitas yang lebih baik.
                    </p>
                    <div class="hero-actions">
                        <a href="#data-mahasiswa" class="btn btn-primary">
                            <i class="fa-solid fa-magnifying-glass"></i> Lihat Data Mahasiswa
                        </a>
                        <button onclick="openSemanticModal()" class="btn btn-outline">
                            <i class="fa-solid fa-book-open"></i> Pelajari Web Semantik
                        </button>
                    </div>
                    <p class="hero-quote">
                        &ldquo;Data yang terhubung, pengetahuan yang lebih luas&rdquo;
                    </p>
                </div>

                <!-- Right Column: Laptop 3D Mockup -->
                <div class="hero-visual-wrapper">
                    <div class="hero-building-bg">
                        <svg viewBox="0 0 450 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="70" y="70" width="310" height="210" fill="#f9a8d4" opacity="0.3" rx="4"/>
                            <polygon points="225,15 390,70 60,70" fill="#f472b6" opacity="0.35"/>
                            <rect x="100" y="100" width="40" height="60" fill="#fbcfe8" opacity="0.4" rx="2"/>
                            <rect x="160" y="100" width="40" height="60" fill="#fbcfe8" opacity="0.4" rx="2"/>
                            <rect x="250" y="100" width="40" height="60" fill="#fbcfe8" opacity="0.4" rx="2"/>
                            <rect x="310" y="100" width="40" height="60" fill="#fbcfe8" opacity="0.4" rx="2"/>
                            <rect x="200" y="190" width="50" height="90" fill="#fbcfe8" opacity="0.5" rx="3"/>
                            <text x="225" y="60" font-family="'Plus Jakarta Sans', sans-serif" font-size="12" font-weight="700" fill="#ec4899" text-anchor="middle" letter-spacing="1">UNIVERSITAS CONTOH</text>
                        </svg>
                    </div>

                    <div class="semantic-laptop-composition">
                        <div class="floating-badge">
                            <h4>Semantic Web<br>for a Smarter<br>University</h4>
                            <div class="badge-line"></div>
                        </div>

                        <div class="laptop-container">
                            <div class="laptop-screen">
                                <div class="laptop-notch"></div>
                                <div class="screen-display">
                                    <div class="rdf-graph-container">
                                        <svg class="rdf-svg-lines" viewBox="0 0 300 200">
                                            <line x1="72" y1="50" x2="150" y2="100" stroke="#f9a8d4" stroke-width="2.5" stroke-dasharray="4 3"/>
                                            <line x1="228" y1="50" x2="150" y2="100" stroke="#f0abfc" stroke-width="2.5" stroke-dasharray="4 3"/>
                                            <line x1="72" y1="150" x2="150" y2="100" stroke="#86efac" stroke-width="2.5" stroke-dasharray="4 3"/>
                                            <line x1="228" y1="150" x2="150" y2="100" stroke="#fdba74" stroke-width="2.5" stroke-dasharray="4 3"/>
                                        </svg>

                                        <div class="rdf-hub">RDF</div>

                                        <div class="rdf-node node-mahasiswa" title="Entitas Mahasiswa">
                                            <div class="node-icon"><i class="fa-solid fa-user"></i></div>
                                            <span class="node-label">Mahasiswa</span>
                                        </div>

                                        <div class="rdf-node node-prodi" title="Entitas Program Studi">
                                            <div class="node-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                                            <span class="node-label">Program Studi</span>
                                        </div>

                                        <div class="rdf-node node-matkul" title="Entitas Mata Kuliah">
                                            <div class="node-icon"><i class="fa-solid fa-book"></i></div>
                                            <span class="node-label">Mata Kuliah</span>
                                        </div>

                                        <div class="rdf-node node-univ" title="Entitas Universitas">
                                            <div class="node-icon"><i class="fa-solid fa-building-columns"></i></div>
                                            <span class="node-label">Universitas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="laptop-base"></div>
                        </div>

                        <div class="books-stack">
                            <div class="book-item book-linked" title="Linked Open Data">LINKED DATA</div>
                            <div class="book-item book-sparql" title="SPARQL Protocol and RDF Query Language">SPARQL</div>
                            <div class="book-item book-owl" title="Web Ontology Language">OWL</div>
                            <div class="book-item book-rdf" title="Resource Description Framework">RDF</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================
         FEATURE CARDS (4 COLUMNS)
         ======================================================== -->
    <section class="features-section">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-circle">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <h3>Data Terintegrasi</h3>
                    <p>Data mahasiswa terhubung dengan program studi, fakultas, dan informasi akademik lainnya.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-circle">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>
                    <h3>Standar Terbuka</h3>
                    <p>Menggunakan teknologi Web Semantik (RDF, OWL, SPARQL) untuk interoperabilitas data.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-circle">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                    <h3>Mudah Diakses</h3>
                    <p>Pencarian dan visualisasi data yang cepat dan interaktif.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon-circle">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3>Mendukung Transparansi</h3>
                    <p>Data yang terbuka untuk mendukung akreditasi, riset, dan pengambilan keputusan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================
         DAFTAR PROGRAM STUDI
         ======================================================== -->
    <section class="prodi-section" id="program-studi">
        <div class="container">
            <div class="section-header-row">
                <div>
                    <h2 class="section-title">Daftar Program Studi</h2>
                    <p class="section-subtitle">Pilih program studi untuk melihat data mahasiswa secara detail</p>
                </div>
                <a href="#data-mahasiswa" class="link-view-all">
                    Lihat Semua Program Studi <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="prodi-grid">
                <?php foreach ($prodi_default as $prodi): ?>
                    <div class="prodi-card" onclick="filterByProdi('<?= htmlspecialchars($prodi['nama']) ?>')">
                        <div class="prodi-info-left">
                            <div class="prodi-icon-wrap" style="background-color: <?= $prodi['bg_color'] ?>; color: <?= $prodi['color'] ?>;">
                                <i class="fa-solid <?= $prodi['icon'] ?>"></i>
                            </div>
                            <div class="prodi-text">
                                <h4><?= htmlspecialchars($prodi['nama']) ?></h4>
                                <span>
                                    <?php if (isset($prodi['is_text']) && $prodi['is_text']): ?>
                                        <?= htmlspecialchars($prodi['jumlah']) ?>
                                    <?php else: ?>
                                        <?= is_numeric($prodi['jumlah']) ? number_format($prodi['jumlah'], 0, ',', '.') : $prodi['jumlah'] ?> Mahasiswa
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="prodi-arrow">
                            <i class="fa-solid fa-arrow-right"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========================================================
         STATISTICS COUNTER BANNER
         ======================================================== -->
    <section class="stats-section">
        <div class="stats-bg-watermark"></div>
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="stat-number">7.842</div>
                    <div class="stat-label">Total Mahasiswa</div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-number">28</div>
                    <div class="stat-label">Program Studi</div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon"><i class="fa-solid fa-building-columns"></i></div>
                    <div class="stat-number">8</div>
                    <div class="stat-label">Fakultas</div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon"><i class="fa-solid fa-globe"></i></div>
                    <div class="stat-number" style="font-size: 1.45rem; padding-top: 5px;">Data Terhubung</div>
                    <div class="stat-label">dengan Web Semantik</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================
         CALLOUT: MEMBANGUN EKOSISTEM DATA TERBUKA
         ======================================================== -->
    <section class="ecosystem-section" id="tentang">
        <div class="container">
            <div class="ecosystem-grid">
                <div class="ecosystem-content">
                    <h2>Membangun Ekosistem Data Terbuka</h2>
                    <p>
                        Dengan pendekatan <strong>Web Semantik</strong>, data mahasiswa tidak hanya disimpan, tetapi juga dapat dipahami, dihubungkan, dan dimanfaatkan oleh berbagai aplikasi untuk mendukung pendidikan, penelitian, dan inovasi.
                    </p>
                    <button class="btn btn-primary" onclick="openSemanticModal()">
                        <i class="fa-solid fa-book-open"></i> Tentang Web Semantik
                    </button>
                </div>

                <div class="ecosystem-quote-wrapper">
                    <div class="quote-box">
                        <blockquote>
                            &ldquo;Web Semantik memungkinkan data di universitas tidak hanya dilihat oleh manusia, tetapi juga dipahami oleh mesin.&rdquo;
                        </blockquote>
                        <cite>— Menuju Universitas yang Lebih Cerdas</cite>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================
         INTERACTIVE DATA MAHASISWA EXPLORER
         ======================================================== -->
    <section class="data-explorer-section" id="data-mahasiswa">
        <div class="container">
            <div class="section-header-row" style="margin-bottom: 24px;">
                <div>
                    <h2 class="section-title">Eksplorasi Data Mahasiswa</h2>
                    <p class="section-subtitle">
                        Data mahasiswa terstruktur yang siap dihubungkan melalui Resource Description Framework (RDF)
                    </p>
                </div>
                <div>
                    <?php if ($db_connected): ?>
                        <span class="db-status-badge online" title="Terhubung ke Database MySQL">
                            <i class="fa-solid fa-circle-check"></i> Database Terhubung
                        </span>
                    <?php else: ?>
                        <span class="db-status-badge demo" title="Mode Demonstrasi Standar">
                            <i class="fa-solid fa-circle-info"></i> Mode Data Demo
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="data-card-box">
                <div class="data-card-header">
                    <div class="search-filter-wrap">
                        <div class="search-input-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="searchInput" placeholder="Cari NPM atau Nama Mahasiswa..." onkeyup="filterTable()">
                        </div>
                        <select class="filter-select" id="prodiSelect" onchange="filterTable()">
                            <option value="">Semua Program Studi</option>
                            <option value="Teknik Informatika">Teknik Informatika</option>
                            <option value="Sistem Informasi">Sistem Informasi</option>
                            <option value="Manajemen">Manajemen</option>
                            <option value="Ekonomi Syariah">Ekonomi Syariah</option>
                            <option value="Teknik Mesin">Teknik Mesin</option>
                            <option value="Agronomi">Agronomi</option>
                            <option value="Keperawatan">Keperawatan</option>
                        </select>
                    </div>
                    <div style="font-size: 0.86rem; color: #8a6b7c;" id="resultCount">
                        Menampilkan <strong><?= count($mahasiswa_list) ?></strong> data mahasiswa
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="custom-table" id="mahasiswaTable">
                        <thead>
                            <tr>
                                <th>NPM</th>
                                <th>Nama Mahasiswa</th>
                                <th>L/P</th>
                                <th>Program Studi</th>
                                <th>Fakultas</th>
                                <th>Tanggal Masuk</th>
                                <th>Alamat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mahasiswa_list as $mhs): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($mhs['npm']) ?></strong></td>
                                    <td><?= htmlspecialchars($mhs['nama_mahasiswa']) ?></td>
                                    <td>
                                        <span class="badge <?= $mhs['jenis_kelamin'] === 'L' ? 'badge-blue' : 'badge-purple' ?>">
                                            <?= $mhs['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($mhs['nama_prodi'] ?? 'Teknik Informatika') ?></td>
                                    <td><?= htmlspecialchars($mhs['nama_fakultas'] ?? 'Teknik') ?></td>
                                    <td><?= htmlspecialchars($mhs['tanggal_masuk'] ?? '2023-08-01') ?></td>
                                    <td><?= htmlspecialchars($mhs['alamat'] ?? 'Bengkulu') ?></td>
                                    <td>
                                        <button class="btn btn-outline" style="padding: 4px 10px; font-size: 0.78rem;" onclick="showDetailModal('<?= htmlspecialchars($mhs['npm']) ?>', '<?= htmlspecialchars($mhs['nama_mahasiswa']) ?>', '<?= htmlspecialchars($mhs['nama_prodi'] ?? 'Teknik Informatika') ?>', '<?= htmlspecialchars($mhs['alamat'] ?? 'Bengkulu') ?>')">
                                            <i class="fa-solid fa-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- ========================================================
         FOOTER SECTION
         ======================================================== -->
    <footer class="site-footer" id="kontak">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">
                        <div class="emblem-wrapper">
                            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="50" cy="50" r="45" stroke="#fcd34d" stroke-width="4" stroke-dasharray="3 3"/>
                                <polygon points="50,15 61,38 85,38 66,54 73,78 50,63 27,78 34,54 15,38 39,38" fill="#fcd34d"/>
                                <circle cx="50" cy="50" r="16" fill="#9d174d"/>
                            </svg>
                        </div>
                        <div class="footer-brand-text">
                            <h3>Universitas Contoh</h3>
                            <p>Unggul • Islam • Berkemajuan</p>
                        </div>
                    </div>
                    <p class="footer-desc">
                        Sistem integrasi data perguruan tinggi berbasis Web Semantik untuk riset, pelaporan terpadu, dan pertukaran pengetahuan antar entitas akademik.
                    </p>
                </div>

                <div class="footer-col">
                    <h4>Tautan Cepat</h4>
                    <ul>
                        <li><a href="#beranda">Beranda</a></li>
                        <li><a href="#data-mahasiswa">Data Mahasiswa</a></li>
                        <li><a href="#program-studi">Program Studi</a></li>
                        <li><a href="#tentang">Tentang</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Sumber Daya</h4>
                    <ul>
                        <li><a href="javascript:void(0)" onclick="openSemanticModal()">RDF</a></li>
                        <li><a href="javascript:void(0)" onclick="openSemanticModal()">OWL</a></li>
                        <li><a href="javascript:void(0)" onclick="openSemanticModal()">SPARQL</a></li>
                        <li><a href="javascript:void(0)" onclick="openSemanticModal()">Dokumentasi</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                        <h4 style="margin-bottom: 0;">Kontak</h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon-btn" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                            <a href="#" class="social-icon-btn" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                            <a href="#" class="social-icon-btn" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="#" class="social-icon-btn" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <ul class="contact-list">
                        <li>
                            <i class="fa-solid fa-location-dot"></i>
                            <span>Jl. Pendidikan No. 1, Kota Bengkulu</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-envelope"></i>
                            <span>info@universitascontoh.ac.id</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-phone"></i>
                            <span>+62 736 123456</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Universitas Contoh. All rights reserved.</p>
                <p style="color: #8a6b7c; font-size: 0.8rem;">
                    Portal Akademik & Semantik Data Mahasiswa
                </p>
            </div>
        </div>
    </footer>

    <!-- ========================================================
         MODAL: LOGIN PORTAL AKADEMIK (Form POST ke login.php)
         ======================================================== -->
    <div class="modal-overlay" id="loginModal" onclick="closeOnBackdrop(event, 'loginModal')">
        <div class="modal-content">
            <button class="modal-close-btn" onclick="closeModal('loginModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div style="text-align: center; margin-bottom: 24px;">
                <div class="emblem-wrapper" style="margin: 0 auto 12px; width: 52px; height: 52px;">
                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" stroke="#fcd34d" stroke-width="4" stroke-dasharray="3 3"/>
                        <polygon points="50,15 61,38 85,38 66,54 73,78 50,63 27,78 34,54 15,38 39,38" fill="#fcd34d"/>
                        <circle cx="50" cy="50" r="16" fill="#9d174d"/>
                    </svg>
                </div>
                <h3 style="font-size: 1.3rem; font-weight: 700; color: var(--text-heading);">Login Portal Akademik</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Masuk dengan NPM atau akun resmi universitas</p>
            </div>

            <!-- FORM LOGIN KE login.php -->
            <form action="login.php" method="POST">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="loginRole" style="display: block; font-size: 0.84rem; font-weight: 700; margin-bottom: 6px;">Masuk Sebagai</label>
                    <select name="role" id="loginRole" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #f0c2d6;" onchange="updateModalRole(this.value)">
                        <option value="mahasiswa">Mahasiswa (Gunakan NPM)</option>
                        <option value="prodi">Program Studi (Prodi)</option>
                        <option value="admin">Administrator Semantik</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label for="loginNPM" id="modalUserLabel" style="display: block; font-size: 0.84rem; font-weight: 700; margin-bottom: 6px;">NPM Mahasiswa</label>
                    <input type="text" name="username" id="loginNPM" class="form-control" style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #f0c2d6;" placeholder="Contoh: 2023010001" required autofocus>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label for="loginPassword" style="font-size: 0.84rem; font-weight: 700;">Kata Sandi</label>
                        <a href="login.php?tab=register" style="font-size: 0.78rem; color: var(--primary-blue); font-weight: 600; text-decoration: none;">Daftar Akun Baru</a>
                    </div>
                    <div style="position: relative;">
                        <input type="password" name="password" id="loginPassword" class="form-control" style="width: 100%; padding: 10px 38px 10px 12px; border-radius: 8px; border: 1px solid #f0c2d6;" placeholder="••••••••" required>
                        <button type="button" onclick="toggleModalPwd()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #b59aa8; cursor: pointer; padding: 4px;">
                            <i class="fa-solid fa-eye" id="modalEyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 700; border-radius: 8px;">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Sistem
                </button>
            </form>

            <!-- Akun Contoh Cepat -->
            <?php
            $quick_mhs = [];
            if (isset($koneksi) && $koneksi instanceof mysqli && !mysqli_connect_errno()) {
                $qm = mysqli_query($koneksi, "SELECT npm, nama_mahasiswa, password FROM mahasiswa ORDER BY npm ASC");
                if ($qm) {
                    while ($r = mysqli_fetch_assoc($qm)) {
                        $quick_mhs[] = $r;
                    }
                }
            }
            if (empty($quick_mhs)) {
                $quick_mhs = [
                    ['npm' => '2023010001', 'nama_mahasiswa' => 'Raka Pratama', 'password' => 'raka2023'],
                    ['npm' => '2023010002', 'nama_mahasiswa' => 'Nadia Putri', 'password' => 'nadia456'],
                    ['npm' => '2023010003', 'nama_mahasiswa' => 'Fikri Hakim', 'password' => 'fikri789'],
                    ['npm' => '2023010004', 'nama_mahasiswa' => 'Salsabila Azzahra', 'password' => 'salsa101']
                ];
            }
            ?>
            <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid #f8dbe8;">
                <span style="display: block; font-size: 0.76rem; font-weight: 700; color: #8a6b7c; text-transform: uppercase; margin-bottom: 8px;">
                    <i class="fa-solid fa-bolt" style="color: #f59e0b;"></i> Klik Akun Cepat Mahasiswa (<?= count($quick_mhs) ?> Akun):
                </span>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 6px; margin-bottom: 8px;">
                    <?php foreach ($quick_mhs as $qm): ?>
                        <button type="button" onclick="modalFill('mahasiswa', '<?= htmlspecialchars($qm['npm']) ?>', '<?= htmlspecialchars($qm['password'] ?? 'pass123') ?>')" style="background: #fff7fb; border: 1px solid #f0c2d6; border-radius: 6px; padding: 6px 4px; font-size: 0.72rem; cursor: pointer; text-align: center;">
                            <strong><?= htmlspecialchars(explode(' ', $qm['nama_mahasiswa'])[0]) ?></strong><br><span style="color:#8a6b7c; font-size: 0.68rem;"><?= htmlspecialchars($qm['npm']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                    <button type="button" onclick="modalFill('prodi', 'TI', 'prodi123')" style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 6px 4px; font-size: 0.72rem; cursor: pointer; text-align: center;">
                        <strong>Prodi TI</strong><br><span style="color:#16a34a; font-size: 0.68rem;">Program Studi</span>
                    </button>
                    <button type="button" onclick="modalFill('admin', 'admin', 'admin123')" style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: 6px 4px; font-size: 0.72rem; cursor: pointer; text-align: center;">
                        <strong>Admin</strong><br><span style="color:#dc2626; font-size: 0.68rem;">Administrator</span>
                    </button>
                </div>
            </div>

            <div style="margin-top: 16px; text-align: center;">
                <a href="login.php" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; font-weight: 700; color: var(--primary-blue); text-decoration: none;">
                    <span>Buka Halaman Login Penuh & Registrasi</span>
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.74rem;"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================
         MODAL: TENTANG WEB SEMANTIK
         ======================================================== -->
    <div class="modal-overlay" id="semanticModal" onclick="closeOnBackdrop(event, 'semanticModal')">
        <div class="modal-content" style="max-width: 600px;">
            <button class="modal-close-btn" onclick="closeModal('semanticModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div style="margin-bottom: 20px;">
                <span class="badge badge-blue" style="margin-bottom: 8px;">Semantic Web Technology</span>
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-heading);">Web Semantik & Linked Data</h3>
            </div>
            
            <p style="font-size: 0.9rem; color: #5b4756; line-height: 1.6; margin-bottom: 16px;">
                Web Semantik adalah evolusi dari web dokumen menjadi web data di mana informasi diberikan makna yang terdefinisi secara eksplisit, sehingga komputer dan manusia dapat bekerja sama secara kooperatif.
            </p>

            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                <div style="background: #fff7fb; border: 1px solid #f8dbe8; border-radius: 8px; padding: 12px 16px;">
                    <h5 style="color: var(--primary-blue); font-size: 0.92rem; font-weight: 700; margin-bottom: 4px;">
                        <i class="fa-solid fa-circle-nodes"></i> RDF (Resource Description Framework)
                    </h5>
                    <p style="font-size: 0.84rem; color: #8a6b7c;">
                        Model representasi data berbasis subjek-predikat-objek (triples) untuk merepresentasikan entitas mahasiswa, prodi, dan fakultas.
                    </p>
                </div>

                <div style="background: #fff7fb; border: 1px solid #f8dbe8; border-radius: 8px; padding: 12px 16px;">
                    <h5 style="color: #a21caf; font-size: 0.92rem; font-weight: 700; margin-bottom: 4px;">
                        <i class="fa-solid fa-project-diagram"></i> OWL (Web Ontology Language)
                    </h5>
                    <p style="font-size: 0.84rem; color: #8a6b7c;">
                        Bahasa ontologi untuk mendefinisikan relasi hierarki, klasifikasi keilmuan, dan inferensi relasi antar entitas perguruan tinggi.
                    </p>
                </div>

                <div style="background: #fff7fb; border: 1px solid #f8dbe8; border-radius: 8px; padding: 12px 16px;">
                    <h5 style="color: #059669; font-size: 0.92rem; font-weight: 700; margin-bottom: 4px;">
                        <i class="fa-solid fa-terminal"></i> SPARQL
                    </h5>
                    <p style="font-size: 0.84rem; color: #8a6b7c;">
                        Protokol dan bahasa kueri canggih untuk mengambil dan memanipulasi data yang disimpan dalam format RDF.
                    </p>
                </div>
            </div>

            <button class="btn btn-primary" onclick="closeModal('semanticModal')" style="width: 100%;">
                Tutup Informasi
            </button>
        </div>
    </div>

    <!-- ========================================================
         MODAL: DETAIL MAHASISWA
         ======================================================== -->
    <div class="modal-overlay" id="detailModal" onclick="closeOnBackdrop(event, 'detailModal')">
        <div class="modal-content">
            <button class="modal-close-btn" onclick="closeModal('detailModal')">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-heading); margin-bottom: 16px;">
                Detail Informasi Mahasiswa
            </h3>
            <div style="background: #fff7fb; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <p style="font-size: 0.86rem; margin-bottom: 8px;"><strong>NPM:</strong> <span id="modalNpm">-</span></p>
                <p style="font-size: 0.86rem; margin-bottom: 8px;"><strong>Nama:</strong> <span id="modalNama">-</span></p>
                <p style="font-size: 0.86rem; margin-bottom: 8px;"><strong>Program Studi:</strong> <span id="modalProdi">-</span></p>
                <p style="font-size: 0.86rem;"><strong>Alamat:</strong> <span id="modalAlamat">-</span></p>
            </div>
            <button class="btn btn-primary" onclick="closeModal('detailModal')" style="width: 100%;">
                Selesai
            </button>
        </div>
    </div>

    <!-- ========================================================
         JAVASCRIPT
         ======================================================== -->
    <script>
        function toggleMobileMenu() {
            const drawer = document.getElementById('mobileDrawer');
            const overlay = document.getElementById('drawerOverlay');
            drawer.classList.toggle('open');
            overlay.classList.toggle('open');
        }

        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
        }

        function openSemanticModal() {
            document.getElementById('semanticModal').classList.add('active');
        }

        function showDetailModal(npm, nama, prodi, alamat) {
            document.getElementById('modalNpm').textContent = npm;
            document.getElementById('modalNama').textContent = nama;
            document.getElementById('modalProdi').textContent = prodi;
            document.getElementById('modalAlamat').textContent = alamat;
            document.getElementById('detailModal').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function closeOnBackdrop(e, id) {
            if (e.target.id === id) {
                closeModal(id);
            }
        }

        function filterTable() {
            const searchVal = document.getElementById('searchInput').value.toLowerCase();
            const prodiVal = document.getElementById('prodiSelect').value.toLowerCase();
            const table = document.getElementById('mahasiswaTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                const npmText = rows[i].cells[0].textContent.toLowerCase();
                const nameText = rows[i].cells[1].textContent.toLowerCase();
                const prodiText = rows[i].cells[3].textContent.toLowerCase();

                const matchSearch = npmText.includes(searchVal) || nameText.includes(searchVal);
                const matchProdi = prodiVal === '' || prodiText.includes(prodiVal);

                if (matchSearch && matchProdi) {
                    rows[i].style.display = '';
                    visibleCount++;
                } else {
                    rows[i].style.display = 'none';
                }
            }

            document.getElementById('resultCount').innerHTML = 
                'Menampilkan <strong>' + visibleCount + '</strong> data mahasiswa';
        }

        function filterByProdi(namaProdi) {
            if (namaProdi.includes('Lainnya')) {
                document.getElementById('prodiSelect').value = '';
            } else {
                const select = document.getElementById('prodiSelect');
                let found = false;
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].text.toLowerCase() === namaProdi.toLowerCase()) {
                        select.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    select.value = '';
                }
            }
            filterTable();
            document.getElementById('data-mahasiswa').scrollIntoView({ behavior: 'smooth' });
        }

        function updateModalRole(role) {
            const label = document.getElementById('modalUserLabel');
            const input = document.getElementById('loginNPM');
            if (role === 'mahasiswa') {
                label.textContent = 'NPM Mahasiswa';
                input.placeholder = 'Contoh: 2023010001';
            } else if (role === 'prodi' || role === 'dosen') {
                label.textContent = 'Kode / Akun Prodi';
                input.placeholder = 'Contoh: TI, SI, MJ atau prodi';
            } else if (role === 'admin') {
                label.textContent = 'Username Admin';
                input.placeholder = 'Contoh: admin';
            }
        }

        function toggleModalPwd() {
            const pwdInput = document.getElementById('loginPassword');
            const eyeIcon = document.getElementById('modalEyeIcon');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        function modalFill(role, user, pass) {
            document.getElementById('loginRole').value = role;
            updateModalRole(role);
            document.getElementById('loginNPM').value = user;
            document.getElementById('loginPassword').value = pass;
        }
    </script>
</body>
</html>
