<?php
session_start();

$error = '';

$adminLogin = 'hotel123';

// Вставь сюда хеш, который получил через generate_hash.php
$adminPasswordHash = '$2y$10$Pe26e3PEXTrdyGys8bX5KuDb1.L.ns2scSGxqXqxiUVTBD20pTL12';

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '') {
        $error = 'Введите логин.';
    } elseif ($password === '') {
        $error = 'Введите пароль.';
    } elseif (mb_strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов.';
    } elseif ($login === $adminLogin && password_verify($password, $adminPasswordHash)) {
        session_regenerate_id(true);

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login'] = $adminLogin;

        header('Location: admin.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>HotelSales — Вход администратора</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <h1>HotelSales</h1>
    <p>Вход администратора</p>
</header>

<main class="main">
    <section class="login-page">
        <form method="POST" action="login.php" class="login-form">
            <h2>Авторизация</h2>

            <?php if ($error): ?>
                <div class="message message-error">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <label>
                Логин*
                <input 
                    type="text" 
                    name="login"
                    autocomplete="username"
                    value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                >
            </label>

            <label>
                Пароль*
                <input 
                    type="password" 
                    name="password"
                    autocomplete="current-password"
                >
            </label>

            <button type="submit" class="btn btn-login">
                Войти
            </button>

            <a href="catalog.php" class="login-back-link">
                Вернуться в каталог
            </a>
        </form>
    </section>
</main>

<footer class="footer">
    <h2>HotelSales</h2>

    <p>Режим работы:</p>
    <p>Пн-Пт: 9:00-20:00<br>Сб-Вс: 10:00-18:00</p>

    <p>Адрес<br>Email<br>+7 (xxx) xxx xx-xx</p>
</footer>

</body>
</html>