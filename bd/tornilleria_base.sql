-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-09-2023 a las 22:25:40
-- Versión del servidor: 10.4.22-MariaDB
-- Versión de PHP: 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tornilleria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id` int(11) NOT NULL,
  `cli_datos_fiscales_id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `apellidos` varchar(45) DEFAULT NULL,
  `correo` varchar(45) DEFAULT NULL,
  `telefono` varchar(10) DEFAULT NULL,
  `descuento` int(11) NOT NULL DEFAULT 0,
  `saldo_favor` decimal(10,2) DEFAULT 0.00,
  `registro` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente_saldo`
--

CREATE TABLE `cliente_saldo` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `saldo` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cli_datos_fiscales`
--

CREATE TABLE `cli_datos_fiscales` (
  `id` int(11) NOT NULL,
  `regimen_fiscal` int(11) DEFAULT NULL,
  `rfc` varchar(14) DEFAULT NULL,
  `razon_social` varchar(45) DEFAULT NULL,
  `codigo_postal` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cotizacion`
--

CREATE TABLE `cotizacion` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `venta_id` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `importe` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `iva` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `comentarios` varchar(255) DEFAULT NULL,
  `vigencia` date DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `coti_detalle`
--

CREATE TABLE `coti_detalle` (
  `id` int(11) NOT NULL,
  `cotizacion_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `importe` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion`
--

CREATE TABLE `direccion` (
  `id` int(11) NOT NULL,
  `calle` varchar(80) DEFAULT NULL,
  `no_ext` varchar(10) DEFAULT NULL,
  `no_int` varchar(10) DEFAULT NULL,
  `colonia` varchar(60) DEFAULT NULL,
  `municipio` varchar(45) DEFAULT NULL,
  `estado` varchar(45) DEFAULT NULL,
  `codigo_postal` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `direccion`
--

INSERT INTO `direccion` (`id`, `calle`, `no_ext`, `no_int`, `colonia`, `municipio`, `estado`, `codigo_postal`) VALUES
(1, 'Cedro', '120', 'B', 'Venta Prieta', 'Pachuca de Soto', 'Hidalgo', '42083'),
(2, 'El Minero', '200', 'C-125', 'Pachuquilla', 'Tulancingo de Bravo', 'Hidalgo', '42500'),
(7, '', '', '', '', '', '', NULL),
(14, 'Santa Rosalia', 'Mzna I', 'Lote 40', 'Parque de Poblamiento', 'Pachuca de Soto', 'Hidalgo', '42086'),
(16, '', '', '', '', '', '', NULL),
(17, 'Cedros', '500', 'B', 'Centro', 'Tulancingo de Bravo', 'Hidalgo', '42820'),
(18, 'Santa Rosalia', 'Manzana I', 'Lote 40', 'Centro', 'Tulancingo de Bravo', 'Hidalgo', ''),
(19, '', '', '', '', '', '', NULL),
(20, 'Av. principal', '150', 'C', 'Centro', 'Tulancingo de Bravo', 'Hidalgo', '69852'),
(21, '', '', '', '', '', '', NULL),
(33, '', '', '', 'El palmar', 'Pachuca de Soto', 'Hidalgo', NULL),
(34, 'CBTIS8', '120', 'C', 'PRI Chacón', 'Pachuca de Soto', 'Hidalgo', NULL),
(35, 'CBTIS8', '120', 'C', 'PRI Chacón', 'Pachuca de Soto', 'Hidalgo', NULL),
(37, 'CBTIS8', '120', 'C', 'PRI Chacón', 'Pachuca de Soto', 'Hidalgo', NULL),
(39, 'CBTIS8', '120', 'C', 'PRI Chacón', 'Pachuca de Soto', 'Hidalgo', NULL),
(41, '', '', '', '', '', '', NULL),
(42, '', '', '', '', 'Pachuca de Soto', 'Hidalgo', NULL),
(43, 'Boulevard Nuevo Hidalgo', '200', '', 'La Puerta de Hierro', 'Pachuca de Soto', 'Hidalgo', NULL),
(44, '', '', '', '', '', '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id` int(11) NOT NULL,
  `prod_unidad_medida_id` int(11) NOT NULL,
  `prod_categoria_id` int(11) NOT NULL,
  `prod_area_id` int(11) DEFAULT NULL,
  `clave` varchar(20) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `medida` varchar(30) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `minimo` int(6) DEFAULT 0,
  `venta_kilo` tinyint(1) DEFAULT 0,
  `es_kilo` tinyint(1) DEFAULT 0,
  `venta` int(11) NOT NULL DEFAULT 0,
  `clave_sat` varchar(40) NOT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_ajuste`
--

CREATE TABLE `prod_ajuste` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `comentarios` varchar(255) DEFAULT NULL,
  `status` varchar(45) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_area`
--

CREATE TABLE `prod_area` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_categoria`
--

CREATE TABLE `prod_categoria` (
  `id` int(11) NOT NULL,
  `prod_categoria_id` int(11) DEFAULT NULL,
  `nombre` varchar(80) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_det_entrada`
--

CREATE TABLE `prod_det_entrada` (
  `id` int(11) NOT NULL,
  `prod_entrada_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_det_inventario`
--

CREATE TABLE `prod_det_inventario` (
  `id` int(11) NOT NULL,
  `prod_inventario_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `sistema` decimal(10,2) DEFAULT NULL,
  `fisico` decimal(10,2) DEFAULT NULL,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_entrada`
--

CREATE TABLE `prod_entrada` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `folio` varchar(25) DEFAULT NULL,
  `importe` decimal(10,2) DEFAULT NULL,
  `descuento` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `iva` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_inventario`
--

CREATE TABLE `prod_inventario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_kilo`
--

CREATE TABLE `prod_kilo` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `producto_origen` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_precio`
--

CREATE TABLE `prod_precio` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `menudeo` decimal(10,2) DEFAULT NULL,
  `medio` decimal(10,2) DEFAULT NULL,
  `mayoreo` decimal(10,2) DEFAULT NULL,
  `distribuidor` decimal(10,2) DEFAULT NULL,
  `actualiza` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_rango`
--

CREATE TABLE `prod_rango` (
  `id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `prod_precio_id` int(11) NOT NULL,
  `menudeo` int(11) DEFAULT NULL,
  `medio` int(11) DEFAULT NULL,
  `mayoreo` int(11) DEFAULT NULL,
  `actualiza` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_stock`
--

CREATE TABLE `prod_stock` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `tipo` tinyint(1) DEFAULT NULL,
  `inicial` decimal(10,2) DEFAULT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `final` decimal(10,2) DEFAULT NULL,
  `fecha` datetime DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `origen_tipo` tinyint(2) DEFAULT NULL,
  `origen_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prod_unidad_medida`
--

CREATE TABLE `prod_unidad_medida` (
  `id` int(11) NOT NULL,
  `clave` varchar(45) NOT NULL,
  `descripcion` varchar(45) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `prod_unidad_medida`
--

INSERT INTO `prod_unidad_medida` (`id`, `clave`, `descripcion`, `status`) VALUES
(1, 'H87', 'Pieza', 1),
(2, 'MTK', 'Metro', 1),
(3, 'KGM', 'Kilo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `regimen_fiscal`
--

CREATE TABLE `regimen_fiscal` (
  `id` int(11) NOT NULL,
  `clave` varchar(4) DEFAULT NULL,
  `descripcion` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `regimen_fiscal`
--

INSERT INTO `regimen_fiscal` (`id`, `clave`, `descripcion`) VALUES
(1, '601', 'General de Ley Personas Morales'),
(2, '603', 'Personas Morales con Fines no Lucrativos'),
(3, '605', 'Sueldos y Salarios e Ingresos Asimilados a Salarios'),
(4, '606', 'Arrendamiento'),
(5, '608', 'Demás ingresos'),
(6, '609', 'Consolidación'),
(7, '610', 'Residentes en el Extranjero sin Establecimiento Permanente en México'),
(8, '611', 'Ingresos por Dividendos (socios y accionistas)'),
(9, '612', 'Personas Físicas con Actividades Empresariales y Profesionales'),
(10, '614', 'Ingresos por intereses'),
(11, '616', 'Sin obligaciones fiscales'),
(12, '620', 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos'),
(13, '621', 'Incorporación Fiscal'),
(14, '622', 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras'),
(15, '623', 'Opcional para Grupos de Sociedades'),
(16, '624', 'Coordinados'),
(17, '628', 'Hidrocarburos'),
(18, '607', 'Régimen de Enajenación o Adquisición de Bienes'),
(19, '629', 'De los Regímenes Fiscales Preferentes y de las Empresas Multinacionales'),
(20, '630', 'Enajenación de acciones en bolsa de valores'),
(21, '615', 'Régimen de los ingresos por obtención de premios'),
(22, '626', 'Régimen Simplificado de Confianza'),
(23, '625', 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_accion`
--

CREATE TABLE `seg_accion` (
  `id` int(11) NOT NULL,
  `seg_modulo_id` int(11) NOT NULL,
  `nombre` varchar(40) DEFAULT NULL,
  `descripcion` varchar(80) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `url` varchar(80) DEFAULT NULL,
  `id_html` varchar(25) DEFAULT NULL,
  `icono` varchar(40) DEFAULT NULL,
  `visible` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `seg_accion`
--

INSERT INTO `seg_accion` (`id`, `seg_modulo_id`, `nombre`, `descripcion`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES
(1, 1, 'Ver', '', 1, NULL, NULL, NULL, 0),
(2, 1, 'Agregar', '', 1, NULL, NULL, NULL, 0),
(3, 1, 'Activar/Desactivar', '', 1, NULL, NULL, NULL, 0),
(4, 1, 'Editar', '', 1, NULL, NULL, NULL, 0),
(5, 1, 'Asignar Permisos', '', 1, NULL, NULL, NULL, 0),
(6, 2, 'Ver', '', 1, NULL, NULL, NULL, 0),
(7, 2, 'Agregar', '', 1, NULL, NULL, NULL, 0),
(8, 2, 'Editar', '', 1, NULL, NULL, NULL, 0),
(9, 2, 'Eliminar', '', 1, NULL, NULL, NULL, 0),
(10, 3, 'Inventario / Precios', '', 1, '/productos', NULL, 'fas fa-boxes', 0),
(11, 3, 'Agregar', '', 1, NULL, NULL, NULL, 0),
(12, 3, 'Editar', '', 1, NULL, NULL, NULL, 0),
(13, 3, 'Eliminar', '', 1, NULL, NULL, NULL, 0),
(14, 4, 'Ver', '', 1, NULL, NULL, NULL, 0),
(15, 4, 'Agregar', '', 1, NULL, NULL, NULL, 0),
(16, 4, 'Editar', '', 1, NULL, NULL, NULL, 0),
(17, 4, 'Eliminar', '', 1, NULL, NULL, NULL, 0),
(18, 3, 'Carga Masiva', '', 1, NULL, NULL, NULL, 0),
(19, 3, 'Modificación Masiva de Precios', '', 1, NULL, NULL, NULL, 0),
(20, 3, 'Ver Kardex', '', 1, '/kardex', NULL, 'fas fa-list', 0),
(21, 3, 'Entrada de Productos', '', 1, '/prod_entrada', NULL, 'fas fa-upload', 0),
(22, 3, 'Agregar Entrada', '', 1, NULL, NULL, NULL, 0),
(23, 3, 'Ver Producto en Sucursales', '', 1, NULL, NULL, NULL, 0),
(24, 3, 'Editar Entrada de Productos', '', 1, NULL, NULL, NULL, 0),
(25, 3, 'Eliminar Entrada  de Productos', '', 1, NULL, NULL, NULL, 0),
(26, 3, 'Agregar Entrada de Productos', '', 1, NULL, NULL, NULL, 0),
(27, 3, 'Baja de Inventario', '', 1, NULL, NULL, NULL, 0),
(28, 6, 'Nueva', '', 1, '/ventas', NULL, 'fas fa-hand-holding-usd', 0),
(29, 6, 'Crédito', '', 1, '/credito', NULL, 'fas fa-file-invoice-dollar', 0),
(30, 2, 'Cerrar Sucursal', '', 1, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_log`
--

CREATE TABLE `seg_log` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `seg_session_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `tabla` varchar(40) DEFAULT NULL,
  `registro` int(11) DEFAULT NULL,
  `mostrar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_modulo`
--

CREATE TABLE `seg_modulo` (
  `id` int(11) NOT NULL,
  `nombre` varchar(40) DEFAULT NULL,
  `url` varchar(40) DEFAULT NULL,
  `id_html` varchar(15) DEFAULT NULL,
  `icono` varchar(20) DEFAULT NULL,
  `orden` int(2) NOT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `seg_modulo`
--

INSERT INTO `seg_modulo` (`id`, `nombre`, `url`, `id_html`, `icono`, `orden`, `status`) VALUES
(1, 'Usuarios', '/usuarios', '', 'fas fa-user', 5, 1),
(2, 'Sucursales', '/sucursales', '', 'fas fa-store', 2, 1),
(3, 'Productos', '/productos', '', 'fas fa-cubes', 3, 1),
(4, 'Clientes', '/clientes', '', 'fas fa-handshake', 4, 1),
(5, 'Kardex', '/kardex', '', 'fas fa-user', 0, 0),
(6, 'Ventas', '/ventas', '', 'fas fa-dollar-sign', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_permiso`
--

CREATE TABLE `seg_permiso` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `seg_accion_id` int(11) NOT NULL,
  `fecha_asigno` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `seg_permiso`
--

INSERT INTO `seg_permiso` (`id`, `usuario_id`, `seg_accion_id`, `fecha_asigno`) VALUES
(14, 1, 1, '2022-09-19 15:03:21'),
(15, 1, 2, '2023-08-09 16:53:00'),
(16, 1, 3, '2022-09-19 15:03:20'),
(18, 1, 5, '2022-09-19 15:03:19'),
(19, 1, 6, '2022-09-19 15:03:19'),
(20, 1, 7, '2023-08-24 18:03:19'),
(21, 1, 8, '2023-08-24 18:03:19'),
(22, 1, 9, '2023-08-24 18:03:19'),
(23, 1, 10, '2023-08-29 09:03:19'),
(24, 1, 11, '2023-08-29 09:03:19'),
(25, 1, 12, '2023-08-29 09:03:19'),
(26, 1, 13, '2023-08-29 09:03:19'),
(27, 1, 14, '2023-08-29 09:03:19'),
(28, 1, 15, '2023-08-29 09:03:19'),
(29, 1, 16, '2023-08-29 09:03:19'),
(30, 1, 17, '2023-08-29 09:03:19'),
(31, 1, 18, '2023-08-29 09:03:19'),
(32, 1, 19, '2023-08-29 09:03:19'),
(33, 1, 20, '2023-08-29 09:03:19'),
(34, 1, 21, '2023-08-29 09:03:19'),
(35, 1, 22, '2023-08-29 09:03:19'),
(36, 5, 17, '2023-09-10 11:45:49'),
(37, 5, 7, '2023-09-10 11:45:52'),
(38, 5, 3, '2023-09-10 11:45:53'),
(39, 5, 11, '2023-09-10 11:45:54'),
(40, 5, 20, '2023-09-10 11:45:56'),
(41, 5, 5, '2023-09-10 11:46:02'),
(42, 5, 22, '2023-09-10 11:46:04'),
(43, 5, 13, '2023-09-10 11:46:06'),
(44, 5, 10, '2023-09-10 11:46:06'),
(45, 5, 12, '2023-09-10 11:46:07'),
(46, 5, 18, '2023-09-10 11:46:08'),
(47, 5, 19, '2023-09-10 11:46:08'),
(48, 5, 21, '2023-09-10 11:46:09'),
(49, 5, 16, '2023-09-10 11:46:10'),
(50, 5, 15, '2023-09-10 11:46:10'),
(51, 5, 14, '2023-09-10 11:46:11'),
(52, 5, 8, '2023-09-10 11:46:11'),
(53, 5, 9, '2023-09-10 11:46:12'),
(54, 5, 6, '2023-09-10 11:46:12'),
(55, 5, 4, '2023-09-10 11:46:13'),
(56, 5, 2, '2023-09-10 11:46:14'),
(57, 5, 1, '2023-09-10 11:46:14'),
(58, 1, 23, '2023-09-10 11:46:14'),
(59, 1, 24, '2023-09-10 11:46:14'),
(60, 1, 25, '2023-09-10 11:46:14'),
(61, 1, 27, '2023-09-10 11:46:14'),
(62, 1, 28, '2023-09-10 11:46:14'),
(63, 1, 29, '2023-09-10 11:46:14'),
(64, 10, 6, '2023-09-12 18:17:45'),
(65, 10, 7, '2023-09-12 18:17:46'),
(66, 10, 8, '2023-09-12 18:17:46'),
(67, 10, 9, '2023-09-12 18:17:47'),
(68, 10, 10, '2023-09-12 18:17:47'),
(69, 10, 11, '2023-09-12 18:17:48'),
(70, 10, 12, '2023-09-12 18:17:48'),
(71, 10, 13, '2023-09-12 18:17:49'),
(72, 10, 18, '2023-09-12 18:17:50'),
(73, 10, 19, '2023-09-12 18:17:50'),
(74, 10, 20, '2023-09-12 18:17:50'),
(75, 10, 21, '2023-09-12 18:17:51'),
(76, 10, 22, '2023-09-12 18:17:51'),
(77, 10, 23, '2023-09-12 18:17:52'),
(78, 10, 24, '2023-09-12 18:17:52'),
(79, 10, 5, '2023-09-12 18:17:53'),
(80, 10, 4, '2023-09-12 18:17:54'),
(81, 10, 3, '2023-09-12 18:17:54'),
(82, 10, 1, '2023-09-12 18:17:54'),
(83, 10, 2, '2023-09-12 18:17:55'),
(84, 10, 17, '2023-09-12 18:17:55'),
(85, 10, 15, '2023-09-12 18:17:56'),
(86, 10, 14, '2023-09-12 18:17:57'),
(87, 10, 16, '2023-09-12 18:17:57'),
(88, 10, 27, '2023-09-12 18:17:58'),
(89, 10, 26, '2023-09-12 18:17:59'),
(90, 10, 25, '2023-09-12 18:17:59'),
(118, 1, 26, '2023-09-13 18:44:02'),
(119, 1, 4, '2023-09-13 18:44:14'),
(121, 5, 27, '2023-09-18 14:13:48'),
(222, 27, 6, '2023-09-18 14:35:50'),
(223, 27, 7, '2023-09-18 14:35:50'),
(224, 27, 8, '2023-09-18 14:35:50'),
(225, 27, 10, '2023-09-18 14:35:50'),
(226, 27, 11, '2023-09-18 14:35:50'),
(227, 27, 12, '2023-09-18 14:35:50'),
(228, 27, 18, '2023-09-18 14:35:50'),
(229, 27, 19, '2023-09-18 14:35:50'),
(230, 27, 20, '2023-09-18 14:35:50'),
(231, 27, 21, '2023-09-18 14:35:50'),
(232, 27, 22, '2023-09-18 14:35:50'),
(233, 27, 23, '2023-09-18 14:35:50'),
(234, 27, 24, '2023-09-18 14:35:50'),
(235, 27, 26, '2023-09-18 14:35:50'),
(236, 27, 27, '2023-09-18 14:35:50'),
(237, 27, 14, '2023-09-18 14:35:50'),
(238, 27, 15, '2023-09-18 14:35:50'),
(239, 27, 16, '2023-09-18 14:35:50'),
(240, 27, 1, '2023-09-18 14:35:50'),
(241, 27, 2, '2023-09-18 14:35:50'),
(242, 27, 3, '2023-09-18 14:35:50'),
(243, 27, 4, '2023-09-18 14:35:50'),
(244, 27, 5, '2023-09-18 14:35:50'),
(245, 28, 1, '2023-09-18 14:39:51'),
(246, 28, 6, '2023-09-18 14:39:51'),
(247, 28, 14, '2023-09-18 14:39:51'),
(248, 28, 15, '2023-09-18 14:39:51'),
(249, 28, 16, '2023-09-18 14:39:51'),
(250, 28, 10, '2023-09-18 14:39:51'),
(251, 28, 23, '2023-09-18 14:39:51'),
(252, 31, 1, '2023-09-18 14:44:41'),
(253, 31, 6, '2023-09-18 14:44:41'),
(254, 31, 14, '2023-09-18 14:44:41'),
(255, 31, 15, '2023-09-18 14:44:41'),
(256, 31, 16, '2023-09-18 14:44:41'),
(257, 31, 10, '2023-09-18 14:44:41'),
(258, 31, 23, '2023-09-18 14:44:41'),
(259, 33, 1, '2023-09-18 14:45:35'),
(260, 33, 6, '2023-09-18 14:45:35'),
(261, 33, 14, '2023-09-18 14:45:35'),
(262, 33, 15, '2023-09-18 14:45:35'),
(263, 33, 16, '2023-09-18 14:45:35'),
(264, 33, 10, '2023-09-18 14:45:35'),
(265, 33, 23, '2023-09-18 14:45:35'),
(266, 35, 1, '2023-09-18 14:47:37'),
(267, 35, 6, '2023-09-18 14:47:37'),
(268, 35, 14, '2023-09-18 14:47:37'),
(269, 35, 15, '2023-09-18 14:47:37'),
(270, 35, 16, '2023-09-18 14:47:37'),
(271, 35, 10, '2023-09-18 14:47:37'),
(272, 35, 23, '2023-09-18 14:47:37'),
(273, 36, 6, '2023-09-18 14:49:10'),
(274, 36, 7, '2023-09-18 14:49:10'),
(275, 36, 8, '2023-09-18 14:49:10'),
(276, 36, 10, '2023-09-18 14:49:10'),
(277, 36, 11, '2023-09-18 14:49:10'),
(278, 36, 12, '2023-09-18 14:49:10'),
(279, 36, 18, '2023-09-18 14:49:10'),
(280, 36, 19, '2023-09-18 14:49:10'),
(281, 36, 20, '2023-09-18 14:49:10'),
(282, 36, 21, '2023-09-18 14:49:10'),
(283, 36, 22, '2023-09-18 14:49:10'),
(284, 36, 23, '2023-09-18 14:49:10'),
(285, 36, 24, '2023-09-18 14:49:10'),
(286, 36, 26, '2023-09-18 14:49:10'),
(287, 36, 27, '2023-09-18 14:49:10'),
(288, 36, 14, '2023-09-18 14:49:10'),
(289, 36, 15, '2023-09-18 14:49:10'),
(290, 36, 16, '2023-09-18 14:49:10'),
(291, 36, 1, '2023-09-18 14:49:10'),
(292, 36, 2, '2023-09-18 14:49:10'),
(293, 36, 3, '2023-09-18 14:49:10'),
(294, 36, 4, '2023-09-18 14:49:10'),
(304, 13, 6, '2023-09-18 15:29:38'),
(305, 13, 7, '2023-09-18 15:29:38'),
(306, 13, 8, '2023-09-18 15:29:38'),
(307, 13, 9, '2023-09-18 15:29:38'),
(308, 13, 10, '2023-09-18 15:29:38'),
(309, 13, 11, '2023-09-18 15:29:38'),
(310, 13, 12, '2023-09-18 15:29:38'),
(311, 13, 13, '2023-09-18 15:29:38'),
(312, 13, 18, '2023-09-18 15:29:38'),
(313, 13, 19, '2023-09-18 15:29:38'),
(314, 13, 20, '2023-09-18 15:29:38'),
(315, 13, 21, '2023-09-18 15:29:38'),
(316, 13, 22, '2023-09-18 15:29:38'),
(317, 13, 23, '2023-09-18 15:29:38'),
(318, 13, 24, '2023-09-18 15:29:38'),
(319, 13, 5, '2023-09-18 15:29:38'),
(320, 13, 4, '2023-09-18 15:29:38'),
(321, 13, 3, '2023-09-18 15:29:38'),
(322, 13, 2, '2023-09-18 15:29:38'),
(323, 13, 1, '2023-09-18 15:29:38'),
(324, 13, 17, '2023-09-18 15:29:38'),
(325, 13, 16, '2023-09-18 15:29:38'),
(326, 13, 15, '2023-09-18 15:29:38'),
(327, 13, 14, '2023-09-18 15:29:38'),
(328, 13, 27, '2023-09-18 15:29:38'),
(329, 13, 26, '2023-09-18 15:29:38'),
(330, 13, 25, '2023-09-18 15:29:38'),
(331, 37, 1, '2023-09-19 10:21:55'),
(332, 37, 6, '2023-09-19 10:21:55'),
(333, 37, 14, '2023-09-19 10:21:55'),
(334, 37, 15, '2023-09-19 10:21:55'),
(335, 37, 16, '2023-09-19 10:21:55'),
(336, 37, 10, '2023-09-19 10:21:55'),
(337, 37, 23, '2023-09-19 10:21:55'),
(338, 1, 30, '2023-09-10 11:46:14'),
(339, 38, 1, '2023-09-29 14:36:10'),
(340, 38, 6, '2023-09-29 14:36:10'),
(341, 38, 14, '2023-09-29 14:36:10'),
(342, 38, 15, '2023-09-29 14:36:10'),
(343, 38, 16, '2023-09-29 14:36:10'),
(344, 38, 10, '2023-09-29 14:36:10'),
(345, 38, 23, '2023-09-29 14:36:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_permiso_perfil`
--

CREATE TABLE `seg_permiso_perfil` (
  `id` int(11) NOT NULL,
  `usuario_tipo_id` int(11) NOT NULL,
  `seg_accion_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seg_session`
--

CREATE TABLE `seg_session` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ip_address` varchar(15) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `iniciada` datetime DEFAULT NULL,
  `finalizada` datetime DEFAULT NULL,
  `token` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

CREATE TABLE `sucursal` (
  `id` int(11) NOT NULL,
  `direccion_id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `telefono` varchar(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`id`, `direccion_id`, `nombre`, `telefono`, `status`) VALUES
(1, 1, 'Tornilleria Monterrey', '7721896574', 1),
(2, 2, 'Tulancingo de Bravo', '7765894236', 1),
(3, 17, 'Centro Monterrey', '7721812716', 1),
(4, 20, 'Matriz', '7721812716', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `usuario_tipo_id` int(11) NOT NULL,
  `direccion_id` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `apellidos` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `celular` varchar(10) DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `contrasena` varchar(40) DEFAULT NULL,
  `passcode` varchar(40) DEFAULT NULL,
  `acceso` datetime DEFAULT NULL,
  `registro` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `sucursal_id`, `usuario_tipo_id`, `direccion_id`, `nombre`, `apellidos`, `email`, `celular`, `username`, `contrasena`, `passcode`, `acceso`, `registro`, `status`) VALUES
(1, 1, 1, 1, 'Angel Gabriel', 'Ramirez', 'leticia@ddsmedia.net', '7712026000', 'aramirez', 'e2f43942557c424fa3b17065b803b7da', '4306157df364819774af4d1ee3a6b67f', '2023-09-29 14:49:52', '2023-08-24 15:18:00', 1),
(5, 1, 1, 7, 'Leticia', 'Benítez', 'leticia@ddsmedia.net', '7721812716', 'Letys09', 'e2f43942557c424fa3b17065b803b7da', 'c55e1bb3af928549721e617e66e3623c', '2023-09-24 20:18:11', '2023-09-09 15:09:04', 1),
(10, 1, 3, 14, 'Luis Carlos', 'Dominguez Del Ángel', 'luis@luis.com', '7711812716', 'LuisCarlos96', '300a9ec51d8341a52f6bd8d2da343a5b', 'c0e44a595c31c43ec0c53a29143af233', '2023-09-15 14:52:28', '2023-09-09 15:57:34', 0),
(12, 1, 2, 16, 'Luis Carlos', 'Dominguez Del Ángel', 'leticia@ddsmedia.net', '7721812716', 'LuisCarlos', 'fa20875250db23d9f74759f014894121', '4d5112a33bb6864c833ef4bce19b7d94', NULL, '2023-09-09 15:59:15', 0),
(13, 1, 1, 18, 'Administrador', 'de Sucursal', 'admin@admin.com', '7721812716', 'adminCentro', '88a3077670dac75e87519272b6f05523', '441422dd17c3432831387179962b300b', '2023-09-12 18:51:23', '2023-09-12 18:47:43', 1),
(14, 0, 3, 19, 'Pedro Daniel', 'Guerrero Martínez', 'daniel@dany.com', '7721812716', 'DanielGuerrero', '83099e7b47e84d49f4396e589cbf600f', 'c5c1be2a23679c6dd260f554ed066089', NULL, '2023-09-13 19:55:13', 1),
(15, 0, 1, 21, 'Ivan', 'Santos Pérez', 'isantos@leticia.com', '7721812716', NULL, '83099e7b47e84d49f4396e589cbf600f', 'aca75defd170e9ba499dd251eac575b9', NULL, '2023-09-18 14:18:48', 1),
(27, 0, 2, 33, 'Ivan', 'Santos Pérez', 'isantos@isantos.com', '7721812716', 'isantos', '83099e7b47e84d49f4396e589cbf600f', 'c5ca8e4aa2672fa138902db5ad31b53b', '2023-09-18 14:53:37', '2023-09-18 14:35:50', 0),
(28, 0, 2, 34, 'Saúl', 'Hernández', 'leticia@ddsmedia.net', '7721812716', NULL, 'efb844bb2d53050a42f110f62d140b12', '5f5a74a3545cf8f61009b7544086f31f', NULL, '2023-09-18 14:39:51', 0),
(29, 0, 2, 35, 'Saúl', 'Hernández', 'leticia@ddsmedia.net', '7721812716', NULL, 'efb844bb2d53050a42f110f62d140b12', '4e0f4f51923a57e2866aca5490440441', NULL, '2023-09-18 14:42:29', 0),
(31, 0, 2, 37, 'Saúl', 'Hernández', 'leticia@ddsmedia.net', '7721812716', NULL, 'efb844bb2d53050a42f110f62d140b12', '4306157df364819774af4d1ee3a6b67f', NULL, '2023-09-18 14:44:39', 0),
(33, 0, 2, 39, 'Saúl', 'Hernández', 'leticia@ddsmedia.net', '7721812716', NULL, 'efb844bb2d53050a42f110f62d140b12', '3db8e0915069c11f602554bc831568dd', NULL, '2023-09-18 14:45:32', 0),
(35, 0, 2, 41, 'Lizandro', 'Gabriel', 'leticia@ddsmedia.net', '7721812716', 'ingeliz', 'efb844bb2d53050a42f110f62d140b12', '0643a95d7e53db69b2593cc4ad2cc33d', NULL, '2023-09-18 14:47:37', 0),
(36, 0, 3, 42, 'Abraham', 'Lechuga', 'leticia@ddsmedia.net', '7721812716', 'chinitos', '59bf0990761c618a4d54d07dc55f2ac3', NULL, '2023-09-18 20:11:32', '2023-09-18 14:49:10', 1),
(37, 0, 2, 43, 'Sharim Guadalupe', 'Bailón Molina', 'leticia@ddsmedia.net', '7721812716', 'sharim', '176cecb0f866fd4abd1d9dded25e8f10', '31340ea7c118bb63b6160e07b65b7623', '2023-09-19 10:26:10', '2023-09-19 10:21:55', 1),
(38, 0, 2, 44, 'Mariane', 'Monterrey', 'leticia@ddsmedia.net', '7721812716', 'marianne', 'dbf5efbce56739b6198ab30b482a395d', '845dfdd388e1fede80833eb1f76159e7', '2023-09-29 14:36:39', '2023-09-29 14:36:10', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_tipo`
--

CREATE TABLE `usuario_tipo` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuario_tipo`
--

INSERT INTO `usuario_tipo` (`id`, `nombre`, `status`) VALUES
(1, 'Administrador', 1),
(2, 'Vendedor', 1),
(3, 'Encargado', 1),
(4, 'Otro', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `tipo` tinyint(1) DEFAULT 1,
  `importe` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `comentarios` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `cancelada` datetime DEFAULT NULL,
  `tipo_cancelacion` tinyint(1) NOT NULL,
  `motivo_cancela` varchar(255) DEFAULT NULL,
  `usuario_cancela` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta_detalle`
--

CREATE TABLE `venta_detalle` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `importe` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta_pago`
--

CREATE TABLE `venta_pago` (
  `id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` datetime DEFAULT NULL,
  `monto` decimal(10,2) DEFAULT NULL,
  `forma_pago` tinyint(1) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cliente_cli_datos_fiscales1_idx` (`cli_datos_fiscales_id`);

--
-- Indices de la tabla `cliente_saldo`
--
ALTER TABLE `cliente_saldo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cliente_saldo_cliente1_idx` (`cliente_id`);

--
-- Indices de la tabla `cli_datos_fiscales`
--
ALTER TABLE `cli_datos_fiscales`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cotizacion`
--
ALTER TABLE `cotizacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cotizacion_cliente1_idx` (`cliente_id`),
  ADD KEY `fk_cotizacion_usuario1_idx` (`usuario_id`),
  ADD KEY `fk_cotizacion_venta1_idx` (`venta_id`);

--
-- Indices de la tabla `coti_detalle`
--
ALTER TABLE `coti_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_coti_detalle_cotizacion1_idx` (`cotizacion_id`),
  ADD KEY `fk_coti_detalle_producto1_idx` (`producto_id`);

--
-- Indices de la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`clave`),
  ADD KEY `fk_producto_categoria1_idx` (`prod_categoria_id`),
  ADD KEY `fk_producto_area1_idx` (`prod_area_id`);

--
-- Indices de la tabla `prod_ajuste`
--
ALTER TABLE `prod_ajuste`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_ajuste_producto1_idx` (`producto_id`),
  ADD KEY `fk_prod_ajuste_usuario1_idx` (`usuario_id`);

--
-- Indices de la tabla `prod_area`
--
ALTER TABLE `prod_area`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `prod_categoria`
--
ALTER TABLE `prod_categoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_categoria_prod_categoria1_idx` (`prod_categoria_id`);

--
-- Indices de la tabla `prod_det_entrada`
--
ALTER TABLE `prod_det_entrada`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_det_entrada_prod_entrada1_idx` (`prod_entrada_id`),
  ADD KEY `fk_prod_det_entrada_producto1_idx` (`producto_id`);

--
-- Indices de la tabla `prod_det_inventario`
--
ALTER TABLE `prod_det_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_det_inventario_prod_inventario1_idx` (`prod_inventario_id`),
  ADD KEY `fk_prod_det_inventario_producto1_idx` (`producto_id`);

--
-- Indices de la tabla `prod_entrada`
--
ALTER TABLE `prod_entrada`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_entrada_usuario1_idx` (`usuario_id`),
  ADD KEY `fk_prod_entrada_sucursal1_idx` (`sucursal_id`);

--
-- Indices de la tabla `prod_inventario`
--
ALTER TABLE `prod_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_inventario_usuario1_idx` (`usuario_id`);

--
-- Indices de la tabla `prod_kilo`
--
ALTER TABLE `prod_kilo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_kilo_producto1_idx` (`producto_id`),
  ADD KEY `fk_prod_kilo_producto2_idx` (`producto_origen`);

--
-- Indices de la tabla `prod_precio`
--
ALTER TABLE `prod_precio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_precio_producto1_idx` (`producto_id`);

--
-- Indices de la tabla `prod_rango`
--
ALTER TABLE `prod_rango`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_rango_sucursal` (`sucursal_id`),
  ADD KEY `fk_prod_rango_producto` (`producto_id`),
  ADD KEY `fk_prod_rango_precio` (`prod_precio_id`);

--
-- Indices de la tabla `prod_stock`
--
ALTER TABLE `prod_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_stock_producto1_idx` (`producto_id`),
  ADD KEY `fk_prod_stock_usuario1_idx` (`usuario_id`),
  ADD KEY `fk_prod_stock_sucursal1_idx` (`sucursal_id`);

--
-- Indices de la tabla `prod_unidad_medida`
--
ALTER TABLE `prod_unidad_medida`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `regimen_fiscal`
--
ALTER TABLE `regimen_fiscal`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `seg_accion`
--
ALTER TABLE `seg_accion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seg_accion_seg_modulo1_idx` (`seg_modulo_id`);

--
-- Indices de la tabla `seg_log`
--
ALTER TABLE `seg_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seg_log_usuario1_idx` (`usuario_id`),
  ADD KEY `fk_seg_log_seg_session1_idx` (`seg_session_id`);

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
  ADD KEY `fk_seg_permiso_usuario1_idx` (`usuario_id`),
  ADD KEY `fk_seg_permiso_seg_accion1_idx` (`seg_accion_id`);

--
-- Indices de la tabla `seg_permiso_perfil`
--
ALTER TABLE `seg_permiso_perfil`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `seg_session`
--
ALTER TABLE `seg_session`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seg_session_usuario1_idx` (`usuario_id`);

--
-- Indices de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sucursal_direccion1_idx` (`direccion_id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_usuario_usuario_tipo1_idx` (`usuario_tipo_id`),
  ADD KEY `fk_usuario_direccion1_idx` (`direccion_id`);

--
-- Indices de la tabla `usuario_tipo`
--
ALTER TABLE `usuario_tipo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_venta_cliente1_idx` (`cliente_id`),
  ADD KEY `fk_venta_usuario1_idx` (`usuario_id`),
  ADD KEY `fk_venta_usuario2_idx` (`usuario_cancela`);

--
-- Indices de la tabla `venta_detalle`
--
ALTER TABLE `venta_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_venta_detalle_venta1_idx` (`venta_id`),
  ADD KEY `fk_venta_detalle_producto1_idx` (`producto_id`);

--
-- Indices de la tabla `venta_pago`
--
ALTER TABLE `venta_pago`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_venta_pago_venta1_idx` (`venta_id`),
  ADD KEY `fk_venta_pago_usuario1_idx` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cliente_saldo`
--
ALTER TABLE `cliente_saldo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cli_datos_fiscales`
--
ALTER TABLE `cli_datos_fiscales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cotizacion`
--
ALTER TABLE `cotizacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `coti_detalle`
--
ALTER TABLE `coti_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_ajuste`
--
ALTER TABLE `prod_ajuste`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_area`
--
ALTER TABLE `prod_area`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_categoria`
--
ALTER TABLE `prod_categoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_det_entrada`
--
ALTER TABLE `prod_det_entrada`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_det_inventario`
--
ALTER TABLE `prod_det_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_entrada`
--
ALTER TABLE `prod_entrada`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_inventario`
--
ALTER TABLE `prod_inventario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_kilo`
--
ALTER TABLE `prod_kilo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_precio`
--
ALTER TABLE `prod_precio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_rango`
--
ALTER TABLE `prod_rango`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_stock`
--
ALTER TABLE `prod_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prod_unidad_medida`
--
ALTER TABLE `prod_unidad_medida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `regimen_fiscal`
--
ALTER TABLE `regimen_fiscal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `seg_accion`
--
ALTER TABLE `seg_accion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `seg_log`
--
ALTER TABLE `seg_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seg_modulo`
--
ALTER TABLE `seg_modulo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `seg_permiso`
--
ALTER TABLE `seg_permiso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=346;

--
-- AUTO_INCREMENT de la tabla `seg_permiso_perfil`
--
ALTER TABLE `seg_permiso_perfil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `seg_session`
--
ALTER TABLE `seg_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `usuario_tipo`
--
ALTER TABLE `usuario_tipo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `venta_detalle`
--
ALTER TABLE `venta_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `venta_pago`
--
ALTER TABLE `venta_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cliente_saldo`
--
ALTER TABLE `cliente_saldo`
  ADD CONSTRAINT `fk_cliente_saldo_cliente1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `cli_datos_fiscales`
--
ALTER TABLE `cli_datos_fiscales`
  ADD CONSTRAINT `fk_cli_datos_fiscales_regimen_fiscal1` FOREIGN KEY (`regimen_fiscal`) REFERENCES `regimen_fiscal` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `cotizacion`
--
ALTER TABLE `cotizacion`
  ADD CONSTRAINT `fk_cotizacion_cliente1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cotizacion_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cotizacion_venta1` FOREIGN KEY (`venta_id`) REFERENCES `venta` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `coti_detalle`
--
ALTER TABLE `coti_detalle`
  ADD CONSTRAINT `fk_coti_detalle_cotizacion1` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizacion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_coti_detalle_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_producto_area1` FOREIGN KEY (`prod_area_id`) REFERENCES `prod_area` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_producto_categoria1` FOREIGN KEY (`prod_categoria_id`) REFERENCES `prod_categoria` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_ajuste`
--
ALTER TABLE `prod_ajuste`
  ADD CONSTRAINT `fk_prod_ajuste_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_prod_ajuste_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_categoria`
--
ALTER TABLE `prod_categoria`
  ADD CONSTRAINT `fk_prod_categoria_prod_categoria1` FOREIGN KEY (`prod_categoria_id`) REFERENCES `prod_categoria` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_det_entrada`
--
ALTER TABLE `prod_det_entrada`
  ADD CONSTRAINT `fk_prod_det_entrada_prod_entrada1` FOREIGN KEY (`prod_entrada_id`) REFERENCES `prod_entrada` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_prod_det_entrada_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_det_inventario`
--
ALTER TABLE `prod_det_inventario`
  ADD CONSTRAINT `fk_prod_det_inventario_prod_inventario1` FOREIGN KEY (`prod_inventario_id`) REFERENCES `prod_inventario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_prod_det_inventario_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_entrada`
--
ALTER TABLE `prod_entrada`
  ADD CONSTRAINT `fk_prod_entrada_sucursal1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_prod_entrada_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_inventario`
--
ALTER TABLE `prod_inventario`
  ADD CONSTRAINT `fk_prod_inventario_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_kilo`
--
ALTER TABLE `prod_kilo`
  ADD CONSTRAINT `fk_prod_kilo_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_prod_kilo_producto2` FOREIGN KEY (`producto_origen`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_precio`
--
ALTER TABLE `prod_precio`
  ADD CONSTRAINT `fk_prod_precio_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `prod_rango`
--
ALTER TABLE `prod_rango`
  ADD CONSTRAINT `fk_prod_rango_precio` FOREIGN KEY (`prod_precio_id`) REFERENCES `prod_precio` (`id`),
  ADD CONSTRAINT `fk_prod_rango_producto` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`),
  ADD CONSTRAINT `fk_prod_rango_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`);

--
-- Filtros para la tabla `prod_stock`
--
ALTER TABLE `prod_stock`
  ADD CONSTRAINT `fk_prod_stock_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_prod_stock_sucursal1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_prod_stock_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `seg_accion`
--
ALTER TABLE `seg_accion`
  ADD CONSTRAINT `fk_seg_accion_seg_modulo1` FOREIGN KEY (`seg_modulo_id`) REFERENCES `seg_modulo` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `seg_log`
--
ALTER TABLE `seg_log`
  ADD CONSTRAINT `fk_seg_log_seg_session1` FOREIGN KEY (`seg_session_id`) REFERENCES `seg_session` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_seg_log_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `seg_permiso`
--
ALTER TABLE `seg_permiso`
  ADD CONSTRAINT `fk_seg_permiso_seg_accion1` FOREIGN KEY (`seg_accion_id`) REFERENCES `seg_accion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_seg_permiso_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `seg_session`
--
ALTER TABLE `seg_session`
  ADD CONSTRAINT `fk_seg_session_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD CONSTRAINT `fk_sucursal_direccion1` FOREIGN KEY (`direccion_id`) REFERENCES `direccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_direccion1` FOREIGN KEY (`direccion_id`) REFERENCES `direccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuario_usuario_tipo1` FOREIGN KEY (`usuario_tipo_id`) REFERENCES `usuario_tipo` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `fk_venta_cliente1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_venta_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_venta_usuario2` FOREIGN KEY (`usuario_cancela`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `venta_detalle`
--
ALTER TABLE `venta_detalle`
  ADD CONSTRAINT `fk_venta_detalle_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_venta_detalle_venta1` FOREIGN KEY (`venta_id`) REFERENCES `venta` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `venta_pago`
--
ALTER TABLE `venta_pago`
  ADD CONSTRAINT `fk_venta_pago_usuario1` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_venta_pago_venta1` FOREIGN KEY (`venta_id`) REFERENCES `venta` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
