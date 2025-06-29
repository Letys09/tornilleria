<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/cliente/', function () use ($app){

        $this->get('get/{id}', function ($req, $res, $args) {
			return $res->withJson($this->model->cliente->get($args['id']));
		});

        $this->get('getAllDataTable', function($req, $res, $args){
			$clientes = $this->model->cliente->getAllDataTable();
			$data = [];
			if(!isset($_SESSION)) { session_start(); }
			foreach($clientes->result as $cliente) {
                $ventas = $this->model->venta->getPendiente($cliente->id);
                $pendiente = 0; $total = 0; $pagado = 0;
                if($ventas->response){
                    foreach($ventas->result as $venta){
                        $total += $venta->total; 
                        $pagos = $this->model->venta_pago->getByVenta($venta->id)->result;
                        foreach($pagos as $pago){
                            $pagado += $pago->monto;
                        }
                    }
                    $pendiente = $total-$pagado;
                }
                $data[] = array(
					"id" => $cliente->id,
					"nombre" => $cliente->nombre,
					"apellidos" => $cliente->apellidos,
					"correo" => $cliente->correo,
					"telefono" => $cliente->telefono,
					"descuento" => $cliente->descuento.' %',
					"pendiente" => '$ '.number_format($pendiente, 2, '.', ','),
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
		});

        $this->get('getCli', function($req, $res, $args){
            $clientes = $this->model->cliente->getCli()->result;
            return $res->withJson($clientes);
        });

        $this->get('regimen', function($req, $res, $args){
            return $res->withJson($this->model->cliente->regimen()->result);
        });

		$this->post('add/',function($req,$res,$args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody(); 
            unset($parsedBody['cliente_id'], $parsedBody['saldo']);
            $fiscales = [
                'rfc' => $parsedBody['rfc'],
                'razon_social' => $parsedBody['razon_social'],
                'codigo_postal' => $parsedBody['codigo_postal'],
                'regimen_fiscal' => $parsedBody['regimen_fiscal']
            ];         
            
            $addFiscales = $this->model->cliente->add($fiscales, 'cli_datos_fiscales');
            if($addFiscales->response){
                $data = [
                    'cli_datos_fiscales_id' => $addFiscales->result,
                    'nombre' => $parsedBody['nombre'],
                    'apellidos' => $parsedBody['apellidos'],
                    'correo' => $parsedBody['correo'],
                    'telefono' => $parsedBody['telefono'],
                    'descuento' => $parsedBody['descuento'],
                    'registro' => date('Y-m-d H:i:s')
                ];
                $cliente = $this->model->cliente->add($data, 'cliente'); 
                if($cliente->response){
                    $client = $cliente->result;
                    $seg_log = $this->model->seg_log->add('Agrega cliente', 'cliente', $client, 1);
                    if($seg_log->response){
                        $cliente->state = $this->model->transaction->confirmaTransaccion();	
                        return $res->withJson($cliente);
                    }else{
                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                    }    
                }else{
                    $cliente->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($cliente->SetResponse(false, 'No se pudo agregar al cliente'));
                }
            }else{
                $addFiscales->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addFiscales->setResponse(false, 'No se pudieron agregar los datos fiscales del cliente'));
            }
            
		});

		$this->post('edit/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();

			$igual = true; $fiscalesI = true;
			$info = $this->model->cliente->get($args['id'])->result;
            $data = [
                'nombre' => $parsedBody['nombre'],
                'apellidos' => $parsedBody['apellidos'],
                'correo' => $parsedBody['correo'],
                'telefono' => $parsedBody['telefono'],
                'descuento' => $parsedBody['descuento'],
            ];

            foreach($data as $field => $value) { 
                if($info->$field != $value) { 
                    $igual = false; break; 
				} 
			}

            $infoF = $this->model->cliente->getFiscales($info->cli_datos_fiscales_id)->result;
            $fiscales = [
                'rfc' => $parsedBody['rfc'],
                'razon_social' => $parsedBody['razon_social'],
                'codigo_postal' => $parsedBody['codigo_postal'],
                'regimen_fiscal' => $parsedBody['regimen_fiscal']
            ];  
            foreach($fiscales as $field => $value) { 
                if($infoF->$field != $value) { 
                    $fiscalesI = false; break; 
				} 
			}

			if(!$igual){
                $edit = $this->model->cliente->edit($data, $args['id'], 'cliente');
                if($edit->response){
                    $seg_log = $this->model->seg_log->add('Modifica cliente', 'cliente', $args['id'], 0);
                    if(!$seg_log->response){
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
                    }
                }else{
                    $edit->state = $this->model->transaction->regresaTransaccion();
                }
			}

            if(!$fiscalesI){
                $edit = $this->model->cliente->edit($fiscales, $info->cli_datos_fiscales_id, 'cli_datos_fiscales');
                if($edit->response){
                    $seg_log = $this->model->seg_log->add('Modifica datos fiscales', 'cli_datos_fiscales', $info->cli_datos_fiscales_id, 0);
                    if(!$seg_log->response){
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
                    }
                }else{
                    $edit->state = $this->model->transaction->regresaTransaccion();
                }
			}

            if($igual && $fiscalesI){
                $edit = 'No existen datos diferentes a los antes registrados';
            }

            $this->model->transaction->confirmaTransaccion();
            return $res->withJson($edit);
		});

        $this->post('del/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $update = $this->model->cliente->del($args['id']);
            if($update->response){
                $seg_log = $this->model->seg_log->add('Elimina cliente', 'cliente', $args['id'], 1);
                if($seg_log->response){
                    $update->state = $this->model->transaction->confirmaTransaccion();	
                    return $res->withJson($update);
                }else{
                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($cliente->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                }
            }else{
                $update->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($update->SetResponse(false, 'No se pudo eliminar al cliente'));
            }
        });

        $this->get('getBy/{param}', function($req, $res, $args){
            $clientes = $this->model->cliente->getBy($args['param'])->result;
            return $res->withJson($clientes);
        });
	});

?>