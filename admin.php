<?php
session_start();

if (
    empty($_SESSION['admin_logged_in']) ||
    empty($_SESSION['admin_login']) ||
    $_SESSION['admin_login'] !== 'hotel123'
) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$errors = [];
$success = '';

$allowedStatuses = ['new', 'approved', 'rejected'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = $_POST['booking_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$bookingId || !filter_var($bookingId, FILTER_VALIDATE_INT)) {
        $errors[] = 'Некорректный номер заявки.';
    }

    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = 'Некорректный статус заявки.';
    }

    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE bookings
                SET status = ?
                WHERE booking_id = ?
            ");

            $updateStmt->execute([$status, $bookingId]);

            $success = 'Статус заявки успешно обновлён.';
        } catch (PDOException $e) {
            $errors[] = 'Ошибка обновления статуса: ' . $e->getMessage();
        }
    }
}

try {
    $stmt = $pdo->query("
        SELECT 
            bookings.booking_id,
            bookings.guest_name,
            bookings.guest_surname,
            bookings.phone,
            bookings.email,
            bookings.check_in,
            bookings.check_out,
            bookings.status,
            bookings.created_at,
            rooms.title AS room_title,
            rooms.price,
            rooms.image,
            categories.name AS category_name,
            categories.color AS category_color
        FROM bookings
        INNER JOIN rooms ON bookings.room_id = rooms.room_id
        INNER JOIN categories ON rooms.category_id = categories.category_id
        ORDER BY bookings.created_at DESC
    ");

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Ошибка получения заявок: ' . $e->getMessage());
}

function getStatusText($status)
{
    switch ($status) {
        case 'new':
            return 'Новая';
        case 'approved':
            return 'Подтверждена';
        case 'rejected':
            return 'Отклонена';
        default:
            return 'Неизвестно';
    }
}

function getStatusClass($status)
{
    switch ($status) {
        case 'new':
            return 'status-new';
        case 'approved':
            return 'status-approved';
        case 'rejected':
            return 'status-rejected';
        default:
            return '';
    }
}

function formatDateRu($date)
{
    return date('d.m.Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>HotelSales — Админ-панель</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <h1>HotelSales</h1>
    <p>Администрирование заявок</p>
</header>

<main class="main">
<div class="admin-top">
    <h2>Заявки на бронирование</h2>

    <div class="admin-top-actions">
        <a href="catalog.php" class="btn btn-admin-back">Вернуться в каталог</a>
        <a href="logout.php" class="btn btn-logout">Выйти</a>
    </div>
</div>

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

        <?php if (empty($bookings)): ?>
            <p class="empty-message">Заявок на бронирование пока нет.</p>
        <?php else: ?>
            <div class="admin-bookings">
                <?php foreach ($bookings as $booking): ?>
                    <article class="admin-card">
                        <div class="admin-room-image">
                            <img 
                                src="images/<?= htmlspecialchars($booking['image']) ?>" 
                                alt="<?= htmlspecialchars($booking['room_title']) ?>"
                            >
                        </div>

                        <div class="admin-card-info">
                            <div class="admin-card-header">
                                <div>
                                    <h3><?= htmlspecialchars($booking['room_title']) ?></h3>
                                    <p class="admin-category">
                                        <?= htmlspecialchars($booking['category_name']) ?>
                                    </p>
                                </div>

                                <span class="booking-status <?= htmlspecialchars(getStatusClass($booking['status'])) ?>">
                                    <?= htmlspecialchars(getStatusText($booking['status'])) ?>
                                </span>
                            </div>

                            <div class="admin-details">
                                <p>
                                    <strong>Гость:</strong>
                                    <?= htmlspecialchars($booking['guest_name']) ?>
                                    <?= htmlspecialchars($booking['guest_surname']) ?>
                                </p>

                                <p>
                                    <strong>Телефон:</strong>
                                    <?= htmlspecialchars($booking['phone']) ?>
                                </p>

                                <p>
                                    <strong>Email:</strong>
                                    <?= htmlspecialchars($booking['email']) ?>
                                </p>

                                <p>
                                    <strong>Даты:</strong>
                                    <?= htmlspecialchars(formatDateRu($booking['check_in'])) ?>
                                    —
                                    <?= htmlspecialchars(formatDateRu($booking['check_out'])) ?>
                                </p>

                                <p>
                                    <strong>Цена:</strong>
                                    <?= number_format($booking['price'], 0, '', '') ?>₽ / ночь
                                </p>

                                <p>
                                    <strong>Создана:</strong>
                                    <?= htmlspecialchars(date('d.m.Y H:i', strtotime($booking['created_at']))) ?>
                                </p>
                            </div>

                            <div class="admin-actions">
                                <form method="POST" action="admin.php">
                                    <input 
                                        type="hidden" 
                                        name="booking_id" 
                                        value="<?= htmlspecialchars($booking['booking_id']) ?>"
                                    >
                                    <input type="hidden" name="status" value="approved">

                                    <button 
                                        type="submit" 
                                        class="btn btn-approve"
                                        <?= $booking['status'] === 'approved' ? 'disabled' : '' ?>
                                    >
                                        Подтвердить
                                    </button>
                                </form>

                                <form method="POST" action="admin.php">
                                    <input 
                                        type="hidden" 
                                        name="booking_id" 
                                        value="<?= htmlspecialchars($booking['booking_id']) ?>"
                                    >
                                    <input type="hidden" name="status" value="rejected">

                                    <button 
                                        type="submit" 
                                        class="btn btn-reject"
                                        <?= $booking['status'] === 'rejected' ? 'disabled' : '' ?>
                                    >
                                        Отклонить
                                    </button>
                                </form>

                                <form method="POST" action="admin.php">
                                    <input 
                                        type="hidden" 
                                        name="booking_id" 
                                        value="<?= htmlspecialchars($booking['booking_id']) ?>"
                                    >
                                    <input type="hidden" name="status" value="new">

                                    <button 
                                        type="submit" 
                                        class="btn btn-new"
                                        <?= $booking['status'] === 'new' ? 'disabled' : '' ?>
                                    >
                                        Вернуть в новые
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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