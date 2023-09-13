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
            $prod->precios = $this->model->producto->getPrecios($prod->id)->result;
            if($prod->venta_kilo){
                $prod->kilo = $this->model->producto->getKiloBy($prod->id, 'producto_origen')->result;
            }else if($prod->es_kilo){
                $prod->kilo = $this->model->producto->getKiloBy($prod->id, 'producto_id')->result;
            }
            return $res->withJson($prod);
		});

        $this->get('getBy/{param}', function($req, $res, $args){
            $prods = $this->model->producto->getBy($args['param'])->result;
            foreach($prods as $prod){
                $prod->nombre = $prod->nombre.', '.$prod->marca.', '.$prod->descripcion.', '.$prod->codigo;
            }
            return $res->withJson($prods);
        });

        $this->get('getStockSuc/{id}', function($req, $res, $args){
            $sucursales = $this->model->sucursal->getAll()->result;
            foreach($sucursales as $sucursal){
                $prod_stock = $this->model->prod_stock->getStock($sucursal->id, $args['id'])->result;
                if(is_object($prod_stock)){
                    $sucursal->stock = $prod_stock->final;
                    $sucursal->date = $prod_stock->date;
                }else{
                    $sucursales = ['msg' => 'No hay entradas registradas del producto en otra sucursal'];
                }
            }
            return $res->withJson($sucursales);
        });

        $this->get('getAllDataTable', function($req, $res, $args){
			$productos = $this->model->producto->getAllDataTable();

			$data = [];
			if(!isset($_SESSION)) { session_start(); }
			foreach($productos->result as $producto) {
                $stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $producto->id)->result;
                $stock = is_object($stock) ? $stock->final : 0;
                $data[] = array(
					"codigo" => $producto->codigo,
					"categoria" => $producto->cat,
					"subcategoria" => $producto->sub,
					"stock" => $stock,
					"nombre" => $producto->nombre,
					"marca" => $producto->marca,
					"data_id" => $producto->id,
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
		});

        $this->get('getRangos/{id}', function($req, $res, $args){
            $rangos = $this->model->producto->get($args['id'])->result;
            $precios = $this->model->producto->getPrecios($args['id'])->result;
            $prod = [
                'menudeo' => $rangos->menudeo,
                'precio_menudeo' => $precios->menudeo,
                'medio' => $rangos->medio,
                'precio_medio' => $precios->medio,
                'mayoreo' => $rangos->mayoreo,
                'precio_mayoreo' => $precios->mayoreo,
                'precio_distribuidor' => $precios->distribuidor,
            ];
            return $res->withJson($prod);
        });

		$this->post('add/',function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $data = [
                'prod_categoria_id' => $parsedBody['prod_categoria_id'],
                'prod_area_id' => $parsedBody['prod_area_id'],
                'nombre' => $parsedBody['nombre'],
                'descripcion' => $parsedBody['descripcion'],
                'codigo' => $parsedBody['codigo'],
                'marca' => $parsedBody['marca'],
                'minimo' => $parsedBody['minimo'],
                'maximo' => $parsedBody['maximo'],
                'venta_kilo' => $parsedBody['venta_kilo'],
                'menudeo' => $parsedBody['menudeo'],
                'medio' => $parsedBody['medio'],
                'mayoreo' => $parsedBody['mayoreo'],
            ];                
            
            $producto = $this->model->producto->add($data, 'producto'); //Producto original
            if($producto->response){
                $prod_origen = $producto->result;
                $dataPrecio = [
                    'producto_id' => $prod_origen,
                    'menudeo' => $parsedBody['precio_menudeo'],
                    'medio' => $parsedBody['precio_medio'],
                    'mayoreo' => $parsedBody['precio_mayoreo'],
                    'distribuidor' => $parsedBody['precio_distribuidor'],
                ];
                $addPrecio = $this->model->producto->add($dataPrecio, 'prod_precio');
                if($addPrecio->response){
                    if($data['venta_kilo'] == 1){
                        $dataKilo = [
                            'prod_categoria_id' => $data['prod_categoria_id'],
                            'prod_area_id' => $data['prod_area_id'],
                            'nombre' => 'Kilo de '.$data['nombre'],
                            'descripcion' => 'Kilo de '.$data['nombre'],
                            'codigo' => 'PK'.$data['codigo'],
                            'marca' => $data['marca'],
                            'es_kilo' => 1,
                        ];
                        $prod_kilo = $this->model->producto->add($dataKilo, 'producto');
                        if($prod_kilo->response){
                            $kilo_id = $prod_kilo->result;
                            $prodKilo = [
                                'producto_id' => $kilo_id,
                                'producto_origen' => $prod_origen,
                                'cantidad' => $parsedBody['cant_kilo'],
                                'precio' => $parsedBody['precio_kilo']
                            ];
                            $kilo = $this->model->producto->add($prodKilo, 'prod_kilo');
                            if($kilo->response){
                                $seg_log = $this->model->seg_log->add('Agrega producto', 'producto', $prod_origen, 1);
                                $seg_log = $this->model->seg_log->add('Agrega kilo', 'producto', $kilo_id, 1);
                                if($seg_log->response){
                                    $kilo->state = $this->model->transaction->confirmaTransaccion();	
                                    return $res->withJson($kilo);
                                }else{
                                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                                    return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                                }
                            }else{
                                $kilo->state = $this->model->transaction->regresaTransaccion();	
                                return $res->withJson($kilo->SetResponse(false, 'No se pudo agregar el kilo del producto'));
                            }
                        }else{
                            $prod_kilo->state = $this->model->transaction->regresaTransaccion();
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

        $this->post('baja/', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $usuario = $_SESSION['usuario_id']; $sucursal = $_SESSION['sucursal_id']; $producto_id = $parsedBody['prod_id_baja']; $fecha = date('Y-m-d H:i:s');
            $dataAjuste = [
                'producto_id' => $producto_id,
                'usuario_id' => $usuario,
                'fecha' => $fecha,
                'tipo' => 2,
                'cantidad' => $parsedBody['cantidad'],
                'comentarios' => $parsedBody['comentarios']
            ];
            $addAjuste = $this->model->prod_baja->add($dataAjuste);
            if($addAjuste->response){
                $inicial = $this->model->prod_stock->getStock($sucursal, $producto_id, 1)->result->final;
                $dataStock = [
                    'usuario_id' => $usuario,
                    'sucursal_id' => $sucursal,
                    'producto_id' => $producto_id,
                    'tipo' => -1,
                    'inicial' => $inicial,
                    'cantidad' => $parsedBody['cantidad'],
                    'final' => intval($inicial - $parsedBody['cantidad']),
                    'fecha' => $fecha,
                    'origen_tipo' => 2,
                    'origen_id' => $addAjuste->result
                ];
                $addStock = $this->model->prod_stock->add($dataStock);
                if($addStock->response){
                    $seg_log = $this->model->seg_log->add('Baja de inventario', 'prod_ajuste', $addAjuste->result, 1);
                    if($seg_log->response){
                        $addAjuste->state = $this->model->transaction->confirmaTransaccion();	
                        return $res->withJson($addAjuste);
                    }else{
                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                    }
                }else{
                    $addStock->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($addStock->SetResponse(false, 'No se pudo agregar el registro de stock'));
                }
            }else{
                $addAjuste->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($addAjuste->SetResponse(false, 'No se pudo agregar el registro del ajuste'));
            }
        });

        $this->get('getCodigo', function($req, $res, $args){
            return $res->withJson($this->model->producto->getCodigo()->result);
        });

        $this->get('getCat', function($req, $res, $args){
            return $res->withJson($this->model->producto->getCat()->result);
        });

        $this->get('getSub/{cat}', function($req, $res, $args){
            return $res->withJson($this->model->producto->getSubC($args['cat'])->result);
        });

        $this->get('getArea', function($req, $res, $args){
            return $res->withJson($this->model->producto->getArea());
        });

        $this->get('getAllSub', function($req, $res, $args){
            return $res->withJson($this->model->producto->getAllSub()->result);
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
            $sheet->getColumnDimension('M')->setAutoSize(true);
            $sheet->getColumnDimension('N')->setAutoSize(true);
            $sheet->getColumnDimension('O')->setAutoSize(true);
            $sheet->getColumnDimension('P')->setAutoSize(true);
            $sheet->getColumnDimension('Q')->setAutoSize(true);
            $sheet->getColumnDimension('R')->setAutoSize(true);
            $sheet->getColumnDimension('S')->setAutoSize(true);
            $sheet->getColumnDimension('T')->setAutoSize(true);

            $sheet->mergeCells('L1:M1');
            $sheet->mergeCells('N1:O1');
            $sheet->mergeCells('P1:Q1');
            $sheet->getStyle("L1:M1")->getAlignment()->setHorizontal('center');
            $sheet->getStyle("N1:O1")->getAlignment()->setHorizontal('center');
            $sheet->getStyle("P1:Q1")->getAlignment()->setHorizontal('center');
           
            $sheet->setCellValue("A2", 'Categoría');
            $sheet->setCellValue("B2", 'Subcategoría');
            $sheet->setCellValue("C2", 'Área');
            $sheet->setCellValue("D2", 'Nombre');
            $sheet->setCellValue("E2", 'Descripción');
            $sheet->setCellValue("F2", 'Código');
            $sheet->setCellValue("G2", 'Marca');
            $sheet->setCellValue("H2", 'Mínimo');
            $sheet->setCellValue("I2", 'Venta por kilo (0->No, 1->Si)');
            $sheet->setCellValue("J2", 'Venta por kilo (Cantidad)');
            $sheet->setCellValue("K2", 'Venta por kilo (Precio)');
            $sheet->setCellValue("L1", 'MENUDEO');
            $sheet->setCellValue("L2", 'Hasta');
            $sheet->setCellValue("M2", 'Precio');
            $sheet->setCellValue("N1", 'MEDIO MAYOREO');
            $sheet->setCellValue("N2", 'Hasta');
            $sheet->setCellValue("O2", 'Precio');
            $sheet->setCellValue("P1", 'MAYOREO');
            $sheet->setCellValue("P2", 'Hasta');
            $sheet->setCellValue("Q2", 'Precio');
            $sheet->setCellValue("R1", 'DISTRIBUIDOR');
            $sheet->setCellValue("R2", 'Precio');
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
            $sheet->setCellValue("B1", 'Nombre');
            $sheet->setCellValue("C1", 'Precio Menudeo');
            $sheet->setCellValue("D1", 'Precio Medio-Mayoreo');
            $sheet->setCellValue("E1", 'Precio Mayoreo');
            $sheet->setCellValue("F1", 'Precio Distribuidor');
            $sheet->setTitle('Hoja 1');
            $fila = 2;

            $prods = $this->model->producto->getAll($args['cat'], $args['sub'], $args['area'])->result;

            foreach($prods as $prod){
                $sheet->setCellValue("A$fila", $prod->id);
                $sheet->setCellValue("B$fila", $prod->nombre);
                $sheet->setCellValue("C$fila", $prod->menudeo);
                $sheet->setCellValue("D$fila", $prod->medio);
                $sheet->setCellValue("E$fila", $prod->mayoreo);
                $sheet->setCellValue("F$fila", $prod->distribuidor);
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
			$fila = 3;

			while(strlen($hojaActual->getCell("A$fila")->getValue()) != '') {
                $categoria = $hojaActual->getCell("A$fila")->getValue();
                $existe = $this->model->producto->getByName('prod_categoria', 'nombre', $categoria);
                if(is_object($existe->result)){
                    $catId = $existe->result->id;
                }else{
                    $data = ['nombre' => $categoria];
                    $catId = $this->model->producto->add($data, 'prod_categoria')->result;
                }

                $subcat = $hojaActual->getCell("B$fila")->getValue();
                $existe = $this->model->producto->getByName('prod_categoria', 'nombre', $subcat);
                if(is_object($existe->result) && $existe->result->prod_categoria_id == $catId){
                    $subId = $existe->result->id;
                }else{
                    $data = ['prod_categoria_id' => $catId,'nombre' => $subcat];
                    $subId = $this->model->producto->add($data, 'prod_categoria')->result;
                }

                $area = $hojaActual->getCell("C$fila")->getValue();
                $existe = $this->model->producto->getByName('prod_area', 'nombre', $area);
                if(is_object($existe->result)){
                    $areaId = $existe->result->id;
                }else{
                    $data = ['nombre' => $area];
                    $areaId = $this->model->producto->add($data, 'prod_area')->result;
                }
                $data = [  
                    'prod_categoria_id' => $subId,
                    'prod_area_id' => $areaId,
                    'nombre' => $hojaActual->getCell("D$fila")->getValue(),
                    'descripcion' => $hojaActual->getCell("E$fila")->getValue(),
                    'codigo' => $hojaActual->getCell("F$fila")->getValue(),
                    'marca' => $hojaActual->getCell("G$fila")->getValue(),
                    'minimo' => $hojaActual->getCell("H$fila")->getValue(),
                    'venta_kilo' => $hojaActual->getCell("I$fila")->getValue(),
                    'menudeo' => $hojaActual->getCell("L$fila")->getValue(),
                    'medio' => $hojaActual->getCell("N$fila")->getValue(),
                    'mayoreo' => $hojaActual->getCell("P$fila")->getValue(),
                ];
                $addProd = $this->model->producto->add($data, 'producto');
                if($addProd->response){
                    $prod_origen = $addProd->result;
                    $dataPrecio = [
                        'producto_id' => $prod_origen,
                        'menudeo' =>  $hojaActual->getCell("M$fila")->getValue(),
                        'medio' =>  $hojaActual->getCell("O$fila")->getValue(),
                        'mayoreo' =>  $hojaActual->getCell("Q$fila")->getValue(),
                        'distribuidor' =>  $hojaActual->getCell("R$fila")->getValue(),
                    ];
                    $addPrecio = $this->model->producto->add($dataPrecio, 'prod_precio');
                    if($addPrecio->response){
                        if($hojaActual->getCell("I$fila")->getValue() == 1){
                            $dataKilo = [
                                'prod_categoria_id' => $subId,
                                'prod_area_id' => $areaId,
                                'nombre' => 'Kilo de '.$hojaActual->getCell("D$fila")->getValue(),
                                'descripcion' => 'Kilo de '.$hojaActual->getCell("E$fila")->getValue(),
                                'codigo' => 'PK'.$hojaActual->getCell("F$fila")->getValue(),
                                'marca' => $hojaActual->getCell("G$fila")->getValue(),
                                'es_kilo' => 1,
                            ];
                            $prod_kilo = $this->model->producto->add($dataKilo, 'producto');
                            if($prod_kilo->response){
                                $kilo_id = $prod_kilo->result;
                                $prodKilo = [
                                    'producto_id' => $kilo_id,
                                    'producto_origen' => $prod_origen,
                                    'cantidad' => $hojaActual->getCell("J$fila")->getValue(),
                                    'precio' => $hojaActual->getCell("K$fila")->getValue()
                                ];
                                $kilo = $this->model->producto->add($prodKilo, 'prod_kilo');
                                if($kilo->response){
                                    $seg_log = $this->model->seg_log->add('Agrega producto', 'producto', $prod_origen, 1);
                                    $seg_log = $this->model->seg_log->add('Agrega kilo', 'producto', $kilo_id, 1);
                                    if(!$seg_log->response){
                                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                                        return $response->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                                    }
                                }else{
                                    $kilo->state = $this->model->transaction->regresaTransaccion();	
                                    return $response->withJson($kilo->SetResponse(false, 'No se pudo agregar el kilo del producto'));
                                }
                            }else{
                                $kilo->state = $this->model->transaction->regresaTransaccion();
                                return $response->withJson($kilo->SetResponse(false, 'No se pudo agregar el producto para venta por kilo'));
                            }
                        }else{
                            $seg_log = $this->model->seg_log->add('Agrega producto', 'producto', $prod_origen, 1);
                            if(!$seg_log->response){
                                $seg_log->state = $this->model->transaction->regresaTransaccion();	
                                return $response->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                            }
                        }
                    }else{
                        $addPrecio->state = $this->model->transaction->regresaTransaccion();	
                        return $response->withJson($addPrecio->SetResponse(false, 'No se pudo agregar la lista de precios'));
                    }
                }else{
                    if($addProd->errors->errorInfo[0] == 23000) {
                        $addProd->error = 23000;
                        return $response->withJson($addProd->SetResponse(false, 'El código '.$hojaActual->getCell("F$fila")->getValue().' del producto '.$hojaActual->getCell("D$fila")->getValue().' en la fila '.$fila.' ya existe.'));
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
			$data = $request->getParsedBody();

			$directory = 'data/uploads/precio/';
			$uploadedFile = $uploadedFiles['file'];
			
			$extension = strtolower(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
			$basename = date('YmdHis').'u'.$_SESSION['usuario_id'];
			$filename = sprintf('%s.%0.8s', $basename, $extension);

			$uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

			$documento = IOFactory::load($directory.DIRECTORY_SEPARATOR.$filename);
    		$hojaActual = $documento->getSheet(0);
			$fila = 2;

			while(strlen($hojaActual->getCell("A$fila")->getValue()) != '') {
                $id_prod = $hojaActual->getCell("A$fila")->getValue();
                $id_precio = $this->model->producto->getPrecios($id_prod)->result->id;
                $data = [  
                    'menudeo' => $hojaActual->getCell("C$fila")->getValue(),
                    'medio' => $hojaActual->getCell("D$fila")->getValue(),
                    'mayoreo' => $hojaActual->getCell("E$fila")->getValue(),
                    'distribuidor' => $hojaActual->getCell("F$fila")->getValue(),
                    'actualiza' => date('Y-m-d H:i:s'),
                ];
                $edit = $this->model->producto->edit('prod_precio', 'producto_id', $data, $id_prod);
                if($edit->response){
                    $seg_log = $this->model->seg_log->add('Edita precios con layout', 'prod_precio', $id_precio, 1);
                    if(!$seg_log->response){
                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                        return $response->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                    }
                }else{
                    $edit->state = $this->model->transaction->regresaTransaccion();	
                    return $response->withJson($edit->SetResponse(false, 'No se editaron los precios del producto'));
                }
				$fila++;
			}
            $edit->state = $this->model->transaction->confirmaTransaccion();	
            return $response->withJson($edit);		
		});

	});

?>