-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-05-2025 a las 13:05:03
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
(3, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelos`
--

CREATE TABLE `modelos` (
  `id` int(11) NOT NULL,
  `nombre_modelo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` float DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `archivo_stl` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modelos`
--

INSERT INTO `modelos` (`id`, `nombre_modelo`, `descripcion`, `precio`, `imagen_url`, `archivo_stl`) VALUES
(1, 'Robot Articulado X-01', 'Un robot genial y articulado, listo para imprimir.', 13, 'img/modelo1_placeholder.jpg', NULL),
(2, 'Jarrón Geométrico', 'Elegante jarrón con diseño geométrico moderno.', 8, 'img/modelo2_placeholder.jpg', NULL),
(3, 'Miniatura Fantasía Épica', 'Detallada miniatura para tus juegos de rol.', 9, 'img/modelo3_placeholder.jpg', NULL),
(4, 'Soporte para Auriculares', 'Práctico soporte para tus auriculares gamer.', 6, 'img/modelo4_placeholder.jpg', NULL),
(5, 'Robot Articulado X-01', 'Un robot genial y articulado, listo para imprimir.', 13, 'img/modelo1_placeholder.jpg', NULL),
(6, 'Jarrón Geométrico', 'Elegante jarrón con diseño geométrico moderno.', 8, 'img/modelo2_placeholder.jpg', NULL),
(7, 'Miniatura Fantasía Épica', 'Detallada miniatura para tus juegos de rol.', 9, 'img/modelo3_placeholder.jpg', NULL),
(8, 'Soporte para Auriculares', 'Práctico soporte para tus auriculares gamer.', 6, 'img/modelo4_placeholder.jpg', NULL),
(9, 'test01', 'test012', 12, NULL, 'models_files/model_68398e63eae3c1.19788283.3mf');

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
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`usuario_id`,`modelo_id`),
  ADD KEY `fk_favoritos_modelos_idx` (`modelo_id`);

--
-- Indices de la tabla `modelos`
--
ALTER TABLE `modelos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `modelos`
--
ALTER TABLE `modelos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favoritos_modelos` FOREIGN KEY (`modelo_id`) REFERENCES `modelos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favoritos_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
