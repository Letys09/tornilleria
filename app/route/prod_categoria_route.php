<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/prod_categoria/', function () use ($app){

        $this->get('getCat', function($req, $res, $args){
            return $res->withJson($this->model->prod_categoria->getCat()->result);
        });

        $this->get('getSub/{cat}', function($req, $res, $args){
            return $res->withJson($this->model->prod_categoria->getSubC($args['cat'])->result);
        });

        $this->get('getAllSub', function($req, $res, $args){
            return $res->withJson($this->model->prod_categoria->getAllSub()->result);
        });

        $this->post('add/',function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $data = [
                'prod_unidad_medida_id' => $parsedBody['prod_unidad_medida_id'],
                'prod_categoria_id' => $parsedBody['prod_categoria_id'],
                'prod_area_id' => $parsedBody['prod_area_id'],
                'clave' => $parsedBody['clave'],
                'descripcion' => $parsedBody['descripcion'],
                'medida' => $parsedBody['medida'],
                'minimo' => $parsedBody['minimo'],
                'venta_kilo' => $parsedBody['venta_kilo'],
                'clave_sat' => $parsedBody['clave_sat'],
            ];                
            
            $producto = $this->model->producto->add($data, 'producto'); //Producto original
            if($producto->response){
                $prod_origen = $producto->result;
                $dataPrecio = [ 'producto_id' => $prod_origen ];
                $addPrecio = $this->model->producto->add($dataPrecio, 'prod_precio');
                if($addPrecio->response){
                    $dataRango = [
                        'sucursal_id' => $_SESSION['sucursal_id'],
                        'producto_id' => $prod_origen,
                        'prod_precio_id' => $addPrecio->result,
                    ];
                    $addRangos = $this->model->producto->add($dataRango, 'prod_rango');
                    if($addRangos->response){
                        if($data['venta_kilo'] == 1){
                            $dataKilo = [
                                'prod_unidad_medida_id' => 3,
                                'prod_categoria_id' => $data['prod_categoria_id'],
                                'prod_area_id' => $data['prod_area_id'],
                                'descripcion' => 'KILO DE '.$data['nombre'],
                                'clave' => $parsedBody['clave_kilo'],
                                'medida' => $data['medida'],
                                'es_kilo' => 1,
                                'clave_sat' => $parsedBody['clave_sat'],
                            ];
                            $prod_kilo = $this->model->producto->add($dataKilo, 'producto');
                            if($prod_kilo->response){
                                $kilo_id = $prod_kilo->result;
                                $prodKilo = [
                                    'producto_id' => $kilo_id,
                                    'producto_origen' => $prod_origen,
                                    'cantidad' => $parsedBody['cant_kilo']
                                ];
                                $kilo = $this->model->producto->add($prodKilo, 'prod_kilo');
                                if($kilo->response){
                                    $precioKilo = [ 'producto_id' => $kilo_id ];
                                    $addPrecioKilo = $this->model->producto->add($precioKilo, 'prod_precio');
                                    if($addPrecioKilo->response){
                                        $rangoKilo = [
                                            'sucursal_id' => $_SESSION['sucursal_id'],
                                            'producto_id' => $kilo_id,
                                            'prod_precio_id' => $addPrecioKilo->result,
                                        ];
                                        $addRangos = $this->model->producto->add($rangoKilo, 'prod_rango');
                                        $seg_log = $this->model->seg_log->add('Agrega producto', 'producto', $prod_origen, 1);
                                        $seg_log = $this->model->seg_log->add('Agrega kilo', 'producto', $kilo_id, 1);
                                        if($seg_log->response){
                                            $kilo->state = $this->model->transaction->confirmaTransaccion();	
                                            return $res->withJson($kilo);
                                        }else{
                                            $seg_log->state = $this->model->transaction->regresaTransaccion();	
                                            return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                                        }
                                    }
                                }else{
                                    $kilo->state = $this->model->transaction->regresaTransaccion();	
                                    return $res->withJson($kilo->SetResponse(false, 'No se pudo agregar el kilo del producto'));
                                }
                            }else{
                                $prod_kilo->state = $this->model->transaction->regresaTransaccion();
                                if($producto->errors->errorInfo[0] == 23000) {
                                    $producto->error = 23000;
                                    return $res->withJson($producto->SetResponse(false, 'El código de producto para venta por kilo ya existe'));
                                }else 
                                    return $res->withJson($prod_kilo->SetResponse(false, 'No se pudo agregar el producto para venta por kilo'));
                            }
                        }else{
                            $seg_log = $this->model->seg_log->add('Agrega producto', 'producto', $prod_origen, 1);
                            if($seg_log->response){
                                $producto->state = $this->model->transaction->confirmaTransaccion();	
                                return $res->withJson($producto);
                            }else{
                                $seg_log->state = $this->model->transaction->regresaTransaccion();	
                                return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                            }
                        }
                    }else{
                        $addRangos->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($addRangos->SetResponse(false, 'No se pudo agregar el registro de rangos'));
                    }
                }else{
                    $addPrecio->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($addPrecio->SetResponse(false, 'No se pudo agregar la lista de precios'));
                }
            }else{
                $producto->state = $this->model->transaction->regresaTransaccion();
                if($producto->errors->errorInfo[0] == 23000) {
                    $producto->error = 23000;
                    return $res->withJson($producto->SetResponse(false, 'El código de producto ya existe'));
                }
                else
                    return $res->withJson($producto->SetResponse(false, 'No se pudo agregar el producto'));
            }
		});

		$this->post('edit/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();

            $igual = true;
            $info = $this->model->prod_categoria->get($args['id'])->result;
            $cat = [
                'nombre' => $parsedBody['nombre']
            ];

            foreach($cat as $field => $value) { 
                if($info->$field != $value) { 
                    $igual = false; break; 
                } 
            }

            if(!$igual){
                $edit = $this->model->prod_categoria->edit($cat, $args['id']);
                if($edit->response){
                    $seg_log = $this->model->seg_log->add('Modifica categoria', 'prod_categoria', $args['id'], 1);
                    if(!$seg_log->response){
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($seg_log->setResponse(false, 'No se agregó el registro de bitácora'));
                    }
                }else{
                    $edit->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($edit->setResponse(false, 'No se editó el nombre de la categoría'));
                }
            }
			
            if($igual){
                $edit = ['code' => 1, 'msg' => 'No existen datos diferentes a los antes registrados'];
                return $res->withJson($edit);
            }else{
                $edit = ['response' => true, 'msg' => 'Registro '.$args['id'].' actualizado'];
                $this->model->transaction->confirmaTransaccion();
                return $res->withJson($edit);
            }
		});

	});

?>