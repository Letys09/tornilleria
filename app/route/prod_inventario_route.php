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

        // verificar si esta abierto el inventario en sucursal
        $this->get('getInventarioActivo/{sucursal_id}', function ($req, $res, $args) {
            $sucursal_id = $args['sucursal_id'];
			$inventario = $this->model->prod_inventario->getInventarioActivo($sucursal_id);
            return $res->withJson($inventario);
		});
    
        // agregar Inventario fisico en prod_inventario
        $this->post('add/', function($req, $res, $args){
            date_default_timezone_set('America/Mexico_City');
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $usuario_id = $parsedBody['usuario_id'];
            $sesion_id = $parsedBody['sesion_id'];
            $sucursal_id = $parsedBody['sucursal_id'];
            $fecha = date('Y-m-d H:i:s');

            $inventario = $this->model->prod_inventario->getInventarioActivo($sucursal_id);
            if(!$inventario->response){
                $data_inv = [
                    'usuario_id' => $usuario_id,
                    'sucursal_id' => $sucursal_id,
                    'estado_inventario' => '2',
                    'fecha' => $fecha
                ];
                $add_inv = $this->model->prod_inventario->add($data_inv);
                if($add_inv->response){
                    $inventario_id = $add_inv->result;
                    $this->model->seg_log->addByApp('Inventario físico abierto en sucursal '.$sucursal_id, 'prod_inventario', $inventario_id, 1, $usuario_id, $sesion_id);
                    $add_inv->state = $this->model->transaction->confirmaTransaccion();
                    return $res->withJson($add_inv);
                }else{
                    $add_inv->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($add_inv);
                }
            }else{
                $inventario->result="";
                $inventario->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($inventario->setResponse(false, 'Inventario ya fue abierto'));
            }
        });

        $this->post('addDetalle/', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $sucursal_id = $parsedBody['sucursal_id'];
            $producto_id = $parsedBody['producto_id'];
            $fisico = intval($parsedBody['fisico']);
            
            $inventario = $this->model->prod_inventario->getInventarioActivo($sucursal_id);
            $prod_inventario_id = $inventario->result->id;
            if($inventario->response){
                $CheckInventario = $this->model->prod_inventario->get($prod_inventario_id);
                if($CheckInventario->response){
                    $prod_stock = $this->model->prod_stock->getStock($sucursal_id, $producto_id);
                    if($prod_stock->response){
                        $stock = $prod_stock->result->final;
                    }else{
                        $stock = 0;    
                    }                    
                    $costo = $this->model->prod_entrada->getLastCosto($sucursal_id, $producto_id);
                    if($costo->response){
                        $costo = $costo->result->costo;
                    }else{
                        $costo = 0;
                    }
                    $diferencia = floatval($fisico-$stock);
                    $monto = $diferencia * $costo;
                    $det_inv = [
                        'prod_inventario_id' => $prod_inventario_id,
                        'producto_id' => $producto_id,
                        'sistema' => $stock,
                        'fisico' => $fisico,
                        'diferencia' => floatval($fisico-$stock),
                        'monto' => $monto,
                        'check_inventario' => '0',
                    ];
                    $add_det = $this->model->prod_det_inventario->add($det_inv);
                    if(!$add_det->response){
                        $add_det->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($add_det);
                    }
                    
                    $add_det->state = $this->model->transaction->confirmaTransaccion();
                    return $res->withJson($add_det->setResponse(true, 'Se agrego detalle de inventario correctamente'));
                }else{
                    $inventario->result="";
                    $inventario->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($inventario->setResponse(false, 'No hay inventario activo en esta sucursal'));
                }
            }else{
                $inventario->result="";
                $inventario->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($inventario->setResponse(false, 'Debe abrir inventario en la sucursal'));
            }
        });

        // cerrar Inventario fisico en prod_inventario
        $this->post('cerrarInventario/', function($req, $res, $args){
            date_default_timezone_set('America/Mexico_City');
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $usuario_id = $parsedBody['usuario_id'];
            $sesion_id = $parsedBody['sesion_id'];
            $sucursal_id = $parsedBody['sucursal_id'];

            $inventario = $this->model->prod_inventario->getInventarioActivo($sucursal_id);
            if($inventario->response){
                $idInventario = $inventario->result->id;
                if($inventario->result->estado_inventario==1){
                    $inventario->result="";
                    $inventario->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($inventario->setResponse(false, 'Inventario ya fue cerrado'));
                }else{
                    $data=[
                        'check_inventario'=>'1'
                    ];
                    $this->model->prod_det_inventario->edit($data, $idInventario);
                    $data_inv = [
                        'estado_inventario' => '1'
                    ];
                    $edit_inv = $this->model->prod_inventario->edit($data_inv, $idInventario);
                    if($edit_inv->response){
                        $this->model->seg_log->addByApp('Inventario físico cerrado en sucursal '.$sucursal_id, 'prod_inventario', $idInventario, 1, $usuario_id, $sesion_id);
                        $edit_inv->state = $this->model->transaction->confirmaTransaccion();
                        return $res->withJson($edit_inv);
                    }else{
                        $edit_inv->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($edit_inv);
                    }
                }
            }else{
                $inventario->result="";
                $inventario->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($inventario->setResponse(false, 'Ocurrio algo extraño, Vuelve a intentar'));
            }
        });
    });
?>