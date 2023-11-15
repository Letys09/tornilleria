<?php
use App\Lib\Auth,
	App\Lib\Response;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/venta_detalle/', function () use ($app){
        
        $this->get('getByVenta/{id}', function($req, $res, $args){
            $detalles = $this->model->venta_detalle->getByVenta($args['id']);
            if(!is_object($detalles)){
                $detalles = ['response' => false, 'msg' => 'No hay detalles de venta'];
            }else{
                foreach($detalles->result as $detalle){
                    $detalle->detalle_id = $detalle->id;
					$prod = $this->model->producto->get($detalle->producto_id)->result;
					$detalle->es_kilo = $prod->es_kilo;
					if($prod->es_kilo){
						$detalle->es_kilo = $this->model->producto->getKiloBy($detalle->producto_id, 'producto_id')->result->cantidad;
					}
					$detalle->clave = $prod->clave;
					$detalle->concepto = '('.$prod->clave.') '.$prod->descripcion;
					if($prod->es_kilo == 0){
						$info_stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $detalle->producto_id)->result->final;
						$detalle->stock = number_format($info_stock, 1);
					}else{
						$info_kilo = $this->model->producto->getKiloBy($detalle->producto_id, 'producto_id')->result;
						$prod_origen = $info_kilo->producto_origen;
						$info_stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result->final;
						$detalle->stock = number_format($info_stock, 1);
					}
					$detalle->descripcion = $prod->descripcion;
					$detalle->desc = $prod->descripcion;
				}
            }
            return $res->withJson($detalles);
        });

        $this->get('getAll/{desde}/{hasta}', function($req, $res, $args){
            $detalles = $this->model->venta_detalle->getAll($args['desde'], $args['hasta']);
            $data = [];
            foreach($detalles->result as $detalle){
                $data[] = array(
                    "fecha" => $detalle->date,
                    "hora" => $detalle->hora,
                    "producto" => $detalle->producto,
                    "cantidad" => $detalle->cantidad,
                    "folio" => $_SESSION['sucursal_identificador'].'-'.$detalle->venta_fecha.'-'.$detalle->venta_id, 
                    "usuario" => $detalle->usuario, 
                    "cliente" => $detalle->cliente,
                );
            }
            echo json_encode(array(
                'data' => $data
            ));

			exit(0);
        });

        $this->post('add/',function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $venta_id = $parsedBody['venta_id'];
            $producto_id = $parsedBody['producto_id'];
            $fecha = date('Y-m-d H:i:s');
            $dataDet = [
                'venta_id' => $venta_id,
                'fecha' => $fecha,
                'producto_id' => $producto_id,
                'cantidad' => $parsedBody['cantidad'],
                'precio' => $parsedBody['precio'],
                'importe' => $parsedBody['importe'],
                'total' => $parsedBody['total'],
            ];
            $addDet = $this->model->venta_detalle->add($dataDet);
            if($addDet->response){
                $det_id = $addDet->result;
                $seg_log = $this->model->seg_log->add('Agrega detalle de venta', 'venta_detalle', $det_id, 1);
                $info_prod = $this->model->producto->get($parsedBody['producto_id'])->result;
                if($info_prod->es_kilo == 0){
                    $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto_id)->result;
                    $inicial = $stock->final;
                    if($inicial != 0){
                        if($inicial >= $parsedBody['cantidad']){
                            $final = floatval($inicial-$parsedBody['cantidad']);
                            $dataStock = [
                                'usuario_id' => $_SESSION['usuario_id'],
                                'sucursal_id' => $_SESSION['sucursal_id'],
                                'producto_id' => $producto_id,
                                'tipo' => -1,
                                'inicial' => $inicial,
                                'cantidad' => $parsedBody['cantidad'],
                                'final' => $final,
                                'fecha' => $fecha,
                                'origen_tipo' => 3,
                                'origen_tabla' => 'venta_detalle',
                                'origen_id' => $det_id
                            ];
                            $addStock = $this->model->prod_stock->add($dataStock);
                            if($addStock->response){
                                $seg_log = $this->model->seg_log->add('Agrega detalle venta', 'venta_detalle', $det_id, 1);
                                $addDet->state = $this->model->transaction->confirmaTransaccion();
                                return $res->withJson($addDet);
                            }else{
                                $addStock->state = $this->model->transaction->regresaTransaccion();
                                return $res->withJson($this->response->SetResponse(false, "No se agregó el registro del stock $addStock"));
                            }
                        }else{
                            $this->response = new Response(); 
                            $this->response->state = $this->model->transaction->regresaTransaccion(); 
                            return $res->withJson($this->response->SetResponse(false, "No hay suficiente stock del producto: $producto_id $info_prod->nombre"));
                        }
                    }else{
                        $this->response = new Response(); 
                        $this->response->state = $this->model->transaction->regresaTransaccion(); 
                        return $res->withJson($this->response->SetResponse(false, "No hay stock disponible del producto: $producto_id $info_prod->nombre"));
                    }
                }else{
                    $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                    $cantidad = ($info_kilo->cantidad * $parsedBody['cantidad']);
                    $prod_origen = $info_kilo->producto_origen;
                    $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result;
                    $inicial = $stock->final;
                    if($inicial != 0){
                        if($inicial >= $cantidad){
                            $final = floatval($inicial-$cantidad);
                            $dataStock = [
                                'usuario_id' => $_SESSION['usuario_id'],
                                'sucursal_id' => $_SESSION['sucursal_id'],
                                'producto_id' => $producto_id,
                                'tipo' => -1,
                                'inicial' => $inicial,
                                'cantidad' => $cantidad,
                                'final' => $final,
                                'fecha' => $fecha,
                                'origen_tipo' => 7,
                                'origen_tabla' => 'venta_detalle',
                                'origen_id' => $det_id
                            ];
                            $addStock = $this->model->prod_stock->add($dataStock);
                            if($addStock->response){
                                $seg_log = $this->model->seg_log->add('Agrega detalle de venta', 'venta_detalle', $det_id, 1);
                                $addDet->state = $this->model->transaction->confirmaTransaccion();
                                return $res->withJson($addDet);
                            }else{
                                $addStock->state = $this->model->transaction->regresaTransaccion();
                                return $res->withJson($this->response->SetResponse(false, "No se agregó el registro del stock $addStock"));
                            }
                        }else{
                            $this->response = new Response(); 
                            $this->response->state = $this->model->transaction->regresaTransaccion(); 
                            return $res->withJson($this->response->SetResponse(false, "No hay suficiente stock del producto: $producto_id $info_prod->nombre"));
                        }
                    }else{
                        $this->response = new Response(); 
                        $this->response->state = $this->model->transaction->regresaTransaccion(); 
                        return $res->withJson($this->response->SetResponse(false, "No hay stock disponible del producto: $producto_id $info_prod->nombre"));
                    }                            
                }
            }else{
                $addDet->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addDet);
            }
		});

        $this->post('edit/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();
            $fecha = date('Y-m-d H:i:s');

            $detIgual = true;
            $info_det = $this->model->venta_detalle->get($args['id'])->result;
            $detalle = [
                'cantidad' => $parsedBody['cantidad'],
                'precio' => $parsedBody['precio'],
                'importe' => $parsedBody['importe'],
                'descuento' => $parsedBody['descuento'],
                'total' => $parsedBody['total'],
            ];

            foreach($detalle as $field => $value) { 
                if($info_det->$field != $value) { 
                    $detIgual = false; break; 
                } 
            }

            if($info_det->cantidad != $parsedBody['cantidad']){
                $producto_id = $info_det->producto_id;
                $info_prod = $this->model->producto->get($producto_id)->result;
                if($parsedBody['cantidad'] < $info_det->cantidad){
                    if($info_prod->es_kilo == 0){
                        $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto_id)->result;
                        $inicial = $stock->final;
                        $cantidad = $info_det->cantidad-$parsedBody['cantidad'];
                        $final = $inicial+$cantidad;
                        $dataStock = [
                            'usuario_id' => $_SESSION['usuario_id'],
                            'sucursal_id' => $_SESSION['sucursal_id'],
                            'producto_id' => $producto_id,
                            'tipo' => 1,
                            'inicial' => $inicial,
                            'cantidad' => $cantidad,
                            'final' => $final,
                            'fecha' => $fecha,
                            'origen_tipo' => 8,
                            'origen_tabla' => 'venta_detalle',
                            'origen_id' => $args['id']
                        ];
                    }else{
                        $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                        $cant_anterior = ($info_kilo->cantidad * $info_det->cantidad);
                        $cant_nueva = ($info_kilo->cantidad * $parsedBody['cantidad']);
                        $prod_origen = $info_kilo->producto_origen;
                        $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result;
                        $inicial = $stock->final;
                        $cantidad = $cant_anterior-$cant_nueva;
                        $final = $inicial+$cantidad;
                        $dataStock = [
                            'usuario_id' => $_SESSION['sucursal_id'],
                            'sucursal_id' => $_SESSION['sucursal_id'],
                            'producto_id' => $producto_id,
                            'tipo' => 1,
                            'inicial' => $inicial,
                            'cantidad' => $cantidad,
                            'final' => $final,
                            'fecha' => $fecha,
                            'origen_tipo' => 9,
                            'origen_tabla' => 'venta_detalle',
                            'origen_id' => $args['id']
                        ];
                    }
                    $addStock = $this->model->prod_stock->add($dataStock);
                    if(!$addStock->response){
                        $addStock->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($addStock);
                    }
                }else{
                    if($info_prod->es_kilo == 0){
                        $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto_id)->result;
                        $inicial = $stock->final;
                        $cant_nueva = $parsedBody['cantidad'] - $info_det->cantidad;
                        if($inicial > 0 && $cant_nueva <= $inicial){
                            $final = $inicial+$cant_nueva;
                            $dataStock = [
                                'usuario_id' => $_SESSION['usuario_id'],
                                'sucursal_id' => $_SESSION['sucursal_id'],
                                'producto_id' => $producto_id,
                                'tipo' => -1,
                                'inicial' => $inicial,
                                'cantidad' => $cant_nueva,
                                'final' => $final,
                                'fecha' => $fecha,
                                'origen_tipo' => 3,
                                'origen_tabla' => 'venta_detalle',
                                'origen_id' => $args['id']
                            ];
                        }else{
                            $this->response = new Response(); 
                            $this->response->state = $this->model->transaction->regresaTransaccion(); 
                            return $res->withJson($this->response->SetResponse(false, "No hay suficiente stock del producto: $producto_id $info_prod->nombre"));
                        }
                    }else{
                        $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                        $cant_anterior = ($info_kilo->cantidad * $info_det->cantidad);
                        $cant_nueva = ($info_kilo->cantidad * $parsedBody['cantidad']);
                        $prod_origen = $info_kilo->producto_origen;
                        $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result;
                        $inicial = $stock->final;
                        $cantidad = $cant_nueva - $cant_anterior;
                        if($inicial > 0 && $cantidad){
                            $final = $inicial - $cantidad;
                            $dataStock = [
                                'usuario_id' => $_SESSION['usuario_id'],
                                'sucursal_id' => $_SESSION['sucursal_id'],
                                'producto_id' => $producto_id,
                                'tipo' => 1,
                                'inicial' => $inicial,
                                'cantidad' => $cantidad,
                                'final' => $final,
                                'fecha' => $fecha,
                                'origen_tipo' => 7,
                                'origen_tabla' => 'venta_detalle',
                                'origen_id' => $args['id']
                            ];
                        }else{
                            $this->response = new Response(); 
                            $this->response->state = $this->model->transaction->regresaTransaccion(); 
                            return $res->withJson($this->response->SetResponse(false, "No hay suficiente stock del producto: $producto_id $info_prod->nombre"));
                        }
                    }
                    $addStock = $this->model->prod_stock->add($dataStock);
                    if(!$addStock->response){
                        $addStock->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($addStock);
                    }
                }
            }

            if(!$detIgual){
                $edit_det = $this->model->venta_detalle->edit($detalle, $args['id']);
                if($edit_det->response){
                    $seg_log = $this->model->seg_log->add('Modifica detalle de venta', 'venta_detalle', $args['id'], 1);
                }else{
                    $edit_det->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($edit_det->setResponse(false, 'No se editó la información del detalle de venta'));
                }
            }
			
            if($detIgual){
                $edit_detalle = ['code' => 1, 'msg' => 'No existen datos diferentes a los antes registrados'];
                return $res->withJson($edit_detalle);
            }else{
                $edit_detalle = ['response' => true, 'msg' => 'Registro '.$args['id'].' actualizado'];
                $this->model->transaction->confirmaTransaccion();
                return $res->withJson($edit_detalle);
            }
		});

        $this->post('del/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $fecha = date('Y-m-d H:i:s');
            $detalle = $this->model->venta_detalle->get($args['id'])->result;
            $producto_id = $detalle->producto_id;
            $info_prod = $this->model->producto->get($producto_id)->result;
            if($info_prod->es_kilo == 0){
                $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto_id)->result;
                $inicial = $stock->final;
                $cantidad = $detalle->cantidad;
                $final = floatval($inicial+$cantidad);
                $data_stock = [
                    'usuario_id' => $_SESSION['usuario_id'],
                    'sucursal_id' => $_SESSION['sucursal_id'],
                    'producto_id' => $detalle->producto_id,
                    'tipo' => 1,
                    'inicial' => $inicial,
                    'cantidad' => $detalle->cantidad,
                    'final' => $final,
                    'fecha' => $fecha,
                    'origen_tipo' => 8,
                    'origen_tabla' => 'venta_detalle',
                    'origen_id' => $args['id']
                ];
            }else{
                $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                $cantidad = floatval($info_kilo->cantidad * $detalle->cantidad);
                $prod_origen = $info_kilo->producto_origen;
                $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result;
                $inicial = $stock->final;
                $final = floatval($inicial+$cantidad);
                $data_stock = [
                    'usuario_id' => $_SESSION['usuario_id'],
                    'sucursal_id' => $_SESSION['sucursal_id'],
                    'producto_id' => $prod_origen,
                    'tipo' => 1,
                    'inicial' => $inicial,
                    'cantidad' => $cantidad,
                    'final' => $final,
                    'fecha' => $fecha,
                    'origen_tipo' => 9,
                    'origen_tabla' => 'venta_detalle',
                    'origen_id' => $args['id']
                ];
            }
            $addStock = $this->model->prod_stock->add($data_stock);
            if($addStock->response){
                $del_detalle = $this->model->venta_detalle->del($args['id']);
                if($del_detalle->response){
                    $seg_log = $this->model->seg_log->add('Elimina detalle de venta', 'venta_detalle', $args['id'], 1);
                    if($seg_log->response){
                        $del_detalle->state = $this->model->transaction->confirmaTransaccion();	
                        return $res->withJson($del_detalle);
                    }else{
                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                    }
                }else{
                    $del_detalle->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($del_detalle->SetResponse(false, 'No se pudo eliminar el detalle de venta'));
                }
            }else{
                $addStock->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addStock);
            }
        });

        $this->post('cambio/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $fecha = date('Y-m-d H:i:s');
            $detalle = $this->model->venta_detalle->get($args['id'])->result;
            $producto_id = $detalle->producto_id;
            $info_prod = $this->model->producto->get($producto_id)->result;
            if($info_prod->es_kilo == 0){
                $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto_id)->result;
                $inicial = $stock->final;
                $cantidad = $detalle->cantidad;
                $final = floatval($inicial+$cantidad);
                $data_stock = [
                    'usuario_id' => $_SESSION['usuario_id'],
                    'sucursal_id' => $_SESSION['sucursal_id'],
                    'producto_id' => $detalle->producto_id,
                    'tipo' => 1,
                    'inicial' => $inicial,
                    'cantidad' => $detalle->cantidad,
                    'final' => $final,
                    'fecha' => $fecha,
                    'origen_tipo' => 15,
                    'origen_tabla' => 'venta_detalle',
                    'origen_id' => $args['id']
                ];
            }else{
                $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                $cantidad = floatval($info_kilo->cantidad * $detalle->cantidad);
                $prod_origen = $info_kilo->producto_origen;
                $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result;
                $inicial = $stock->final;
                $final = floatval($inicial+$cantidad);
                $data_stock = [
                    'usuario_id' => $_SESSION['usuario_id'],
                    'sucursal_id' => $_SESSION['sucursal_id'],
                    'producto_id' => $prod_origen,
                    'tipo' => 1,
                    'inicial' => $inicial,
                    'cantidad' => $cantidad,
                    'final' => $final,
                    'fecha' => $fecha,
                    'origen_tipo' => 15,
                    'origen_tabla' => 'venta_detalle',
                    'origen_id' => $args['id']
                ];
            }
            $addStock = $this->model->prod_stock->add($data_stock);
            if($addStock->response){
                $del_detalle = $this->model->venta_detalle->cambio($args['id']);
                if($del_detalle->response){
                    $seg_log = $this->model->seg_log->add('Elimina detalle de venta por cambio de producto', 'venta_detalle', $args['id'], 1);
                    if($seg_log->response){
                        $del_detalle->state = $this->model->transaction->confirmaTransaccion();	
                        return $res->withJson($del_detalle);
                    }else{
                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                    }
                }else{
                    $del_detalle->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($del_detalle->SetResponse(false, 'No se pudo eliminar el detalle de venta para cambio de producto'));
                }
            }else{
                $addStock->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addStock);
            }
        });

	});

?>