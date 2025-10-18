-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Окт 12 2025 г., 17:47
-- Версия сервера: 8.4.3
-- Версия PHP: 8.3.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `flowers`
--

-- --------------------------------------------------------

--
-- Структура таблицы `carts`
--

CREATE TABLE `carts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `items` json DEFAULT NULL COMMENT '{productId:1,\r\n''quantity'':4}',
  `createdAt` int DEFAULT NULL,
  `updatedAt` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `items`, `createdAt`, `updatedAt`) VALUES
(12, 1, NULL, NULL, 1759450970),
(14, 2, '[{\"quantity\": 2, \"productId\": 2}, {\"quantity\": 3, \"productId\": 4}]', 1759262231, 1759344113),
(17, 7, NULL, NULL, 1759753519);

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `name_en` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `name_de` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `url` varchar(30) COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`id`, `name`, `name_en`, `name_de`, `url`) VALUES
(1, 'Комнатные цветы', 'Home Flowers', 'Hausblumen', 'home_flowers'),
(2, 'Кактусы', 'Cacti', 'Kakteen', 'сacti'),
(3, 'Орхидеи', 'Orchids', 'Orchideen', 'orchids'),
(4, 'Искусственные', 'Artificial', 'Künstlich', 'artificial_flowers');

-- --------------------------------------------------------

--
-- Структура таблицы `delivery_types`
--

CREATE TABLE `delivery_types` (
  `id` int NOT NULL,
  `deliveryType` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `deliveryType_en` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `deliveryType_de` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `addressNeed` tinyint NOT NULL DEFAULT '0',
  `delivery_price` smallint DEFAULT '0',
  `low_price` smallint NOT NULL DEFAULT '0',
  `lPMinPrice` int DEFAULT '0' COMMENT 'Стоимость для активации скидки на доставку',
  `disabled` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `delivery_types`
--

INSERT INTO `delivery_types` (`id`, `deliveryType`, `deliveryType_en`, `deliveryType_de`, `addressNeed`, `delivery_price`, `low_price`, `lPMinPrice`, `disabled`) VALUES
(1, 'Самовывоз', 'Pickup', 'Abholung', 0, 0, 0, 0, 0),
(2, 'Курьер', 'Courier', 'Lieferung per Kurier', 1, 12, 0, 50, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `favorites`
--

CREATE TABLE `favorites` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `addDate` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `product_id`, `addDate`) VALUES
(2, 4, 2, 1758475785),
(3, 4, 3, 1758475788),
(4, 4, 4, 1758475791),
(9, 1, 4, 1759394969),
(11, 1, 33, 1759402899);

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `deliveryCost` mediumint NOT NULL,
  `deliveryType_id` int NOT NULL,
  `delivery_info` json DEFAULT NULL,
  `firstName` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `lastName` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_bin NOT NULL,
  `email` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `paymentType_id` int NOT NULL,
  `comment` varchar(500) COLLATE utf8mb4_bin NOT NULL,
  `status_id` int NOT NULL,
  `items` json NOT NULL,
  `user_id` int NOT NULL,
  `totalAmount` mediumint NOT NULL,
  `createdAt` int NOT NULL,
  `updatedAt` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`id`, `deliveryCost`, `deliveryType_id`, `delivery_info`, `firstName`, `lastName`, `phone`, `email`, `paymentType_id`, `comment`, `status_id`, `items`, `user_id`, `totalAmount`, `createdAt`, `updatedAt`) VALUES
(5, 12, 2, '{\"zip\": \"66119\", \"city\": \"Saarbrücken\", \"house\": \"10\", \"region\": \"Saarland\", \"cistreetty\": \"Rubensstr.\"}', 'Volodymyr', 'Volobuiev', '+491551068321', 'bobbygtx@gmail.com', 2, 'Ich brauche keinen Rückruf!', 1, '[{\"id\": \"1\", \"name\": \"Azalee\", \"price\": \"19\", \"total\": 76, \"quantity\": 4}, {\"id\": \"2\", \"name\": \"Hibiskus\", \"price\": \"23\", \"total\": 69, \"quantity\": 3}, {\"id\": \"4\", \"name\": \"Kalanchoe\", \"price\": \"13\", \"total\": 39, \"quantity\": 3}, {\"id\": \"65\", \"name\": \"Dattelpalme\", \"price\": \"42\", \"total\": 42, \"quantity\": 1}]', 1, 12, 1759450556, NULL),
(6, 12, 2, '{\"zip\": \"66119\", \"city\": \"Saarbrücken\", \"house\": \"10\", \"region\": \"Saarland\", \"cistreetty\": \"Rubensstr.\"}', 'Volodymyr', 'Volobuiev', '+491551068321', 'bobbygtx@gmail.com', 2, 'Ich brauche keinen Rückruf!', 1, '[{\"id\": \"2\", \"name\": \"Hibiskus\", \"price\": \"23\", \"total\": 69, \"quantity\": 3}, {\"id\": \"4\", \"name\": \"Kalanchoe\", \"price\": \"13\", \"total\": 39, \"quantity\": 3}]', 1, 12, 1759450838, NULL),
(7, 12, 2, '{\"zip\": \"66119\", \"city\": \"Saarbrücken\", \"house\": \"10\", \"region\": \"Saarland\", \"cistreetty\": \"Rubensstr.\"}', 'Volodymyr', 'Volobuiev', '+491551068321', 'bobbygtx@gmail.com', 2, 'Ich brauche keinen Rückruf!', 1, '[{\"id\": \"2\", \"name\": \"Hibiskus\", \"price\": \"23\", \"total\": 69, \"quantity\": 3}, {\"id\": \"62\", \"name\": \"Kentia-Palme\", \"price\": \"41\", \"total\": 123, \"quantity\": 3}]', 1, 12, 1759450970, NULL),
(8, 12, 2, '{\"zip\": \"66119\", \"city\": \"Saarbrücken\", \"house\": \"10\", \"region\": \"Saarland\", \"cistreetty\": \"Rubensstr.\"}', 'Vladyslav', 'Volobuiev', '+491771750803', 'bladegtx@gmail.com', 2, 'Ich brauche Rückruf!', 1, '[{\"id\": \"1\", \"name\": \"Azalee\", \"price\": \"19\", \"total\": 76, \"quantity\": 4}, {\"id\": \"5\", \"name\": \"Einblatt\", \"price\": \"20\", \"total\": 40, \"quantity\": 2}, {\"id\": \"62\", \"name\": \"Kentia-Palme\", \"price\": \"41\", \"total\": 123, \"quantity\": 3}]', 7, 12, 1759741402, NULL),
(9, 12, 2, '{\"zip\": \"66119\", \"city\": \"Saarbrücken\", \"house\": \"10\", \"region\": \"Saarland\", \"cistreetty\": \"Rubensstr.\"}', 'Vladyslav', 'Volobuiev', '+491771750803', 'bladegtx@gmail.com', 2, 'Ich brauche Rückruf!', 1, '[{\"id\": \"1\", \"name\": \"Azalee\", \"price\": \"19\", \"total\": 76, \"quantity\": 4}, {\"id\": \"2\", \"name\": \"Hibiskus\", \"price\": \"23\", \"total\": 23, \"quantity\": 1}]', 7, 12, 1759753519, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `payment_types`
--

CREATE TABLE `payment_types` (
  `id` int NOT NULL,
  `paymentType` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `paymentType_en` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `paymentType_de` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `disabled` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `payment_types`
--

INSERT INTO `payment_types` (`id`, `paymentType`, `paymentType_en`, `paymentType_de`, `disabled`) VALUES
(1, 'Оплата картой', 'Card Payment', 'Kartenzahlung', 0),
(2, 'Наличный расчёт', 'Cash Payment', 'Barzahlung', 0),
(3, 'Безналичный расчёт при получении', 'Card on Delivery', 'EC-Kartenzahlung bei Lieferung', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `name_en` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `name_de` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `price` smallint NOT NULL,
  `image` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `type_id` int NOT NULL,
  `lightning` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `lightning_en` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `lightning_de` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `humidity` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `humidity_en` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `humidity_de` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `temperature` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `temperature_en` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `temperature_de` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `height` smallint NOT NULL,
  `diameter` tinyint NOT NULL,
  `url` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `count` smallint NOT NULL,
  `disabled` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `name`, `name_en`, `name_de`, `price`, `image`, `type_id`, `lightning`, `lightning_en`, `lightning_de`, `humidity`, `humidity_en`, `humidity_de`, `temperature`, `temperature_en`, `temperature_de`, `height`, `diameter`, `url`, `count`, `disabled`) VALUES
(1, 'Азалия', 'Azalea', 'Azalee', 19, 'azalea.jpg', 1, 'Яркий рассеянный свет, избегать прямого солнца. Подходит восточное или западное окно.', 'Bright indirect light, avoid direct sunlight. East or west window is ideal.', 'Helles indirektes Licht, direkte Sonne vermeiden. Ost- oder Westfenster ist ideal.', 'Высокая влажность, регулярное опрыскивание листьев.', 'High humidity, regular misting of leaves.', 'Hohe Luftfeuchtigkeit, regelmäßiges Besprühen der Blätter.', 'Идеальная температура 15–20°C, избегать перегрева.', 'Ideal temperature 15–20°C, avoid overheating.', 'Ideale Temperatur 15–20°C, Überhitzung vermeiden.', 14, 40, 'azalea', 78, 0),
(2, 'Гибискус', 'Hibiscus', 'Hibiskus', 23, 'hibiscus.jpg', 1, 'Любит много света, лучше прямое утреннее солнце.', 'Prefers plenty of light, best with direct morning sun.', 'Mag viel Licht, am besten mit direkter Morgensonne.', 'Средняя влажность, регулярный полив.', 'Moderate humidity, regular watering.', 'Mittlere Luftfeuchtigkeit, regelmäßiges Gießen.', 'Температура 18–25°C, не ниже 15°C.', 'Temperature 18–25°C, not below 15°C.', 'Temperatur 18–25°C, nicht unter 15°C.', 16, 60, 'hibiscus', 83, 0),
(3, 'Антуриум', 'Anthurium', 'Anthurium', 28, 'anthurium.jpg', 1, 'Яркий рассеянный свет, хорошо переносит полутень.', 'Bright indirect light, tolerates partial shade.', 'Helles indirektes Licht, verträgt Halbschatten.', 'Влажность воздуха высокая, желательно опрыскивать.', 'High humidity, misting recommended.', 'Hohe Luftfeuchtigkeit, Besprühen empfohlen.', 'Температура 20–28°C, избегать сквозняков.', 'Temperature 20–28°C, avoid drafts.', 'Temperatur 20–28°C, Zugluft vermeiden.', 55, 14, 'anthurium', 98, 0),
(4, 'Калланхоэ', 'Kalanchoe', 'Kalanchoe', 13, 'kalanchoe.jpg', 1, 'Хорошо растет на ярком солнце, подходит южное окно.', 'Grows well in bright sun, suitable for south-facing window.', 'Wächst gut in voller Sonne, geeignet für Südfenster.', 'Средняя влажность, избегать переувлажнения.', 'Moderate humidity, avoid overwatering.', 'Mittlere Luftfeuchtigkeit, Staunässe vermeiden.', 'Лучше всего при 18–25°C, зимой не ниже 12°C.', 'Best at 18–25°C, not below 12°C in winter.', 'Am besten bei 18–25°C, im Winter nicht unter 12°C.', 30, 12, 'kalanchoe', 90, 0),
(5, 'Спатифиллум', 'Spathiphyllum', 'Einblatt', 20, 'spathiphyllum.jpg', 1, 'Полутень или рассеянный свет, прямые лучи нежелательны.', 'Partial shade or indirect light, avoid direct rays.', 'Halbschatten oder indirektes Licht, direkte Sonne vermeiden.', 'Высокая влажность, регулярное опрыскивание.', 'High humidity, frequent misting.', 'Hohe Luftfeuchtigkeit, häufiges Besprühen.', 'Лучше всего 18–23°C, избегать холодных сквозняков.', 'Best at 18–23°C, avoid cold drafts.', 'Am besten bei 18–23°C, Zugluft vermeiden.', 50, 15, 'spathiphyllum', 98, 0),
(6, 'Кливия', 'Clivia', 'Clivie', 27, 'clivia.jpg', 1, 'Нужен яркий свет, но без прямого солнца.', 'Needs bright light, but avoid direct sunlight.', 'Braucht helles Licht, direkte Sonne vermeiden.', 'Средняя влажность, полив умеренный.', 'Moderate humidity, watering moderate.', 'Mittlere Luftfeuchtigkeit, mäßiges Gießen.', 'Температура 18–24°C, зимой прохладнее.', 'Temperature 18–24°C, cooler in winter.', 'Temperatur 18–24°C, im Winter kühler.', 60, 17, 'clivia', 18, 0),
(7, 'Фиалка', 'Violet', 'Veilchen', 14, 'violet.jpg', 1, 'Лучше всего рассеянный свет, не переносит прямого солнца.', 'Best with indirect light, does not tolerate direct sun.', 'Am besten mit indirektem Licht, verträgt keine direkte Sonne.', 'Высокая влажность, но полив умеренный.', 'High humidity, but moderate watering.', 'Hohe Luftfeuchtigkeit, aber mäßiges Gießen.', 'Оптимум 18–22°C, зимой не ниже 15°C.', 'Optimal 18–22°C, not below 15°C in winter.', 'Optimal 18–22°C, im Winter nicht unter 15°C.', 25, 11, 'violet', 30, 0),
(8, 'Сансевиерия', 'Sansevieria', 'Sansevierie', 16, 'sansevieria.jpg', 2, 'Теневынослива, но лучше растет на ярком свете.', 'Shade tolerant, but grows better in bright light.', 'Schattenverträglich, wächst besser bei hellem Licht.', 'Сухой воздух переносит, полив редкий.', 'Tolerates dry air, watering rare.', 'Verträgt trockene Luft, seltenes Gießen.', 'Температура 15–30°C.', 'Temperature 15–30°C.', 'Temperatur 15–30°C.', 50, 14, 'sansevieria', 25, 0),
(9, 'Замиокулькас', 'Zamioculcas', 'Glücksfeder', 24, 'zamioculcas.jpg', 2, 'Хорошо себя чувствует в полутени.', 'Thrives in partial shade.', 'Wächst gut im Halbschatten.', 'Низкая влажность не проблема, полив умеренный.', 'Low humidity is fine, moderate watering.', 'Niedrige Luftfeuchtigkeit ist kein Problem, mäßiges Gießen.', 'Температура 18–28°C.', 'Temperature 18–28°C.', 'Temperatur 18–28°C.', 70, 18, 'zamioculcas', 20, 0),
(10, 'Фикус Бенджамина', 'Ficus Benjamina', 'Birkenfeige', 29, 'ficus.jpg', 2, 'Нужен яркий свет, возможна полутень.', 'Needs bright light, tolerates partial shade.', 'Braucht helles Licht, verträgt Halbschatten.', 'Средняя влажность, регулярный полив.', 'Moderate humidity, regular watering.', 'Mittlere Luftfeuchtigkeit, regelmäßiges Gießen.', 'Лучше всего при 18–25°C.', 'Best at 18–25°C.', 'Am besten bei 18–25°C.', 120, 20, 'ficus', 15, 0),
(11, 'Монстера', 'Monstera', 'Monstera', 36, 'monstera.jpg', 2, 'Нужен яркий свет без прямых лучей.', 'Bright light without direct rays.', 'Helles Licht ohne direkte Sonne.', 'Любит высокую влажность.', 'Likes high humidity.', 'Mag hohe Luftfeuchtigkeit.', 'Температура 18–28°C.', 'Temperature 18–28°C.', 'Temperatur 18–28°C.', 150, 24, 'monstera', 12, 0),
(12, 'Алоказия', 'Alocasia', 'Alokasie', 33, 'alocasia.jpg', 2, 'Яркий рассеянный свет, избегать прямых лучей.', 'Bright indirect light, avoid direct rays.', 'Helles indirektes Licht, direkte Sonne vermeiden.', 'Высокая влажность обязательна.', 'High humidity required.', 'Hohe Luftfeuchtigkeit erforderlich.', 'Температура 20–28°C.', 'Temperature 20–28°C.', 'Temperatur 20–28°C.', 90, 19, 'alocasia', 10, 0),
(13, 'Пилея', 'Pilea', 'Ufopflanze', 17, 'pilea.jpg', 2, 'Хорошо растет в рассеянном свете.', 'Grows well in indirect light.', 'Wächst gut im indirekten Licht.', 'Средняя влажность, опрыскивание желательно.', 'Moderate humidity, misting recommended.', 'Mittlere Luftfeuchtigkeit, Besprühen empfohlen.', 'Оптимальная температура 18–24°C.', 'Optimal temperature 18–24°C.', 'Optimale Temperatur 18–24°C.', 35, 13, 'pilea', 22, 0),
(14, 'Аспидистра', 'Aspidistra', 'Schusterpalme', 21, 'aspidistra.jpg', 2, 'Выдерживает тень, свет не обязателен.', 'Tolerates shade, light not essential.', 'Verträgt Schatten, Licht nicht zwingend.', 'Переносит сухой воздух.', 'Tolerates dry air.', 'Verträgt trockene Luft.', 'Температура 10–25°C.', 'Temperature 10–25°C.', 'Temperatur 10–25°C.', 70, 18, 'aspidistra', 16, 0),
(15, 'Арека', 'Areca Palm', 'Areca-Palme', 39, 'areca.jpg', 3, 'Яркий рассеянный свет, полутень допустима.', 'Bright indirect light, partial shade tolerated.', 'Helles indirektes Licht, Halbschatten verträglich.', 'Высокая влажность предпочтительна.', 'High humidity preferred.', 'Hohe Luftfeuchtigkeit bevorzugt.', 'Оптимум 20–28°C.', 'Optimal 20–28°C.', 'Optimal 20–28°C.', 140, 26, 'areca', 14, 0),
(16, 'Хамедорея', 'Chamaedorea', 'Bergpalme', 34, 'chamaedorea.jpg', 3, 'Нуждается в рассеянном свете.', 'Needs indirect light.', 'Braucht indirektes Licht.', 'Средняя влажность, полив регулярный.', 'Moderate humidity, regular watering.', 'Mittlere Luftfeuchtigkeit, regelmäßiges Gießen.', 'Температура 18–25°C.', 'Temperature 18–25°C.', 'Temperatur 18–25°C.', 120, 22, 'chamaedorea', 19, 0),
(17, 'Кокосовая пальма', 'Coconut Palm', 'Kokospalme', 45, 'coconut_palm.jpg', 3, 'Яркий свет, желательно южное окно.', 'Bright light, south-facing window preferred.', 'Helles Licht, Südfenster bevorzugt.', 'Любит высокую влажность.', 'Loves high humidity.', 'Liebt hohe Luftfeuchtigkeit.', 'Температура 22–28°C.', 'Temperature 22–28°C.', 'Temperatur 22–28°C.', 160, 28, 'coconut_palm', 11, 0),
(18, 'Кентия', 'Kentia Palm', 'Kentia-Palme', 41, 'kentia.jpg', 3, 'Яркий рассеянный свет, переносит полутень.', 'Bright indirect light, tolerates partial shade.', 'Helles indirektes Licht, verträgt Halbschatten.', 'Средняя влажность воздуха.', 'Moderate air humidity.', 'Mittlere Luftfeuchtigkeit.', 'Температура 18–26°C.', 'Temperature 18–26°C.', 'Temperatur 18–26°C.', 150, 25, 'kentia', 7, 0),
(19, 'Вашингтония', 'Washingtonia', 'Washingtonpalme', 37, 'washingtonia.jpg', 3, 'Любит много света.', 'Loves plenty of light.', 'Liebt viel Licht.', 'Средняя влажность, полив регулярный.', 'Moderate humidity, regular watering.', 'Mittlere Luftfeuchtigkeit, regelmäßiges Gießen.', 'Оптимальная температура 20–30°C.', 'Optimal temperature 20–30°C.', 'Optimale Temperatur 20–30°C.', 180, 27, 'washingtonia', 9, 0),
(20, 'Ливистона', 'Livistona', 'Livistona-Palme', 39, 'livistona.jpg', 3, 'Нуждается в ярком рассеянном свете.', 'Needs bright indirect light.', 'Braucht helles indirektes Licht.', 'Высокая влажность предпочтительна.', 'High humidity preferred.', 'Hohe Luftfeuchtigkeit bevorzugt.', 'Температура 20–28°C.', 'Temperature 20–28°C.', 'Temperatur 20–28°C.', 160, 24, 'livistona', 12, 0),
(21, 'Финиковая пальма', 'Date Palm', 'Dattelpalme', 42, 'date_palm.jpg', 3, 'Хорошо растет на ярком солнце.', 'Grows well in bright sun.', 'Wächst gut in voller Sonne.', 'Средняя влажность воздуха.', 'Moderate air humidity.', 'Mittlere Luftfeuchtigkeit.', 'Температура 20–30°C.', 'Temperature 20–30°C.', 'Temperatur 20–30°C.', 200, 30, 'date_palm', 8, 0),
(22, 'Венерина мухоловка', 'Venus Flytrap', 'Venusfliegenfalle', 22, 'venus_flytrap.jpg', 4, 'Нуждается в ярком солнце.', 'Requires bright sunlight.', 'Braucht viel Sonnenlicht.', 'Высокая влажность обязательна.', 'High humidity required.', 'Hohe Luftfeuchtigkeit erforderlich.', 'Температура 20–30°C.', 'Temperature 20–30°C.', 'Temperatur 20–30°C.', 15, 10, 'venus_flytrap', 25, 0),
(23, 'Непентес', 'Nepenthes', 'Kannenpflanze', 28, 'nepenthes.jpg', 4, 'Яркий рассеянный свет.', 'Bright indirect light.', 'Helles indirektes Licht.', 'Влажность 70–90%.', 'Humidity 70–90%.', 'Luftfeuchtigkeit 70–90%.', 'Температура 22–28°C.', 'Temperature 22–28°C.', 'Temperatur 22–28°C.', 40, 14, 'nepenthes', 18, 0),
(24, 'Саррацения', 'Sarracenia', 'Schlauchpflanze', 25, 'sarracenia.jpg', 4, 'Любит прямое солнце.', 'Prefers direct sunlight.', 'Mag direkte Sonne.', 'Влажность высокая, почва всегда влажная.', 'High humidity, soil always moist.', 'Hohe Luftfeuchtigkeit, Erde stets feucht.', 'Температура 18–28°C.', 'Temperature 18–28°C.', 'Temperatur 18–28°C.', 35, 12, 'sarracenia', 15, 0),
(25, 'Дарлингтония', 'Darlingtonia', 'Kobralilie', 30, 'darlingtonia.jpg', 4, 'Нуждается в ярком свете.', 'Needs bright light.', 'Braucht helles Licht.', 'Влажность высокая, корни держать прохладными.', 'High humidity, keep roots cool.', 'Hohe Luftfeuchtigkeit, Wurzeln kühl halten.', 'Температура 15–25°C.', 'Temperature 15–25°C.', 'Temperatur 15–25°C.', 30, 13, 'darlingtonia', 10, 0),
(26, 'Гелиамфора', 'Heliamphora', 'Heliamphora', 33, 'heliamphora.jpg', 4, 'Яркий рассеянный свет.', 'Bright indirect light.', 'Helles indirektes Licht.', 'Высокая влажность, регулярное опрыскивание.', 'High humidity, regular misting.', 'Hohe Luftfeuchtigkeit, regelmäßiges Besprühen.', 'Температура 18–24°C.', 'Temperature 18–24°C.', 'Temperatur 18–24°C.', 25, 12, 'heliamphora', 11, 0),
(27, 'Цефалотус', 'Cephalotus', 'Zwergkrug', 27, 'cephalotus.jpg', 4, 'Нуждается в ярком освещении.', 'Needs bright light.', 'Braucht helles Licht.', 'Влажность высокая, но не застой воды.', 'High humidity, but no stagnant water.', 'Hohe Luftfeuchtigkeit, aber kein stehendes Wasser.', 'Температура 18–26°C.', 'Temperature 18–26°C.', 'Temperatur 18–26°C.', 20, 11, 'cephalotus', 14, 0),
(28, 'Дросера', 'Drosera', 'Sonnentau', 20, 'drosera.jpg', 4, 'Предпочитает яркий свет.', 'Prefers bright light.', 'Mag helles Licht.', 'Влажность высокая, субстрат всегда влажный.', 'High humidity, substrate always moist.', 'Hohe Luftfeuchtigkeit, Substrat immer feucht.', 'Температура 18–28°C.', 'Temperature 18–28°C.', 'Temperatur 18–28°C.', 18, 9, 'drosera', 20, 0),
(29, 'Альдрованда', 'Aldrovanda', 'Wasserschlauchpflanze', 28, 'aldrovanda.jpg', 4, 'Нуждается в ярком солнце.', 'Requires bright sunlight.', 'Braucht viel Sonne.', 'Высокая влажность, вода обязательна.', 'High humidity, water required.', 'Hohe Luftfeuchtigkeit, Wasser erforderlich.', 'Температура 20–28°C.', 'Temperature 20–28°C.', 'Temperatur 20–28°C.', 12, 8, 'aldrovanda', 9, 0),
(30, 'Маммиллярия изящная', 'Mammillaria gracilis', 'Mammillaria gracilis', 13, 'mammillaria.jpg', 5, 'Яркий рассеянный свет, избегать прямых солнечных лучей', 'Bright indirect light, avoid direct sun', 'Helles indirektes Licht, direkte Sonne vermeiden', 'Умеренная влажность, без застоя воды', 'Moderate humidity, no waterlogging', 'Mäßige Luftfeuchtigkeit, keine Staunässe', '18–26°C', '18–26°C', '18–26°C', 12, 10, 'mammillaria', 40, 0),
(31, 'Эхинокактус Грузона', 'Golden Barrel Cactus', 'Schwiegermutterstuhl', 19, 'echinocactus.jpg', 5, 'Яркий солнечный свет', 'Bright sunlight', 'Helles Sonnenlicht', 'Низкая влажность', 'Low humidity', 'Niedrige Luftfeuchtigkeit', '20–30°C', '20–30°C', '20–30°C', 25, 15, 'echinocactus', 30, 0),
(32, 'Ребуция солнечная', 'Rebutia heliosa', 'Rebutia heliosa', 12, 'rebutia.jpg', 5, 'Яркий свет', 'Bright light', 'Helles Licht', 'Средняя влажность', 'Moderate humidity', 'Mittlere Luftfeuchtigkeit', '18–28°C', '18–28°C', '18–28°C', 10, 9, 'rebutia', 35, 0),
(33, 'Астрофитум звёздчатый', 'Bishop\'s Cap', 'Bischofsmütze', 17, 'astrophytum.jpg', 5, 'Прямое солнце или яркий свет', 'Direct sun or bright light', 'Direkte Sonne oder helles Licht', 'Низкая влажность', 'Low humidity', 'Niedrige Luftfeuchtigkeit', '18–30°C', '18–30°C', '18–30°C', 20, 14, 'astrophytum', 25, 0),
(34, 'Пародия великолепная', 'Ball Cactus', 'Kugelkaktus', 15, 'parodia.jpg', 5, 'Яркий свет', 'Bright light', 'Helles Licht', 'Умеренная влажность', 'Moderate humidity', 'Mäßige Luftfeuchtigkeit', '18–27°C', '18–27°C', '18–27°C', 18, 12, 'parodia', 28, 0),
(35, 'Гимнокалициум Михановича', 'Moon Cactus', 'Mondkaktus', 13, 'gymnocalycium.jpg', 5, 'Яркий свет без прямых лучей', 'Bright light without direct sun', 'Helles Licht ohne direkte Sonne', 'Средняя влажность', 'Moderate humidity', 'Mittlere Luftfeuchtigkeit', '18–26°C', '18–26°C', '18–26°C', 12, 8, 'gymnocalycium', 50, 0),
(36, 'Цереус перуанский', 'Peruvian Apple Cactus', 'Peruanischer Apfelkaktus', 20, 'cereus.jpg', 6, 'Любит яркий свет, переносит прямое солнце', 'Prefers bright light, tolerates direct sun', 'Liebt helles Licht, verträgt direkte Sonne', 'Низкая влажность, редкий полив', 'Low humidity, rare watering', 'Niedrige Luftfeuchtigkeit, seltenes Gießen', '20–30°C', '20–30°C', '20–30°C', 60, 18, 'cereus', 20, 0),
(37, 'Трихоцереус Паханой', 'San Pedro Cactus', 'San Pedro Kaktus', 23, 'trichocereus.jpg', 6, 'Яркий солнечный свет', 'Bright sunlight', 'Helles Sonnenlicht', 'Низкая влажность, без застоя', 'Low humidity, no waterlogging', 'Niedrige Luftfeuchtigkeit, keine Staunässe', '18–28°C', '18–28°C', '18–28°C', 55, 16, 'trichocereus', 18, 0),
(38, 'Стеноцереус сизый', 'Gray Ghost Organ Pipe', 'Organrohrkaktus', 21, 'stenocereus.jpg', 6, 'Прямой солнечный свет', 'Direct sunlight', 'Direktes Sonnenlicht', 'Очень низкая влажность', 'Very low humidity', 'Sehr niedrige Luftfeuchtigkeit', '20–32°C', '20–32°C', '20–32°C', 70, 20, 'stenocereus', 12, 0),
(39, 'Пилосоцереус толстостебельный', 'Blue Torch Cactus', 'Blauer Säulenkaktus', 25, 'pilosocereus.jpg', 6, 'Яркий свет, лучше южное окно', 'Bright light, best south window', 'Helles Licht, am besten Südfenster', 'Низкая влажность, полив раз в месяц', 'Low humidity, monthly watering', 'Niedrige Luftfeuchtigkeit, monatliches Gießen', '22–30°C', '22–30°C', '22–30°C', 80, 22, 'pilosocereus', 10, 0),
(40, 'Эспостоа шерстистая', 'Peruvian Old Man Cactus', 'Peruanischer Greisenkaktus', 23, 'espostoa.jpg', 6, 'Яркий свет, солнце', 'Bright light, sun', 'Helles Licht, Sonne', 'Низкая влажность, редкий полив', 'Low humidity, rare watering', 'Niedrige Luftfeuchtigkeit, seltenes Gießen', '18–28°C', '18–28°C', '18–28°C', 65, 17, 'espostoa', 15, 0),
(41, 'Опунция мелкоушковая', 'Bunny Ear Cactus', 'Hasenohrkaktus', 15, 'opuntia.jpg', 7, 'Прямое солнце или яркий свет', 'Direct sun or bright light', 'Direkte Sonne oder helles Licht', 'Низкая влажность', 'Low humidity', 'Niedrige Luftfeuchtigkeit', '20–32°C', '20–32°C', '20–32°C', 25, 14, 'opuntia', 25, 0),
(42, 'Шлюмбергера усечённая', 'Christmas Cactus', 'Weihnachtskaktus', 17, 'schlumbergera.jpg', 7, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Умеренная влажность', 'Moderate humidity', 'Mäßige Luftfeuchtigkeit', '16–24°C', '16–24°C', '16–24°C', 20, 12, 'schlumbergera', 40, 0),
(43, 'Эпифиллум остролепестный', 'Queen of the Night', 'Königin der Nacht', 18, 'epiphyllum.jpg', 7, 'Полутень или рассеянный свет', 'Partial shade or indirect light', 'Halbschatten oder indirektes Licht', 'Средняя влажность', 'Moderate humidity', 'Mittlere Luftfeuchtigkeit', '18–26°C', '18–26°C', '18–26°C', 35, 15, 'epiphyllum', 22, 0),
(44, 'Нопалея кошенильная', 'Cochineal Nopal Cactus', 'Koschenillkaktus', 17, 'nopalea.jpg', 7, 'Яркий свет, переносит солнце', 'Bright light, tolerates sun', 'Helles Licht, verträgt Sonne', 'Низкая влажность', 'Low humidity', 'Niedrige Luftfeuchtigkeit', '20–30°C', '20–30°C', '20–30°C', 40, 18, 'nopalea', 18, 0),
(45, 'Консолея красноватая', 'Road Kill Cactus', 'Straßenkaktus', 19, 'consolea.jpg', 7, 'Яркий солнечный свет', 'Bright sunlight', 'Helles Sonnenlicht', 'Низкая влажность', 'Low humidity', 'Niedrige Luftfeuchtigkeit', '22–32°C', '22–32°C', '22–32°C', 50, 20, 'consolea', 14, 0),
(46, 'Рипсалис ягодоносный', 'Mistletoe Cactus', 'Besen-Kaktus', 16, 'rhipsalis.jpg', 8, 'Полутень, избегать прямых лучей', 'Partial shade, avoid direct sun', 'Halbschatten, direkte Sonne vermeiden', 'Высокая влажность', 'High humidity', 'Hohe Luftfeuchtigkeit', '18–26°C', '18–26°C', '18–26°C', 30, 13, 'rhipsalis', 28, 0),
(47, 'Хатиора солянкообразная', 'Drunkard\'s Dream', 'Trinkerkaktus', 15, 'hatiora.jpg', 8, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Средняя влажность', 'Moderate humidity', 'Mittlere Luftfeuchtigkeit', '18–24°C', '18–24°C', '18–24°C', 28, 12, 'hatiora', 32, 0),
(48, 'Аустроцилиндропунция шиловидная', 'Eve\'s Needle', 'Austrocylindropuntia', 17, 'austrocylindropuntia.jpg', 8, 'Яркий свет, прямое солнце', 'Bright light, direct sun', 'Helles Licht, direkte Sonne', 'Низкая влажность', 'Low humidity', 'Niedrige Luftfeuchtigkeit', '20–30°C', '20–30°C', '20–30°C', 60, 18, 'austrocylindropuntia', 20, 0),
(49, 'Цилиндропунция черепитчатая', 'Cane Cholla', 'Kettenkaktus', 18, 'cylindropuntia.jpg', 8, 'Яркое солнце', 'Bright sun', 'Helle Sonne', 'Низкая влажность', 'Low humidity', 'Niedrige Luftfeuchtigkeit', '20–35°C', '20–35°C', '20–35°C', 70, 20, 'cylindropuntia', 15, 0),
(50, 'Майхуения Пёппига', 'Maihuenia poeppigii', 'Maihuenia poeppigii', 18, 'maihueniap.jpg', 8, 'Солнце или яркий свет', 'Sun or bright light', 'Sonne oder helles Licht', 'Низкая влажность', 'Low humidity', 'Niedrige Luftfeuchtigkeit', '15–25°C', '15–25°C', '15–25°C', 40, 16, 'maihueniap', 12, 0),
(51, 'Орхидейный кактус', 'Orchid Cactus', 'Orchideenkaktus', 16, 'disocactus.jpg', 9, 'Полутень или рассеянный свет', 'Partial shade or indirect light', 'Halbschatten oder indirektes Licht', 'Высокая влажность', 'High humidity', 'Hohe Luftfeuchtigkeit', '18–26°C', '18–26°C', '18–26°C', 35, 14, 'disocactus', 26, 0),
(52, 'Эпифиллум угловатый', 'Fishbone Cactus', 'Fischgrätenkaktus', 17, 'epiphyllum_anguliger.jpg', 9, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Высокая влажность', 'High humidity', 'Hohe Luftfeuchtigkeit', '18–26°C', '18–26°C', '18–26°C', 40, 15, 'epiphyllum_anguliger', 22, 0),
(53, 'Селеницереус крупноцветковый', 'Large-flowered Cactus', 'Großblütiger Kaktus', 20, 'selenicereus.jpg', 9, 'Яркий свет, полутень', 'Bright light, partial shade', 'Helles Licht, Halbschatten', 'Средняя влажность', 'Moderate humidity', 'Mittlere Luftfeuchtigkeit', '18–26°C', '18–26°C', '18–26°C', 50, 18, 'selenicereus', 18, 0),
(54, 'Вебероцереус Тондузи', 'Weberocereus tonduzii', 'Weberocereus tonduzii', 18, 'weberocereus.jpg', 9, 'Полутень или светлое место', 'Partial shade or bright spot', 'Halbschatten oder heller Standort', 'Высокая влажность', 'High humidity', 'Hohe Luftfeuchtigkeit', '18–26°C', '18–26°C', '18–26°C', 45, 16, 'weberocereus', 16, 0),
(55, 'Эпифиллум золотосердцевидный', 'Fern Leaf Cactus', 'Farnblattkaktus', 20, 'epiphyllum_chryso.jpg', 9, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Высокая влажность', 'High humidity', 'Hohe Luftfeuchtigkeit', '18–26°C', '18–26°C', '18–26°C', 48, 17, 'epiphyllum_chryso', 14, 0),
(56, 'Фаленопсис Белый', 'White Phalaenopsis', 'Weiße Phalaenopsis', 29, 'phalaenopsis_white.jpg', 10, 'Яркий рассеянный свет, избегать прямых солнечных лучей', 'Bright indirect light, avoid direct sun', 'Helles indirektes Licht, direkte Sonne vermeiden', 'Высокая влажность 60–80%, регулярное опрыскивание', 'High humidity 60–80%, regular misting', 'Hohe Luftfeuchtigkeit 60–80%, regelmäßiges Besprühen', '18–28°C', '18–28°C', '18–28°C', 45, 12, 'phalaenopsis_white', 20, 0),
(57, 'Фаленопсис Розовый', 'Pink Phalaenopsis', 'Rosa Phalaenopsis', 30, 'phalaenopsis_pink.jpg', 10, 'Светлое место с рассеянным светом', 'Bright spot with indirect light', 'Heller Standort mit indirektem Licht', 'Высокая влажность и регулярный полив', 'High humidity and regular watering', 'Hohe Luftfeuchtigkeit und regelmäßiges Gießen', '20–28°C', '20–28°C', '20–28°C', 50, 13, 'phalaenopsis_pink', 18, 0),
(58, 'Фаленопсис Желтый', 'Yellow Phalaenopsis', 'Gelbe Phalaenopsis', 32, 'phalaenopsis_yellow.jpg', 10, 'Нуждается в ярком, но не прямом освещении', 'Needs bright but not direct light', 'Benötigt helles, aber kein direktes Licht', 'Влажность 60–75%, частое опрыскивание', 'Humidity 60–75%, frequent misting', 'Luftfeuchtigkeit 60–75%, häufiges Besprühen', '18–26°C', '18–26°C', '18–26°C', 55, 14, 'phalaenopsis_yellow', 15, 0),
(59, 'Фаленопсис Фиолетовый', 'Purple Phalaenopsis', 'Violette Phalaenopsis', 32, 'phalaenopsis_purple.jpg', 10, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Высокая влажность', 'High humidity', 'Hohe Luftfeuchtigkeit', '20–27°C', '20–27°C', '20–27°C', 52, 13, 'phalaenopsis_purple', 16, 0),
(60, 'Фаленопсис Оранжевый', 'Orange Phalaenopsis', 'Orange Phalaenopsis', 30, 'phalaenopsis_orange.jpg', 10, 'Предпочитает светлое место без прямого солнца', 'Prefers bright spot without direct sun', 'Bevorzugt hellen Standort ohne direkte Sonne', 'Влажность 65–80%', 'Humidity 65–80%', 'Luftfeuchtigkeit 65–80%', '19–27°C', '19–27°C', '19–27°C', 48, 12, 'phalaenopsis_orange', 12, 0),
(61, 'Каттлея Белая', 'White Cattleya', 'Weiße Cattleya', 35, 'cattleya_white.jpg', 11, 'Яркий свет, часть дня допустимо прямое солнце', 'Bright light, some direct sun tolerated', 'Helles Licht, teilweise direkte Sonne möglich', 'Умеренная влажность 50–70%', 'Moderate humidity 50–70%', 'Mäßige Luftfeuchtigkeit 50–70%', '18–30°C', '18–30°C', '18–30°C', 60, 15, 'cattleya_white', 10, 0),
(62, 'Каттлея Лавандовая', 'Lavender Cattleya', 'Lavendel Cattleya', 38, 'cattleya_lavender.jpg', 11, 'Требует яркого освещения', 'Requires bright light', 'Benötigt helles Licht', 'Влажность средняя, регулярный полив', 'Moderate humidity, regular watering', 'Mäßige Luftfeuchtigkeit, regelmäßiges Gießen', '20–28°C', '20–28°C', '20–28°C', 65, 16, 'cattleya_lavender', 12, 0),
(63, 'Каттлея Жёлтая', 'Yellow Cattleya', 'Gelbe Cattleya', 37, 'cattleya_yellow.jpg', 11, 'Яркое место, лучше у окна', 'Bright place, preferably near a window', 'Heller Standort, am besten am Fenster', 'Влажность 55–70%', 'Humidity 55–70%', 'Luftfeuchtigkeit 55–70%', '18–27°C', '18–27°C', '18–27°C', 58, 14, 'cattleya_yellow', 14, 0),
(64, 'Каттлея Розовая', 'Pink Cattleya', 'Rosa Cattleya', 38, 'cattleya_pink.jpg', 11, 'Яркий свет, но с притенением днём', 'Bright light, but shaded at noon', 'Helles Licht, aber Mittagsbeschattung', 'Умеренная влажность', 'Moderate humidity', 'Mäßige Luftfeuchtigkeit', '20–28°C', '20–28°C', '20–28°C', 62, 15, 'cattleya_pink', 8, 0),
(65, 'Каттлея Красная', 'Red Cattleya', 'Rote Cattleya', 40, 'cattleya_red.jpg', 11, 'Нуждается в ярком рассеянном свете', 'Needs bright indirect light', 'Benötigt helles indirektes Licht', 'Средняя влажность 50–65%', 'Moderate humidity 50–65%', 'Mäßige Luftfeuchtigkeit 50–65%', '19–27°C', '19–27°C', '19–27°C', 63, 15, 'cattleya_red', 9, 0),
(66, 'Дендробиум Нобиле Белый', 'White Dendrobium Nobile', 'Weißes Dendrobium Nobile', 28, 'dendrobium_white.jpg', 12, 'Светлое место, прямое утреннее солнце полезно', 'Bright place, morning sun beneficial', 'Heller Standort, Morgensonne ist günstig', 'Влажность 60–75%', 'Humidity 60–75%', 'Luftfeuchtigkeit 60–75%', '16–28°C', '16–28°C', '16–28°C', 55, 13, 'dendrobium_white', 20, 0),
(67, 'Дендробиум Нобиле Розовый', 'Pink Dendrobium Nobile', 'Rosa Dendrobium Nobile', 29, 'dendrobium_pink.jpg', 12, 'Нуждается в ярком свете', 'Requires bright light', 'Benötigt helles Licht', 'Влажность 60–70%', 'Humidity 60–70%', 'Luftfeuchtigkeit 60–70%', '18–27°C', '18–27°C', '18–27°C', 58, 14, 'dendrobium_pink', 18, 0),
(68, 'Дендробиум Кинга', 'Dendrobium Kingianum', 'Dendrobium Kingianum', 30, 'dendrobium_kingianum.jpg', 12, 'Яркий свет, переносит прямое солнце', 'Bright light, tolerates direct sun', 'Helles Licht, verträgt direkte Sonne', 'Влажность средняя', 'Moderate humidity', 'Mäßige Luftfeuchtigkeit', '15–26°C', '15–26°C', '15–26°C', 50, 12, 'dendrobium_kingianum', 16, 0),
(69, 'Дендробиум Антенный', 'Dendrobium Antennatum', 'Dendrobium Antennatum', 33, 'dendrobium_antennatum.jpg', 12, 'Нуждается в ярком освещении', 'Needs bright light', 'Benötigt helles Licht', 'Влажность высокая, частое опрыскивание', 'High humidity, frequent misting', 'Hohe Luftfeuchtigkeit, häufiges Besprühen', '20–30°C', '20–30°C', '20–30°C', 60, 14, 'dendrobium_antennatum', 10, 0),
(70, 'Дендробиум Фаленопсис', 'Dendrobium Phalaenopsis', 'Dendrobium Phalaenopsis', 32, 'dendrobium_phalaenopsis.jpg', 12, 'Яркое освещение, лучше южное окно', 'Bright light, best on a south window', 'Helles Licht, am besten Südfenster', 'Влажность 65–80%', 'Humidity 65–80%', 'Luftfeuchtigkeit 65–80%', '20–28°C', '20–28°C', '20–28°C', 65, 15, 'dendrobium_phalaenopsis', 9, 0),
(71, 'Цимбидиум Зелёный', 'Green Cymbidium', 'Grünes Cymbidium', 40, 'cymbidium_green.jpg', 13, 'Светлое место, переносит прохладу', 'Bright spot, tolerates cool temps', 'Heller Standort, verträgt Kühle', 'Влажность 50–70%', 'Humidity 50–70%', 'Luftfeuchtigkeit 50–70%', '12–24°C', '12–24°C', '12–24°C', 75, 18, 'cymbidium_green', 10, 0),
(72, 'Цимбидиум Жёлтый', 'Yellow Cymbidium', 'Gelbes Cymbidium', 43, 'cymbidium_yellow.jpg', 13, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Средняя влажность', 'Moderate humidity', 'Mäßige Luftfeuchtigkeit', '15–25°C', '15–25°C', '15–25°C', 80, 17, 'cymbidium_yellow', 8, 0),
(73, 'Цимбидиум Розовый', 'Pink Cymbidium', 'Rosa Cymbidium', 44, 'cymbidium_pink.jpg', 13, 'Яркое место, избегать прямого солнца', 'Bright place, avoid direct sun', 'Heller Standort, direkte Sonne vermeiden', 'Влажность 55–70%', 'Humidity 55–70%', 'Luftfeuchtigkeit 55–70%', '14–22°C', '14–22°C', '14–22°C', 85, 18, 'cymbidium_pink', 6, 0),
(74, 'Цимбидиум Белый', 'White Cymbidium', 'Weißes Cymbidium', 41, 'cymbidium_white.jpg', 13, 'Светлое место, хорошо возле окна', 'Bright place, good near window', 'Heller Standort, gut am Fenster', 'Умеренная влажность', 'Moderate humidity', 'Mäßige Luftfeuchtigkeit', '12–22°C', '12–22°C', '12–22°C', 70, 16, 'cymbidium_white', 12, 0),
(75, 'Цимбидиум Красный', 'Red Cymbidium', 'Rotes Cymbidium', 43, 'cymbidium_red.jpg', 13, 'Нуждается в ярком освещении', 'Needs bright light', 'Benötigt helles Licht', 'Влажность 50–65%', 'Humidity 50–65%', 'Luftfeuchtigkeit 50–65%', '14–24°C', '14–24°C', '14–24°C', 90, 19, 'cymbidium_red', 7, 0),
(76, 'Онцидиум Шерри Бэби', 'Oncidium Sharry Baby', 'Oncidium Sharry Baby', 28, 'oncidium_sharry.jpg', 14, 'Яркий рассеянный свет, избегать прямого солнца', 'Bright indirect light, avoid direct sun', 'Helles indirektes Licht, direkte Sonne vermeiden', 'Влажность воздуха 60–70%', 'Air humidity 60–70%', 'Luftfeuchtigkeit 60–70%', '18–28°C', '18–28°C', '18–28°C', 40, 12, 'oncidium_sharry', 18, 0),
(77, 'Онцидиум Твинкл', 'Oncidium Twinkle', 'Oncidium Twinkle', 24, 'oncidium_twinkle.jpg', 14, 'Светлое место, защита от прямого солнца', 'Bright spot, protected from direct sun', 'Heller Standort, Schutz vor direkter Sonne', 'Влажность 55–70%', 'Humidity 55–70%', 'Luftfeuchtigkeit 55–70%', '16–26°C', '16–26°C', '16–26°C', 35, 11, 'oncidium_twinkle', 20, 0),
(78, 'Онцидиум Сладкий Шоколад', 'Oncidium Sweet Chocolate', 'Oncidium Sweet Chocolate', 26, 'oncidium_choco.jpg', 14, 'Светлое место, лучше утреннее солнце', 'Bright location, preferably morning sun', 'Heller Standort, vorzugsweise Morgensonne', 'Влажность 60%', 'Humidity 60%', 'Luftfeuchtigkeit 60%', '18–27°C', '18–27°C', '18–27°C', 45, 13, 'oncidium_choco', 15, 0),
(79, 'Онцидиум Голден Шауэр', 'Oncidium Golden Shower', 'Oncidium Golden Shower', 24, 'oncidium_golden.jpg', 14, 'Много света, но не прямые полуденные лучи', 'Plenty of light, but avoid midday sun', 'Viel Licht, aber keine Mittagssonne', 'Влажность 55–65%', 'Humidity 55–65%', 'Luftfeuchtigkeit 55–65%', '18–28°C', '18–28°C', '18–28°C', 50, 14, 'oncidium_golden', 12, 0),
(80, 'Онцидиум Джунгл Монарх', 'Oncidium Jungle Monarch', 'Oncidium Jungle Monarch', 27, 'oncidium_jungle.jpg', 14, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Влажность 60–70%', 'Humidity 60–70%', 'Luftfeuchtigkeit 60–70%', '17–26°C', '17–26°C', '17–26°C', 55, 15, 'oncidium_jungle', 10, 0),
(81, 'Ванда Блю Мэджик', 'Vanda Blue Magic', 'Vanda Blue Magic', 45, 'vanda_blue.jpg', 15, 'Очень яркий свет, в том числе прямой', 'Very bright light, including direct sun', 'Sehr helles Licht, auch direkte Sonne', 'Высокая влажность 70–80%', 'High humidity 70–80%', 'Hohe Luftfeuchtigkeit 70–80%', '20–30°C', '20–30°C', '20–30°C', 60, 16, 'vanda_blue', 8, 0),
(82, 'Ванда Коэрулеа', 'Vanda coerulea', 'Vanda coerulea', 49, 'vanda_coerulea.jpg', 15, 'Нуждается в ярком солнечном освещении', 'Needs bright sunlight', 'Benötigt helles Sonnenlicht', 'Влажность воздуха 65–75%', 'Air humidity 65–75%', 'Luftfeuchtigkeit 65–75%', '18–28°C', '18–28°C', '18–28°C', 70, 17, 'vanda_coerulea', 6, 0),
(83, 'Ванда Сансай Блю', 'Vanda Sansai Blue', 'Vanda Sansai Blue', 50, 'vanda_sansai.jpg', 15, 'Яркий свет и свежий воздух', 'Bright light and fresh air', 'Helles Licht und frische Luft', 'Влажность 70%', 'Humidity 70%', 'Luftfeuchtigkeit 70%', '20–29°C', '20–29°C', '20–29°C', 75, 18, 'vanda_sansai', 5, 0),
(84, 'Ванда Ротшильд', 'Vanda Rothschildiana', 'Vanda Rothschildiana', 52, 'vanda_roths.jpg', 15, 'Яркий свет, допустимо утреннее солнце', 'Bright light, morning sun acceptable', 'Helles Licht, Morgensonne möglich', 'Влажность 65–75%', 'Humidity 65–75%', 'Luftfeuchtigkeit 65–75%', '20–30°C', '20–30°C', '20–30°C', 80, 19, 'vanda_roths', 4, 0),
(85, 'Ванда Гордена', 'Vanda Gordon Dillon', 'Vanda Gordon Dillon', 55, 'vanda_gordon.jpg', 15, 'Очень яркое место, много воздуха', 'Very bright place, plenty of air circulation', 'Sehr heller Standort, viel Luftzirkulation', 'Влажность 70–80%', 'Humidity 70–80%', 'Luftfeuchtigkeit 70–80%', '20–32°C', '20–32°C', '20–32°C', 85, 20, 'vanda_gordon', 3, 0),
(86, 'Мильтония Санта Барбара', 'Miltonia Santa Barbara', 'Miltonia Santa Barbara', 28, 'miltonia_sb.jpg', 16, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Влажность воздуха 55–65%', 'Air humidity 55–65%', 'Luftfeuchtigkeit 55–65%', '16–24°C', '16–24°C', '16–24°C', 45, 14, 'miltonia_sb', 14, 0),
(87, 'Мильтония Кловесия', 'Miltonia Clowesii', 'Miltonia Clowesii', 30, 'miltonia_clowesii.jpg', 16, 'Светлое место, утреннее солнце допустимо', 'Bright place, morning sun acceptable', 'Heller Standort, Morgensonne möglich', 'Влажность 60%', 'Humidity 60%', 'Luftfeuchtigkeit 60%', '17–25°C', '17–25°C', '17–25°C', 50, 15, 'miltonia_clowesii', 12, 0),
(88, 'Мильтония Мореллиана', 'Miltonia moreliana', 'Miltonia moreliana', 30, 'miltonia_moreliana.jpg', 16, 'Светлое место без прямого солнца', 'Bright place without direct sun', 'Heller Standort ohne direkte Sonne', 'Влажность 60–70%', 'Humidity 60–70%', 'Luftfeuchtigkeit 60–70%', '18–26°C', '18–26°C', '18–26°C', 55, 15, 'miltonia_moreliana', 10, 0),
(89, 'Мильтония Флавесценс', 'Miltonia flavescens', 'Miltonia flavescens', 28, 'miltonia_flav.jpg', 16, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Влажность 55–65%', 'Humidity 55–65%', 'Luftfeuchtigkeit 55–65%', '16–24°C', '16–24°C', '16–24°C', 42, 13, 'miltonia_flav', 15, 0),
(90, 'Мильтония Реджнелл', 'Miltonia regnellii', 'Miltonia regnellii', 29, 'miltonia_reg.jpg', 16, 'Светлое место, избегать прямого солнца', 'Bright place, avoid direct sun', 'Heller Standort, direkte Sonne vermeiden', 'Влажность 60%', 'Humidity 60%', 'Luftfeuchtigkeit 60%', '17–25°C', '17–25°C', '17–25°C', 48, 14, 'miltonia_reg', 11, 0),
(91, 'Пафиопедилум Мауди', 'Paphiopedilum Maudiae', 'Paphiopedilum Maudiae', 27, 'paph_maudiae.jpg', 17, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Влажность воздуха 55–65%', 'Air humidity 55–65%', 'Luftfeuchtigkeit 55–65%', '18–26°C', '18–26°C', '18–26°C', 35, 13, 'paph_maudiae', 20, 0),
(92, 'Пафиопедилум Ротшильда', 'Paphiopedilum rothschildianum', 'Paphiopedilum rothschildianum', 35, 'paph_roths.jpg', 17, 'Умеренный свет, рассеянное освещение', 'Moderate light, diffused lighting', 'Mäßiges Licht, diffuses Licht', 'Влажность 60–70%', 'Humidity 60–70%', 'Luftfeuchtigkeit 60–70%', '20–28°C', '20–28°C', '20–28°C', 40, 15, 'paph_roths', 8, 0),
(93, 'Пафиопедилум Беллатум', 'Paphiopedilum bellatulum', 'Paphiopedilum bellatulum', 29, 'paph_bell.jpg', 17, 'Яркое место без прямого солнца', 'Bright place without direct sun', 'Heller Standort ohne direkte Sonne', 'Влажность 60%', 'Humidity 60%', 'Luftfeuchtigkeit 60%', '18–26°C', '18–26°C', '18–26°C', 30, 14, 'paph_bell', 12, 0),
(94, 'Пафиопедилум Суки', 'Paphiopedilum sukhakulii', 'Paphiopedilum sukhakulii', 29, 'paph_sukh.jpg', 17, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Влажность воздуха 60–70%', 'Air humidity 60–70%', 'Luftfeuchtigkeit 60–70%', '19–27°C', '19–27°C', '19–27°C', 32, 13, 'paph_sukh', 14, 0),
(95, 'Пафиопедилум Хенрианум', 'Paphiopedilum henryanum', 'Paphiopedilum henryanum', 31, 'paph_henry.jpg', 17, 'Светлое место с фильтрованным светом', 'Bright spot with filtered light', 'Heller Standort mit gefiltertem Licht', 'Влажность 60%', 'Humidity 60%', 'Luftfeuchtigkeit 60%', '18–26°C', '18–26°C', '18–26°C', 34, 14, 'paph_henry', 10, 0),
(96, 'Мини-Фаленопсис Белый', 'Mini Phalaenopsis White', 'Mini-Phalaenopsis Weiß', 15, 'mini_phal_white.jpg', 18, 'Яркий рассеянный свет', 'Bright indirect light', 'Helles indirektes Licht', 'Влажность воздуха 55–65%', 'Air humidity 55–65%', 'Luftfeuchtigkeit 55–65%', '18–26°C', '18–26°C', '18–26°C', 18, 8, 'mini_phal_white', 30, 0),
(97, 'Мини-Фаленопсис Розовый', 'Mini Phalaenopsis Pink', 'Mini-Phalaenopsis Rosa', 16, 'mini_phal_pink.jpg', 18, 'Светлое место, избегать прямого солнца', 'Bright spot, avoid direct sun', 'Heller Standort, direkte Sonne vermeiden', 'Влажность 60%', 'Humidity 60%', 'Luftfeuchtigkeit 60%', '18–26°C', '18–26°C', '18–26°C', 20, 9, 'mini_phal_pink', 25, 0),
(98, 'Мини-Дендробиум Белый', 'Mini Dendrobium White', 'Mini-Dendrobium Weiß', 17, 'mini_dend_white.jpg', 18, 'Яркий свет, в том числе утреннее солнце', 'Bright light, including morning sun', 'Helles Licht, einschließlich Morgensonne', 'Влажность 55–65%', 'Humidity 55–65%', 'Luftfeuchtigkeit 55–65%', '18–27°C', '18–27°C', '18–27°C', 22, 9, 'mini_dend_white', 22, 0),
(99, 'Мини-Онцидиум Желтый', 'Mini Oncidium Yellow', 'Mini-Oncidium Gelb', 18, 'mini_oncidium_yellow.jpg', 18, 'Яркий свет, прямые лучи в утренние часы', 'Bright light, morning sun acceptable', 'Helles Licht, Morgensonne möglich', 'Влажность 60%', 'Humidity 60%', 'Luftfeuchtigkeit 60%', '18–26°C', '18–26°C', '18–26°C', 24, 10, 'mini_oncidium_yellow', 20, 0),
(100, 'Мини-Ванда Сиреневая', 'Mini Vanda Purple', 'Mini-Vanda Lila', 20, 'mini_vanda_purple.jpg', 18, 'Очень яркий свет, свежий воздух', 'Very bright light, fresh air', 'Sehr helles Licht, frische Luft', 'Влажность 65–75%', 'Humidity 65–75%', 'Luftfeuchtigkeit 65–75%', '20–28°C', '20–28°C', '20–28°C', 25, 10, 'mini_vanda_purple', 18, 0),
(101, 'Искусственная Роза Красная', 'Artificial Red Rose', 'Künstliche Rote Rose', 19, 'artificial_rose_red.jpg', 19, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 40, 12, 'artificial_rose_red', 120, 0),
(102, 'Искусственная Роза Белая', 'Artificial White Rose', 'Künstliche Weiße Rose', 20, 'artificial_rose_white.jpg', 19, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 45, 14, 'artificial_rose_white', 90, 0),
(103, 'Искусственная Роза Розовая', 'Artificial Pink Rose', 'Künstliche Rosa Rose', 20, 'artificial_rose_pink.jpg', 19, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 50, 15, 'artificial_rose_pink', 100, 0),
(104, 'Искусственная Орхидея Белая', 'Artificial White Orchid', 'Künstliche Weiße Orchidee', 23, 'artificial_orchid_white.jpg', 20, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 60, 14, 'artificial_orchid_white', 75, 0),
(105, 'Искусственная Орхидея Фиолетовая', 'Artificial Purple Orchid', 'Künstliche Lila Orchidee', 23, 'artificial_orchid_purple.jpg', 20, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 65, 16, 'artificial_orchid_purple', 80, 0),
(106, 'Искусственная Орхидея Желтая', 'Artificial Yellow Orchid', 'Künstliche Gelbe Orchidee', 24, 'artificial_orchid_yellow.jpg', 20, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 70, 16, 'artificial_orchid_yellow', 60, 0),
(107, 'Искусственные Тюльпаны Красные', 'Artificial Red Tulips', 'Künstliche Rote Tulpen', 16, 'artificial_tulip_red.jpg', 21, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 35, 12, 'artificial_tulip_red', 150, 0),
(108, 'Искусственные Тюльпаны Белые', 'Artificial White Tulips', 'Künstliche Weiße Tulpen', 17, 'artificial_tulip_white.jpg', 21, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 35, 12, 'artificial_tulip_white', 150, 0),
(109, 'Искусственные Тюльпаны Микс', 'Artificial Mixed Tulips', 'Künstliche Gemischte Tulpen', 17, 'artificial_tulip_mix.jpg', 21, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 40, 14, 'artificial_tulip_mix', 120, 0),
(110, 'Искусственная Лилия Белая', 'Artificial White Lily', 'Künstliche Weiße Lilie', 22, 'artificial_lily_white.jpg', 22, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 55, 15, 'artificial_lily_white', 95, 0),
(111, 'Искусственная Лилия Розовая', 'Artificial Pink Lily', 'Künstliche Rosa Lilie', 22, 'artificial_lily_pink.jpg', 22, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 55, 15, 'artificial_lily_pink', 85, 0),
(112, 'Искусственная Лилия Желтая', 'Artificial Yellow Lily', 'Künstliche Gelbe Lilie', 23, 'artificial_lily_yellow.jpg', 22, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 60, 16, 'artificial_lily_yellow', 70, 0),
(113, 'Искусственная Гортензия Голубая', 'Artificial Blue Hydrangea', 'Künstliche Blaue Hortensie', 25, 'artificial_hydrangea_blue.jpg', 23, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 50, 18, 'artificial_hydrangea_blue', 70, 0),
(114, 'Искусственная Гортензия Розовая', 'Artificial Pink Hydrangea', 'Künstliche Rosa Hortensie', 26, 'artificial_hydrangea_pink.jpg', 23, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 50, 18, 'artificial_hydrangea_pink', 65, 0),
(115, 'Искусственная Гортензия Белая', 'Artificial White Hydrangea', 'Künstliche Weiße Hortensie', 26, 'artificial_hydrangea_white.jpg', 23, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 55, 18, 'artificial_hydrangea_white', 60, 0),
(116, 'Искусственная Калла Белая', 'Artificial White Calla', 'Künstliche Weiße Calla', 20, 'artificial_calla_white.jpg', 24, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 45, 13, 'artificial_calla_white', 100, 0),
(117, 'Искусственная Калла Желтая', 'Artificial Yellow Calla', 'Künstliche Gelbe Calla', 21, 'artificial_calla_yellow.jpg', 24, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 50, 14, 'artificial_calla_yellow', 95, 0),
(118, 'Искусственная Калла Розовая', 'Artificial Pink Calla', 'Künstliche Rosa Calla', 21, 'artificial_calla_pink.jpg', 24, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 55, 15, 'artificial_calla_pink', 90, 0),
(119, 'Искусственная Сакура Розовая', 'Artificial Pink Sakura', 'Künstliche Rosa Sakura', 29, 'artificial_sakura_pink.jpg', 25, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 80, 20, 'artificial_sakura_pink', 50, 0),
(120, 'Искусственная Сакура Белая', 'Artificial White Sakura', 'Künstliche Weiße Sakura', 29, 'artificial_sakura_white.jpg', 25, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 85, 20, 'artificial_sakura_white', 40, 0),
(121, 'Искусственная Сакура Микс', 'Artificial Mixed Sakura', 'Künstliche Gemischte Sakura', 30, 'artificial_sakura_mix.jpg', 25, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 90, 22, 'artificial_sakura_mix', 35, 0),
(122, 'Искусственные Ветки Зеленые', 'Artificial Green Branches', 'Künstliche Grüne Zweige', 13, 'artificial_branches_green.jpg', 26, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 60, 10, 'artificial_branches_green', 200, 0),
(123, 'Искусственные Ветки Красные', 'Artificial Red Branches', 'Künstliche Rote Zweige', 13, 'artificial_branches_red.jpg', 26, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 65, 10, 'artificial_branches_red', 180, 0),
(124, 'Искусственные Ветки Сухие', 'Artificial Dry Branches', 'Künstliche Trockene Zweige', 14, 'artificial_branches_dry.jpg', 26, 'Не требует освещения.', 'No light required.', 'Benötigt kein Licht.', 'Не требует влажности.', 'No humidity required.', 'Benötigt keine Luftfeuchtigkeit.', 'Не требует температурных условий.', 'No temperature requirements.', 'Benötigt keine Temperaturbedingungen.', 70, 10, 'artificial_branches_dry', 160, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `settings`
--

CREATE TABLE `settings` (
  `secretKey` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin COMMENT='eyBR4yUsGO3aN3Ta8Ml1tReK2VXiZjA32vFpdweQSKAfu9Qo3jb9JTcex7hdiXUFnHnAotYARzl1GKVDJZQlF0DnVhN7M4P12ySt';

--
-- Дамп данных таблицы `settings`
--

INSERT INTO `settings` (`secretKey`) VALUES
('eyBR4yUsGO3aN3Ta8Ml1tReK2VXiZjA32vFpdweQSKAfu9Qo3jb9JTcex7hdiXUFnHnAotYARzl1GKVDJZQlF0DnVhN7M4P12ySt');

-- --------------------------------------------------------

--
-- Структура таблицы `statuses`
--

CREATE TABLE `statuses` (
  `id` int NOT NULL,
  `statusName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `statusName_en` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `statusName_de` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `statuses`
