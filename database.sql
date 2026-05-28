CREATE DATABASE IF NOT EXISTS sweet_dreams
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sweet_dreams;

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(30) NOT NULL
);

CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    characteristics TEXT NOT NULL,

    FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    guest_name VARCHAR(100) NOT NULL,
    guest_surname VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('new', 'approved', 'rejected') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
INSERT INTO categories (name, color) VALUES
('Стандартный', 'green'),
('Студия', 'blue'),
('Люкс', 'red');

INSERT INTO rooms 
(category_id, title, price, image, description, characteristics) 
VALUES
(2, 'Студия с рабочей зоной', 3591, 'studio1.jpg',
'Практичное пространство, где легко совмещать работу и отдых.',
'Рабочее место, Wi-Fi, односпальная кровать, душ'),

(1, 'Стандартный номер', 1084, 'standard1.jpg',
'Приятный номер с лаконичным дизайном и всем необходимым для проживания.',
'Wi-Fi, двуспальная кровать, кондиционер, телевизор'),

(3, 'Люкс с дизайнерским интерьером', 5251, 'lux1.jpg',
'Элегантное пространство с дизайнерской мебелью, где каждая деталь создана для вашего уюта и удовольствия.',
'Большая кровать, ванная, Wi-Fi, кондиционер, мини-бар'),

(2, 'Студия с панорамным видом', 4252, 'studio2.jpg',
'Номер с эргономичным рабочим местом и быстрым интернетом для эффективного рабочего процесса.',
'Рабочая зона, Wi-Fi, вид из окна, телевизор'),

(3, 'Премиум люкс', 9345, 'lux2.jpg',
'Современный премиум-номер с атмосферой уединения и безупречным комфортом.',
'Большая кровать, ванная, мини-бар, кондиционер, Wi-Fi'),

(1, 'Компактный стандартный номер', 1045, 'standard2.jpg',
'Комфорт и простота в одном пространстве — для тех, кто ценит удобство без лишнего.',
'Односпальная кровать, Wi-Fi, душ, телевизор');