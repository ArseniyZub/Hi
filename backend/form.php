<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=webform;charset=utf8',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Ошибка подключения к БД"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?: [];

// Опциональные поля заявки
$name    = trim($data["name"]    ?? "");
$phone   = trim($data["phone"]   ?? "");
$email   = trim($data["email"]   ?? "");
$comment = trim($data["comment"] ?? "");

function generateLogin($pdo) {
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";

    for ($i = 0; $i < 10; $i++) {
        $login = "user_";
        for ($j = 0; $j < 6; $j++) {
            $login .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if (!$stmt->fetch()) {
            return $login;
        }
    }
    return "user_" . time();
}

function generatePassword() {
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#\$%";
    $pass = "";
    for ($i = 0; $i < 10; $i++) {
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pass;
}

try {
    $login    = generateLogin($pdo);
    $password = generatePassword();

    $stmt = $pdo->prepare("
        INSERT INTO users (login, password, name, phone, email, comment)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $login,
        $password,
        $name,
        $phone,
        $email,
        $comment
    ]);

    echo json_encode([
        "success"  => true,
        "id"       => $pdo->lastInsertId(),
        "login"    => $login,
        "password" => $password
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Ошибка БД: " . $e->getMessage()
    ]);
}