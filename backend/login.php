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

$login    = trim($data["login"]    ?? "");
$password = trim($data["password"] ?? "");

if (!$login || !$password) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Введите логин и пароль"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, login, name, email, phone
        FROM users
        WHERE login = ? AND password = ?
        LIMIT 1
    ");
    $stmt->execute([$login, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            "success" => true,
            "user"    => $user
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Неверный логин или пароль"
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Ошибка БД"
    ]);
}