--

INSERT INTO `statuses` (`id`, `statusName`, `statusName_en`, `statusName_de`) VALUES
(1, 'В обработке', 'Processing', 'In Bearbeitung'),
(2, 'Ожидается отправка', 'Awaiting Shipment', 'Versand wird vorbereitet'),
(3, 'Доставляется', 'Out for Delivery', 'In Zustellung'),
(4, 'Выполнен', 'Completed', 'Abgeschlossen');

-- --------------------------------------------------------

--
-- Структура таблицы `types`
--

CREATE TABLE `types` (
  `id` int NOT NULL,
  `name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `name_en` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `name_de` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `category_id` int NOT NULL,
  `url` varchar(30) COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `types`
--

INSERT INTO `types` (`id`, `name`, `name_en`, `name_de`, `category_id`, `url`) VALUES
(1, 'Цветущие', 'Flowering', 'Blühende', 1, 'flowering'),
(2, 'Не цветущие', 'Non-flowering', 'Nicht blühende', 1, 'non-flowering'),
(3, 'Пальмы', 'Palms', 'Palmen', 1, 'palms'),
(4, 'Хищные', 'Carnivorous', 'Fleischfressende', 1, 'carnivorous'),
(5, 'Шаровидные', 'Globular', 'Kugelförmige', 2, 'globular'),
(6, 'Колонновидные', 'Columnar', 'Säulenförmige', 2, 'columnar'),
(7, 'Плоские', 'Flat', 'Flache', 2, 'flat'),
(8, 'Кустящиеся', 'Bushy', 'Buschige', 2, 'bushy'),
(9, 'Эпифитные', 'Epiphytic', 'Epiphytische', 2, 'epiphytic'),
(10, 'Фаленопсисы', 'Phalaenopsis', 'Phalaenopsis', 3, 'phalaenopsis'),
(11, 'Каттлеи', 'Cattleya', 'Cattleya', 3, 'cattleya'),
(12, 'Дендробиумы', 'Dendrobium', 'Dendrobium', 3, 'dendrobium'),
(13, 'Цимбидиумы', 'Cymbidium', 'Cymbidium', 3, 'cymbidium'),
(14, 'Онцидиумы', 'Oncidium', 'Oncidium', 3, 'oncidium'),
(15, 'Ванды', 'Vanda', 'Vanda', 3, 'vanda'),
(16, 'Мильтонии', 'Miltonia', 'Miltonia', 3, 'miltonia'),
(17, 'Пафиопедилумы', 'Paphiopedilum', 'Paphiopedilum', 3, 'paphiopedilum'),
(18, 'Мини-орхидеи', 'Mini orchids', 'Mini-Orchideen', 3, 'mini-orchids'),
(19, 'Розы', 'Roses', 'Rosen', 4, 'roses'),
(20, 'Орхидеи', 'Orchids', 'Orchideen', 4, 'orchids-kunst'),
(21, 'Тюльпаны', 'Tulips', 'Tulpen', 4, 'tulips'),
(22, 'Лилии', 'Lilies', 'Lilien', 4, 'lilies'),
(23, 'Гортензии', 'Hydrangeas', 'Hortensien', 4, 'hydrangeas'),
(24, 'Каллы', 'Calla lilies', 'Calla-Lilien', 4, 'calla-lilies'),
(25, 'Сакура', 'Sakura', 'Sakura', 4, 'sakura'),
(26, 'Декоративные ветки', 'Decorative branches', 'Lilien', 4, 'decor-branches');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `firstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `lastName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `password` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `accessToken` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `accTokenEndTime` int DEFAULT NULL,
  `refreshToken` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `refrTokenEndTime` int DEFAULT NULL,
  `updatedAt` int DEFAULT NULL,
  `deliveryType_id` int DEFAULT NULL,
  `deliveryInfo` json DEFAULT NULL,
  `paymentType_id` int DEFAULT NULL,
  `emailVerification` tinyint(1) NOT NULL DEFAULT '0',
  `blocked` tinyint(1) NOT NULL DEFAULT '0',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `firstName`, `lastName`, `email`, `phone`, `password`, `accessToken`, `accTokenEndTime`, `refreshToken`, `refrTokenEndTime`, `updatedAt`, `deliveryType_id`, `deliveryInfo`, `paymentType_id`, `emailVerification`, `blocked`, `createdAt`) VALUES
(1, 'Volodymyr', 'Volobuiev', 'bobbygtx@gmail.com', '+491551068321', 'd4df90785ea3b4309efd8050397005cd9efd4d56ba3301a85361486830cc30cc', '3IOETDjlV3bH4K1diPtBwMUyxxHVeYTZCSC6iMbkzcabdDgpFR9l3UVeQgzATnHho1g91OKEeikz2VviwXc2KqOZXc2O10ybiWxQ', 1759540637, 'FyhF3nWoTYeRZ0Mn5fHswd3kUy4lG08hummtqslvJibRZt4GR6U83KKTqL3OiVzJ55p9Src2r3pDAsVJcQMW1PZy7MViu33ebaP0tHuF5IJNXQjknGdqGJX7', 1762070380, 1759449568, 2, '{\"zip\": \"70373\", \"city\": \"Stuttgart\", \"house\": \"20\", \"region\": \"Baden-Württemberg\", \"street\": \"Munichstraße\", \"entrance\": \"2\", \"apartment\": \"\"}', 1, 0, 0, '2025-09-16 10:10:15'),
(2, 'Volodymyr', 'Volobuiev', 'bobbygtx2@gmail.com', '+491771750803', 'd4df90785ea3b4309efd8050397005cd9efd4d56ba3301a85361486830cc30cc', 'RiQE7dvksifO5HRuKsQQ9oJXQa6NgFOpkvPDWYX1JBKpVFGGuV0muZexFQAyiIcahIzjjEacotxLXunpdKsxEMdiWJIVDiZPnCCW', 1759361845, 'KJeeFTwsUmm4PpTNi5WuJwFzN0WA8k7VZ9L8mbU4AGyfPUqgpp8m0sU2NeElMkWEdVCQO5VAdesaT5vQGJudU48smam6BfPJ24uPcV9xiFvCv6ElrYQqn4OB', 1761891588, 1757860434, 2, '{\"zip\": \"70373\", \"city\": \"Stuttgart\", \"house\": \"19\", \"region\": \"Baden Württemberg\", \"street\": \"Munichstraße\", \"entrance\": \"2\", \"apartment\": \"\"}', 3, 0, 0, '2025-09-16 10:10:15'),
(3, 'Nikolayß', 'Müller', 'bobbygtx3@gmail.com', '+491771750803', 'd4df90785ea3b4309efd8050397005cd9efd4d56ba3301a85361486830cc30cc', 'dGf8Gg45VLDMrN6OjvmTscsasUbEF7fhZRHCNPYVS6w3KsNHJxICcHf50p1IOYAui5KJhB9dzwe3WnAd0upHJG7UKjJUO2GOe7jT', 1757871039, 'b8Xf1BYISBt7Pm3SABVtv28vADQ1Ur2u4D1KoOK1L9SGj7ItUupjnNkGIiyE220oNECPLDTdKN2xREvlXZaMgAXA40CxxZMEVRwDWE1kwZPuKDmLky2cJc5W', 1760488662, 1757859321, 2, NULL, 3, 0, 1, '2025-09-16 10:10:15'),
(4, 'Olesia', 'Volobuieva', 'leskagtx@gmail.com', '+4915510448557', 'd4df90785ea3b4309efd8050397005cd9efd4d56ba3301a85361486830cc30cc', 'M4sl2B9tkdJZ5PW1H2wFiOQ8tC1dfZ8aDhvc83LtB0NLPi5nP2SvWcSnC17rcPnEzGSaVS8hcVgy1crSfhfwX7MS3VQqEahFrLRV', 1758487760, '9cDGTIhdSRnbU8MZYEM75t1L6ckcmVUVbWcx2FA3qyvOnkgOSRPhGfl1MssAu7oUECB0tz8gwqTMX4qJIG5VFJ2eUQ2IjVD5yC7aQj7EFVK1ODOdLHaYlA6f', 1761105383, 1757858717, 1, NULL, 1, 0, 0, '2025-09-16 10:10:15'),
(5, 'Volodymyr', 'Volobuiev', 'bobbygtx5@gmail.com', '+491771750803', 'd4df90785ea3b4309efd8050397005cd9efd4d56ba3301a85361486830cc30cc', 'P1jLWlKOcwo2zOSDtsunR7BFnhBdiNMaahrXETxhTYWfIuXjcB0ZC5HLqVROecgOLvhwEsATpmnbnkKVs04906Qnth7ZppqlVMKF', 1758323069, 'uZtkZjutDUETFsdJjVFN5ZHQRfy51EnBdxoFE83hb9AMSrD3ph5mQPxyF8Aoz6RQj96A750qeA3cf620eNUvOjZefeaLfuRpJ9VaQ1AaH9wBKjxkokTU3hsD', 1760940692, 1758016205, NULL, NULL, NULL, 0, 0, '2025-09-16 10:10:15'),
(7, 'Vladyslav', 'Volobuiev', 'bladegtx@gmail.com', '+491771750803', 'd4df90785ea3b4309efd8050397005cd9efd4d56ba3301a85361486830cc30cc', 'wfmcxMegSIDSshOY5aNBvtsYJpTA4BOsBMulGaUAShFsqiACadrYxxfXfDzv13xORHQ8XpUHfg5IkuTsi1FGh3fPDEG7EgZDoVQ7', 1760369860, '1TggbEIeMiJM2Hqu1yWLLseyp7lFJojd6wHaznUzRND4VTo3X35Ff6uloX7EzgC1BkzAUmezCvvx3TlrwTN7G78ZQxQpsFpwGvwZQYvX70xGEqiakIFBTENh', 1762899603, 1759682649, NULL, NULL, NULL, 0, 0, '2025-10-05 16:44:09');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `delivery_types`
--
ALTER TABLE `delivery_types`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `userId_and_productId` (`user_id`,`product_id`),
  ADD KEY `user-id-fav` (`user_id`),
  ADD KEY `product-id-fav` (`product_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user-id-orders` (`user_id`),
  ADD KEY `status-id-orders` (`status_id`),
  ADD KEY `deliveryType_id` (`deliveryType_id`),
  ADD KEY `paymentType_id` (`paymentType_id`);

--
-- Индексы таблицы `payment_types`
--
ALTER TABLE `payment_types`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type-id-products` (`type_id`);

--
-- Индексы таблицы `statuses`
--
ALTER TABLE `statuses`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `types`
--
ALTER TABLE `types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category-id-types` (`category_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paymentType_index` (`paymentType_id`),
  ADD KEY `deliveryType_index` (`deliveryType_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `delivery_types`
--
ALTER TABLE `delivery_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `payment_types`
--
ALTER TABLE `payment_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT для таблицы `statuses`
--
ALTER TABLE `statuses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `types`
--
ALTER TABLE `types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`deliveryType_id`) REFERENCES `delivery_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`paymentType_id`) REFERENCES `payment_types` (`id`) ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `types`
--
ALTER TABLE `types`
  ADD CONSTRAINT `types_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`deliveryType_id`) REFERENCES `delivery_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`paymentType_id`) REFERENCES `payment_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
