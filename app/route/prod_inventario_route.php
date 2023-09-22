<?php 
use App\Lib\Auth,
    App\Lib\Response;

    date_default_timezone_set('America/Mexico_City');

    $app->group('/prod_inventario/', function() use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('get/{id}', function ($req, $res, $args) {
			$inventario = $this->model->prod_inventario->get($args['id'])->result;
            $inventario->detalles = $this->model->prod_det_inventario->get($args['id'])->result;
            return $res->withJson($inventario);
		});

        $this->get('getAllDataTable/{desde}/{hasta}', function($req, $res, $args){
			$inventarios = $this->model->prod_inventario->getAllDataTable($args['desde'], $args['hasta']);

			$data = [];
			foreach($inventarios->result as $inventario) {
                $data[] = array(
					"id" => $inventario->id,
					"usuario" => $inventario->usuario,
					"fecha" => $inventario->fecha,
					"hora" => $inventario->hora
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
		});

        $this->post('add/', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $detalles = $parsedBody['detalles'];
            $fecha = date('Y-m-d H:i:s');
            $data_inv = [
                'usuario_id' => 1,
                // 'usuario_id' => $usuario_id,
                'fecha' => $fecha
            ];
            $add_inv = $this->model->prod_inventario->add($data_inv);
            if($add_inv->response){
                $inventario_id = $add_inv->result;
                foreach($detalles as $detalle){
                    $prod_stock = $this->model->prod_stock->getStock(3, $detalle['producto_id'])->result;
                    // $prod_stock = $this->model->prod_stock->getStock($sucursal_id, $detalle['producto_id']);
                    $stock = $prod_stock->final;
                    $costo = $this->model->prod_entrada->getLastCosto(3, $detalle['producto_id'])->result->costo;
                    // $costo = $this->model->prod_entrada->getLastCosto($sucursal_id, $detalle['producto_id'])->result->costo;
                    $diferencia = floatval($detalle['fisico']-$stock);
                    $monto = $diferencia * $costo; 
                    $det_inv = [
                        'prod_inventario_id' => $inventario_id,
                        'producto_id' => $detalle['producto_id'],
                        'sistema' => $stock,
                        'fisico' => $detalle['fisico'],
                        'diferencia' => floatval($detalle['fisico']-$stock),
                        'monto' => $monto
                    ];
                    $add_det = $this->model->prod_det_inventario->add($det_inv);
                }
                $seg_log = $this->model->seg_log->add('Inventario físico', 'prod_inventario', $inventario_id, 1);
                $add_inv->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($add_inv);
            }else{
                $add_inv->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($add_inv);
            }
        });
    });
?>