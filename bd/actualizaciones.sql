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

-- Agregar la sucursal en venta
ALTER TABLE `venta` ADD `sucursal_id` INT(11) NOT NULL AFTER `id`; 

ALTER TABLE `venta_detalle` ADD `fecha` DATETIME NULL DEFAULT NULL AFTER `producto_id`; 
ALTER TABLE `venta` ADD `fecha_finaliza` DATETIME NULL DEFAULT NULL AFTER `comentarios`, ADD `usuario_finaliza` INT(11) NOT NULL AFTER `fecha_finaliza`; 
ALTER TABLE `prod_stock` ADD `origen_tabla` VARCHAR(45) NOT NULL AFTER `origen_tipo`; 
ALTER TABLE `seg_modulo` CHANGE `icono` `icono` VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL; 
INSERT INTO `seg_modulo` (`id`, `nombre`, `url`, `id_html`, `icono`, `orden`, `status`) VALUES (NULL, 'Cotizaciones', '/cotizaciones', '', 'fas fa-clipboard-list', '2', '1')
UPDATE `seg_modulo` SET `orden` = '3' WHERE `seg_modulo`.`id` = 2; 
UPDATE `seg_modulo` SET `orden` = '4' WHERE `seg_modulo`.`id` = 3; 
UPDATE `seg_modulo` SET `orden` = '5' WHERE `seg_modulo`.`id` = 4; 
UPDATE `seg_modulo` SET `orden` = '6' WHERE `seg_modulo`.`id` = 1; 
UPDATE `seg_modulo` SET `orden` = '7' WHERE `seg_modulo`.`id` = 7; 
INSERT INTO `seg_accion` (`id`, `seg_modulo_id`, `nombre`, `descripcion`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES (NULL, '8', 'Ver', NULL, '1', '', NULL, '', NULL);
INSERT INTO `seg_accion` (`id`, `seg_modulo_id`, `nombre`, `descripcion`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES (NULL, '8', 'Editar', NULL, '1', '', NULL, '', NULL); 
INSERT INTO `seg_accion` (`id`, `seg_modulo_id`, `nombre`, `descripcion`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES (NULL, '8', 'Cancelar', NULL, '1', '', NULL, '', NULL);
INSERT INTO `seg_accion` (`id`, `seg_modulo_id`, `nombre`, `descripcion`, `status`, `url`, `id_html`, `icono`, `visible`) VALUES (NULL, '8', 'Realizar Venta', NULL, '1', '', NULL, '', NULL);
ALTER TABLE `cotizacion` ADD `sucursal_id` INT(11) NOT NULL AFTER `id`;
ALTER TABLE `coti_detalle` CHANGE `status` `status` TINYINT(1) NULL DEFAULT '1'; 
ALTER TABLE `venta_detalle` CHANGE `cantidad` `cantidad` DECIMAL(10,1) NULL DEFAULT NULL;