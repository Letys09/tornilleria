INSERT INTO `cli_datos_fiscales` (`id`, `regimen_fiscal`, `rfc`, `razon_social`, `codigo_postal`) VALUES (1, 11, 'XAXX010101000', 'PÚBLICO EN GENERAL', '43630'); 
INSERT INTO `cliente` (`id`, `cli_datos_fiscales_id`, `nombre`, `apellidos`, `correo`, `telefono`, `descuento`, `saldo_favor`, `registro`, `status`) VALUES
(1, 1, 'Público', 'en General', 'general@gmail.com', '7777777777', 0, 0.00, '2023-11-02 11:45:26', 1);
INSERT INTO `direccion` (`id`, `calle`, `no_ext`, `no_int`, `colonia`, `municipio`, `estado`, `codigo_postal`) VALUES (1, 'Nayarit', '610', '', 'Vicente Guerrero', 'Tulancingo de Bravo', 'Hidalgo', '43630'); 
INSERT INTO `sucursal` (`id`, `direccion_id`, `identificador`, `nombre`, `telefono`, `folio_venta`, `folio_cotizacion`, `status`) VALUES
(1, 1, 'A', 'SUC. NAYARIT', '7757531529', 52, 6, 1),
INSERT INTO `usuario_tipo` (`id`, `nombre`, `status`) VALUES
(1, 'Administrador', 1);
INSERT INTO `direccion` (`id`, `calle`, `no_ext`, `no_int`, `colonia`, `municipio`, `estado`, `codigo_postal`) VALUES (NULL, 'Boulevard Nuevo Hidalgo', '200', '', 'La Puerta de Hierro', 'Pachuca de Soto', 'Hidalgo', '42086') 
INSERT INTO `usuario` (`id`, `usuario_tipo_id`, `direccion_id`, `nombre`, `apellidos`, `email`, `celular`, `username`, `contrasena`, `passcode`, `acceso`, `registro`, `status`) VALUES (1, 1, 2, 'Leticia', 'Benítez Medina', 'leticia@ddsmedia.net', '7721812716', 'lety09', '19e443c9d171d2f0d3ad1157fed5a39d', '', '2023-11-24 10:58:17', '2023-08-24 15:18:00', 1); 
INSERT INTO `seg_permiso` (`id`, `usuario_id`, `seg_accion_id`, `fecha_asigno`) VALUES (NULL, '1', '5', '2023-11-26 18:56:42');