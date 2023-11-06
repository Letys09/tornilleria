
drop procedure if exists tbl_busqueda;
delimiter //
create procedure tbl_busqueda(in bus varchar(100), in sucursal_id int, in inicial int, in limite int, in tbl_name varchar(50))
begin

start transaction;

set @t1 = CONCAT(
	'CREATE TABLE ', tbl_name, '(FULLTEXT(id, clave, descripcion, venta, es_kilo, medida), FULLTEXT(descripcion)) ENGINE InnoDB ',
		'SELECT ',
			'CONVERT(producto.id, CHAR(10)) AS id, ',
			'CONVERT(REPLACE(producto.descripcion, \'/\', \'_\'), CHAR(100)) AS descripcion, ',
			'CONVERT(producto.venta, CHAR(100)) AS venta, ',
			'CONVERT(producto.es_kilo, CHAR(100)) AS es_kilo, ',
			'CONVERT(producto.medida, CHAR(100)) AS medida, ',
            -- 'CONVERT(prod_area.nombre, CHAR(100)) AS area, ',
			'CONVERT(CASE WHEN producto.es_kilo = 0 THEN IFNULL(clave, \'\') ELSE \'N/A\' END, CHAR(50)) AS clave, ',
			'CONVERT(CASE WHEN producto.es_kilo = 0 THEN (SELECT final FROM prod_stock WHERE prod_stock.sucursal_id = ',sucursal_id,' AND producto_id = producto.id ORDER BY prod_stock.id DESC LIMIT 1) ELSE (SELECT MIN((SELECT final FROM prod_stock WHERE prod_stock.sucursal_id = ',sucursal_id,' AND producto_id = producto.id ORDER BY prod_stock.id DESC LIMIT 1) DIV cantidad) FROM prod_kilo WHERE producto_origen = producto.id) END, CHAR(10)) AS cantidad ',
		'FROM producto ',
		-- 'LEFT JOIN prod_area ON prod_area.id = producto.prod_area_id ',
		'WHERE producto.status = 1 ',
		'GROUP BY producto.id ',
		'ORDER BY producto.venta DESC;'
	);
set @t2 = CONCAT(
		'SELECT ',
			'*, ',
            'CASE WHEN \'', bus, '\' != \'_\' THEN MATCH(descripcion) AGAINST(\'', bus, '\' IN BOOLEAN MODE) ELSE TRUE END AS prioridad ',
		'FROM ', tbl_name, ' ',
		'WHERE ',
			'CASE WHEN \'', bus, '\' != \'_\' THEN MATCH(id, clave, descripcion, venta, es_kilo, medida) AGAINST(\'', bus, '\' IN BOOLEAN MODE) ELSE TRUE END ',
		'ORDER BY prioridad DESC, venta DESC', ' ',
		'LIMIT ', inicial, ', ', limite, ';'
	);
    
set @t3 = CONCAT('DROP TABLE ', tbl_name, ';');

prepare stmt3 from @t1;
execute stmt3;

prepare stmt3 from @t2;
execute stmt3;

prepare stmt3 from @t3;
execute stmt3;

commit;

end
//
delimiter ;
