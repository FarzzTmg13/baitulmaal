-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql201.infinityfree.com
-- Waktu pembuatan: 09 Bulan Mei 2025 pada 04.24
-- Versi server: 10.6.19-MariaDB
-- Versi PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38583122_baitulmaal`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('cash','bank','digital') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `type`, `is_active`, `created_at`) VALUES
(1, 'Tunai', 'cash', 1, '2025-04-11 11:23:14'),
(2, 'Transfer Bank', 'bank', 1, '2025-04-11 11:23:14'),
(3, 'OVO', 'digital', 1, '2025-04-11 11:23:14'),
(4, 'Dana', 'digital', 1, '2025-04-11 11:23:14'),
(5, 'Gopay', 'digital', 1, '2025-04-11 11:23:14'),
(6, 'ShopeePay', 'digital', 1, '2025-04-11 11:23:14'),
(7, 'Bank Mandiri', 'bank', 1, '2025-04-11 11:23:14'),
(8, 'Bank BCA', 'bank', 1, '2025-04-11 11:23:14'),
(9, 'Bank BRI', 'bank', 1, '2025-04-11 11:23:14'),
(10, 'Bank BNI', 'bank', 1, '2025-04-11 11:23:14'),
(11, 'LinkAja', 'digital', 1, '2025-04-11 11:23:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `jenis` enum('pemasukan','pengeluaran') NOT NULL,
  `nama` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `jumlah` double NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `tanggal` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id`, `jenis`, `nama`, `deskripsi`, `jumlah`, `payment_method`, `payment_reference`, `tanggal`) VALUES
(73, 'pemasukan', 'Fariz Husain Albar', 'Zakat', 50000, NULL, NULL, '2025-04-19'),
(74, 'pemasukan', 'Gahyaka Ararya Fairuz', 'Infaq', 45000, NULL, NULL, '2025-04-19'),
(75, 'pengeluaran', 'Faiz Satria Ahimsa', 'Membeli Al-Quran', 10000, 'ShopeePay', '089654398291', '2025-04-19'),
(77, 'pemasukan', 'Myuki', 'Pembangunan Masjid', 0, 'ShopeePay', '085365716968', '2025-04-24');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$bY0k.LtF9U4BDpbDibQOHeg0uSLeC/HR./G/ygUyVKFAQeFAEQojK', 'admin', '2025-03-22 10:21:06'),
(3, 'user', '$2y$10$HVK5pv0U9kXsMsfmWEI14ea7cG44xsg.Yxwvse0Qpcmha6r/WEHM.', 'user', '2025-03-22 10:23:07'),
(7, 'admin1', '$2y$10$8Fl3hTO5IxwQ65W7KQproe8whSDGqbSCbLXNUWrp1iq.YeMhzEE/i', 'user', '2025-04-03 15:56:23'),
(8, 'user0', '$2y$10$T490SRJsp1uBEMdZ35kA.OViHzkXFFJ4S4qIRkfiPilrwckNg1xUO', 'user', '2025-04-06 15:29:41'),
(9, 'fariz123', '$2y$10$0eesrD2Tw9J5tXynw7KJBOZT1zH7ximKreWckcRjt7fBLfAhKFk8m', 'user', '2025-04-12 02:29:47'),
(10, 'cek', '$2y$10$.FDtHrd.am6prr1swSVu6u/DEMg7SdTNsulWxeOCUWOIjx5pc6rWi', 'user', '2025-04-12 03:51:37'),
(11, 'farz', '$2y$10$vwwz03TDeyPOHTurBz6.8OvGVUQ4vR3DW//GyBHPGT8NM8zyI2Siy', 'user', '2025-04-13 22:46:28'),
(12, 'user1', '$2y$10$pLnhLvrbHJRFV7RDxHZaDOGm4wPb31iUfVOvLobF234UuGuVPDxjK', 'user', '2025-04-14 06:25:18'),
(13, 'mfajri856@gmail.com', '$2y$10$N/HIHY4M/BiARemjjkcL7ui0qx0QJ5iYOG24gF8O0nH1s0xExiKzi', 'user', '2025-04-14 14:16:39');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
