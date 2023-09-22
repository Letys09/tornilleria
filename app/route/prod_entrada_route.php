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
                // print_r($entrada);
                // print_r('<hr>');
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
                $marca = $prod->marca != '' ? ', '.$prod->marca : '';
                $descripcion = $prod->descripcion != '' ? ', '.$prod->descripcion : '';
                $codigo = $prod->codigo != '' ? ', '.$prod->codigo : '';
                $detalle->producto = $prod->nombre.$marca.$descripcion.$codigo;
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

            $prodIgual = true; $precioIgual = true; $prodKiloI = true; $kiloDatos = true;
            $infoP = $this->model->producto->get($args['id'])->result;
            $prod = [
                'prod_categoria_id' => $parsedBody['prod_categoria_id'],
                'prod_area_id' => $parsedBody['prod_area_id'],
                'nombre' => $parsedBody['nombre'],
                'descripcion' => $parsedBody['descripcion'],
                'codigo' => $parsedBody['codigo'],
                'marca' => $parsedBody['marca'],
                'minimo' => $parsedBody['minimo'],
                'venta_kilo' => $parsedBody['venta_kilo'],
                'es_kilo' => 0,
                'menudeo' => $parsedBody['menudeo'],
                'medio' => $parsedBody['medio'],
                'mayoreo' => $parsedBody['mayoreo'],
            ];

            foreach($prod as $field => $value) { 
                if($infoP->$field != $value) { 
                    $prodIgual = false; break; 
                } 
            }

            $infoPrecio = $this->model->producto->getPrecios($args['id'])->result;
            $precio = [
                'menudeo' => $parsedBody['precio_menudeo'],
                'medio' => $parsedBody['precio_medio'],
                'mayoreo' => $parsedBody['precio_mayoreo'],
                'distribuidor' => $parsedBody['precio_distribuidor'],
            ];

            foreach($precio as $field => $value){
                if($infoPrecio->$field != $value){
                    $precioIgual = false; break;
                }
            }

            if($parsedBody['venta_kilo'] != $infoP->venta_kilo){
                if($parsedBody['venta_kilo'] == 0){ // era 1 y ahora es 0, debemos poner el prod_kilo y el kilo del producto en 0 
                    $infoKilo = $this->model->producto->getKiloBy($args['id'], 'producto_origen')->result;
                    $delKilo = $this->model->producto->del('prod_kilo', $infoKilo->id);
                    if($delKilo->response){
                        $delProdKilo = $this->model->producto->del('producto', $infoKilo->producto_id);
                        if(!$delProdKilo->response){
                            $delProdKilo->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($delProdKilo->setResponse(false, 'No se dio de baja el kilo del producto (producto)'));
                        }else{
                            $seg_log = $this->model->seg_log->add('Baja venta del producto por kilo', 'producto', $args['id'], 1);
                        }
                    }else{
                        $delProdKilo->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($delProdKilo->setResponse(false, 'No se dio de baja el kilo (prod_kilo)'));
                    }
                }else{ 
                    // era 0 y ahora es 1
                    // verficiar, si ya existía un prod_kilo del producto original poner en status 1 prod_kilo y producto
                    $infoKilo = $this->model->producto->getKiloBy($args['id'], 'producto_origen')->result;
                    if(is_object($infoKilo)){
                        $dataKilo = ['cantidad' => $parsedBody['cant_kilo'], 'precio' => $parsedBody['precio_kilo'], 'status' => 1];
                        $editKilo = $this->model->producto->edit('prod_kilo', 'id', $dataKilo, $infoKilo->id);
                        if($editKilo->response){
                            $dataP = ['status' => 1];
                            $editProdKilo = $this->model->producto->edit('producto', 'id', $dataP, $infoKilo->producto_id);
                            if(!$editProdKilo->response){
                                $editProdKilo->state = $this->model->transaction->regresaTransaccion();
                                return $res->withJson($editProdKilo->setResponse(false, 'No se editó el kilo del producto (producto)'));
                            }else{
                                $seg_log = $this->model->seg_log->add('Alta venta del producto por kilo', 'producto', $args['id'], 1);
                            }
                        }else{
                            $editKilo->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($editKilo->setResponse(false, 'No se editó el kilo (prod_kilo)'));
                        }
                    }else{
                        // agregar producto y prod_kilo
                        $dataKilo = [
                            'prod_categoria_id' => $parsedBody['prod_categoria_id'],
                            'prod_area_id' => $parsedBody['prod_area_id'],
                            'nombre' => 'Kilo de '.$parsedBody['nombre'],
                            'descripcion' => 'Kilo de '.$parsedBody['nombre'],
                            'codigo' => 'PK'.$parsedBody['codigo'],
                            'marca' => $parsedBody['marca'],
                            'es_kilo' => 1,
                        ];
                        $prod_kilo = $this->model->producto->add($dataKilo, 'producto');
                        if($prod_kilo->response){
                            $kilo_id = $prod_kilo->result;
                            $prodKilo = [
                                'producto_id' => $kilo_id,
                                'producto_origen' => $args['id'],
                                'cantidad' => $parsedBody['cant_kilo'],
                                'precio' => $parsedBody['precio_kilo']
                            ];
                            $kilo = $this->model->producto->add($prodKilo, 'prod_kilo');
                            if(!$kilo->response){
                                $kilo->state = $this->model->transaction->confirmaTransaccion();	
                                return $res->withJson($kilo->setResponse(false, 'No se pudo agregar el kilo (prod_kilo)'));
                            }else{
                                $seg_log = $this->model->seg_log->add('Venta del producto por kilo', 'producto', $args['id'], 1);
                            }
                        }else{
                            $prod_kilo->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($prod_kilo->SetResponse(false, 'No se pudo agregar el producto para venta por kilo'));
                        }
                    }
                }
            }else{
                if($infoP->venta_kilo == 1){
                    $prodKilo = $this->model->producto->getKiloBy($args['id'], 'producto_origen')->result;
                    $infoProdKilo = $this->model->producto->get($prodKilo->producto_id)->result;
                    $dataCompartida = [
                        'prod_categoria_id' => $parsedBody['prod_categoria_id'],
                        'prod_area_id' => $parsedBody['prod_area_id'],
                        'marca' => $parsedBody['marca'],
                    ];

                    foreach($dataCompartida as $field => $value){
                        if($infoProdKilo->$field != $value){
                            $prodKiloI = false; break;
                        }
                    }

                    $dataKilo = [
                        'cantidad' => $parsedBody['cant_kilo'],
                        'precio' => $parsedBody['precio_kilo'],
                    ];

                    foreach($dataKilo as $field => $value){
                        if($prodKilo->$field != $value){
                            $kiloDatos = false; break;
                        }
                    }

                    if(!$prodKiloI){
                        $editProdKilo = $this->model->producto->edit('producto', 'id', $dataCompartida, $prodKilo->producto_id);
                        if($editProdKilo->response){
                            $seg_log = $this->model->seg_log->add('Modifica producto', 'producto', $prodKilo->producto_id, 1);
                            if(!$seg_log->response){
                                $seg_log->state = $this->model->transaction->regresaTransaccion();
                                return $res->withJson($seg_log->setResponse(false, 'No se agregó el registro de bitácora'));
                            }
                        }else{
                            $editProdKilo->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($editProdKilo->setResponse(false, 'No se editó la información general del kilo de producto'));
                        }
                    }
                    if(!$kiloDatos){
                        $editKiloP = $this->model->producto->edit('prod_kilo', 'id', $dataKilo, $prodKilo->id);
                        if($editKiloP->response){
                            $seg_log = $this->model->seg_log->add('Modifica kilo', 'prod_kilo', $prodKilo->id, 1);
                            if(!$seg_log->response){
                                $seg_log->state = $this->model->transaction->regresaTransaccion();
                                return $res->withJson($seg_log->setResponse(false, 'No se agregó el registro de bitácora'));
                            }
                        }else{
                            $editKiloP->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($editKiloP->setResponse(false, 'No se editó la información del kilo de producto'));
                        }
                    }
                }
            }

            if(!$prodIgual){
                $editProd = $this->model->producto->edit('producto', 'id', $prod, $args['id']);
                if($editProd->response){
                    $seg_log = $this->model->seg_log->add('Modifica producto', 'producto', $args['id'], 1);
                    if(!$seg_log->response){
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($seg_log->setResponse(false, 'No se agregó el registro de bitácora'));
                    }
                }else{
                    $editProd->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($editProd->setResponse(false, 'No se editó la información general del producto'));
                }
            }
            if(!$precioIgual){
                $editPrecio = $this->model->producto->edit('prod_precio', 'producto_id', $precio, $args['id']);
                if($editPrecio->response){
                        $seg_log = $this->model->seg_log->add('Modifica precios', 'prod_precio', $infoPrecio->id, 1);
                        if(!$seg_log->response){
                            $seg_log->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($seg_log->setResponse(false, 'No se agregó el registro de bitácora'));
                        }
                }else{
                    $editPrecio->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($editPrecio->setResponse(false, 'No se edititaron los precios del producto'));
                }
            }
			
            if($prodIgual && $precioIgual && $prodKiloI && $kiloDatos){
                $editProd = ['code' => 1, 'msg' => 'No existen datos diferentes a los antes registrados'];
                return $res->withJson($editProd);
            }else{
                $editProd = ['response' => true, 'msg' => 'Registro '.$args['id'].' actualizado'];
                $this->model->transaction->confirmaTransaccion();
                return $res->withJson($editProd);
            }
		});

        $this->post('del/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $update = $this->model->sucursal->del($args['id']);
            if($update->response){
                $seg_log = $this->model->seg_log->add('Elimina sucursal', 'sucursal', $args['id'], 1);
                if($seg_log->response){
                    $update->state = $this->model->transaction->confirmaTransaccion();	
                    return $res->withJson($update);
                }else{
                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                }
            }else{
                $update->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($update->SetResponse(false, 'No se pudo eliminar la sucursal'));
            }
        });
	});

?>