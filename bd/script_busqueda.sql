
drop procedure if exists tbl_busqueda;
delimiter //
create procedure tbl_busqueda(in bus varchar(100), in cat_id int, in sucursal_id int, in inicial int, in limite int, in tbl_name varchar(50))
begin

start transaction;

set @t1 = CONCAT(
	'CREATE TABLE ', tbl_name, '(FULLTEXT(id, nombre2, categoria, subcategoria, area, codigo, marca), FULLTEXT(nombre2)) ENGINE InnoDB ',
		'SELECT ',
			'CONVERT(producto.id, CHAR(10)) AS id, ',
			'CONVERT(REPLACE(producto.nombre, \'/\', \'_\'), CHAR(100)) AS nombre2, ',
			'producto.nombre, ',
			'producto.prod_categoria_id, ',
			'producto.prod_area_id, ',
			'producto.venta, ',
			'CONVERT(categoria.nombre, CHAR(100)) AS categoria, ',
            'CONVERT(subcategoria.nombre, CHAR(100)) AS subcategoria, ',
            'CONVERT(prod_area.nombre, CHAR(100)) AS area, ',
			'CONVERT(CASE WHEN producto.es_kilo = 0 THEN IFNULL(codigo, \'\') ELSE \'N/A\' END, CHAR(50)) AS codigo, ',
			'CONVERT(CASE WHEN producto.es_kilo = 0 THEN IFNULL(marca, \'\') ELSE \'N/A\' END, CHAR(100)) AS marca, ',
			'CONVERT(CASE WHEN producto.es_kilo = 0 THEN (SELECT final FROM prod_stock INNER JOIN producto pro ON pro.id = prod_stock.producto_id WHERE prod_stock.sucursal_id = 2 AND producto_id = producto.id ORDER BY prod_stock.id DESC LIMIT 1) ELSE (SELECT MIN(prod.stock DIV cantidad) FROM prod_kilo INNER JOIN producto prod ON prod.id = prod_kilo.producto_origen WHERE producto_id = producto.id) END, CHAR(10)) AS cantidad, ',
		'FROM producto ',
		'LEFT JOIN prod_categoria subcategoria ON subcategoria.id = producto.prod_categoria_id',
		'LEFT JOIN prod_categoria categoria ON categoria.id = subcategoria.prod_categoria_id',
		'LEFT JOIN prod_area ON prod_area.id = producto.prod_area_id',
		'WHERE producto.status = 1 ',
		'GROUP BY producto.id',
		'ORDER BY producto.venta DESC;'
	);
set @t2 = CONCAT(
		'SELECT ',
			'*, ',
            'CASE WHEN \'', bus, '\' != \'_\' THEN MATCH(nombre2) AGAINST(\'', bus, '\' IN BOOLEAN MODE) ELSE TRUE END AS prioridad ',
		'FROM ', tbl_name, ' ',
		'WHERE ',
			'CASE WHEN \'', bus, '\' != \'_\' THEN MATCH(id, nombre2, categoria, subcategoria, area, codigo, marca, cantidad) AGAINST(\'', bus, '\' IN BOOLEAN MODE) ELSE TRUE END AND ',
			'CASE WHEN ', cat_id, ' != 0 THEN categoria_id = ', cat_id, ' ELSE TRUE END AND ',
		'ORDER BY venta DESC, ',
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
