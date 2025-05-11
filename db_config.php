<?php
$host = "sql201.infinityfree.com";
$user = "if0_38583122";
$pass = "5bU5SKDN4D6v3k";
$dbname = "if0_38583122_baitulmaal";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>