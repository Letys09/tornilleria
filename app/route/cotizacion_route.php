<?php
use App\Lib\Auth,
	App\Lib\Response;

    date_default_timezone_set('America/Mexico_City');

	$app->group('/cotizacion/', function () use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('get/{id}', function($req, $res, $args){
            $cotizacion_id = $args['id'];
            $cotizacion = $this->model->cotizacion->get($cotizacion_id)->result;
            $cotizacion->detalles = $this->model->coti_detalle->getByCot($cotizacion_id)->result;

            return $res->withJson($venta);
        });

        $this->get('getAllDataTable', function($req, $res, $args){
            $cotizaciones = $this->model->cotizacion->getAllDataTable()->result;
            $data = [];
            foreach($cotizaciones as $cotizacion){
                $fecha = explode('-', $cotizacion->date);
                $fecha = $fecha[0].$fecha[1].$fecha[2];
                $data[] = array(
                    "id" => $cotizacion->id,
                    "fecha" => $cotizacion->date,
                    "hora" => $cotizacion->hora,
                    "folio" => $cotizacion->identificador.'-'.$fecha.'-'.$cotizacion->id, 
                    "usuario" => $cotizacion->usuario, 
                    "cliente_id" => $cotizacion->cliente_id,
                    "cliente" => $cotizacion->cliente, 
                    "total" => $cotizacion->total, 
                    "en_uso" => $cotizacion->en_uso, 
                );
            }
            echo json_encode(array(
                'data' => $data
            ));

			exit(0);
        });

        $this->get('enUso/{id}', function($req, $res, $args){
            return $res->withJson($this->model->cotizacion->enUso($args['id'])->result);
        });

        $this->post('desbloquear/{id}', function($req, $res, $args){
            $desbloquear = $this->model->cotizacion->desbloquear($args['id']);
            $this->model->seg_log->add('Desbloquea cotización', 'cotizacion', $args['id'], 1);
            return $res->withJson($desbloquear);
        });

        $this->post('add/', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $detalles = $parsedBody['detalles']; unset($parsedBody['detalles']);
            $fecha = date('Y-m-d H:i:s'); $vigencia = date('Y-m-d'); $cliente_id = $parsedBody['cliente_id'];
            $dataCoti = [
                'sucursal_id' => $_SESSION['sucursal_id'],
                'usuario_id' => $_SESSION['usuario_id'],
                'cliente_id' => $cliente_id,
                'fecha' => $fecha,
                'importe' => $parsedBody['total'],
                'subtotal' => $parsedBody['total'],
                'total' => $parsedBody['total'],
                'comentarios' => $parsedBody['comentarios'],
                'vigencia' => $vigencia
            ];
            $addCoti = $this->model->cotizacion->add($dataCoti);
            if($addCoti->response){
                $cotizacion_id = $addCoti->result;
                foreach($detalles as $detalle){
                    $producto_id = $detalle['producto_id'];
                    $info_prod = $this->model->producto->get($producto_id)->result;
                    $total = floatval($detalle['cantidad'] * $detalle['precio']);
                    $dataDet = [
                        'cotizacion_id' => $cotizacion_id,
                        'producto_id' => $producto_id,
                        'cantidad' => $detalle['cantidad'],
                        'precio' => $detalle['precio'],
                        'total' => $total,
                    ];
                    $addDet = $this->model->coti_detalle->add($dataDet);
                    if($addDet->response){
                        $det_id = $addDet->result;
                        $seg_log = $this->model->seg_log->add('Agrega detalle de cotización', 'coti_detalle', $det_id, 0);
                    }else{
                        $addDet->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($addDet);
                    }
                }   

                $seg_log = $this->model->seg_log->add('Nueva cotización', 'cotización', $cotizacion_id, 1);
                $addCoti->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($addCoti);
            }else{
                $addCoti->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addCoti);
            }
		});

        $this->post('edit/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $detalles = $parsedBody['detalles'];
            unset($parsedBody['detalles']);
            $fecha = date('Y-m-d H:i:s');
            $cotizacion = [
                'importe' => $parsedBody['total'],
                'subtotal' => $parsedBody['total'],
                'total' => $parsedBody['total'],
                'comentarios' => $parsedBody['comentarios'],
                'fecha_actualiza' => $fecha,
                'en_uso' => 0,
            ];
            $edit = $this->model->cotizacion->edit($cotizacion, $args['id']);
            if($edit->response){
                $this->model->seg_log->add('Edita cotización', 'cotizacion', $args['id'], 1);
                $edit->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($edit);
            }else{
                $edit->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($edit);
            }
        });

        $this->post('cancelar/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $coti_id = $args['id'];
            $detalles = $this->model->coti_detalle->getByCot($coti_id)->result;
            foreach($detalles as $detalle){
                $det_id = $detalle->id;
                $del_det = $this->model->coti_detalle->del($det_id);
                if(!$del_det->response){
                    $del_det->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($del_det);
                }
            }
            $del = $this->model->cotizacion->del($coti_id);
            if($del->response){
                $this->model->seg_log->add('Cancela cotización', 'cotizacion', $coti_id, 1);
                $del->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($del);
            }else{
                $del->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($del);
            }
        });

        $this->post('convertirVenta/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $cotizacion_id = $args['id']; $fecha = date('Y-m-d H:i:s');
            $cotizacion = $this->model->cotizacion->get($cotizacion_id)->result;
            $detalles_cot = $this->model->coti_detalle->getByCot($cotizacion_id)->result;
            $data_venta = [
                'sucursal_id' => $cotizacion->sucursal_id,
                'cliente_id' => $cotizacion->cliente_id,
                'usuario_id' => $_SESSION['usuario_id'],
                'fecha' => $fecha,
                'tipo' => 1,
                'subtotal' => $cotizacion->subtotal,
                'total' => $cotizacion->total,
                'total' => $cotizacion->total,
                'fecha_actualiza' => $fecha,
            ];
            $add_venta = $this->model->venta->add($data_venta);
            if($add_venta->response){
                $venta_id = $add_venta->result;
                foreach($detalles_cot as $detalle){
                    $producto_id = $detalle->producto_id;
                    $info_prod = $this->model->producto->get($producto_id)->result;
                    $data_detalle = [
                        'venta_id' => $venta_id,
                        'producto_id' => $producto_id,
                        'fecha' => $fecha,
                        'cantidad' => $detalle->cantidad,
                        'precio' => $detalle->precio,
                        'importe' => $detalle->importe,
                        'total' => $detalle->total,
                    ];
                    $add_detalle = $this->model->venta_detalle->add($data_detalle);
                    if($add_detalle->response){
                        $data_prod = ['venta' => $info_prod->venta+1];
                        $this->model->producto->edit('producto', 'id', $data_prod, $producto_id);
                        $det_id = $add_detalle->result;
                        $seg_log = $this->model->seg_log->add('Agrega detalle de cotización a venta', 'venta_detalle', $det_id, 1);
                        if($info_prod->es_kilo == 0){
                            $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto_id)->result;
                            $inicial = $stock->final;
                            if($inicial != 0){
                                if($inicial >= $detalle->cantidad){
                                    $final = floatval($inicial-$detalle->cantidad);
                                    $dataStock = [
                                        'usuario_id' => $_SESSION['usuario_id'],
                                        'sucursal_id' => $_SESSION['sucursal_id'],
                                        'producto_id' => $producto_id,
                                        'tipo' => -1,
                                        'inicial' => $inicial,
                                        'cantidad' => $detalle->cantidad,
                                        'final' => $final,
                                        'fecha' => $fecha,
                                        'origen_tipo' => 13,
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
                                    return $res->withJson($this->response->SetResponse(false, "No hay suficiente stock del producto: ($info_prod->clave) $info_prod->descripcion"));
                                }
                            }else{
                                $this->response = new Response(); 
                                $this->response->state = $this->model->transaction->regresaTransaccion(); 
                                return $res->withJson($this->response->SetResponse(false, "No hay stock disponible del producto: ($info_prod->clave) $info_prod->descripcion"));
                            }
                        }else{
                            $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                            $cantidad = ($info_kilo->cantidad * $detalle->cantidad);
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
                                        'origen_tipo' => 14,
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
                                    return $res->withJson($this->response->SetResponse(false, 'No hay suficiente stock del producto: ('.$info_prod_origen->clave.') '.$info_prod_origen->descripcion.' para realizar la venta de '.$detalle->cantidad.' kilo(s)'));
                                }
                            }else{
                                $this->response = new Response(); 
                                $this->response->state = $this->model->transaction->regresaTransaccion(); 
                                return $res->withJson($this->response->SetResponse(false, 'No hay stock disponible del producto: ('.$info_prod_origen->clave.') '.$info_prod_origen->descripcion.' para realizar la venta de '.$detalle->cantidad.' kilo(s)'));
                            }                            
                        }
                    }else{
                        $add_detalle->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($add_detalle);
                    }
                }
                $data_cot = ['venta_id' => $venta_id, 'status' => 2];
                $edit_cot = $this->model->cotizacion->edit($data_cot, $cotizacion_id);
                $this->model->seg_log->add('Venta de cotización', 'venta', $venta_id, 1);
                $add_venta->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($add_venta);
            }else{
                $add_venta->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($add_venta);
            }
        }); 

	});

?>