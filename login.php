<?php
include 'db_config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            echo "<script>alert('Login gagal! Password salah.');</script>";
        }
    } else {
        echo "<script>alert('Login gagal! Username tidak ditemukan.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Baitul Maal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom right, #e8f5e9, #ffffff);
            background-image: url('https://www.transparenttextures.com/patterns/arabesque.png');
            background-repeat: repeat;
            font-size: 14px;
        }

        .container {
            width: 90%;
            max-width: 420px;
            min-width: 325px;
            margin: 30px auto;
            background: white;
            padding: 25px 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
            position: relative;
            z-index: 10;
        }

        h2 {
            color: #27ae60;
            text-align: center;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        form {
            display: grid;
            gap: 12px;
        }

        label {
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 4px;
            display: block;
            font-size: 0.9rem;
        }

        .input-group {
            display: flex;
            align-items: center;
            background: #f8f8f8;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 0 10px;
        }

        .input-group svg {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            flex-shrink: 0;
            fill: #27ae60;
        }

        .input-group input {
            border: none;
            outline: none;
            padding: 12px 0;
            font-size: 0.9rem;
            background: transparent;
            flex-grow: 1;
        }

        button {
            background: #27ae60;
            color: white;
            font-weight: bold;
            border: none;
            padding: 12px;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: background 0.3s ease-in-out;
            cursor: pointer;
            margin-top: 5px;
        }

        button:hover {
            background: #219150;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
            font-size: 0.85rem;
        }

        .register-link a {
            color: #27ae60;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .donation-section {
            margin-top: 30px;
            text-align: center;
            color: #555;
        }

        .donation-section svg {
            width: 100px;
            height: auto;
            margin-bottom: 15px;
        }

        .donation-text {
            font-size: 0.8rem;
            color: #444;
            line-height: 1.5;
            padding: 0 5px;
        }

        .donation-text strong {
            display: block;
            margin-top: 6px;
            color: #27ae60;
            font-size: 0.85rem;
        }

        .donation-text em {
            font-size: 0.75rem;
            display: block;
            margin-top: 5px;
        }

        .ornament {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://www.transparenttextures.com/patterns/asfalt-dark.png');
            opacity: 0.05;
            z-index: 1;
            border-radius: 16px;
            pointer-events: none;
        }

        @media (max-width: 420px) {
            .container {
                margin: 20px auto;
                padding: 20px 15px;
            }
            
            h2 {
                font-size: 1.3rem;
            }
            
            .input-group input {
                padding: 10px 0;
            }
            
            .donation-section svg {
                width: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="ornament"></div>
        <h2>Login ke Baitul Maal</h2>
        <form method="POST">
            <label for="username">Username</label>
            <div class="input-group">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                </svg>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required>
            </div>

            <label for="password">Password</label>
            <div class="input-group">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M17 8h-1V6c0-2.8-2.2-5-5-5S6 3.2 6 6v2H5c-1.1 0-2 .9-2 2v10c0 
                    1.1.9 2 2 2h12c1.1 0 2-.9 
                    2-2V10c0-1.1-.9-2-2-2zM8 6c0-1.7 1.3-3 
                    3-3s3 1.3 3 3v2H8V6zm9 14H5V10h12v10z"/>
                </svg>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit">Masuk</button>
        </form>

        <div class="register-link">
            <p>Belum punya akun? <a href="signup.php">Daftar di sini</a></p>
        </div>

        <div class="donation-section">
            <svg viewBox="0 0 64 64" fill="#27ae60" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 2C30 6 26 10 20 12c6 2 10 6 12 10 2-4 6-8 12-10-6-2-10-6-12-10zM12 22v6H6v28h52V28h-6v-6h-8v6h-8v-6h-8v6h-8v-6h-8zm2 8h8v6h8v-6h8v6h8v-6h6v20H10V30h4z"/>
            </svg>
            <p class="donation-text">
                "Setiap tetes kebaikan yang kau beri, akan tumbuh menjadi pohon pahala yang rimbun di akhirat."
                <strong>"Perumpamaan orang yang menginfakkan hartanya seperti sebutir biji yang menumbuhkan tujuh tangkai..."</strong>
                <em>– QS. Al-Baqarah: 261</em>
            </p>
        </div>
    </div>
</body>
</html>