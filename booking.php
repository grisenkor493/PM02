<?php
require_once 'config.php';

$errors = [];
$success = '';

$roomId = $_GET['room_id'] ?? $_POST['room_id'] ?? null;

if (!$roomId || !filter_var($roomId, FILTER_VALIDATE_INT)) {
    die('Ошибка: номер не найден.');
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            rooms.*, 
            categories.name AS category_name, 
            categories.color AS category_color
        FROM rooms
        INNER JOIN categories ON rooms.category_id = categories.category_id
        WHERE rooms.room_id = ?
    ");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        die('Ошибка: выбранный номер не найден.');
    }
} catch (PDOException $e) {
    die('Ошибка получения номера: ' . $e->getMessage());
}

$name = '';
$surname = '';
$phone = '';
$email = '';
$checkIn = '';
$checkOut = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['guest_name'] ?? '');
    $surname = trim($_POST['guest_surname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');

    if ($name === '') {
    $errors[] = 'Введите имя.';
} elseif (!preg_match('/^[А-Яа-яЁё\s.-]+$/u', $name)) {
    $errors[] = 'Имя может содержать только кириллицу, пробелы, точки и тире.';
}

    if ($surname === '') {
    $errors[] = 'Введите фамилию.';
} elseif (!preg_match('/^[А-Яа-яЁё\s.-]+$/u', $surname)) {
    $errors[] = 'Фамилия может содержать только кириллицу, пробелы, точки и тире.';
}

    if ($phone === '') {
    $errors[] = 'Введите телефон.';
} elseif (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $phone)) {
    $errors[] = 'Введите телефон в формате +7(XXX)XXX-XX-XX.';
}

    if ($email === '') {
        $errors[] = 'Введите e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный e-mail.';
    }

    if ($checkIn === '') {
        $errors[] = 'Выберите дату заезда.';
    }

    if ($checkOut === '') {
        $errors[] = 'Выберите дату выезда.';
    }

    if ($checkIn !== '' && $checkOut !== '') {
        $today = date('Y-m-d');

        if ($checkIn < $today) {
            $errors[] = 'Дата заезда не может быть раньше сегодняшнего дня.';
        }

        if ($checkOut <= $checkIn) {
            $errors[] = 'Дата выезда должна быть позже даты заезда.';
        }
    }

    if (empty($errors)) {
    try {
        $busyStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM bookings
            WHERE room_id = ?
            AND status IN ('new', 'approved')
            AND check_in < ?
            AND check_out > ?
        ");

        $busyStmt->execute([
            $roomId,
            $checkOut,
            $checkIn
        ]);

        $busyCount = $busyStmt->fetchColumn();

        if ($busyCount > 0) {
            $errors[] = 'К сожалению, выбранный номер уже занят на указанные даты.';
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO bookings 
                (room_id, guest_name, guest_surname, phone, email, check_in, check_out)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->execute([
                $roomId,
                $name,
                $surname,
                $phone,
                $email,
                $checkIn,
                $checkOut
            ]);

            $success = 'Заявка успешно отправлена. Администратор рассмотрит бронирование в ближайшее время.';

            $name = '';
            $surname = '';
            $phone = '';
            $email = '';
            $checkIn = '';
            $checkOut = '';
        }

    } catch (PDOException $e) {
        $errors[] = 'Ошибка сохранения заявки: ' . $e->getMessage();
    }
}
}

function getCategoryTextClass($color)
{
    switch ($color) {
        case 'green':
            return 'category-text-green';
        case 'blue':
            return 'category-text-blue';
        case 'red':
            return 'category-text-red';
        default:
            return '';
    }
}

