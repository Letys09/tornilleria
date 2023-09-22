<?php
use App\Lib\Auth,
	App\Lib\Response;

    date_default_timezone_set('America/Mexico_City');

	$app->group('/venta/', function () use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('get/{id}', function($req, $res, $args){
            $venta_id = $args['id'];
            $venta = $this->model->venta->get($venta_id)->result;
            $venta->detalles = $this->model->venta_detalle->getByVenta($venta_id)->result;
            $venta->pagos = $this->model->venta_pago->getByVenta($venta_id)->result;

            return $res->withJson($venta);
        });

        $this->post('add/', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $detalles = $parsedBody['detalles']; unset($parsedBody['detalles']);
            $pagos = $parsedBody['pagos']; unset($parsedBody['pagos']);
            $fecha = date('Y-m-d H:i:s'); $cliente_id = $parsedBody['cliente_id'];
            $status = $parsedBody['tipo'] == 1 ? 1 : 2;
            $dataVenta = [
                'cliente_id' => $cliente_id,
                'usuario_id' => 1,
                // 'usuario_id' => $usuario_id,
                'fecha' => $fecha,
                'tipo' => $parsedBody['tipo'],
                'importe' => $parsedBody['importe'],
                'descuento' => $parsedBody['descuento'],
                'subtotal' => $parsedBody['subtotal'],
                'total' => $parsedBody['total'],
                'comentarios' => $parsedBody['comentarios'],
                'status' => $status
            ];
            $addVenta = $this->model->venta->add($dataVenta);
            if($addVenta->response){
                $venta_id = $addVenta->result;
                foreach($detalles as $detalle){
                    $producto_id = $detalle['producto_id'];
                    $info_prod = $this->model->producto->get($producto_id)->result;
                    $dataDet = [
                        'venta_id' => $venta_id,
                        'producto_id' => $producto_id,
                        'cantidad' => $detalle['cantidad'],
                        'precio' => $detalle['precio'],
                        'importe' => $detalle['importe'],
                        'descuento' => $detalle['descuento'],
                        'total' => $detalle['total'],
                    ];
                    $addDet = $this->model->venta_detalle->add($dataDet);
                    if($addDet->response){
                        $det_id = $addDet->result;
                        $seg_log = $this->model->seg_log->add('Agrega detalle de venta', 'venta_detalle', $det_id, 1);
                        if($info_prod->es_kilo == 0){
                            // $stock = $this->model->prod_stock->getStock($sucursal_id, $producto_id)->result;
                            $stock = $this->model->prod_stock->getStock(3, $producto_id)->result;
                            $inicial = $stock->final;
                            if($inicial != 0){
                                if($inicial >= $detalle['cantidad']){
                                    $final = floatval($inicial-$detalle['cantidad']);
                                    $dataStock = [
                                        'usuario_id' => 1,
                                        // 'usuario_id' => $usuario_id,
                                        'sucursal_id' => 3,
                                        // 'sucursal_id' => $sucursal_id,
                                        'producto_id' => $producto_id,
                                        'tipo' => -1,
                                        'inicial' => $inicial,
                                        'cantidad' => $detalle['cantidad'],
                                        'final' => $final,
                                        'fecha' => $fecha,
                                        'origen_tipo' => 3,
                                        'origen_id' => $det_id
                                    ];
                                    $addStock = $this->model->prod_stock->add($dataStock);
                                    if(!$addStock->response){
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
                            $cantidad = ($info_kilo->cantidad * $detalle['cantidad']);
                            $prod_origen = $info_kilo->producto_origen;
                            $stock = $this->model->prod_stock->getStock($sucursal_id, $prod_origen)->result;
                            $inicial = $stock->final;
                            if($inicial != 0){
                                if($inicial >= $cantidad){
                                    $final = floatval($inicial-$cantidad);
                                    $dataStock = [
                                        'usuario_id' => $usuario_id,
                                        'sucursal_id' => $sucursal_id,
                                        'producto_id' => $producto_id,
                                        'tipo' => -1,
                                        'inicial' => $inicial,
                                        'cantidad' => $cantidad,
                                        'final' => $final,
                                        'fecha' => $fecha,
                                        'origen_tipo' => 7,
                                        'origen_id' => $det_id
                                    ];
                                    $addStock = $this->model->prod_stock->add($dataStock);
                                    if(!$addStock->response){
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
                }   

                foreach($pagos as $pago){
                    if($pago['forma_pago'] == 2){
                        $saldo_actual = $this->model->cliente_saldo->getByCli($cliente_id)->result;
                        if(is_object($saldo_actual)){
                            $saldo_a = $saldo_actual->saldo;
                            if($saldo_a >= $pago['monto']){
                                $saldo = floatval($saldo_a-$pago['monto']);
                                $data_saldo = [
                                    'cliente_id' => $cliente_id,
                                    'fecha' => $fecha,
                                    'tipo' => -1,
                                    'cantidad' => $pago['monto'], 
                                    'saldo' =>  $saldo,
                                ]; 
                                $add_saldo = $this->model->cliente_saldo->add($data_saldo);
                                if($add_saldo->response){
                                    $data = ['saldo_favor' => $saldo];
                                    $edit_saldo_cli = $this->model->cliente->edit($data, $cliente_id, 'cliente');
                                    if(!$edit_saldo_cli->response){
                                        $edit_saldo_cli->state = $this->model->transaction->regresaTransaccion(); 
                                        return $res->withJson($edit_saldo_cli->SetResponse(false, "No se editó el saldo a favor del cliente"));
                                    }
                                }else{
                                    $add_saldo->state = $this->model->transaction->regresaTransaccion(); 
                                    return $res->withJson($add_saldo->SetResponse(false, "No se restó el monto del saldo a favor del cliente"));
                                }
                            }else{
                                $this->response = new Response(); 
                                $this->response->state = $this->model->transaction->regresaTransaccion(); 
                                return $res->withJson($this->response->SetResponse(false, "El cliente no tiene suficiente saldo a favor para cubrir el pago, modifique el método de pago"));
                            }
                        }else{
                            $this->response = new Response(); 
                            $this->response->state = $this->model->transaction->regresaTransaccion(); 
                            return $res->withJson($this->response->SetResponse(false, "El cliente no tiene saldo a favor, modifique el método de pago"));
                        }
                    }
                    $dataPago = [
                        'venta_id' => $venta_id,
                        'usuario_id' => 1,
                        // 'usuario_id' => $usuario_id,
                        'fecha' => $fecha, 
                        'monto' => $pago['monto'],
                        'forma_pago' => $pago['forma_pago']
                    ];
                    $addPago = $this->model->venta_pago->add($dataPago);
                    if(!$addPago->response){
                        $addPago->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($addPago);
                    }else{
                        $seg_log = $this->model->seg_log->add('Agrega pago', 'venta_pago', $addPago->result, 1);
                    }
                }
                $seg_log = $this->model->seg_log->add('Nueva venta', 'venta', $venta_id, 1);
                $addVenta->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($addVenta);
            }else{
                $addVenta->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addVenta);
            }
		});

        $this->post('cancel/{id}/{motivo}/{tipo}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $venta = $this->model->venta->get($args['id'])->result;
            $cliente_id = $venta->cliente_id; $fecha = date('Y-m-d H:i:s');
            $detalles = $this->model->venta_detalle->getByVenta($args['id'])->result;
            $pagos = $this->model->venta_pago->getByVenta($args['id'])->result;
            $fecha = date('Y-m-d H:i:s');
            
            $del_venta = $this->model->venta->del($args['id']);
            if($del_venta->response){
                foreach($detalles as $detalle){
                    $prod_id = $detalle->producto_id;
                    $info_prod = $this->model->producto->get($prod_id)->result;
                    if($info_prod->es_kilo == 0){
                        $stock = $this->model->prod_stock->getStock($sucursal_id, $prod_id)->result;
                        $inicial = $stock->final;
                        $cantidad = $detalle->cantidad;
                        $final = floatval($inicial+$cantidad);
                        $dataStock = [
                            'usuario_id' => $usuario_id,
                            'sucursal_id' => $sucursal_id,
                            'producto_id' => $prod_id,
                            'tipo' => 1,
                            'inicial' => $inicial,
                            'cantidad' => $cantidad,
                            'final' => $final,
                            'fecha' => $fecha,
                            'origen_tipo' => 8,
                            'origen_id' => $detalle->id
                        ];
                    }else{
                        $info_kilo = $this->model->producto->getKiloBy($prod_id, 'producto_id')->result;
                        $cantidad = floatval($info_kilo->cantidad * $detalle->cantidad);
                        $prod_origen = $info_kilo->producto_origen;
                        $stock = $this->model->prod_stock->getStock($sucursal_id, $prod_origen)->result;
                        $inicial = $stock->final;
                        $final = floatval($inicial+$cantidad);
                        $dataStock = [
                            'usuario_id' => $usuario_id,
                            'sucursal_id' => $sucursal_id,
                            'producto_id' => $producto_id,
                            'tipo' => 1,
                            'inicial' => $inicial,
                            'cantidad' => $cantidad,
                            'final' => $final,
                            'fecha' => $fecha,
                            'origen_tipo' => 9,
                            'origen_id' => $detalle->id
                        ];
                    }
                    $addStock = $this->model->prod_stock->add($dataStock);
                    if($addStock->response){
                        $del_detalle = $this->model->venta_detalle->del($detalle->id);
                        if(!$del_detalle->response){
                            $del_detalle->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($del_detalle);
                        }
                    }else{
                        $addStock->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($addStock);
                    }
                }

                $monto = 0;
                foreach($pagos as $pago){
                    $monto += $pago->monto;
                    $del_pago = $this->model->venta_pago->del($pago->id);
                }
                if($args['tipo'] == 1){
                    $saldo_actual = $this->model->cliente_saldo->getByCli($cliente_id)->result;
                    if(is_object($saldo_actual)){
                        $saldo_a = $saldo_actual->saldo;
                    }else{
                        $saldo_a = 0;
                    }
                    $saldo = floatval($saldo_a + $monto);
                    $data_saldo = [
                        'cliente_id' => $cliente_id,
                        'fecha' => $fecha,
                        'tipo' => 1,
                        'cantidad' => $monto, 
                        'saldo' =>  $saldo,
                    ]; 
                    $add_saldo = $this->model->cliente_saldo->add($data_saldo);
                    if($add_saldo){
                        $data = [ 'saldo_favor' => $saldo];
                        $edit_cli = $this->model->cliente->edit($data, $cliente_id, 'cliente');
                        if(!$edit_cli->response){
                            $edit_cli->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($edit_cli);
                        }
                    }else{
                        $add_saldo->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($add_saldo);
                    }
                }

                $seg_log = $this->model->seg_log->add('Cancela venta', 'venta', $args['id'], 1);
                $del_venta->state = $this->model->transaction->confirmaTransaccion();
                if($args['tipo'] == 2) $del_venta->devolucion = $monto;
                return $res->withJson($del_venta);
            }else{
                $del_venta->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($del_venta);
            }
        });

	});

?>