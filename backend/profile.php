<?php

session_start();

if (empty($_SESSION["auth"])) {

    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Профиль</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 40px;
        }

        .card {
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        a {
            display: inline-block;
            margin-top: 20px;
        }
    </style>

</head>

<body>

    <div class="card">

        <h1>Личный кабинет</h1>

        <p>Вы успешно вошли в систему.</p>

        <a href="admin.php">
            Перейти в админку
        </a>

    </div>

</body>

</html>