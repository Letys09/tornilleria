-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-08-2023 a las 17:29:21
-- Versión del servidor: 10.4.21-MariaDB
-- Versión de PHP: 7.3.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `base_inventario`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_accion`
--

CREATE TABLE `seg_accion` (
  `id_accion` int(11) NOT NULL,
  `fk_modulo` int(11) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `descripcion` varchar(80) NOT NULL,
  `creada` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `url` varchar(80) DEFAULT NULL,
  `id_html` varchar(25) DEFAULT NULL,
  `icono` varchar(40) DEFAULT NULL,
  `visible` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `seg_accion`
--

INSERT INTO `seg_accion` (`id_accion`, `fk_modulo`, `nombre`, `descripcion`, `creada`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES
(6, 6, 'Ver Usuarios', '', '2017-11-03 14:10:42', 1, NULL, NULL, NULL, 0),
(17, 17, 'Ver Bitácora de acciones', '', '2017-11-03 14:10:42', 1, NULL, NULL, NULL, 0),
(37, 6, 'Agregar Usuario', '', '2017-11-03 14:10:42', 1, NULL, NULL, NULL, 0),
(38, 6, 'Activar/Desactivar Usuario', '', '2017-11-03 14:10:42', 1, NULL, NULL, NULL, 0),
(39, 6, 'Modificar Información', '', '2017-11-03 14:10:42', 1, NULL, NULL, NULL, 0),
(66, 6, 'Agregar Directivo y Administrador', '', '2017-11-03 17:37:21', 1, NULL, NULL, NULL, 0),
(67, 6, 'Asignar Permisos', '', '2017-11-06 14:37:39', 1, NULL, NULL, NULL, 0),
(134, 6, 'Editar usuario y contraseña', '', '2022-04-21 18:18:50', 1, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_log`
--

