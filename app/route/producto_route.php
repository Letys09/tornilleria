<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

    use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
	use PhpOffice\PhpSpreadsheet\Style\Alignment;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
	use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/producto/', function () use ($app){

        $this->get('get/{id}', function ($req, $res, $args) {
			$prod = $this->model->producto->get($args['id'])->result;
            $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $args['id'])->result;
            $prod->stock = is_object($stock) ? $stock->final : 0;
            if($prod->venta_kilo){
                $prod->kilo = $this->model->producto->getKiloBy($prod->id, 'producto_origen')->result;
                $prod->kilo->clave = $this->model->producto->get($prod->kilo->producto_id)->result->clave;
            }else if($prod->es_kilo){
                $prod->kilo = $this->model->producto->getKiloBy($prod->id, 'producto_id')->result;
            }
            return $res->withJson($prod);
		});

        $this->get('getBy/{param}', function($req, $res, $args){
            $prods = $this->model->producto->getBy($args['param'])->result;
            foreach($prods as $prod){
                $medida = $prod->medida != '' ? ', '.$prod->medida : '';
                $descripcion = $prod->descripcion != '' ? ', '.$prod->descripcion : '';
                $clave = $prod->clave != '' ? $prod->clave : '';
                $prod->nombre = $clave.$descripcion.$medida;
            }
            return $res->withJson($prods);
        });

        $this->get('getProdsBy/{param}', function($req, $res, $args){
            $prods = $this->model->producto->getProdsBy($args['param'])->result;
            foreach($prods as $prod){
                $medida = $prod->medida != '' ? ', '.$prod->medida : '';
                $descripcion = $prod->descripcion != '' ? ', '.$prod->descripcion : '';
                $clave = $prod->clave != '' ? $prod->clave : '';
                $prod->nombre = $clave.$descripcion.$medida;
            }
            return $res->withJson($prods);
        });

        $this->get('getAllDataTable', function($req, $res, $args){ 
			$productos = $this->model->producto->getAllDataTable();

			$data = [];
			if(!isset($_SESSION)) { session_start(); }
			foreach($productos->result as $producto) {
                $stockMin = $producto->es_kilo ? 'N/A' : $producto->minimo;
                $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto->id)->result;
                $stock = $producto->es_kilo ? 'N/A' : (is_object($stock) ? $stock->final : 0);
                $minimo = $producto->es_kilo ? 'N/A' : (floatval($stock) <= $producto->minimo ? 'Mínimo' : 'Suficiente');
                $data[] = array(
					"clave" => $producto->clave,
					"descripcion" => $producto->descripcion,
					"medida" => $producto->medida,
					"codigo_barras" => '*'.$producto->clave.'*',
					// "categoria" => $producto->cat,
					// "subcategoria" => $producto->sub,
                    "area" => $producto->area,
					"minimo" => $stockMin,
					"stock" => $stock,
					"enMinimo" => $minimo,
					"data_id" => $producto->id,
                    "es_kilo" => $producto->es_kilo,
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
		});

        $this->get('getAllProdsVenta', function($req, $res, $args){ 
			$productos = $this->model->producto->getAllProdsVenta();

			$data = [];
			if(!isset($_SESSION)) { session_start(); }
			foreach($productos->result as $producto) {
                if($producto->es_kilo){
                    $info_kilo = $this->model->producto->getKiloBy($producto->id, 'producto_id')->result;
                    $cant_kilo = $info_kilo->cantidad;
                    $stock_prod_origen = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $info_kilo->producto_origen)->result;
                    if(is_object($stock_prod_origen)) $stock = number_format($stock_prod_origen->final / $cant_kilo, 3);
                    else $stock = '0.0';
                }else{
                    $info = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto->id)->result;
                    if(is_object($info)) $stock = number_format($info->final,1);
                    else $stock = '0.0';
                }
                $data[] = array(
					"venta" => $producto->venta,
					"clave" => $producto->clave,
					"descripcion" => $producto->descripcion,
					"medida" => $producto->medida,
					"stock" => $stock,
					"data_id" => $producto->id,
                    "es_kilo" => $producto->es_kilo,
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
		});

        $this->get('getAllProds/[{busqueda}]', function($req, $res, $args){
            // $bus = isset($args['bus']) ? $args['bus'] : '';
			$busqueda = isset($_GET['search']['value'])? (strlen($_GET['search']['value'])>0? $_GET['search']['value']: '_') : $args['busqueda'];
            $productos = $this->model->producto->getAllProds($busqueda, $_SESSION['sucursal_id'], 0, 10);
            $data = [];
            foreach($productos->result as $producto) {
                $stock = $producto->es_kilo ? 'N/A' : ($producto->cantidad != null ? $producto->cantidad : 0.00);
                $data[] = array(
					"stock" => $stock,
					"clave" => $producto->clave,
					"descripcion" => $producto->descripcion,
					"medida" => $producto->medida,
					"codigo_barras" => '*'.$producto->clave.'*',
                    "area" => '',
                    "minimo" => '',
                    "enMinimo" => '',
					"data_id" => $producto->id,
                    "es_kilo" => $producto->es_kilo,
				);
			}

            echo json_encode(array(
				// 'draw'=>$_GET['draw'],
                'busqueda' => $productos->busqueda,
				'data'=>$data,
				'recordsTotal'=>intval($productos->total),
				'recordsFiltered'=>$productos->filtered,
			));
			exit(0);
        });

        $this->get('getUnidades', function($req, $res, $args){
            return $res->withJson($this->model->producto->getUnidades()->result);
        });

        $this->get('getRangos/{id}', function($req, $res, $args){
            $rangos = $this->model->producto->getRangos($_SESSION['sucursal_id'], $args['id'])->result;
            if(is_object($rangos)){
                $menudeo = $rangos->menudeo;  $precio_menudeo = $rangos->precio_menudeo;
                $medio = $rangos->medio; $precio_medio = $rangos->precio_medio;
                $mayoreo = $rangos->mayoreo; $precio_mayoreo = $rangos->precio_mayoreo;
                $precio_distribuidor = $rangos->precio_distribuidor;
            }else{
                $menudeo = 0;  $precio_menudeo = 0;
                $medio = 0; $precio_medio = 0;
                $mayoreo = 0; $precio_mayoreo = 0;
                $precio_distribuidor = 0;
            }
            $prod = [
                'menudeo' => $menudeo,
                'precio_menudeo' => $precio_menudeo,
                'medio' => $medio,
                'precio_medio' => $precio_medio,
                'mayoreo' => $mayoreo,
                'precio_mayoreo' => $precio_mayoreo,
                'precio_distribuidor' => $precio_distribuidor,
            ];
            return $res->withJson($prod);
        });
        
        $this->post('editRangos', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $prod_id = $parsedBody['prod_rangos_id'];
            unset($parsedBody['prod_rangos_id']);
            $rangos = true; $precios = true;
            $info = $this->model->producto->getRangos($_SESSION['sucursal_id'], $prod_id)->result;
            $dataRangos = [
                'menudeo' => $parsedBody['menudeo'],
                'medio' => $parsedBody['medio'],
                'mayoreo' => $parsedBody['mayoreo'],
            ];

            foreach($dataRangos as $field => $value) { 
                if($info->$field != $value) { 
                    $rangos = false; break; 
                } 
            }

            $infoP = $this->model->producto->getPrecios($info->prod_precio_id)->result;
            $dataPrecios = [
                'menudeo' => $parsedBody['precio_menudeo'],
                'medio' => $parsedBody['precio_medio'],
                'mayoreo' => $parsedBody['precio_mayoreo'],
                'distribuidor' => $parsedBody['precio_distribuidor'],
            ];

            foreach($dataPrecios as $field => $value) { 
                if($infoP->$field != $value) { 
                    $precios = false; break; 
                } 
            }

            if(!$rangos){
                $edit = $this->model->producto->edit('prod_rango', 'id', $dataRangos, $info->id);
                if($edit->response){
                    $seg_log = $this->model->seg_log->add('Modifica rangos de producto', 'prod_rango', $info->id, 1);
                    if(!$seg_log->response){
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
                    }
                }else{
                    $edit->state = $this->model->transaction->regresaTransaccion();
                }
            }

            if(!$precios){
                $dataPrecios['actualiza'] = date('Y-m-d H:i:s');
                $edit = $this->model->producto->edit('prod_precio', 'id', $dataPrecios, $info->prod_precio_id);
                if($edit->response){
                    $seg_log = $this->model->seg_log->add('Modifica precios de producto', 'prod_precio', $info->prod_precio_id, 1);
                    if(!$seg_log->response){
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
                    }
                }else{
                    $edit->state = $this->model->transaction->regresaTransaccion();
                }
            }

            if($rangos && $precios){
                $edit = ['code' => 1, 'msg' => 'No existen datos diferentes a los antes registrados'];
            }else{
                $edit->state = $this->model->transaction->confirmaTransaccion();
            }
            return $res->withJson($edit);
        });

        $this->get('getPrecioByCant/{prod_id}/{cant}', function($req, $res, $args){
            $cant = $args['cant']; 
            $rangos = $this->model->producto->getRangos($_SESSION['sucursal_id'], $args['prod_id'])->result;
            if(is_object($rangos)){
                switch ($cant) {
                    case $cant <= $rangos->menudeo:
                        $precio = $rangos->precio_menudeo; break;
                    case $cant > $rangos->menudeo && $cant <= $rangos->medio:
                        $precio = $rangos->precio_medio; break;
                    case $cant > $rangos->medio && $cant <= $rangos->mayoreo:
                        $precio = $rangos->precio_mayoreo; break;
                    case $cant > $rangos->mayoreo:
                        $precio = $rangos->precio_distribuidor; break;
                    default: $precio = 0; break;
                }
            }else{
                $precio = 0;
            }
            
            return $res->withJson($precio);
        });

        $this->post('addCorto/', function($req, $res, $args){
            $existe = $this->model->producto->getLast()->result;
            if(is_object($existe)){
                $existe->clave = $existe->id;
                return $res->withJson($existe); 
            }else{
                $this->model->transaction->iniciaTransaccion();
                $data = [
                    'prod_unidad_medida_id' => 1,
                    'prod_categoria_id' => 1,
                    'prod_area_id' => 1,
                    'status' => 2,
                ];
                $add = $this->model->producto->add($data, 'producto');
                if($add->response){
                    $producto_id = $add->result;
                    $precio = ['producto_id' => $producto_id];
                    $addPrecio = $this->model->producto->add($precio, 'prod_precio');
                    if($addPrecio->response){
                        $precio_id = $addPrecio->result;
                        $rangos = ['sucursal_id' => $_SESSION['sucursal_id'], 'producto_id' => $producto_id, 'prod_precio_id' => $precio_id];
                        $addRangos = $this->model->producto->add($rangos, 'prod_rango');
                        if($addRangos->response){
                            $add->clave = $producto_id;
                            $add->state = $this->model->transaction->confirmaTransaccion();
                            return $res->withJson($add);
                        }else{
                            $addRangos->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($addRangos);
                        }
                    }else{
                        $addPrecio->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($addPrecio);
                    }
                }else{
                    $add->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($add);
                }
            }
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
                $sucursales = $this->model->sucursal->getAll()->result;
                foreach($sucursales as $sucursal){              
                    $sucursal_id = $sucursal->id;
                    $dataPrecio = [ 'producto_id' => $prod_origen, ];
                    $addPrecio = $this->model->producto->add($dataPrecio, 'prod_precio');
                    if($addPrecio->response){
                        $dataRango = [ 
                            'sucursal_id' => $sucursal_id, 
                            'producto_id' => $prod_origen, 
                            'prod_precio_id' => $addPrecio->result, 
                        ];
                        $addRango = $this->model->producto->add($dataRango, 'prod_rango');
                    }
                }
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
                                        $seg_log = $this->model->seg_log->add('Agrega kilo', 'producto', $kilo_id, 0);
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
                                    return $res->withJson($producto->SetResponse(false, 'La clave de producto para venta por kilo ya existe'));
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
                    return $res->withJson($producto->SetResponse(false, 'La clave de producto ya existe'));
                }
                else
                    return $res->withJson($producto->SetResponse(false, 'No se pudo agregar el producto'));
            }
		});

		$this->post('edit/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();

            $prodIgual = true; $precioIgual = true; $prodKiloI = true; $kiloDatos = true;
            $infoP = $this->model->producto->get($args['id'])->result;
            $prod = [
                'prod_unidad_medida_id' => $parsedBody['prod_unidad_medida_id'],
                'prod_categoria_id' => $parsedBody['prod_categoria_id'],
                'prod_area_id' => $parsedBody['prod_area_id'],
                'clave' => $parsedBody['clave'],
                'descripcion' => $parsedBody['descripcion'],
                'medida' => $parsedBody['medida'],
                'minimo' => $parsedBody['minimo'],
                'venta_kilo' => $parsedBody['venta_kilo'],
                'es_kilo' => 0,
                'clave_sat' => $parsedBody['clave_sat'],
                // 'status' => 1
            ];

            foreach($prod as $field => $value) { 
                if($infoP->$field != $value) { 
                    $prodIgual = false; break; 
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
                            $seg_log = $this->model->seg_log->add('Baja venta del producto por kilo', 'producto', $args['id'], 0);
                        }
                    }else{
                        $delProdKilo->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($delProdKilo->setResponse(false, 'No se dio de baja el kilo (prod_kilo)'));
                    }
                }else{ 
                    // era 0 y ahora es 1
                    // verificar, si ya existía un prod_kilo del producto original poner en status 1 prod_kilo y producto
                    $infoKilo = $this->model->producto->getKiloBy($args['id'], 'producto_origen')->result;
                    if(is_object($infoKilo)){
                        $dataKilo = ['cantidad' => $parsedBody['cant_kilo'], 'status' => 1];
                        $editKilo = $this->model->producto->edit('prod_kilo', 'id', $dataKilo, $infoKilo->id);
                        if($editKilo->response){
                            $dataP = ['status' => 1];
                            $editProdKilo = $this->model->producto->edit('producto', 'id', $dataP, $infoKilo->producto_id);
                            if(!$editProdKilo->response){
                                $editProdKilo->state = $this->model->transaction->regresaTransaccion();
                                return $res->withJson($editProdKilo->setResponse(false, 'No se editó el kilo del producto (producto)'));
                            }else{
                                $seg_log = $this->model->seg_log->add('Alta venta del producto por kilo', 'producto', $args['id'], 0);
                            }
                        }else{
                            $editKilo->state = $this->model->transaction->regresaTransaccion();
                            return $res->withJson($editKilo->setResponse(false, 'No se editó el kilo (prod_kilo)'));
                        }
                    }else{
                        // agregar producto y prod_kilo
                        $dataKilo = [
                            'prod_unidad_medida_id' => 3,
                            'prod_categoria_id' => $parsedBody['prod_categoria_id'],
                            'prod_area_id' => $parsedBody['prod_area_id'],
                            'clave' => $parsedBody['clave_kilo'],
                            'descripcion' => 'KILO DE '.$parsedBody['descripcion'],
                            'medida' => $parsedBody['medida'],
                            'es_kilo' => 1,
                            'clave_sat' => $parsedBody['clave_sat'],
                        ];
                        $prod_kilo = $this->model->producto->add($dataKilo, 'producto');
                        if($prod_kilo->response){
                            $kilo_id = $prod_kilo->result;
                            $prodKilo = [
                                'producto_id' => $kilo_id,
                                'producto_origen' => $args['id'],
                                'cantidad' => $parsedBody['cant_kilo']
                            ];
                            $kilo = $this->model->producto->add($prodKilo, 'prod_kilo');
                            if(!$kilo->response){
                                $kilo->state = $this->model->transaction->confirmaTransaccion();	
                                return $res->withJson($kilo->setResponse(false, 'No se pudo agregar el kilo (prod_kilo)'));
                            }else{
                                $sucursales = $this->model->sucursal->getAll()->result;
                                foreach($sucursales as $sucursal){              
                                    $sucursal_id = $sucursal->id;
                                    $dataPrecio = [ 'producto_id' => $kilo_id, ];
                                    $addPrecio = $this->model->producto->add($dataPrecio, 'prod_precio');
                                    if($addPrecio->response){
                                        $dataRango = [ 
                                            'sucursal_id' => $sucursal_id, 
                                            'producto_id' => $kilo_id, 
                                            'prod_precio_id' => $addPrecio->result, 
                                        ];
                                        $addRango = $this->model->producto->add($dataRango, 'prod_rango');
                                    }
                                }
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
                        'medida' => $parsedBody['medida'],
                        'clave_sat' => $parsedBody['clave_sat'],
                        'clave' => $parsedBody['clave_kilo']
                    ];

                    foreach($dataCompartida as $field => $value){
                        if($infoProdKilo->$field != $value){
                            $prodKiloI = false; break;
                        }
                    }

                    $dataKilo = [
                        'cantidad' => $parsedBody['cant_kilo'],
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
                            $seg_log = $this->model->seg_log->add('Modifica kilo', 'prod_kilo', $prodKilo->id, 0);
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
                    if($editProd->errors->errorInfo[0] == 23000) {
                        $editProd->error = 23000;
                        return $res->withJson($editProd->SetResponse(false, 'La clave de producto ya existe'));
                    }
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
            $infoP = $this->model->producto->getKiloBy($args['id'], 'producto_origen');
            if($infoP->response){
                $prod_id = $infoP->result->producto_id;
                $prod_kilo_id = $infoP->result->id;
                $update = $this->model->producto->del('producto', $prod_id);
                $update = $this->model->producto->del('prod_kilo', $prod_kilo_id);
            }
            $update = $this->model->producto->del('producto', $args['id']);
            if($update->response){
                $seg_log = $this->model->seg_log->add('Elimina producto', 'producto', $args['id'], 1);
                if($seg_log->response){
                    $update->state = $this->model->transaction->confirmaTransaccion();	
                    return $res->withJson($update);
                }else{
                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                }
            }else{
                $update->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($update->SetResponse(false, 'No se pudo eliminar el producto'));
            }
        });

        $this->get('excel/', function($req, $res, $args){
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);
            $sheet->getColumnDimension('H')->setAutoSize(true);
            $sheet->getColumnDimension('I')->setAutoSize(true);
            $sheet->getColumnDimension('J')->setAutoSize(true);
            $sheet->getColumnDimension('K')->setAutoSize(true);
            $sheet->getColumnDimension('L')->setAutoSize(true);
           
            $sheet->setCellValue("A1", 'Categoría');
            $sheet->setCellValue("B1", 'Subcategoría');
            $sheet->setCellValue("C1", 'Área');
            $sheet->setCellValue("D1", 'Clave');
            $sheet->setCellValue("E1", 'Descripción');
            $sheet->setCellValue("F1", 'Medida');
            $sheet->setCellValue("G1", 'Stock Mínimo');
            $sheet->setCellValue("H1", 'Unidad de Medida (1->Pieza, 2->Metro)');
            $sheet->setCellValue("I1", 'Clave SAT');
            $sheet->setCellValue("J1", 'Venta por kilo (0->No, 1->Si)');
            $sheet->setCellValue("K1", 'Venta por kilo (Cantidad)');
            $sheet->setCellValue("L1", 'Venta por kilo (Clave)');
            $sheet->setTitle('Hoja 1');

            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"Layout_inventario.xlsx\"");
            $writer->save('php://output');
        });

        $this->get('precios/{cat}/{sub}/{area}', function($req, $res, $args){
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
           
            $sheet->setCellValue("A1", 'ID');
            $sheet->setCellValue("B1", 'Descripción');
            $sheet->setCellValue("C1", 'Medida');
            $sheet->setCellValue("D1", 'Menudeo (Hasta)');
            $sheet->setCellValue("E1", 'Precio Menudeo');
            $sheet->setCellValue("F1", 'Medio-Mayoreo (Hasta)');
            $sheet->setCellValue("G1", 'Precio Medio-Mayoreo');
            $sheet->setCellValue("H1", 'Mayoreo (Hasta)');
            $sheet->setCellValue("I1", 'Precio Mayoreo');
            $sheet->setCellValue("J1", 'Precio Distribuidor');
            $sheet->setTitle('Hoja 1');
            $fila = 2;

            $prods = $this->model->producto->getAll($args['cat'], $args['sub'], $args['area'])->result;
            foreach($prods as $prod){
                $rangos = $this->model->producto->getRangos($_SESSION['sucursal_id'], $prod->id)->result;
                if(is_object($rangos)){
                    $id = $rangos->id; $menudeo = $rangos->menudeo;
                    $precio_menudeo = $rangos->precio_menudeo; $medio = $rangos->medio;
                    $precio_medio = $rangos->precio_medio; $mayoreo = $rangos->mayoreo;
                    $precio_mayoreo = $rangos->precio_mayoreo; $precio_distribuidor = $rangos->precio_distribuidor;
                }else{
                    $dataPrecio = [ 'producto_id' => $prod->id ];
                    $addPrecio = $this->model->producto->add($dataPrecio, 'prod_precio');
                    $dataRango = [
                        'sucursal_id' => $_SESSION['sucursal_id'],
                        'producto_id' => $prod->id,
                        'prod_precio_id' => $addPrecio->result,
                    ];
                    $addRangos = $this->model->producto->add($dataRango, 'prod_rango');
                    $id = $addRangos->result; $menudeo = ''; $precio_menudeo = ''; $medio = '';
                    $precio_medio = ''; $mayoreo = ''; $precio_mayoreo = ''; $precio_distribuidor = '';
                }

                $sheet->setCellValue("A$fila", $id);
                $sheet->setCellValue("B$fila", $prod->descripcion);
                $sheet->setCellValue("C$fila", $prod->medida);
                $sheet->setCellValue("D$fila", $menudeo);
                $sheet->setCellValue("E$fila", $precio_menudeo);
                $sheet->setCellValue("F$fila", $medio);
                $sheet->setCellValue("G$fila", $precio_medio);
                $sheet->setCellValue("H$fila", $mayoreo);
                $sheet->setCellValue("I$fila", $precio_mayoreo);
                $sheet->setCellValue("J$fila", $precio_distribuidor);
                $fila++;
            }

            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"Layout_precios.xlsx\"");
            $writer->save('php://output');
        });

        $this->post('cargarInv/', function($request, $response, $arguments){
			$this->response = new Response();
			$uploadedFiles = $request->getUploadedFiles();
			$data = $request->getParsedBody();

			$directory = 'data/uploads/inventario/';
			$uploadedFile = $uploadedFiles['file'];
			
			$extension = strtolower(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
			$basename = date('YmdHis').'u'.$_SESSION['usuario_id'];
			$filename = sprintf('%s.%0.8s', $basename, $extension);

			$uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

			$documento = IOFactory::load($directory.DIRECTORY_SEPARATOR.$filename);
    		$hojaActual = $documento->getSheet(0);
			$fila = 2;

			while(strlen(trim($hojaActual->getCell("A$fila")->getValue())) > 0) {
                $categoria = $hojaActual->getCell("A$fila")->getValue();
                $existe = $this->model->producto->getByName('prod_categoria', 'nombre', $categoria);
                if(is_object($existe->result)){
                    $catId = $existe->result->id;
                }else{
                    $data = ['nombre' => $categoria];
                    $catId = $this->model->producto->add($data, 'prod_categoria')->result;
                }

                $subcat = $hojaActual->getCell("B$fila")->getValue();
                $existeSub = $this->model->producto->getByName('prod_categoria', 'nombre', $subcat);
                if(is_object($existeSub->result) && $existeSub->result->prod_categoria_id == $catId){
                    $subId = $existeSub->result->id;
                }else{
                    $data = ['prod_categoria_id' => $catId, 'nombre' => $subcat];
                    $subId = $this->model->producto->add($data, 'prod_categoria')->result;
                }

                $area = $hojaActual->getCell("C$fila")->getValue();
                $existeArea = $this->model->producto->getByName('prod_area', 'nombre', $area);
                if(is_object($existeArea->result)){
                    $areaId = $existeArea->result->id;
                }else{
                    $data = ['nombre' => $area];
                    $areaId = $this->model->producto->add($data, 'prod_area')->result;
                }
                $data = [  
                    'prod_categoria_id' => $subId,
                    'prod_unidad_medida_id' => $hojaActual->getCell("H$fila")->getValue(),
                    'prod_area_id' => $areaId,
                    'clave' => $hojaActual->getCell("D$fila")->getValue(),
                    'descripcion' => $hojaActual->getCell("E$fila")->getValue(),
                    'medida' => $hojaActual->getCell("F$fila")->getValue(),
                    'minimo' => $hojaActual->getCell("G$fila")->getValue(),
                    'clave_sat' => $hojaActual->getCell("I$fila")->getValue(),
                    'venta_kilo' => $hojaActual->getCell("J$fila")->getValue(),
                ];
                $addProd = $this->model->producto->add($data, 'producto');
                if($addProd->response){
                    $prod_origen = $addProd->result;
                    $sucursales = $this->model->sucursal->getAll()->result;
                    foreach($sucursales as $sucursal){              
                        $sucursal_id = $sucursal->id;
                        $dataPrecio = [ 'producto_id' => $prod_origen, ];
                        $addPrecio = $this->model->producto->add($dataPrecio, 'prod_precio');
                        if($addPrecio->response){
                            $dataRango = [ 
                                'sucursal_id' => $sucursal_id, 
                                'producto_id' => $prod_origen, 
                                'prod_precio_id' => $addPrecio->result, 
                            ];
                            $addRango = $this->model->producto->add($dataRango, 'prod_rango');
                        }
                    }
                    if($hojaActual->getCell("J$fila")->getValue() == 1){
                        $dataKilo = [
                            'prod_categoria_id' => $subId,
                            'prod_unidad_medida_id' => 3,
                            'prod_area_id' => $areaId,
                            'clave' => $hojaActual->getCell("L$fila")->getValue(),
                            'descripcion' => 'KILO DE '.$hojaActual->getCell("E$fila")->getValue(),
                            'medida' => $hojaActual->getCell("F$fila")->getValue(),
                            'es_kilo' => 1,
                            'clave_sat' => $hojaActual->getCell("I$fila")->getValue(),
                        ];
                        $prod_kilo = $this->model->producto->add($dataKilo, 'producto');
                        if($prod_kilo->response){
                            $kilo_id = $prod_kilo->result;
                            $prodKilo = [
                                'producto_id' => $kilo_id,
                                'producto_origen' => $prod_origen,
                                'cantidad' => $hojaActual->getCell("K$fila")->getValue()
                            ];
                            $kilo = $this->model->producto->add($prodKilo, 'prod_kilo');
                            if($kilo->response){
                                $sucursal = $this->model->sucursal->getAll()->result;
                                foreach($sucursal as $suc){
                                    $sucursal_id = $suc->id;
                                    $precioKilo = [ 'producto_id' => $kilo_id, ];
                                    $addPrecioK = $this->model->producto->add($precioKilo, 'prod_precio');
                                    if($addPrecioK->response){
                                        $dataRangoK = [ 
                                            'sucursal_id' => $sucursal_id, 
                                            'producto_id' => $kilo_id, 
                                            'prod_precio_id' => $addPrecioK->result, 
                                        ];
                                        $addRangoK = $this->model->producto->add($dataRangoK, 'prod_rango');
                                    }
                                }
                                $seg_log = $this->model->seg_log->add('Agrega producto', 'producto', $prod_origen, 1);
                                $seg_log = $this->model->seg_log->add('Agrega kilo', 'producto', $kilo_id, 0);
                                if(!$seg_log->response){
                                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                                    return $response->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                                }
                            }else{
                                $kilo->state = $this->model->transaction->regresaTransaccion();	
                                return $response->withJson($kilo->SetResponse(false, 'No se pudo agregar el kilo del producto'));
                            }
                        }else{
                            if($prod_kilo->errors->errorInfo[0] == 23000) {
                                $prod_kilo->error = 23000;
                                $prod_kilo->state = $this->model->transaction->regresaTransaccion();
                                return $response->withJson($prod_kilo->SetResponse(false, 'La clave '.$hojaActual->getCell("L$fila")->getValue().' del producto '.$hojaActual->getCell("E$fila")->getValue().' para venta por kilo en la fila '.$fila.' ya existe.'));
                            }else{
                                $prod_kilo->state = $this->model->transaction->regresaTransaccion();
                                return $response->withJson($prod_kilo->SetResponse(false, 'No se pudo agregar el producto para venta por kilo'));
                            }
                        }
                    }else{
                        $seg_log = $this->model->seg_log->add('Agrega producto', 'producto', $prod_origen, 1);
                        if(!$seg_log->response){
                            $seg_log->state = $this->model->transaction->regresaTransaccion();	
                            return $response->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                        }
                    }
                }else{
                    if($addProd->errors->errorInfo[0] == 23000) {
                        $addProd->error = 23000;
                        $addProd->state = $this->model->transaction->regresaTransaccion();	
                        return $response->withJson($addProd->SetResponse(false, 'La clave '.$hojaActual->getCell("D$fila")->getValue().' del producto '.$hojaActual->getCell("E$fila")->getValue().' en la fila '.$fila.' ya existe.'));
                    }else{
                        $addProd->state = $this->model->transaction->regresaTransaccion();	
                        return $response->withJson($addProd->SetResponse(false, 'No se pudo agregar el producto'));
                    }
                }
				$fila++;
			}
            $addProd->state = $this->model->transaction->confirmaTransaccion();	
            return $response->withJson($addProd);		
		});

        $this->post('cambiarP/', function($request, $response, $arguments){
			$this->response = new Response();
			$uploadedFiles = $request->getUploadedFiles();
			$data = $request->getParsedBody(); $fecha = date('Y-m-d H:i:s');

			$directory = 'data/uploads/precio/';
			$uploadedFile = $uploadedFiles['file'];
			
			$extension = strtolower(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
			$basename = date('YmdHis').'u'.$_SESSION['usuario_id'];
			$filename = sprintf('%s.%0.8s', $basename, $extension);

			$uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

			$documento = IOFactory::load($directory.DIRECTORY_SEPARATOR.$filename);
    		$hojaActual = $documento->getSheet(0);
			$fila = 2;

			while(strlen(trim($hojaActual->getCell("A$fila")->getValue())) > 0) {
                $rango_id = $hojaActual->getCell("A$fila")->getValue();
                $precio_id = $this->model->producto->getByName('prod_rango', 'id', $rango_id)->result->prod_precio_id;
                $rango = [
                    'menudeo' => $hojaActual->getCell("D$fila")->getValue(),
                    'medio' => $hojaActual->getCell("F$fila")->getValue(),
                    'mayoreo' => $hojaActual->getCell("H$fila")->getValue(),   
                    'actualiza' => $fecha,
                ];
                $edit = $this->model->producto->edit('prod_rango', 'id', $rango, $rango_id);
                $precio = [  
                    'menudeo' => $hojaActual->getCell("E$fila")->getValue(),
                    'medio' => $hojaActual->getCell("G$fila")->getValue(),
                    'mayoreo' => $hojaActual->getCell("I$fila")->getValue(),
                    'distribuidor' => $hojaActual->getCell("J$fila")->getValue(),
                    'actualiza' => $fecha,
                ];
                $edit = $this->model->producto->edit('prod_precio', 'id', $precio, $precio_id);
                if(!$edit->response){
                    $edit->state = $this->model->transaction->regresaTransaccion();	
                    return $response->withJson($edit->SetResponse(false, 'No se editaron los precios'));
                }
				$fila++;
                $seg_log = $this->model->seg_log->add('Edita rangos de precios con layout', 'prod_precio', $precio_id, 1);
			}

            $edit->state = $this->model->transaction->confirmaTransaccion();	
            return $response->withJson($edit);		
		});

        $this->get('exportar/minimos', function($req, $res, $args){
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
           
            $sheet->setCellValue("A1", 'Clave');
            $sheet->setCellValue("B1", 'Descripción');
            $sheet->setCellValue("C1", 'Medida');
            $sheet->setCellValue("D1", 'Mínimo requerido');
            $sheet->setCellValue("E1", 'Stock disponible');
            $sheet->setTitle('Hoja 1');
            $fila = 2;

            $productos = $this->model->producto->getProductos()->result;
            foreach($productos as $producto){
                $info = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto->id)->result;
                if(is_object($info)) $stock = $info->final;
                else $stock = 0;
                if($stock < $producto->minimo){
                    $sheet->setCellValue("A$fila", $producto->clave);
                    $sheet->setCellValue("B$fila", $producto->descripcion);
                    $sheet->setCellValue("C$fila", $producto->medida);
                    $sheet->setCellValue("D$fila", $producto->minimo);
                    $sheet->setCellValue("E$fila", $stock);
                    $fila++;
                }
            }

            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"Prods_Stock_Minimo.xlsx\"");
            $writer->save('php://output');
        });

        // Obtener todos los productos app
		$this->post('getAllApp/', function($req, $res, $arg) {
            $parsedBody = $req->getParsedBody();
            $pagina = $parsedBody['pagina'];
            $limite = $parsedBody['limite'];
            $sucursal = $parsedBody['sucursal'];
            $busqueda = isset($parsedBody['busqueda']) ? $parsedBody['busqueda'] : null;
            $resultado = $this->model->producto->getAllApp($pagina, $limite, $busqueda)->result;
            foreach($resultado as $item){
                $resp = $this->model->prod_inventario->getCheckInventario($sucursal, $item->id);
                if($resp->response){
                    $item->check_inventario = $resp->result->check_inventario;
                }else{
                    $item->check_inventario = '1';
                }
            }
			return $res->withJson($resultado);
		});

        // Obtener producto por clave
        $this->post('getProductoByCode/', function ($req, $res, $args) {
            $parsedBody = $req->getParsedBody();
            $clave = $parsedBody['clave'];
            $sucursal = $parsedBody['sucursal'];
			$prod = $this->model->producto->getProductoByCode($clave);
            if($prod->response){
                    $info = $prod->result;
                    $idProd = $prod->result->id;
                    $resp = $this->model->prod_inventario->getCheckInventario($sucursal, $idProd);
                    if($resp->response){
                        $info->check = $resp->result->check_inventario;
                    }else{
                        $info->check = '1';
                    }
                    if($info->es_kilo=="1"){
                        $cantidadPorKilo = $this->model->producto->getKiloBy($idProd, 'producto_id');
                        if($cantidadPorKilo->response){
                            $cantidadKilo = $cantidadPorKilo->result->cantidad;
                            $idProdOrigen = $cantidadPorKilo->result->producto_origen;
                            $info->cantidadPorKilo = $cantidadKilo;
                            $stock = $this->model->prod_stock->getStock($sucursal, $idProdOrigen);
                            if($stock->response){
                                if(is_object($stock->result)){
                                    $info->stock = $stock->result->final;
                                }else{
                                    $info->stock = '0.00';
                                }
                            }else{
                                $info->stock = '0.00';
                            }
                        }else{
                            $info->cantidadPorKilo = "0";
                        }
                    }else{
                        $info->cantidadPorKilo = "0";
                    
                        $stock = $this->model->prod_stock->getStock($sucursal, $idProd);
                        if($stock->response){
                            if(is_object($stock->result)){
                                $info->stock = $stock->result->final;
                            }else{
                                $info->stock = '0.00';
                            }
                        }else{
                            $info->stock = '0.00';
                        }
                    }
                    $prod->result = $info;
            }else{
                // $prod->result->check = "3";
            }
            return $res->withJson($prod);
		});

	});

?>