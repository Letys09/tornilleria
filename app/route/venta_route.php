<?php
use App\Lib\Auth,
	App\Lib\Response;

    date_default_timezone_set('America/Mexico_City');

	$app->group('/venta/', function () use ($app){

        $this->get('get/{id}', function($req, $res, $args){
            $venta_id = $args['id'];
            $venta = $this->model->venta->get($venta_id)->result;
            $venta->detalles = $this->model->venta_detalle->getByVenta($venta_id)->result;
            $venta->pagos = $this->model->venta_pago->getByVenta($venta_id)->result;

            return $res->withJson($venta);
        });

        $this->get('getAllDataTable', function($req, $res, $args){
            $ventas = $this->model->venta->getAllDataTable()->result;
            $data = [];
            foreach($ventas as $venta){
                $fecha = explode('-', $venta->date);
                $fecha = $fecha[0].$fecha[1].$fecha[2];
                $pagado = $this->model->venta_pago->getTotal($venta->id)->result->total;
                $pagado = $pagado != null ? $pagado : 0.00;
                $pendiente = floatval($venta->total-$pagado);
                $data[] = array(
                    "id" => $venta->id,
                    "fecha" => $venta->date,
                    "hora" => $venta->hora,
                    "folio" => $venta->identificador.'-'.$fecha.'-'.$venta->id, 
                    "usuario" => $venta->usuario, 
                    "cliente_id" => $venta->cliente_id,
                    "cliente" => $venta->cliente, 
                    "total" => $venta->total, 
                    "pagado" => number_format($pagado, 2, ".", ","),
                    "pendiente" => number_format($pendiente, 2, ".", ","),
                    "saldo" => $venta->saldo,
                );
            }
            echo json_encode(array(
                'data' => $data
            ));

			exit(0);
        });

        $this->get('getAllByDay/{dia}', function($req, $res, $args){
            $ventas = $this->model->venta->getAllByDay($args['dia']);
            $data = [];
            foreach($ventas->result as $venta){
                $fecha = explode('-', $venta->date);
                $fecha = $fecha[0].$fecha[1].$fecha[2];
                $data[] = array(
                    "id" => $venta->id,
                    "fecha" => $venta->date,
                    "hora" => $venta->hora,
                    "folio" => $venta->identificador.'-'.$fecha.'-'.$venta->id, 
                    "usuario" => $venta->usuario,
                    "cliente_id" => $venta->cliente_id, 
                    "cliente" => $venta->cliente,
                    "total" => $venta->total,
                );
            }
            echo json_encode(array(
                'data' => $data
            ));

			exit(0);
        });

        $this->get('getAll/{desde}/{hasta}', function($req, $res, $args){
            $ventas = $this->model->venta->getAll($args['desde'], $args['hasta']);
            $data = [];
            foreach($ventas->result as $venta){
                $finaliza = $venta->date_fin != '00-00-0000' ? $venta->date_fin : '';  
                $fecha = explode('-', $venta->date);
                $fecha = $fecha[0].$fecha[1].$fecha[2];
                $tipo = $venta->tipo == 0 ? 'Cancelada' : ($venta->tipo == 1 ? 'Contado' : 'Crédito');
                $data[] = array(
                    "id" => $venta->id,
                    "fecha_inicia" => $venta->date,
                    "hora" => $venta->hora,
                    "fecha_fin" => $finaliza,
                    "usuario" => $venta->usuario, 
                    "cliente" => $venta->cliente,
                    "folio" => $venta->identificador.'-'.$fecha.'-'.$venta->id, 
                    "tipo" => $tipo,
                    "total" => $venta->total,
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
            $detalles = $parsedBody['detalles']; unset($parsedBody['detalles']);
            $pago = isset($parsedBody['pago']) ? $parsedBody['pago'] : ''; unset($parsedBody['pago']);
            $fecha = date('Y-m-d H:i:s'); $cliente_id = $parsedBody['cliente_id'];
            $status = $parsedBody['tipo'] == 1 ? 1 : 2;
            $dataVenta = [
                'sucursal_id' => $_SESSION['sucursal_id'],
                'cliente_id' => $cliente_id,
                'usuario_id' => $_SESSION['usuario_id'],
                'fecha' => $fecha,
                'tipo' => $parsedBody['tipo'],
                'subtotal' => $parsedBody['subtotal'],
                'descuento' => $parsedBody['descuento'],
                'total' => $parsedBody['total'],
                'comentarios' => $parsedBody['comentarios'],
                'fecha_finaliza' => $status == 1 ? $fecha : '',
                'usuario_finaliza' => $status == 1 ? $_SESSION['usuario_id'] : '',
                'status' => $status
            ];
            $addVenta = $this->model->venta->add($dataVenta);
            if($addVenta->response){
                $venta_id = $addVenta->result;
                foreach($detalles as $detalle){
                    $producto_id = $detalle['producto_id'];
                    $info_prod = $this->model->producto->get($producto_id)->result;
                    $total = floatval($detalle['cantidad'] * $detalle['precio']);
                    $dataDet = [
                        'venta_id' => $venta_id,
                        'producto_id' => $producto_id,
                        'cantidad' => $detalle['cantidad'],
                        'precio' => $detalle['precio'],
                        'total' => $total,
                    ];
                    $addDet = $this->model->venta_detalle->add($dataDet);
                    if($addDet->response){
                        $data_prod = ['venta' => $info_prod->venta+1];
                        $this->model->producto->edit('producto', 'id', $data_prod, $info_prod->id);
                        $det_id = $addDet->result;
                        $seg_log = $this->model->seg_log->add('Agrega detalle de venta', 'venta_detalle', $det_id, 1);
                        if($info_prod->es_kilo == 0){
                            $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto_id)->result;
                            $inicial = $stock->final;
                            if($inicial != 0){
                                if($inicial >= $detalle['cantidad']){
                                    $final = floatval($inicial-$detalle['cantidad']);
                                    $dataStock = [
                                        'usuario_id' => $_SESSION['usuario_id'],
                                        'sucursal_id' => $_SESSION['sucursal_id'],
                                        'producto_id' => $producto_id,
                                        'tipo' => -1,
                                        'inicial' => $inicial,
                                        'cantidad' => $detalle['cantidad'],
                                        'final' => $final,
                                        'fecha' => $fecha,
                                        'origen_tipo' => 3,
                                        'origen_tabla' => 'venta_detalle',
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
                            $info_prod_origen = $this->model->producto->get($prod_origen)->result;
                            $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result;
                            $inicial = $stock->final;
                            if($inicial != 0){
                                if($inicial >= $cantidad){
                                    $final = floatval($inicial-$cantidad);
                                    $dataStock = [
                                        'usuario_id' => $_SESSION['usuario_id'],
                                        'sucursal_id' => $_SESSION['sucursal_id'],
                                        'producto_id' => $prod_origen,
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
                                    if(!$addStock->response){
                                        $addStock->state = $this->model->transaction->regresaTransaccion();
                                        return $res->withJson($this->response->SetResponse(false, "No se agregó el registro del stock $addStock"));
                                    }
                                }else{
                                    $this->response = new Response(); 
                                    $this->response->state = $this->model->transaction->regresaTransaccion(); 
                                    return $res->withJson($this->response->SetResponse(false, 'No hay suficiente stock del producto: '.$info_prod_origen->descripcion.' para realizar la venta de '.$detalle['cantidad'].' kilo(s)'));
                                }
                            }else{
                                $this->response = new Response(); 
                                $this->response->state = $this->model->transaction->regresaTransaccion(); 
                                return $res->withJson($this->response->SetResponse(false, 'No hay stock disponible del producto: '.$info_prod_origen->descripcion.' para realizar la venta de '.$detalle['cantidad'].' kilo(s)'));
                            }                            
                        }
                    }else{
                        $addDet->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($addDet);
                    }
                }   

                if($pago != ''){
                    $dataPago = [
                        'venta_id' => $venta_id,
                        'usuario_id' => $_SESSION['usuario_id'],
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

        $this->post('edit/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $venta = [
                'subtotal' => $parsedBody['subtotal'],
                'total' => $parsedBody['total'],
                'comentarios' => $parsedBody['comentarios'],
            ];
            $edit = $this->model->venta->edit($venta, $args['id']);
            if($edit->response){
                $this->model->seg_log->add('Edita venta', 'venta', $args['id'], 1);
                $edit->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($edit);
            }else{
                $edit->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($edit);
            }
        });

        $this->post('devolucion/{id}', function($req, $res, $args){
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
                        $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_id)->result;
                        $inicial = $stock->final;
                        $cantidad = $detalle->cantidad;
                        $final = floatval($inicial+$cantidad);
                        $dataStock = [
                            'usuario_id' => $_SESSION['usuario_id'],
                            'sucursal_id' => $_SESSION['sucursal_id'],
                            'producto_id' => $prod_id,
                            'tipo' => 1,
                            'inicial' => $inicial,
                            'cantidad' => $cantidad,
                            'final' => $final,
                            'fecha' => $fecha,
                            'origen_tipo' => 8,
                            'origen_tabla' => 'venta_detalle',
                            'origen_id' => $detalle->id
                        ];
                    }else{
                        $info_kilo = $this->model->producto->getKiloBy($prod_id, 'producto_id')->result;
                        $cantidad = floatval($info_kilo->cantidad * $detalle->cantidad);
                        $prod_origen = $info_kilo->producto_origen;
                        $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result;
                        $inicial = $stock->final;
                        $final = floatval($inicial+$cantidad);
                        $dataStock = [
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
                // if($args['tipo'] == 1){
                //     $saldo_actual = $this->model->cliente_saldo->getByCli($cliente_id)->result;
                //     if(is_object($saldo_actual)){
                //         $saldo_a = $saldo_actual->saldo;
                //     }else{
                //         $saldo_a = 0;
                //     }
                //     $saldo = floatval($saldo_a + $monto);
                //     $data_saldo = [
                //         'cliente_id' => $cliente_id,
                //         'fecha' => $fecha,
                //         'tipo' => 1,
                //         'cantidad' => $monto, 
                //         'saldo' =>  $saldo,
                //     ]; 
                //     $add_saldo = $this->model->cliente_saldo->add($data_saldo);
                //     if($add_saldo){
                //         $data = [ 'saldo_favor' => $saldo];
                //         $edit_cli = $this->model->cliente->edit($data, $cliente_id, 'cliente');
                //         if(!$edit_cli->response){
                //             $edit_cli->state = $this->model->transaction->regresaTransaccion();
                //             return $res->withJson($edit_cli);
                //         }
                //     }else{
                //         $add_saldo->state = $this->model->transaction->regresaTransaccion();
                //         return $res->withJson($add_saldo);
                //     }
                // }

                $seg_log = $this->model->seg_log->add('Devolución Total Venta', 'venta', $args['id'], 1);
                $del_venta->state = $this->model->transaction->confirmaTransaccion();
                $del_venta->devolucion = $monto;
                return $res->withJson($del_venta);
            }else{
                $del_venta->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($del_venta);
            }
        });

        $this->post('finalizar/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $fecha = date('Y-m-d H:i:s');
            $data_fin = [
                'status' => 1,
                'fecha_finaliza' => $fecha,
                'usuario_finaliza' => $_SESSION['usuario_id']
            ];
            $finaliza = $this->model->venta->edit($data_fin, $args['id']);
            if($finaliza->response){
                $seg_log = $this->model->seg_log->add('Finaliza venta', 'venta', $args['id'], 1);
                $finaliza->state = $this->model->transaction->confirmaTransaccion();
            }else{
                $finaliza->state = $this->model->transaction->regresaTransaccion();
            }
            return $res->withJson($finaliza);
        });

	});

?>