$categoryTextClass = getCategoryTextClass($room['category_color']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сладкие сны — Бронирование номера</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <h1>Сладкие сны</h1>
    <p>Бронирование номера</p>
</header>

<main class="main">
    <section class="booking-page">

        <div class="booking-left">
            <div class="booking-image">
                <img 
                    src="images/<?= htmlspecialchars($room['image']) ?>" 
                    alt="<?= htmlspecialchars($room['title']) ?>"
                >
            </div>

            <div class="rating-row">
                <div class="stars">★★★★★</div>
                <div class="rating-value">9.8</div>
                <a href="catalog.php" class="btn btn-details">Назад</a>
                

            </div>

            <p class="reviews-count">На основе 124 отзывов</p>
        </div>

        <div class="booking-right">
            <p class="booking-category <?= htmlspecialchars($categoryTextClass) ?>">
                <?= htmlspecialchars($room['category_name']) ?>
            </p>
            

            <p class="booking-price">
                <?= number_format($room['price'], 0, '', '') ?>₽ / Ночь
            </p>

            <p class="booking-characteristics">
                <?= htmlspecialchars($room['characteristics']) ?>
            </p>

            <?php if (!empty($errors)): ?>
                <div class="message message-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message message-success">
                    <p><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="booking.php" class="booking-form">
                <input 
                    type="hidden" 
                    name="room_id" 
                    value="<?= htmlspecialchars($room['room_id']) ?>"
                >

                <label>
                    Имя*
                    <input 
                        type="text" 
                        name="guest_name" 
                        value="<?= htmlspecialchars($name) ?>"
                    >
                </label>

                <label>
                    Фамилия*
                    <input 
                        type="text" 
                        name="guest_surname" 
                        value="<?= htmlspecialchars($surname) ?>"
                    >
                </label>

                <label>
    Телефон*
    <input
        type="tel" 
        name="phone" 
        id="phoneInput"
        placeholder="+7(___)___-__-__"
        maxlength="16"
        value="<?= htmlspecialchars($phone) ?>"
    >
</label>

                <label>
                    Email*
                    <input 
                        type="text" 
                        name="email" 
                        value="<?= htmlspecialchars($email) ?>"
                    >
                </label>

                <div class="date-row">
                    <label>
                        Дата заезда*
                        <input 
                            type="date" 
                            name="check_in" 
                            value="<?= htmlspecialchars($checkIn) ?>"
                        >
                    </label>

                    <label>
                        Дата выезда*
                        <input 
                            type="date" 
                            name="check_out" 
                            value="<?= htmlspecialchars($checkOut) ?>"
                        >
                    </label>
                </div>

                <p class="required-note">* обязательные поля</p>

                <div class="booking-actions">

                    <button type="submit" class="btn btn-booking-submit">
                        Отправить заявку
                    </button>
                </div>
            </form>
        </div>

    </section>
</main>

<footer class="footer">
    <h2>Сладкие сны</h2>

    <p>Режим работы:</p>
    <p>Пн-Пт: 9:00-20:00<br>Сб-Вс: 10:00-18:00</p>

    <p>Адрес<br>Email<br>+7 (xxx) xxx xx-xx</p>
</footer>

<script>
    const phoneInput = document.getElementById('phoneInput');

    phoneInput.addEventListener('input', function () {
        let digits = phoneInput.value.replace(/\D/g, '');

        if (digits.startsWith('8')) {
            digits = '7' + digits.slice(1);
        }

        if (!digits.startsWith('7')) {
            digits = '7' + digits;
        }

        digits = digits.slice(0, 11);

        let result = '+7';

        if (digits.length > 1) {
            result += '(' + digits.slice(1, 4);
        }

        if (digits.length >= 4) {
            result += ')';
        }

        if (digits.length > 4) {
            result += digits.slice(4, 7);
        }

        if (digits.length > 7) {
            result += '-' + digits.slice(7, 9);
        }

        if (digits.length > 9) {
            result += '-' + digits.slice(9, 11);
        }

        phoneInput.value = result;
    });

    phoneInput.addEventListener('focus', function () {
        if (phoneInput.value === '') {
            phoneInput.value = '+7(';
        }
    });
</script>

</body>
</html>

