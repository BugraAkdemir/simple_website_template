<?php
include "mainTools.php";
$ddd = $databaseVerisi;

// Ban Veritabanı bağlantısı
$ban_dsn = "mysql:host=localhost;dbname={$ddd}banlist;charset=utf8";
$ban_username = 'bigracom_bugra';
$ban_password = 'bugra2005bugra';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $ban_pdo = new PDO($ban_dsn, $ban_username, $ban_password, $options);
} catch (PDOException $e) {
    die("Ban veritabanı bağlantısı hatası: " . $e->getMessage());
}

// Ziyaretçi Veritabanı bağlantısı
$visit_dsn = "mysql:host=localhost;dbname={$ddd}visits;charset=utf8";
$visit_username = 'bigracom_bugra';
$visit_password = 'bugra2005bugra';

try {
    $visit_pdo = new PDO($visit_dsn, $visit_username, $visit_password, $options);
} catch (PDOException $e) {
    die("Ziyaretçi veritabanı bağlantısı hatası: " . $e->getMessage());
}

// Kullanıcının IP adresini öğren
function get_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

$user_ip = get_ip_address();

// IP adresinin banlı olup olmadığını kontrol et
function is_ip_banned($ban_pdo, $ip_address) {
    $stmt = $ban_pdo->prepare("SELECT COUNT(*) FROM banned_ips WHERE ip_address = :ip_address");
    $stmt->execute(['ip_address' => $ip_address]);
    return $stmt->fetchColumn() > 0;
}

if (is_ip_banned($ban_pdo, $user_ip)) {
    header("Location: ban.php");
    exit;
}

// Ziyaretçi verisini veritabanına ekle
function add_visitor_data($visit_pdo, $ip_address) {
    $stmt = $visit_pdo->prepare("INSERT INTO visitor_data (ip_address, visit_time) VALUES (:ip_address, NOW())");
    $stmt->execute(['ip_address' => $ip_address]);
}

add_visitor_data($visit_pdo, $user_ip);


?>
<html>
<head>
<meta
  name="description"
  content="Bugra Akdemir Blog Sayfası. Ben Bugra Akdemir">
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Buğra Akdmeir</title>
  <meta content="Ben Bugra Akdemir Bir Mobil Geliştiriciyim" name="description">
  <meta content="Hey Selem Ben Bugra Akdemir. Sitemi Siyaret Etmeye Ne dersin" name="keywords">
  <link rel="icon" href="favicon.ico">
    <style>
        @import url("https://fonts.googleapis.com/css?family=Montserrat:400,400i,700");

          html,
          body,
          .main-wrapper {
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;
          }

          .main-wrapper {
            font-size: 15vmin;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: auto;
            float: right;
            z-index: 999;
            position: fixed;
          }

          .aciklama {
            color: #ffffff;
            font-family: Montserrat, sans-serif;
            font-size: 3vmin;
            background-color: black;
            height: auto;
            display: block, flex;
            position: absolute;
            text-shadow: 0 -0.015em #be2b00;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 100%;
            bottom: 0;
            text-decoration: none;
          }

          .aciklama a {
            text-decoration: none;
            color: #ffffff;
            font-family: Montserrat, sans-serif;
            font-size: 3vmin;
          }


          .signboard-wrapper {
            width: 105vmin;
            height: 55vmin;
            position: relative;
            flex-shrink: 0;
            transform-origin: center 2.5vmin;
            animation: 1000ms init forwards, 1000ms init-sign-move ease-out 1000ms, 3000ms sign-move 2000ms infinite;
          }

          .signboard-wrapper .signboard {
            color: #ffffff;
            font-family: Montserrat, sans-serif;
            font-weight: bold;
            background-color: #ff5625;
            width: 100vmin;
            height: 35vmin;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            bottom: 0;
            border-radius: 4vmin;
            text-shadow: 0 -0.015em #be2b00;
          }

          .signboard-wrapper .string {
            width: 30vmin;
            height: 30vmin;
            border: solid 0.9vmin #893d00;
            border-bottom: none;
            border-right: none;
            position: absolute;
            left: 50%;
            transform-origin: top left;
            transform: rotatez(45deg);
          }

          .signboard-wrapper .pin {
            width: 5vmin;
            height: 5vmin;
            position: absolute;
            border-radius: 50%;
          }

          .signboard-wrapper .pin.pin1 {
            background-color: #9f9f9f;
            top: 0;
            left: calc(50% - 2.5vmin);
          }

          .signboard-wrapper .pin.pin2,
          .signboard-wrapper .pin.pin3 {
            background-color: #d83000;
            top: 21.5vmin;
          }

          .signboard-wrapper .pin.pin2 {
            left: 13vmin;
          }

          .signboard-wrapper .pin.pin3 {
            right: 13vmin;
          }

          @keyframes init {
            0% {
              transform: scale(0);
            }

            40% {
              transform: scale(1.1);
            }

            60% {
              transform: scale(0.9);
            }

            80% {
              transform: scale(1.05);
            }

            100% {
              transform: scale(1);
            }
          }

          @keyframes init-sign-move {
            100% {
              transform: rotatez(3deg);
            }
          }

          @keyframes sign-move {
            0% {
              transform: rotatez(3deg);
            }

            50% {
              transform: rotatez(-3deg);
            }

            100% {
              transform: rotatez(3deg);
            }
          }

    </style>
</head>
<body>

<div class="main-wrapper">
<div class="signboard-wrapper">
<div class="signboard">Bakımdayız</div>
<div class="string"></div>
<div class="pin pin1"></div>
<div class="pin pin2"></div>
<div class="pin pin3"></div>
</div>
</div>
<div class="aciklama">
    <h1>bigra.com.tr</h1>
    <h2>Bakım çalışmaları devam ediyor.</h2>
    <p>Lütfen daha sonra tekrar ziyaret edin.</p>
    <p>İletişim: dev@bigra.com.tr</p>
    <p><a href="https://bugraa.com">bugraa.com.tr</a></p>

</div>
</body>
</html>