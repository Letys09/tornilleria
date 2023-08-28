-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-08-2023 a las 02:35:14
-- Versión del servidor: 10.4.22-MariaDB
-- Versión de PHP: 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Base de datos: `tornilleria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion`
--

CREATE TABLE `direccion` (
  `id` int(10) UNSIGNED NOT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `colonia` varchar(100) DEFAULT NULL,
  `calle` varchar(100) DEFAULT NULL,
  `exterior` varchar(8) DEFAULT NULL,
  `interior` varchar(8) DEFAULT NULL,
  `cp` varchar(5) DEFAULT NULL,
  `entrecalle` varchar(255) DEFAULT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_accion`
--

CREATE TABLE `seg_accion` (
  `id` int(10) NOT NULL,
  `seg_modulo_id` int(10) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `descripcion` varchar(80) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `url` varchar(80) DEFAULT NULL,
  `id_html` varchar(25) DEFAULT NULL,
  `icono` varchar(40) DEFAULT NULL,
  `visible` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `seg_accion`
--

INSERT INTO `seg_accion` (`id`, `seg_modulo_id`, `nombre`, `descripcion`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES
(1, 1, 'Ver Usuarios', '', 1, NULL, NULL, NULL, 0),
(2, 1, 'Agregar Usuario', '', 1, NULL, NULL, NULL, 0),
(3, 1, 'Activar/Desactivar Usuario', '', 1, NULL, NULL, NULL, 0),
(4, 1, 'Modificar Información', '', 1, NULL, NULL, NULL, 0),
(5, 1, 'Asignar Permisos', '', 1, NULL, NULL, NULL, 0),
(6, 1, 'Ver Sucursales', '', 1, NULL, NULL, NULL, 0),
(7, 1, 'Agregar Sucursal', '', 1, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_log`
--

CREATE TABLE `seg_log` (
  `id` int(10) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `seg_session_id` int(10) NOT NULL,
  `fecha` datetime NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `tabla` varchar(40) NOT NULL,
  `registro` int(10) UNSIGNED NOT NULL,
  `mostrar` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_modulo`
--

CREATE TABLE `seg_modulo` (
  `id` int(10) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `url` varchar(40) NOT NULL,
  `id_html` varchar(15) NOT NULL,
  `icono` varchar(20) NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `seg_modulo`
--

INSERT INTO `seg_modulo` (`id`, `nombre`, `url`, `id_html`, `icono`, `status`) VALUES
(1, 'Usuarios', '/usuarios', '', 'fas fa-user', 1),
(2, 'Sucursales', '/sucursales', '', 'fas fa-store', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_permiso`
--

CREATE TABLE `seg_permiso` (
  `id` int(10) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `seg_accion_id` int(10) NOT NULL,
  `fecha_asigno` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `seg_permiso`
--

INSERT INTO `seg_permiso` (`id`, `usuario_id`, `seg_accion_id`, `fecha_asigno`) VALUES
(1, 1, 1, '2022-09-19 15:03:21'),
(2, 1, 2, '2023-08-09 16:53:00'),
(3, 1, 3, '2022-09-19 15:03:20'),
(4, 1, 4, '2022-09-19 15:03:19'),
(5, 1, 5, '2022-09-19 15:03:19'),
(6, 1, 6, '2022-09-19 15:03:19'),
(7, 1, 7, '2023-08-24 18:03:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_session`
--

CREATE TABLE `seg_session` (
  `id` int(10) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `iniciada` datetime NOT NULL,
  `finalizada` datetime NOT NULL,
  `token` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_tipo_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `celular` varchar(13) DEFAULT NULL,
  `login` varchar(50) NOT NULL,
  `contrasena` varchar(50) NOT NULL,
  `passcode` varchar(40) NOT NULL,
  `ultimo_acceso` datetime NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `direccion_id` int(10) NOT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `usuario_tipo_id`, `nombre`, `apellidos`, `email`, `celular`, `login`, `contrasena`, `passcode`, `ultimo_acceso`, `fecha_registro`, `direccion_id`, `status`) VALUES
(1, 1, 'Angel Gabriel', 'Ramirez Alva', 'info@ddsmedia.net', '7712026000', 'aramirez', '300a9ec51d8341a52f6bd8d2da343a5b', '866b7017f2af9f5c1eeb602eda1cf056', '2023-08-24 16:18:00', '2023-08-24 15:18:00', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_tipo`
--

CREATE TABLE `usuario_tipo` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuario_tipo`
--

INSERT INTO `usuario_tipo` (`id`, `nombre`, `status`) VALUES
(1, 'Administrador', 1),
(2, 'General', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `seg_accion`
--
ALTER TABLE `seg_accion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accion_modulo` (`seg_modulo_id`);

--
-- Indices de la tabla `seg_log`
--
ALTER TABLE `seg_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `log_usuario` (`usuario_id`),
  ADD KEY `log_session` (`seg_session_id`);

--
-- Indices de la tabla `seg_modulo`
--
ALTER TABLE `seg_modulo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `seg_permiso`
--
ALTER TABLE `seg_permiso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permiso_usuario` (`usuario_id`),
  ADD KEY `seg_permiso_accion` (`seg_accion_id`);

--
-- Indices de la tabla `seg_session`
--
ALTER TABLE `seg_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_usuario` (`usuario_id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_tipo` (`usuario_tipo_id`);

--
-- Indices de la tabla `usuario_tipo`
--
ALTER TABLE `usuario_tipo`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seg_accion`
--
ALTER TABLE `seg_accion`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de la tabla `seg_log`
--
ALTER TABLE `seg_log`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seg_modulo`
--
ALTER TABLE `seg_modulo`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `seg_permiso`
--
ALTER TABLE `seg_permiso`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `seg_session`
--
ALTER TABLE `seg_session`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuario_tipo`
--
ALTER TABLE `usuario_tipo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `seg_accion`
--
ALTER TABLE `seg_accion`
  ADD CONSTRAINT `accion_modulo` FOREIGN KEY (`seg_modulo_id`) REFERENCES `seg_modulo` (`id`);

--
-- Filtros para la tabla `seg_log`
--
ALTER TABLE `seg_log`
  ADD CONSTRAINT `log_session` FOREIGN KEY (`seg_session_id`) REFERENCES `seg_session` (`id`),
  ADD CONSTRAINT `log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `seg_permiso`
--
ALTER TABLE `seg_permiso`
  ADD CONSTRAINT `permiso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`),
  ADD CONSTRAINT `seg_permiso_accion` FOREIGN KEY (`seg_accion_id`) REFERENCES `seg_accion` (`id`);

--
-- Filtros para la tabla `seg_session`
--
ALTER TABLE `seg_session`
  ADD CONSTRAINT `session_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `usuario_tipo` FOREIGN KEY (`usuario_tipo_id`) REFERENCES `usuario_tipo` (`id`);
COMMIT;
