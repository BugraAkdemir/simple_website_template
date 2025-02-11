<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title>Banlandınız</title>
    <style>
        /* CSS kodunu doğrudan buraya yazıyoruz */
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .container h1 {
            color: #e74c3c;
        }
        .container p {
            margin: 10px 0;
        }
        .contact-info {
            margin-top: 20px;
        }
        .contact-info p {
            margin: 5px 0;
            color: #555;
        }
        .ip-address {
            font-weight: bold;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Banlandınız</h1>
        <p>IP adresiniz sistemimizde yasaklı olarak kayıtlıdır. Lütfen daha fazla bilgi için site yöneticisi ile iletişime geçin.</p>
        <p class="ip-address">
            IP Adresiniz:
            <?php
            // Kullanıcının IP adresini almak için PHP kodu
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');
            ?>
        </p>
        <div class="contact-info">
            <p><strong>Site Adı:</strong> bugraa.com</p>
            <p><strong>İletişim:</strong> dev@bugraa.com</p>
            <p><strong>Telefon:</strong> +90 537 315 42 37</p>
        </div>
    </div>
</body>
</html>