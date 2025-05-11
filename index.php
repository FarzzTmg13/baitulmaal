<?php
// file dashboard.php
session_start();
include 'db_config.php'; // Pastikan untuk menyertakan file config database Anda
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hero Section</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
        }
        
        /* Hero Section Styles */
        .hero {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
            background-color: white;
            color: #333;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 768px) {
            .hero {
                flex-direction: row;
                min-height: 500px;
                padding: 0 50px;
            }
            .hero-content {
                text-align: left;
                max-width: 600px;
                margin-right: 40px;
            }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
            line-height: 1.2;
            color: #219150;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
            color: #555;
        }
        
        .cta-button {
            background-color: #219150;
            color: white;
            padding: 15px 30px;
            font-size: 1rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .cta-button:hover {
            background-color: #1a7a43;
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .hero-image {
            width: 100%;
            max-width: 550px;
            height: auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .hero-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
        }
        
        .hero-image:hover img {
            transform: scale(1.05);
        }
        
        .hero-image::before {
            content: '';
            position: absolute;
            top: -20px;
            left: -20px;
            width: 100%;
            height: 100%;
            border: 2px dashed rgba(52, 211, 153, 0.3);
            border-radius: 15px;
            z-index: -1;
        }

        /* Decorative elements */
        .hero-image::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: -15px;
            width: 80px;
            height: 80px;
            background-color: #219150;
            opacity: 0.2;
            border-radius: 12px;
            z-index: -1;
        }

        .hero-image .decorative-circle {
            position: absolute;
            top: -15px;
            right: -15px;
            width: 60px;
            height: 60px;
            background-color: #34d399;
            opacity: 0.2;
            border-radius: 50%;
            z-index: -1;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-image {
                max-width: 400px;
            }
            
            .hero-image::before,
            .hero-image::after,
            .hero-image .decorative-circle {
                display: none; /* Hide decorative elements on mobile */
            }
        }

        /* Partner Section Styles */
        .partners-section {
            padding: 60px 20px;
            background-color: #f8fafc;
            text-align: center;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: #219150;
            margin-bottom: 40px;
        }
        
        .partners-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .partner-card {
            background-color: white;
            border-radius: 12px;
            padding: 30px;
            width: 280px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .partner-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 20px rgba(0, 0, 0, 0.1);
        }
        
        .partner-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 3px solid #e2e8f0;
        }
        
        .partner-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .partner-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .partner-role {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Auth buttons styles */
        .auth-buttons {
            display: flex;
            gap: 10px;
        }
        
        .auth-button {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .login-btn {
            background-color: #f1f5f9;
            color: #334155;
        }
        
        .login-btn:hover {
            background-color: #e2e8f0;
        }
        
        .register-btn {
            background-color: #219150;
            color: white;
        }
        
        .register-btn:hover {
            background-color: #1a7a43;
        }
    </style>
</head>
<body>
    <!-- Header Section - Matching the first example -->
    <header class="flex flex-col md:flex-row justify-between items-center mb-8 p-4 bg-white shadow-sm">
        <div class="mb-4 md:mb-0">
            <a href="index.php" class="flex items-center">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-mosque text-[#219150] mr-2"></i> Baitulmaal Dashboard
                </h1>
            </a>
            <p class="text-gray-600">Manajemen keuangan masjid yang transparan</p>
        </div>
        
        <div class="flex items-center space-x-4">
            <?php if (isset($_SESSION['username'])): ?>
                <!-- Tampilkan info user jika sudah login -->
                <div class="text-right">
                    <p class="text-sm text-gray-500">Halo,</p>
                    <p class="font-medium text-blue-600"><?= htmlspecialchars($_SESSION['username']) ?></p>
                </div>
                <div class="relative group">
                    <button class="flex items-center space-x-1 px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        <i class="fas fa-bell text-gray-600"></i>
                        <span class="hidden md:inline">Notifikasi</span>
                    </button>
                    <div class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg py-2 z-10 hidden group-hover:block">
                        <a href="#" class="block px-4 py-2 hover:bg-gray-100">Pemberitahuan 1</a>
                        <a href="#" class="block px-4 py-2 hover:bg-gray-100">Pemberitahuan 2</a>
                        <div class="border-t border-gray-200"></div>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-500 hover:bg-gray-100">Lihat semua</a>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i> <span class="hidden md:inline">Logout</span>
                </a>
            <?php else: ?>
                <!-- Tampilkan tombol login/register jika belum login -->
                <div class="auth-buttons">
                    <a href="login.php" class="auth-button login-btn">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login
                    </a>
                    <a href="signup.php" class="auth-button register-btn">
                        <i class="fas fa-user-plus mr-2"></i> Daftar
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <!-- Content -->
        <div class="hero-content">
            <h1>Berikan Donasi Anda untuk Masa Depan yang Lebih Baik</h1>
            <p>
                Bergabunglah dengan kami dalam usaha membangun dan memajukan masjid melalui donasi Anda. Setiap kontribusi akan sangat berarti bagi perkembangan masjid kita.
            </p>
            <a href="dashboard.php" class="cta-button">
                <i class="fas fa-tachometer-alt mr-2"></i> Lihat Dashboard
            </a>
        </div>
        
        <!-- Image Container -->
        <div class="hero-image">
            <img src="masjid.jpeg" alt="Masjid dengan arsitektur modern">
            <div class="decorative-circle"></div>
        </div>
    </section>

    <!-- Our Partner Section -->
    <section class="partners-section">
        <h2 class="section-title">Tim Kami</h2>
        <div class="partners-container">
            <!-- Partner 1 -->
            <div class="partner-card">
                <div class="partner-avatar">
                    <img src="fairuz.jpg" alt="fairuz">
                </div>
                <h3 class="partner-name">Gahyaka Ararya Fairuz</h3>
                <p class="partner-role">Perancang Konsep</p>
            </div>
            
            <!-- Partner 2 -->
            <div class="partner-card">
                <div class="partner-avatar">
                    <img src="fariz.jpg" alt="fariz">
                </div>
                <h3 class="partner-name">Fariz Husain Albar</h3>
                <p class="partner-role">Programmer Utama</p>
            </div>
            
            <!-- Partner 3 -->
            <div class="partner-card">
                <div class="partner-avatar">
                    <img src="faiz.jpg" alt="faiz">
                </div>
                <h3 class="partner-name">Faiz Satria Ahimsa</h3>
                <p class="partner-role">Pemikir Kreatif</p>
            </div>
        </div>
    </section>
    
    <footer class="bg-[#219150] text-white py-12 px-4 mt-12">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Logo dan Deskripsi -->
                <div class="col-span-1">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-mosque text-2xl mr-2"></i>
                        <h2 class="text-xl font-bold">Baitulmaal</h2>
                    </div>
                    <p class="text-gray-100 mb-4">Platform manajemen keuangan masjid berbasis digital yang transparan dan akuntabel.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-100 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-100 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-100 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-100 hover:text-white transition">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Kontak -->
                <div class="col-span-1">
                    <h3 class="text-lg font-semibold mb-4 border-b border-[#34d399] pb-2">Hubungi Kami</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-[#34d399]"></i>
                            <span>Jl. Laksda Adisucipto, Papringan, Caturtunggal, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55281</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone-alt mt-1 mr-3 text-[#34d399]"></i>
                            <span>+6289661832244</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope mt-1 mr-3 text-[#34d399]"></i>
                            <span>info@baitulmaal.id</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-[#34d399] mt-8 pt-8 text-center text-gray-100">
                <p>&copy; <?php echo date('Y'); ?> Baitulmaal Dashboard. All Rights Reserved.</p>
                <p class="mt-2 text-sm">Dibangun dengan <i class="fas fa-heart text-red-400"></i> untuk ummat</p>
            </div>
        </div>
    </footer>

    <!-- Font Awesome untuk ikon -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>