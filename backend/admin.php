<?php

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=webform;charset=utf8',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}


if (isset($_POST['create_user'])) {
    $login    = trim($_POST['login']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $name     = trim($_POST['name']     ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $email    = trim($_POST['email']    ?? '');
    $comment  = trim($_POST['comment']  ?? '');

    if ($login && $password) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (login, password, name, phone, email, comment)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$login, $password, $name, $phone, $email, $comment]);
        } catch (PDOException $e) {
            $create_error = "Не удалось создать: " . $e->getMessage();
        }
    } else {
        $create_error = "Логин и пароль обязательны";
    }

    if (empty($create_error)) {
        header("Location: admin.php");
        exit();
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    header("Location: admin.php");
    exit();
}

// UPDATE
if (isset($_POST['update_user'])) {
    $id = (int) $_POST['id'];
    $stmt = $pdo->prepare("
        UPDATE users
        SET login=?, password=?, name=?, phone=?, email=?, comment=?
        WHERE id=?
    ");
    $stmt->execute([
        $_POST['login'],
        $_POST['password'],
        $_POST['name'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['comment'],
        $id
    ]);
    header("Location: admin.php");
    exit();
}


$stmt  = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$edit_id = $_GET['edit'] ?? null;

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка — Пользователи</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 30px;
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }

        .container { max-width: 1400px; margin: auto; }

        .card {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        h1 { margin: 0 0 8px; color: #222; }

        .subtitle {
            color: #888;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .warning {
            background: #fff8e1;
            color: #7a5300;
            padding: 8px 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 3px solid #f5b400;
        }

        .badge {
            display: inline-block;
            background: #eee;
            color: #555;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: normal;
            margin-left: 8px;
        }

        h2 { margin: 0 0 16px; color: #444; font-size: 18px; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }

        th, td {
            border: 1px solid #e5e5e5;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th { background: #f8f8f8; font-weight: 600; font-size: 13px; }

        input, textarea {
            width: 100%;
            padding: 5px 7px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font: inherit;
        }

        textarea { resize: vertical; min-height: 40px; }

        .btn {
            padding: 5px 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin-right: 3px;
            margin-bottom: 3px;
            white-space: nowrap;
        }

        .btn.edit    { background: #ddd; color: #111; }
        .btn.delete  { background: #ffb3b3; color: #111; }
        .btn.save    { background: #b3ffb3; color: #111; }
        .btn.cancel  { background: #f0f0f0; color: #555; }
        .btn.primary { background: #2b7fff; color: #fff; }

        .btn:hover { opacity: 0.85; }

        .empty {
            text-align: center;
            padding: 24px;
            color: #999;
        }

        .error {
            background: #fee;
            color: #a00;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .add-form {
            display: grid;
            grid-template-columns: repeat(6, 1fr) auto;
            gap: 8px;
            margin-bottom: 20px;
            padding: 12px;
            background: #f8f8f8;
            border-radius: 6px;
            align-items: start;
        }

        .add-form input,
        .add-form textarea { font-size: 13px; }

        .add-form .btn { align-self: stretch; padding: 8px 14px; font-size: 13px; }

        .password-cell {
            font-family: 'Courier New', monospace;
            color: #d63384;
            word-break: break-all;
        }

        .login-cell { font-weight: 600; }

        td.actions { width: 180px; }

        .nowrap { white-space: nowrap; }
    </style>
</head>

<body>

<div class="container">

    <div class="card">

        <h1>Пользователи <span class="badge"><?= count($users) ?></span></h1>
        <div class="subtitle">Таблица <code>users</code> базы <code>webform</code></div>

        <div class="warning">
            ⚠ Авторизация отключена. Не публикуйте эту страницу в открытом доступе.
        </div>

        <?php if (!empty($create_error)): ?>
            <div class="error"><?= htmlspecialchars($create_error) ?></div>
        <?php endif; ?>

        <!-- Форма добавления -->
        <form method="POST" class="add-form">
            <input type="text"     name="login"    placeholder="Логин *"   required>
            <input type="text"     name="password" placeholder="Пароль *"  required>
            <input type="text"     name="name"     placeholder="Имя">
            <input type="text"     name="phone"    placeholder="Телефон">
            <input type="email"    name="email"    placeholder="Email">
            <textarea              name="comment"  placeholder="Комментарий"></textarea>
            <button type="submit" name="create_user" class="btn primary">
                + Добавить
            </button>
        </form>

        <?php if (empty($users)): ?>
            <div class="empty">Пользователей пока нет</div>
        <?php else: ?>
            <table>
                <tr>
                    <th style="width:50px">ID</th>
                    <th>Логин</th>
                    <th>Пароль</th>
                    <th>Имя</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Комментарий</th>
                    <th>Действия</th>
                </tr>

                <?php foreach ($users as $u): ?>
                    <tr>
                        <?php if ($edit_id == $u['id']): ?>
                            <form method="POST">
                                <td>
                                    <?= $u['id'] ?>
                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                </td>
                                <td><input name="login"    value="<?= htmlspecialchars($u['login']) ?>"    required></td>
                                <td><input name="password" value="<?= htmlspecialchars($u['password']) ?>" required></td>
                                <td><input name="name"     value="<?= htmlspecialchars($u['name'] ?? '') ?>"></td>
                                <td><input name="phone"    value="<?= htmlspecialchars($u['phone'] ?? '') ?>"></td>
                                <td><input name="email"    value="<?= htmlspecialchars($u['email'] ?? '') ?>"></td>
                                <td><textarea name="comment"><?= htmlspecialchars($u['comment'] ?? '') ?></textarea></td>
                                <td class="actions">
                                    <button type="submit" name="update_user" class="btn save">Сохранить</button>
                                    <a class="btn cancel" href="admin.php">Отмена</a>
                                </td>
                            </form>
                        <?php else: ?>
                            <td><?= $u['id'] ?></td>
                            <td class="login-cell"><?= htmlspecialchars($u['login']) ?></td>
                            <td class="password-cell"><?= htmlspecialchars($u['password']) ?></td>
                            <td><?= htmlspecialchars($u['name'] ?? '') ?></td>
                            <td class="nowrap"><?= htmlspecialchars($u['phone'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['comment'] ?? '') ?></td>
                            <td class="actions">
                                <a class="btn edit" href="?edit=<?= $u['id'] ?>">Редактировать</a>
                                <a class="btn delete"
                                   href="?delete=<?= $u['id'] ?>"
                                   onclick="return confirm('Удалить пользователя <?= htmlspecialchars($u['login']) ?>?')">
                                    Удалить
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

    </div>

</div>

</body>
</html>