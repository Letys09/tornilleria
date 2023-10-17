-- Permiso de cerrar sucursal
-- INSERT INTO `seg_accion` (`id`, `seg_modulo_id`, `nombre`, `descripcion`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES (NULL, '2', 'Cerrar Sucursal', NULL, '1', NULL, NULL, NULL, NULL);

-- Permiso de inventario fisico
INSERT INTO `seg_accion` (`id`, `seg_modulo_id`, `nombre`, `descripcion`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES (NULL, '3', 'Inventario Fisico', NULL, '1', NULL, NULL, NULL, NULL);

-- Agregar sucursal_id a prod_inventario
ALTER TABLE `prod_inventario` ADD `sucursal_id` INT NOT NULL AFTER `usuario_id`;

-- Agregar estado_inventario a prod_inventario
ALTER TABLE `prod_inventario` ADD `estado_inventario` TINYINT(1) NOT NULL DEFAULT '2' AFTER `sucursal_id`;

-- Agregar check_inventario a prod_det_inventario
ALTER TABLE `prod_det_inventario` ADD `check_inventario` TINYINT(1) NOT NULL DEFAULT '0' AFTER `monto`;