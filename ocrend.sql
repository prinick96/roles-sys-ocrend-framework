-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-09-2017 a las 14:53:37
-- Versión del servidor: 10.1.10-MariaDB
-- Versión de PHP: 7.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ocrend`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id_user` int(11) UNSIGNED NOT NULL,
  `user` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `pass` varchar(90) NOT NULL,
  `tmp_pass` varchar(90) NOT NULL,
  `token` varchar(90) NOT NULL,
  `rol` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0: normal , 1: admin'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id_user`, `user`, `name`, `lastname`, `email`, `pass`, `tmp_pass`, `token`, `rol`) VALUES
(1, 'prinick', 'Brayan', 'Narváez', 'prinick@ocrend.com', '$2a$10$d380221198f9c8fc02fa2OeLW0VYYVGOHJnQd1bzgem8S59YR7bK.', '', '', 1),
(2, 'prinick2', 'Brayan2', 'Narvaez2', 'asasas@xcac.com', '$2a$10$d380221198f9c8fc02fa2OeLW0VYYVGOHJnQd1bzgem8S59YR7bK.', '', '', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users_admins`
--

CREATE TABLE `users_admins` (
  `id_user_admin` int(11) UNSIGNED NOT NULL,
  `id_user` int(11) UNSIGNED NOT NULL,
  `edad` tinyint(3) UNSIGNED NOT NULL,
  `dni` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `users_admins`
--

INSERT INTO `users_admins` (`id_user_admin`, `id_user`, `edad`, `dni`) VALUES
(1, 1, 20, '1235454878');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users_normales`
--

CREATE TABLE `users_normales` (
  `id_user_normal` int(11) UNSIGNED NOT NULL,
  `id_user` int(11) UNSIGNED NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `users_normales`
--

INSERT INTO `users_normales` (`id_user_normal`, `id_user`, `direccion`, `phone`) VALUES
(1, 2, 'La Urbina, CAracas\r\nBuilding', '4149392600');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- Indices de la tabla `users_admins`
--
ALTER TABLE `users_admins`
  ADD PRIMARY KEY (`id_user_admin`),
  ADD KEY `id_user` (`id_user`);

--
-- Indices de la tabla `users_normales`
--
ALTER TABLE `users_normales`
  ADD PRIMARY KEY (`id_user_normal`),
  ADD KEY `id_user` (`id_user`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT de la tabla `users_admins`
--
ALTER TABLE `users_admins`
  MODIFY `id_user_admin` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT de la tabla `users_normales`
--
ALTER TABLE `users_normales`
  MODIFY `id_user_normal` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `users_admins`
--
ALTER TABLE `users_admins`
  ADD CONSTRAINT `users_admins_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Filtros para la tabla `users_normales`
--
ALTER TABLE `users_normales`
  ADD CONSTRAINT `users_normales_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
