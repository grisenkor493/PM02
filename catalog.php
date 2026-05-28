<?php
require_once 'config.php';

$selectedCategories = $_GET['categories'] ?? [];

if (!is_array($selectedCategories)) {
    $selectedCategories = [];
}

$selectedCategories = array_filter($selectedCategories, function ($categoryId) {
    return filter_var($categoryId, FILTER_VALIDATE_INT);
});

try {
    $categoryStmt = $pdo->query("SELECT * FROM categories");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($selectedCategories)) {
        $placeholders = implode(',', array_fill(0, count($selectedCategories), '?'));

        $stmt = $pdo->prepare("
            SELECT 
                rooms.*, 
                categories.name AS category_name, 
                categories.color AS category_color
            FROM rooms
            INNER JOIN categories ON rooms.category_id = categories.category_id
            WHERE rooms.category_id IN ($placeholders)
            ORDER BY RAND()
            LIMIT 5
        ");

        $stmt->execute($selectedCategories);
    } else {
        $stmt = $pdo->query("
            SELECT 
                rooms.*, 
                categories.name AS category_name, 
                categories.color AS category_color
            FROM rooms
            INNER JOIN categories ON rooms.category_id = categories.category_id
            ORDER BY RAND()
            LIMIT 5
        ");
    }

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Ошибка получения данных: ' . $e->getMessage());


    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Ошибка получения данных: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">ы
<head>
    <meta charset="UTF-8">
    <title>Сладкие сны</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="header">
    <h1>Сладкие сны</h1>
    <p>Приезжайте как гости, уезжайте как друзья!</p>
</header>

<main class="main">
    <section class="catalog">
<form class="filter" method="GET" action="catalog.php" id="filterForm">
    <div class="filter-categories">
        <?php foreach ($categories as $category): ?>
            <label class="filter-item">
                <input 
                    type="checkbox" 
                    name="categories[]" 
                    value="<?= htmlspecialchars($category['category_id']) ?>"
                    <?= in_array($category['category_id'], $selectedCategories) ? 'checked' : '' ?>
                >

                <span class="category-btn category-<?= htmlspecialchars($category['color']) ?>">
                    <?= htmlspecialchars($category['name']) ?>
                </span>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="filter-actions">
        <button 
            type="submit" 
            class="btn btn-outline" 
            id="applyFilterBtn"
            disabled
        >
            Применить
        </button>

        <a 
            href="catalog.php" 
            class="btn btn-reset <?= empty($selectedCategories) ? 'disabled-link' : '' ?>"
        >
            Сбросить фильтр
        </a>
    </div>
</form>

        <?php if (empty($rooms)): ?>
            <p class="empty-message">Номера по выбранной категории не найдены.</p>
        <?php else: ?>
            <div class="rooms-grid">
                <?php foreach ($rooms as $room): ?>
                    <article class="room-card">
                        <div class="room-image">
    <img 
        src="images/<?= htmlspecialchars($room['image']) ?>" 
        alt="<?= htmlspecialchars($room['title']) ?>"
    >

    <span class="room-category-badge room-category-<?= htmlspecialchars($room['category_color']) ?>">
        <?= htmlspecialchars($room['category_name']) ?>
    </span>
</div>

                        <h2 class="room-price">
                            <?= number_format($room['price'], 0, '', '') ?>₽ / Ночь
                        </h2>

                        <p class="room-characteristics">
                            <?= htmlspecialchars($room['characteristics']) ?>
                        </p>

                        <a 
                            href="booking.php?room_id=<?= htmlspecialchars($room['room_id']) ?>" 
                            class="btn btn-book"
                        >
                            Забронировать
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </section>
</main>

<footer class="footer">
    <h2>Сладкие сны</h2>

    <p>Режим работы:</p>
    <p>Пн-Пт: 9:00-20:00<br>Сб-Вс: 10:00-18:00</p>

    <p>Адрес<br>Email<br>+7 (xxx) xxx xx-xx</p>
</footer>

<script>
    const checkboxes = document.querySelectorAll('input[name="categories[]"]');
    const applyButton = document.getElementById('applyFilterBtn');

    function updateApplyButton() {
        const hasChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        applyButton.disabled = !hasChecked;
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateApplyButton);
    });

    updateApplyButton();
</script>

</body>
</html>