CREATE TABLE `seg_log` (
  `id` int(10) NOT NULL,
  `fk_id_usuario` int(10) NOT NULL,
  `fk_session` int(10) NOT NULL,
  `fecha` datetime NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `registro` varchar(20) NOT NULL,
  `tipo` int(10) NOT NULL,
  `fk_empresarial` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_modulo`
--

CREATE TABLE `seg_modulo` (
  `id_modulo` int(11) NOT NULL,
  `nombre` varchar(40) NOT NULL,
  `descripcion` varchar(80) NOT NULL,
  `url` varchar(40) NOT NULL,
  `id_html` varchar(15) NOT NULL,
  `icono` varchar(20) NOT NULL,
  `creado` datetime NOT NULL,
  `status` tinyint(1) NOT NULL,
  `visible` tinyint(1) DEFAULT 0,
  `icono_nuevo` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `seg_modulo`
--

INSERT INTO `seg_modulo` (`id_modulo`, `nombre`, `descripcion`, `url`, `id_html`, `icono`, `creado`, `status`, `visible`, `icono_nuevo`) VALUES
(6, 'Usuarios', '', '/usuarios', 'users', 'group', '2017-10-31 17:28:16', 1, 1, 'user'),
(17, 'Bitacora de acciones', '', '/log', '', 'edit', '2017-10-31 17:28:16', 1, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_permiso_perfil`
--

CREATE TABLE `seg_permiso_perfil` (
  `fk_perfil` int(11) NOT NULL,
  `fk_accion` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `seg_permiso_perfil`
--

INSERT INTO `seg_permiso_perfil` (`fk_perfil`, `fk_accion`) VALUES
(1, 6),
(1, 17),
(1, 37),
(1, 38),
(1, 39),
(1, 66),
(1, 67),
(1, 134);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_permiso_user`
--

CREATE TABLE `seg_permiso_user` (
  `id_permiso` int(11) NOT NULL,
  `fk_usuario` int(11) NOT NULL,
  `fk_accion` int(11) NOT NULL,
  `fecha_asignacion` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `seg_permiso_user`
--

INSERT INTO `seg_permiso_user` (`id_permiso`, `fk_usuario`, `fk_accion`, `fecha_asignacion`) VALUES
(1, 1, 134, '2022-09-19 15:03:21'),
(2, 1, 17, '2023-08-09 16:53:00'),
(3, 1, 66, '2022-09-19 15:03:20'),
(4, 1, 39, '2022-09-19 15:03:19'),
(5, 1, 38, '2022-09-19 15:03:19'),
(6, 1, 37, '2022-09-19 15:03:19'),
(7, 1, 67, '2022-09-19 15:02:32'),
(8, 1, 6, '2022-09-19 14:59:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_session`
--

CREATE TABLE `seg_session` (
  `id` int(10) NOT NULL,
  `fk_id_usuario` int(10) NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `token` text DEFAULT NULL,
  `started` datetime NOT NULL,
  `finished` datetime NOT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_usuario`
--

CREATE TABLE `tipo_usuario` (
  `id_tipo_usuario` int(10) UNSIGNED NOT NULL,
  `descripcion` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Volcado de datos para la tabla `tipo_usuario`
--

INSERT INTO `tipo_usuario` (`id_tipo_usuario`, `descripcion`, `status`) VALUES
(1, 'Administrador', 1),
(2, 'General', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `fk_id_tipo_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `apellidos` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `empresa` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `calle` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `numero` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `colonia` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `cod_postal` varchar(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `tel_casa` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `tel_cel` varchar(13) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(12) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `login` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `contrasena` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `nom_razon_social` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `rfc` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `calle_fact` varchar(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `no_fact` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `col_fact` varchar(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `ciudad_fact` varchar(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `edo_fact` tinyint(4) DEFAULT NULL,
  `com_got` varchar(4) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `com_fir` varchar(4) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `com_ent` varchar(4) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `entre_calle` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `periodo_fact` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `del_muni` varchar(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `del_muni_fact` varchar(150) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `cp_fact` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `edo_usuario` tinyint(1) DEFAULT 0,
  `horario` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `num_int` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `tel_adi` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `observaciones` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `id_fact` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email_confir` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `fk_empresarial` int(10) NOT NULL DEFAULT 1,
  `centro_costos` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ' ',
  `push_id` varchar(255) DEFAULT '',
  `alta` datetime DEFAULT NULL,
  `edo_electronica` tinyint(4) DEFAULT 0,
  `ilimitadas` tinyint(1) DEFAULT NULL,
  `iniciar` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `fk_id_tipo_usuario`, `nombre`, `apellidos`, `empresa`, `email`, `calle`, `numero`, `colonia`, `ciudad`, `cod_postal`, `tel_casa`, `tel_cel`, `fax`, `login`, `contrasena`, `nom_razon_social`, `rfc`, `calle_fact`, `no_fact`, `col_fact`, `ciudad_fact`, `edo_fact`, `com_got`, `com_fir`, `com_ent`, `entre_calle`, `periodo_fact`, `del_muni`, `del_muni_fact`, `cp_fact`, `edo_usuario`, `horario`, `num_int`, `tel_adi`, `observaciones`, `id_fact`, `email_confir`, `fk_empresarial`, `centro_costos`, `push_id`, `alta`, `edo_electronica`, `ilimitadas`, `iniciar`) VALUES
(1, 1, 'Angel Gabriel', 'Ramirez Alva', '', 'info@ddsmedia.net', 'Bulevar Nuevo Hidalgo', '200', 'Puerta de Hierro', 'Hidalgo', '', '7717105689', '7712026000', '', 'aramirez', 'bd2c46d6f64d7d56f2be14278d104d16', '', '', '', '', '', '', NULL, '5', '5', '', '', '', 'Pachuca de Soto', '', '', 1, '', '', '', 'Observaciones para verificar que guarde las modificaciones..', '10008003', '', 1, '', 'cveMYvC7Rh0:APA91bFQY82q7A4wz-uNvQO_yPbi9VZ4VviIk2UWqD5FmON4o5cE_JWCw5BVPzsT59P63WDDJWfhgQRl1-daCK3HTTDwEI3QrWuih29tcpQbRB1WwO-q60QFKjtAmUOfi3Ro3lUvELzv', NULL, 0, NULL, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `seg_accion`
--
ALTER TABLE `seg_accion`
  ADD PRIMARY KEY (`id_accion`),
  ADD KEY `fk_modulo` (`fk_modulo`);

--
-- Indices de la tabla `seg_log`
--
ALTER TABLE `seg_log`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `seg_modulo`
--
ALTER TABLE `seg_modulo`
  ADD PRIMARY KEY (`id_modulo`);

--
-- Indices de la tabla `seg_permiso_perfil`
--
ALTER TABLE `seg_permiso_perfil`
  ADD PRIMARY KEY (`fk_perfil`,`fk_accion`);

--
-- Indices de la tabla `seg_permiso_user`
--
ALTER TABLE `seg_permiso_user`
  ADD PRIMARY KEY (`id_permiso`),
  ADD KEY `fk_usuario` (`fk_usuario`),
  ADD KEY `fk_accion` (`fk_accion`);

--
-- Indices de la tabla `seg_session`
--
ALTER TABLE `seg_session`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  ADD PRIMARY KEY (`id_tipo_usuario`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `usuario_FKIndex1` (`fk_id_tipo_usuario`) USING BTREE,
  ADD KEY `periodo_fact` (`periodo_fact`) USING BTREE;

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `seg_accion`
--
ALTER TABLE `seg_accion`
  MODIFY `id_accion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT de la tabla `seg_log`
--
ALTER TABLE `seg_log`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `seg_modulo`
--
ALTER TABLE `seg_modulo`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `seg_permiso_user`
--
ALTER TABLE `seg_permiso_user`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `seg_session`
--
ALTER TABLE `seg_session`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  MODIFY `id_tipo_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
