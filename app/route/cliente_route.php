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
                $data[] = array(
					"id" => $cliente->id,
					"nombre" => $cliente->nombre,
					"apellidos" => $cliente->apellidos,
					"correo" => $cliente->correo,
					"telefono" => $cliente->telefono,
					"saldo" => '$ '.$cliente->saldo_favor,
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
		});

		$this->post('add/',function($req,$res,$args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody(); 
            unset($parsedBody['cliente_id'], $parsedBody['saldo']);           
            
            $cliente = $this->model->cliente->add($parsedBody); 
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
		});

		$this->post('edit/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();
            unset($parsedBody['cliente_id'], $parsedBody['saldo']);

			$igual = true;
			$info = $this->model->cliente->get($args['id'])->result;
			
			foreach($parsedBody as $field => $value) { 
                if($info->$field != $value) { 
                    $igual = false; break; 
				} 
			}

			if(!$igual){
                $editCli = $this->model->cliente->edit($parsedBody, $args['id']);
                if($editCli->response){
                    $seg_log = $this->model->seg_log->add('Modifica cliente', 'cliente', $args['id'], 1);
                    if($seg_log->response){
                        $this->model->transaction->confirmaTransaccion();
			            return $res->withJson($editCli);
                    }else{
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
			            return $res->withJson($seg_log);
                    }
                }else{
                    $editCli->state = $this->model->transaction->regresaTransaccion();
			        return $res->withJson($editCli);
                }
			}else{
                $editCli = ['code' => 1, 'msg' => 'No existen datos diferentes a los antes registrados'];
                return $res->withJson($editCli);
            }
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
	});

?>