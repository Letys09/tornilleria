<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/prod_entrada/', function () use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('get/{id}', function ($req, $res, $args) {
			$prod = $this->model->producto->get($args['id'])->result;
            $prod->precios = $this->model->producto->getPrecios($prod->id)->result;
            if($prod->venta_kilo){
                $prod->kilo = $this->model->producto->getKiloBy($prod->id, 'producto_origen')->result;
            }else if($prod->es_kilo){
                $prod->kilo = $this->model->producto->getKiloBy($prod->id, 'producto_id')->result;
            }
            return $res->withJson($prod);
		});

        $this->get('getAllDataTable', function($req, $res, $args){
			$entradas = $this->model->prod_entrada->getAllDataTable();

			$data = [];
			if(!isset($_SESSION)) { session_start(); }
			foreach($entradas->result as $entrada) {
                $data[] = array(
					"fecha" => $entrada->date,
					"hora" => $entrada->hora,
					"folio" => $entrada->folio,
					"usuario" => $entrada->usuario,
					"sucursal_id" => $entrada->sucursal_id,
					"sucursal" => $entrada->nombre,
					"importe" => $entrada->importe,
					"descuento" => $entrada->descuento,
					"subtotal" => $entrada->subtotal,
					"iva" => $entrada->iva,
					"total" => $entrada->total,
					"data_id" => $entrada->id,
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
		});

        $this->get('getEntrada/{id}', function($req, $res, $args){
            $entrada = $this->model->prod_entrada->get($args['id'])->result;
            $entrada->detalles = $this->model->prod_entrada->getDetalles($args['id'])->result;

            foreach($entrada->detalles as $detalle){
                $prod = $this->model->producto->get($detalle->producto_id)->result;
                $detalle->producto = '('.$prod->clave.') '.$prod->descripcion;
            }
            return $res->withJson($entrada);
        });

        $this->get('getLastCosto/{producto_id}', function($req, $res, $args){
            $sucursal_id = 3;
            return $res->withJson($this->model->prod_entrada->getLastCosto($sucursal_id, $args['producto_id']));
        });

		$this->post('add/',function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $entradas = $parsedBody['entradas'];
            $sucursal_id = $parsedBody['sucursal_id'];
            $entrada = [
                'usuario_id' => $_SESSION['usuario_id'],
                'sucursal_id' => $sucursal_id,
                'folio' => $parsedBody['folio'],
                'importe' => $parsedBody['importe'],
                'descuento' => $parsedBody['descuento'],
                'subtotal' => $parsedBody['subtotal'],
                'iva' => $parsedBody['iva'],
                'total' => $parsedBody['total'],
                'fecha' => date('Y-m-d H:i:s'),
            ];                
            $addEnt = $this->model->prod_entrada->add($entrada, 'prod_entrada');
            if($addEnt->response){
                $id_entrada = $addEnt->result;
                $total = COUNT($entradas); $hecho = 0;
                foreach($entradas as $prod_entrada){
                    $prod_id = $prod_entrada['producto_id']; $cantidad = $prod_entrada['cantidad']; $costo = $prod_entrada['costo'];
                    $det_entrada = [
                        'prod_entrada_id' => $id_entrada,
                        'producto_id' => $prod_id,
                        'cantidad' => $cantidad,
                        'costo' => $costo,
                        'total' => $prod_entrada['total'],
                    ];
                    $addDet = $this->model->prod_entrada->add($det_entrada, 'prod_det_entrada');
                    if($addDet->response){
                        $stock = $this->model->prod_stock->getStock($sucursal_id, $prod_id)->result;
                        $dataStock = [
                            'usuario_id' => $_SESSION['usuario_id'],
                            'sucursal_id' => $sucursal_id,
                            'producto_id' => $prod_id,
                            'tipo' => 1,
                            'fecha' => date('Y-m-d H:i:s'),
                            'motivo' => '',
                            'origen_tipo' => 1,
                            'origen_tabla' => 'prod_entrada',
                            'origen_id' => $id_entrada,
                        ];
                        if(is_object($stock)){
                            $dataStock['inicial'] = $stock->final;
                            $dataStock['cantidad'] = $cantidad;
                            $dataStock['final'] = $stock->final + $cantidad;
                        }else{
                            $dataStock['inicial'] = 0;
                            $dataStock['cantidad'] = $cantidad;
                            $dataStock['final'] = $cantidad;
                        }
                        $addStock = $this->model->prod_stock->add($dataStock);
                        if($addStock->response){
                            $costo = [ 'costo' => $costo];
                            $editProd = $this->model->producto->edit('producto', 'id', $costo, $prod_id);
                        }else{
                            $addStock->state = $this->model->transaction->regresaTransaccion();	
                            return $res->withJson($addStock->SetResponse(false, 'No se pudo agregar el registro de stock del producto'));
                        }
                    }else{
                        $addDet->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($addDet->SetResponse(false, 'No se pudo agregar el detalle de la entrada, prod: '.$prod_id));
                    }
                }
                $seg_log = $this->model->seg_log->add('Agrega entrada de productos', 'prod_entrada', $id_entrada, 1);
                if($seg_log->response){
                    $addEnt->state = $this->model->transaction->confirmaTransaccion();	
                    return $res->withJson($addEnt);
                }else{
                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                }
            }else{
                $addEnt->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($addEnt->SetResponse(false, 'No se pudo agregar el registro de la entrada'));
            }
		});

		$this->post('edit/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $entrada_id = $args['id'];
            $dataIgual = true;
            $entrada = $this->model->prod_entrada->get($entrada_id)->result;
            $entrada_ant = [
                'importe' => $entrada->importe,
                'subtotal' => $entrada->subtotal,
                'iva' => $entrada->iva,
                'total' => $entrada->total,
            ];
            $entrada_new = [
                'importe' => $parsedBody['importe'],
                'subtotal' => $parsedBody['subtotal'],
                'iva' => $parsedBody['iva'],
                'total' => $parsedBody['total'],
            ];    
            foreach($entrada_new as $field => $value) { 
                if($entrada_ant[$field] != $value) { 
                    $dataIgual = false; break; 
                } 
            }
            if(!$dataIgual){
                $edit_entrada = $this->model->prod_entrada->edit($entrada_new, $entrada_id, 'prod_entrada');
                if($edit_entrada->response){
                    $seg_log = $this->model->seg_log->add('Modifica entrada de productos', 'prod_entrada', $entrada_id, 1);
                    if($seg_log->response){
                        $edit_entrada->state = $this->model->transaction->confirmaTransaccion();	
                        return $res->withJson($edit_entrada);
                    }else{
                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                    }
                }else{
                    $edit_entrada->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($edit_entrada->SetResponse(false, 'No se pudo modificar el registro de la entrada'));
                }
            }else{
                $edit_entrada = ['code' => 1, 'msg' => 'No existen datos diferentes a los antes registrados'];
                return $res->withJson($edit_entrada);
            }
		});

        $this->post('editDet/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $data = $req->getParsedBody();
            $det_id = $args['id'];
            $fecha = date('Y-m-d H:i:s'); $sucursal_id = $_SESSION['sucursal_id']; $usuario_id = $_SESSION['usuario_id'];
            $detalle = $this->model->prod_entrada->getDet($det_id);
            if(isset($data['cantidad'])){
                $cant_ant = $detalle->result->cantidad;
                $data_det = ['cantidad' => $data['cantidad'], 'total' => $data['total']];
                $edit = $this->model->prod_entrada->edit($data_det, $det_id, 'prod_det_entrada');
                if($edit->response){
                    $prod_id = $data['prod_id'];
                    $prod_stock = $this->model->prod_stock->getStock($sucursal_id, $prod_id)->result;
                    $inicial = $prod_stock->final;
                    $cant_new = $data['cantidad'];
                    if($cant_ant < $cant_new){
                        $tipo = 1;   
                        $cantidad = $cant_new - $cant_ant; 
                        $final = floatval($inicial+$cantidad);
                        $origen_tipo = 10; // Entrada por modificación de entrada de productos  
                    }else{
                        $tipo = -1;    
                        $cantidad = $cant_ant - $cant_new; 
                        $final = floatval($inicial-$cantidad);
                        $origen_tipo = 11; // Salida por modificación de entrada de productos  
                    }
                    $data_stock = [
                        'usuario_id' => $usuario_id,
                        'sucursal_id' => $sucursal_id,
                        'producto_id' => $prod_id, 
                        'tipo' => $tipo,
                        'inicial' => $inicial,
                        'cantidad' => $cantidad,
                        'final' => $final,
                        'fecha' => $fecha,
                        'origen_tipo' => $origen_tipo,
                        'origen_tabla' => 'prod_det_entrada',
                        'origen_id' => $det_id,
                    ];
                    $add_stock  =$this->model->prod_stock->add($data_stock);
                    if($add_stock->response){
                        $seg_log = $this->model->seg_log->add('Modifica cantidad de detalle. Ant: '.$cant_ant.' Desp: '.$cant_new, 'prod_det_entrada', $det_id, 1);
                        $seg_log->state = $this->model->transaction->confirmaTransaccion();
                        return $res->withJson($edit);
                    }else{
                        $add_stock->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($add_stock);
                    }
                }else{
                    $edit->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($edit);

                }
            }else{
                $costo_ant = $detalle->result->costo;
                $data_det = ['costo' => $data['costo'], 'total' => $data['total']];
                $edit = $this->model->prod_entrada->edit($data_det, $det_id, 'prod_det_entrada');
                if($edit->response){
                    $seg_log = $this->model->seg_log->add('Modifica costo de detalle. Ant: '.$costo_ant.' Desp: '.$data['costo'], 'prod_det_entrada', $det_id, 1);
                    $seg_log->state = $this->model->transaction->confirmaTransaccion();
                    return $res->withJson($edit);
                }else{
                    $edit->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($edit);
                }
            }
        });

        $this->post('del/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $entrada_id = $args['id']; $fecha = date('Y-m-d H:i:s');
            $detalles = $this->model->prod_entrada->getDetalles($entrada_id)->result;
            foreach($detalles as $detalle){
                $id = $detalle->id;
                $del_detalle = $this->model->prod_entrada->del($id, 'prod_det_entrada');
                if($del_detalle->response){
                    $prod_stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $detalle->producto_id)->result;
                    $info_prod = $this->model->producto->get($detalle->producto_id)->result;
                    $producto = '('.$info_prod->clave.') '.$info_prod->descripcion;
                    if($detalle->cantidad <= $prod_stock->final){
                        $data_stock = [
                            'usuario_id' => $_SESSION['usuario_id'],
                            'sucursal_id' => $_SESSION['sucursal_id'],
                            'producto_id' => $detalle->producto_id,
                            'tipo' => -1,
                            'inicial' => $prod_stock->final,
                            'cantidad' => $detalle->cantidad,
                            'final' => floatval($prod_stock->final-$detalle->cantidad),
                            'fecha' => $fecha,
                            'origen_tipo' => 12,
                            'origen_tabla' => 'prod_det_entrada',
                            'origen_id' => $id
                        ];   
                        $add_stock = $this->model->prod_stock->add($data_stock);
                        if(!$add_stock->response){
                            $add_stock->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($add_stock);
                        }
                    }else{
                        $del_detalle->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($del_detalle->SetResponse(false, "No se puede eliminar la entrada ya que hay menos stock disponible del que se ingresó. \nProducto: ".$producto."\nStock ingresado: ".$detalle->cantidad."\nStock actual: ".$prod_stock->final));
                    }
                }
            }
            $del_entrada = $this->model->prod_entrada->del($entrada_id, 'prod_entrada');
            if($del_entrada->response){
                $seg_log = $this->model->seg_log->add('Elimina entrada de productos', 'prod_entrada', $entrada_id, 1);
                if($seg_log->response){
                    $del_entrada->state = $this->model->transaction->confirmaTransaccion();	
                    return $res->withJson($del_entrada);
                }else{
                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                }
            }else{
                $del_entrada->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($del_entrada->SetResponse(false, 'No se pudo eliminar la entrada de productos'));
            }
        });

        $this->post('delDet/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $id = $args['id']; $fecha = date('Y-m-d H:i:s');
            $detalle = $this->model->prod_entrada->getDet($id)->result;
            $del_detalle = $this->model->prod_entrada->del($id, 'prod_det_entrada');
            if($del_detalle->response){
                $prod_stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $detalle->producto_id)->result;
                $producto = $this->model->producto->get($detalle->producto_id)->result->descripcion;
                if($detalle->cantidad <= $prod_stock->final){
                    $data_stock = [
                        'usuario_id' => $_SESSION['usuario_id'],
                        'sucursal_id' => $_SESSION['sucursal_id'],
                        'producto_id' => $detalle->producto_id,
                        'tipo' => -1,
                        'inicial' => $prod_stock->final,
                        'cantidad' => $detalle->cantidad,
                        'final' => floatval($prod_stock->final-$detalle->cantidad),
                        'fecha' => $fecha,
                        'origen_tipo' => 12,
                        'origen_tabla' => 'prod_det_entrada',
                        'origen_id' => $id
                    ];   
                    $add_stock = $this->model->prod_stock->add($data_stock);
                    if($add_stock->response){
                        $seg_log = $this->model->seg_log->add('Elimina entrada de producto', 'prod_det_entrada', $id, 1);
                        if($seg_log->response){
                            $del_detalle->state = $this->model->transaction->confirmaTransaccion();	
                            return $res->withJson($del_detalle);
                        }else{
                            $seg_log->state = $this->model->transaction->regresaTransaccion();	
                            return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                        }
                    }else{
                        $add_stock->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($add_stock);
                    }
                }else{
                    $del_detalle->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($this->response->SetResponse(false, "No se puede eliminar la entrada ya que hay menos stock disponible del que se ingresó, producto: ".$descripcion));
                }
            }
        });
	});

?>