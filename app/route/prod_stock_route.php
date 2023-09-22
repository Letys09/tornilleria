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
            foreach($datos as $dato) {
                $origen = $dato->origen_tipo;
                $folio = '';
                switch ($origen) {
                    case 1:
                        $folio = $this->model->prod_entrada->get($dato->origen_id)->result->folio; break;
                    default:
                        # code...
                        break;
                }
                $entrada = $dato->tipo == 1 ? $dato->cantidad : '';
                $salida = $dato->tipo == -1 ? $dato->cantidad : '';
                $data[] = array(
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

	});

?>