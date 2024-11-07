<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

    use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
	use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/prod_stock/', function () use ($app){
        $this->get('getStockSuc/{id}', function($req, $res, $args){
            $sucursales = $this->model->sucursal->getAll()->result;
            $data = [];
            foreach($sucursales as $sucursal){
                $prod_stock = $this->model->prod_stock->getStock($sucursal->id, $args['id'])->result;
                if(is_object($prod_stock)){
                    if($prod_stock->final > 0){
                        $sucursal->stock = $prod_stock->final;
                        $sucursal->date = $prod_stock->date;
                        $data[] = $sucursal;
                    }
                }
            }
            return $res->withJson($data);
        });

        $this->get('getAllDataTable/{id}/{desde}/{hasta}', function($req, $res, $args){
            $datos = $this->model->prod_stock->getAllDataTable($args['id'], $args['desde'], $args['hasta'])->result;
            $data = [];
            foreach($datos as $dato) {
                $origen = $dato->origen_tipo;
                $folio = '';
                switch ($origen) {
                    case 1: // entrada
                        $info_entrada = $this->model->prod_entrada->get($dato->origen_id)->result;
                        if(is_object($info_entrada)) $folio_entrada = 'Folio: '.$info_entrada->folio;
                        else $folio_entrada = 'Folio no registrado';
                        $folio = 'Entrada. '.$folio_entrada; break;
                    case 3: // venta por pieza origen_id = id del detalle de venta en el que salió el prod (venta_detalle)
                        $detalle_venta = $this->model->venta_detalle->getBy('id', $dato->origen_id)->result;
                        $venta = $this->model->venta->get($detalle_venta->venta_id)->result;
                        $folio = 'Venta de producto por pieza. Venta: '.$venta->folio;
                        break;
                    case 4: // entrada por ajuste de inventario 
                        $ajuste = $this->model->prod_ajuste->get($dato->origen_id)->result;
                        $folio = 'Entrada por ajuste de inventario';
                        $dato->motivo = $ajuste->comentarios;
                        break;
                    case 5: // baja por ajuste de inventario 
                        $ajuste = $this->model->prod_ajuste->get($dato->origen_id)->result;
                        $folio = 'Salida por ajuste de inventario';
                        $dato->motivo = $ajuste->comentarios;
                        break;
                    case 7: // venta por kilo
                        $detalle_venta = $this->model->venta_detalle->getBy('id', $dato->origen_id)->result;
                        $venta = $this->model->venta->get($detalle_venta->venta_id)->result;
                        $folio = 'Venta de producto por kilo. Venta: '.$venta->folio;
                        break;
                    case 8: // entrada por devolución de producto vendido por unidad
                        $detalle_venta = $this->model->venta_detalle->getBy('id', $dato->origen_id)->result;
                        $venta = $this->model->venta->get($detalle_venta->venta_id)->result;
                        $folio = 'Entrada por devolución de producto vendido por pieza. Venta: '.$venta->folio;
                        break;
                    case 9: // entrada por devolución de producto vendido por kilo
                        $detalle_venta = $this->model->venta_detalle->getBy('id', $dato->origen_id)->result;
                        $venta = $this->model->venta->get($detalle_venta->venta_id)->result;
                        $folio = 'Entrada por devolución de producto vendido por kilo. Venta: '.$venta->folio;
                        break;
                    case 10: // entrada por modificación de entrada de productos
                        $det_entrada = $this->model->prod_entrada->getDet($dato->origen_id)->result;
                        $entrada = $this->model->prod_entrada->get($det_entrada->prod_entarda_id)->result;
                        $folio = 'Entrada por modificación de entrada de productos. Folio de entrada: '.$entrada->folio;
                        break;
                    case 11: // salida por modificación de entrada de productos
                        $det_entrada = $this->model->prod_entrada->getDet($dato->origen_id)->result;
                        $entrada = $this->model->prod_entrada->get($det_entrada->prod_entarda_id)->result;
                        $folio = 'Salida por modificación de entrada de productos. Folio de entrada: '.$entrada->folio;
                        break;
                    case 12: // salida por cancelación de entrada de productos
                        $folio = 'Salida por cancelación de entrada de productos.';
                        break;
                    case 13: // de cotización a venta pieza
                        $detalle_venta = $this->model->venta_detalle->getBy('id', $dato->origen_id)->result;
                        $venta = $this->model->venta->get($detalle_venta->venta_id)->result;
                        $folio = 'Salida por venta de producto por pieza. Se concreta venta de cotización. Venta: '.$venta->folio;
                        break;
                    case 14: // de cotización a venta kilo
                        $detalle_venta = $this->model->venta_detalle->getBy('id', $dato->origen_id)->result;
                        $venta = $this->model->venta->get($detalle_venta->venta_id)->result;
                        $folio = 'Salida por venta de producto por kilo. Se concreta venta de cotización. Venta: '.$venta->folio;
                        break;
                    case 15: // entrada por cambio de producto
                        $detalle_venta = $this->model->venta_detalle->getBy('id', $dato->origen_id)->result;
                        $venta = $this->model->venta->get($detalle_venta->venta_id)->result;
                        $folio = 'Entrada por cambio de producto. Se concreta venta de cotización. Venta: '.$venta->folio;
                        break;
                    case 16: // entrada por eliminación de venta a crédito
                        $detalle_venta = $this->model->venta_detalle->getBy('id', $dato->origen_id)->result;
                        $venta = $this->model->venta->get($detalle_venta->venta_id)->result;
                        $folio = 'Entrada por eliminación de venta a crédito. Venta: '.$venta->folio;
                        break;
                    default:
                        # code...
                        break;
                }
                $entrada = $dato->tipo == 1 ? $dato->cantidad : '';
                $salida = $dato->tipo == -1 ? $dato->cantidad : '';
                $data[] = array(
					"id" => $dato->id,
					"fecha" => $dato->fecha,
					"hora" => $dato->hora,
					"folio" => $folio,
					"entrada" => $entrada,
					"salida" => $salida,
					"existencia" => $dato->final,
					"usuario" => $dato->usuario,
					"notas" => $dato->motivo,
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
        });

        $this->get('getStock/{prod_id}', function($req, $res, $args){
            return $res->withJson($this->model->prod_stock->getStock($_SESSION['sucursal_id'], $args['prod_id'])->result);
        });

        $this->get('getStockByKilo/{id}/{cantidad}', function($req, $res, $args){
            $prod_id = $args['id'];
            $cantidad = $args['cantidad'];
            $info_kilo = $this->model->producto->getKiloBy($prod_id, 'producto_id')->result;
            // Cantidad de piezas que componen un kilo
            $piezas_por_kilo = $info_kilo->cantidad;
            // Convertir la cantidad solicitada en piezas
            $cant_necesaria = floatval($cantidad * $piezas_por_kilo);
            // Obtener el producto y stock
            $prod_origen = $info_kilo->producto_origen;
            $info_stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result;
            // Determinar el stock disponible en piezas
            if (is_object($info_stock)) {
                $stock_disp = $info_stock->final;
            } else {
                $stock_disp = 0;
            }

            // Calcular el máximo en gramos que se puede vender con el stock disponible
            // 1 kilo = 1000 gramos, por lo que necesitamos calcular cuántos gramos
            // corresponden al stock disponible en piezas
            $gramos_por_pieza = 1000 / $piezas_por_kilo; // Gramos por cada pieza
            $gramos_disponibles = number_format((($stock_disp * $gramos_por_pieza)/1000), 3); // Total de gramos disponibles
        
            $data = [
                'cant_necesaria' => $cant_necesaria, 
                'stock_disp' => $stock_disp,
                'gramos_disponibles' => $gramos_disponibles // Máximo en gramos que se puede vender
            ];
        
            // Verificar si se puede realizar la venta
            if ($gramos_disponibles > 0) {
                $data['response'] = true;
                $data['max'] = $gramos_disponibles;
                return $res->withJson($data);
            } else {
                $data['response'] = false;
                return $res->withJson($data);
            }
        });
        

	});

?>