-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-06-2025 a las 12:09:40
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `webmodelos3d`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre_categoria`, `fecha_creacion`) VALUES
(1, 'Figuras y Miniaturas', '2025-06-03 08:49:04'),
(2, 'Herramientas y Gadgets', '2025-06-03 08:49:04'),
(3, 'Decoración del Hogar', '2025-06-03 08:49:04'),
(4, 'Joyería', '2025-06-03 08:49:04'),
(5, 'Componentes Mecánicos', '2025-06-03 08:49:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `modelo_id` int(11) NOT NULL,
  `precio_compra` float NOT NULL,
  `fecha_compra` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `metodo_pago` varchar(50) NOT NULL,
  `transaccion_id_gateway` varchar(100) NOT NULL,
  `estado_pago` varchar(20) NOT NULL DEFAULT 'completado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `usuario_id` int(11) NOT NULL,
  `modelo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `favoritos`
--

INSERT INTO `favoritos` (`usuario_id`, `modelo_id`) VALUES
(2, 2),
(2, 3),
(2, 10),
(2, 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelos`
--

CREATE TABLE `modelos` (
  `id` int(11) NOT NULL,
  `nombre_modelo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` float DEFAULT NULL,
  `url_compra_externa` varchar(2083) DEFAULT NULL COMMENT 'URL para comprar el modelo en un sitio externo',
  `imagen_url` varchar(255) DEFAULT NULL,
  `archivo_stl` varchar(255) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `orden_destacado_index` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modelos`
--

INSERT INTO `modelos` (`id`, `nombre_modelo`, `descripcion`, `precio`, `url_compra_externa`, `imagen_url`, `archivo_stl`, `categoria_id`, `orden_destacado_index`) VALUES
(1, 'Robot Articulado X-01', 'Un robot genial y articulado, listo para imprimir.', 13, NULL, 'img/modelo1_placeholder.jpg', NULL, 4, NULL),
(2, 'Jarrón Geométrico', 'Elegante jarrón con diseño geométrico moderno.', 8, NULL, 'img/modelo2_placeholder.jpg', NULL, 1, 3),
(3, 'Miniatura Fantasía Épica', 'Detallada miniatura para tus juegos de rol.', 9, NULL, 'img/modelo3_placeholder.jpg', NULL, 1, NULL),
(4, 'Soporte para Auriculares', 'Práctico soporte para tus auriculares gamer.', 6, NULL, 'img/modelo4_placeholder.jpg', NULL, NULL, NULL),
(5, 'Robot Articulado X-01', 'Un robot genial y articulado, listo para imprimir.', 13, NULL, 'img/modelo1_placeholder.jpg', NULL, 1, NULL),
(6, 'Jarrón Geométrico', 'Elegante jarrón con diseño geométrico moderno.', 8, NULL, 'img/modelo2_placeholder.jpg', NULL, 5, NULL),
(7, 'Miniatura Fantasía Épica', 'Detallada miniatura para tus juegos de rol.', 9, NULL, 'img/modelo3_placeholder.jpg', NULL, 3, NULL),
(8, 'Soporte para Auriculares', 'Práctico soporte para tus auriculares gamer.', 6, NULL, 'img/modelo4_placeholder.jpg', NULL, NULL, NULL),
(10, 'test02', 'test02', 12.99, 'https://www.google.com/?zx=1748871149011&no_sw_cr=1', NULL, 'models_files/model_6839921b8d6a56.63255684.3mf', 2, 1),
(11, 'test01', 'Test de que funciona poner modelo con imagen', 99.99, 'https://www.google.com/?zx=1748871149011&no_sw_cr=1', 'img/model_images/img_683dbefc9ddbf5.11286333.jpg', 'models_files/model_683dbeb5a07f41.22300213.3mf', 2, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(255) NOT NULL,
  `nombre` text DEFAULT NULL,
  `alias` varchar(50) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contraseña` varchar(100) DEFAULT NULL,
  `es_admin` tinyint(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `alias`, `correo`, `contraseña`, `es_admin`) VALUES
(2, 'admin', 'admin', 'admin@gmail.com', '$2y$10$mvJld8pbnJgnT4kQtSX.We9rCmAMAuGNodo4YA3fT.8UH7Jep7YGm', 1),
(3, 'Joel Añón Rosendo', 'joelin11', 'joelin1108205@gmail.com', '$2y$10$VykDQM/I5Nv3vovolXbBTOw6etVwuMMj5UI/QlQV3i1dAacj1aL5y', 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_categoria` (`nombre_categoria`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `modelo_id` (`modelo_id`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`usuario_id`,`modelo_id`),
  ADD KEY `fk_favoritos_modelos_idx` (`modelo_id`);

--
-- Indices de la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_modelo_categoria` (`categoria_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modelos`
--
ALTER TABLE `modelos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `modelo_id` FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`),
  ADD CONSTRAINT `usuario_id` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favoritos_modelos` FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favoritos_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD CONSTRAINT `fk_modelo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
