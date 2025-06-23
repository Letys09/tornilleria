<?php
use App\Lib\Auth,
	App\Lib\Response;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

    date_default_timezone_set('America/Mexico_City');

	$app->group('/venta/', function () use ($app){

        $this->get('get/{id}', function($req, $res, $args){
            $venta_id = $args['id'];
            $venta = $this->model->venta->get($venta_id)->result;
            $venta->detalles = $this->model->venta_detalle->getByVenta($venta_id)->result;
            $venta->pagos = $this->model->venta_pago->getByVenta($venta_id)->result;

            return $res->withJson($venta);
        });

        $this->get('getAllDataTable/{fecha}', function($req, $res, $args){
            $ventas = $this->model->venta->getAllDataTable($args['fecha'])->result;
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
                    "folio" => $venta->folio, 
                    "usuario" => $venta->usuario, 
                    "cliente_id" => $venta->cliente_id,
                    "cliente" => $venta->cliente, 
                    "total" => $venta->total, 
                    "pagado" => number_format($pagado, 2, ".", ","),
                    "pendiente" => number_format($pendiente, 2, ".", ","),
                    "saldo" => $venta->saldo,
                    "en_uso" => $venta->en_uso,
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
                    "folio" => $venta->folio, 
                    "usuario" => $venta->usuario,
                    "cliente_id" => $venta->cliente_id, 
                    "cliente" => $venta->cliente,
                    "total" => $venta->total,
                    "en_uso" => $venta->en_uso,
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
                    "folio" => $venta->folio, 
                    "tipo" => $tipo,
                    "total" => $venta->total,
                );
            }
            echo json_encode(array(
                'data' => $data
            ));

			exit(0);
        });

        $this->get('enUso/{id}', function($req, $res, $args){
            return $res->withJson($this->model->venta->enUso($args['id'])->result);
        });

        $this->post('desbloquear/{id}', function($req, $res, $args){
            $desbloquear = $this->model->venta->desbloquear($args['id']);
            $this->model->seg_log->add('Desbloquea venta', 'venta', $args['id'], 1);
            return $res->withJson($desbloquear);
        });

        $this->post('add/', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $detalles = $parsedBody['detalles']; unset($parsedBody['detalles']);
            $pago = isset($parsedBody['pago']) ? $parsedBody['pago'] : ''; unset($parsedBody['pago']);
            $fecha = date('Y-m-d H:i:s'); $cliente_id = $parsedBody['cliente_id'];
            $status = $parsedBody['tipo'] == 1 ? 1 : 2;
            $info_suc = $this->model->sucursal->get($_SESSION['sucursal_id'])->result;
            $folio_venta = $info_suc->identificador.'-'.date('dmY').'-'.($info_suc->folio_venta+1);
            $dataVenta = [
                'sucursal_id' => $_SESSION['sucursal_id'],
                'cliente_id' => $cliente_id,
                'usuario_id' => $_SESSION['usuario_id'],
                'fecha' => $fecha,
                'folio' => $folio_venta,
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
                        'fecha' => $fecha,
                        'cantidad' => $detalle['cantidad'],
                        'precio' => $detalle['precio'],
                        'importe' => $total,
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
                                    return $res->withJson($this->response->SetResponse(false, "No hay suficiente stock del producto: $producto_id $info_prod->descripcion"));
                                }
                            }else{
                                $this->response = new Response(); 
                                $this->response->state = $this->model->transaction->regresaTransaccion(); 
                                return $res->withJson($this->response->SetResponse(false, "No hay stock disponible del producto: $producto_id $info_prod->descripcion"));
                            }
                        }else{
                            $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                            $cantidad = round($info_kilo->cantidad * $detalle['cantidad']);
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
                        'monto_recibido' => $pago['monto_recibido'],
                        'cambio' => $pago['cambio'],
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
                $data_folio = ['folio_venta' => ($info_suc->folio_venta+1)];
                $edit_folio = $this->model->sucursal->edit($data_folio, $_SESSION['sucursal_id']);
                if($edit_folio->response){
                    $seg_log = $this->model->seg_log->add('Nueva venta', 'venta', $venta_id, 1);
                    $addVenta->state = $this->model->transaction->confirmaTransaccion();
                    return $res->withJson($addVenta);
                }else{
                    $edit_folio->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($edit_folio);
                }
            }else{
                $addVenta->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addVenta);
            }
		});

        $this->post('edit/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $detalles = $parsedBody['detalles'];
            $pago = isset($parsedBody['pago']) ? $parsedBody['pago'] : ''; unset($parsedBody['pago']);
            $fecha = date('Y-m-d H:i:s');
            $venta = [
                'cliente_id' => $parsedBody['cliente_id'],
                'tipo' => $parsedBody['tipo'],
                'descuento' => $parsedBody['descuento'],
                'subtotal' => $parsedBody['subtotal'],
                'total' => $parsedBody['total'],
                'comentarios' => $parsedBody['comentarios'],
                'fecha_actualiza' => $fecha,
                'en_uso' => 0,
            ];
            $edit = $this->model->venta->edit($venta, $args['id']);
            if($edit->response){
                foreach($detalles as $detalle){
                    if($detalle['detalle_id'] == ''){
                        $producto_id = $detalle['producto_id'];
                        $info_prod = $this->model->producto->get($producto_id)->result;
                        $total = floatval($detalle['cantidad'] * $detalle['precio']);
                        $dataDet = [
                            'venta_id' => $args['id'],
                            'producto_id' => $producto_id,
                            'fecha' => $fecha,
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
                                        return $res->withJson($this->response->SetResponse(false, "No hay suficiente stock del producto: $producto_id $info_prod->descripcion"));
                                    }
                                }else{
                                    $this->response = new Response(); 
                                    $this->response->state = $this->model->transaction->regresaTransaccion(); 
                                    return $res->withJson($this->response->SetResponse(false, "No hay stock disponible del producto: $producto_id $info_prod->descripcion"));
                                }
                            }else{
                                $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                                $cantidad = round($info_kilo->cantidad * $detalle['cantidad']);
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
                } 
                if($pago != ''){
                    if($pago['monto'] > 0){
                        $dataPago = [
                            'venta_id' => $args['id'],
                            'usuario_id' => $_SESSION['usuario_id'],
                            'fecha' => $fecha, 
                            'monto' => $pago['monto'],
                            'monto_recibido' => $pago['monto_recibido'],
                            'cambio' => $pago['cambio'],
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
                }
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
                        $cantidad = round($info_kilo->cantidad * $detalle->cantidad);
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

                $seg_log = $this->model->seg_log->add('Devolución Total Venta', 'venta', $args['id'], 1);
                $del_venta->state = $this->model->transaction->confirmaTransaccion();
                $del_venta->devolucion = $monto;
                return $res->withJson($del_venta);
            }else{
                $del_venta->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($del_venta);
            }
        });

        $this->post('devEfectivo', function($req, $res, $args){
            // Se van a cancelar todos los pagos en efectivo y se agregará uno solo con el monto real pagado
            $this->model->transaction->iniciaTransaccion();
            date_default_timezone_set('America/Mexico_City');
            $parsedBody = $req->getParsedBody();
            $detalles = isset($parsedBody['detalles']) ? $parsedBody['detalles'] : array(); unset($parsedBody['detalles']);
            $fecha = date('Y-m-d H:i:s');
            $pagos = $this->model->venta_pago->getByVenta($parsedBody['venta_id'])->result;
            $pago_nuevo = floatval($parsedBody['pagado'] + $parsedBody['total']);
            foreach($pagos as $pago){
                if($pago->forma_pago == 1){
                    $del_pago = $this->model->venta_pago->del($pago->id);
                }
            }
            $data = [
                'venta_id' => $parsedBody['venta_id'],
                'usuario_id' => $_SESSION['usuario_id'],
                'fecha' => $fecha,
                'monto' => $pago_nuevo,
                'monto_recibido' => $pago_nuevo,
                'cambio' => '0.00',
                'forma_pago' => 1,
            ];
            $add_pago = $this->model->venta_pago->add($data);
            if($add_pago->response){
                $venta = [
                    'subtotal' => $parsedBody['subtotal'],
                    'total' => $parsedBody['subtotal'],
                    'comentarios' => $parsedBody['comentarios'],
                    'fecha_actualiza' => $fecha,
                    'en_uso' => 0,
                ];
                $edit_venta = $this->model->venta->edit($venta, $parsedBody['venta_id']);
                if($edit_venta->response){
                    foreach($detalles as $detalle){
                        $producto_id = $detalle['producto_id'];
                        $info_prod = $this->model->producto->get($producto_id)->result;
                        $total = floatval($detalle['cantidad'] * $detalle['precio']);
                        $dataDet = [
                            'venta_id' => $parsedBody['venta_id'],
                            'producto_id' => $producto_id,
                            'fecha' => $fecha,
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
                                        return $res->withJson($this->response->SetResponse(false, "No hay suficiente stock del producto: $producto_id $info_prod->descripcion"));
                                    }
                                }else{
                                    $this->response = new Response(); 
                                    $this->response->state = $this->model->transaction->regresaTransaccion(); 
                                    return $res->withJson($this->response->SetResponse(false, "No hay stock disponible del producto: $producto_id $info_prod->descripcion"));
                                }
                            }else{
                                $info_kilo = $this->model->producto->getKiloBy($producto_id, 'producto_id')->result;
                                $cantidad = round($info_kilo->cantidad * $detalle['cantidad']);
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
                }else{
                    $edit_venta->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($edit_venta);
                }

                $this->model->seg_log->add('Devolución de efectivo', 'venta_pago', $add_pago->result, 0);
                $add_pago->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($add_pago);
            }else{
                $add_pago->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($add_pago);
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

        $this->get('exportar/cambios/{fecha}', function($req, $res, $args){
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            
            $sheet->setCellValue("A1", 'Fecha');
            $sheet->setCellValue("B1", 'Folio Venta');
            $sheet->setCellValue("C1", 'Producto');
            $sheet->setCellValue("D1", 'Cantidad');
            $sheet->setTitle('Hoja 1');
            $fila = 2;

            $cambios = $this->model->venta_detalle->getCambios($args['fecha'])->result;
            $total = COUNT($cambios);
            if($total > 0){
                foreach($cambios as $cambio){
                    $sheet->setCellValue("A$fila", $cambio->fecha.' '.$cambio->hora);
                    $sheet->setCellValue("B$fila", $cambio->identificador.'-'.$cambio->date.'-'.$cambio->id);
                    $sheet->setCellValue("C$fila", '( '.$cambio->clave.' )'.$cambio->descripcion.' '.$cambio->medida);
                    $sheet->setCellValue("D$fila", $cambio->cantidad);
                    $fila++;
                }
            }else{
                $sheet->mergeCells("A2:D2");
                $sheet->setCellValue("A2", 'No se realizaron cambios el día '.$args['fecha']);
            }

            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"Cambios_".$args['fecha'].".xlsx\"");
            $writer->save('php://output');
        });

        $this->post('delete/', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $body = $req->getParsedBody();
            $venta_id = $body['venta_id'];
            $motivo = $body['motivo'];
            $fecha = date('Y-m-d H:i:s');

            $detalles = $this->model->venta_detalle->getByVenta($venta_id)->result;
            $pagos = $this->model->venta_pago->getByVenta($venta_id)->result;
            
            $edit_venta = $this->model->venta->edit(['motivo_cancela' => $motivo, 'usuario_cancela' => $_SESSION['usuario_id']], $venta_id);
            $del_venta = $this->model->venta->del($venta_id);
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
                            'motivo' => $motivo,
                            'origen_tipo' => 16,
                            'origen_tabla' => 'venta_detalle',
                            'origen_id' => $detalle->id
                        ];
                    }else{
                        $info_kilo = $this->model->producto->getKiloBy($prod_id, 'producto_id')->result;
                        $cantidad = round($info_kilo->cantidad * $detalle->cantidad);
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
                            'origen_tipo' => 16,
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

                $seg_log = $this->model->seg_log->add('Elimina venta a crédito', 'venta', $venta_id, 1);
                $del_venta->state = $this->model->transaction->confirmaTransaccion();
                $del_venta->devolucion = $monto;
                return $res->withJson($del_venta);
            }else{
                $del_venta->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($del_venta);
            }
        });

	});

?>