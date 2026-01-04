<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'utils.php'; 
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>العيادة الذكية</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>العيادة الذكية</h1>
            <nav>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php">الرئيسية</a>
                    <a href="logout.php">خروج</a>
                <?php else: ?>
                    <a href="login.php">دخول</a>
                    <a href="register.php">تسجيل</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">