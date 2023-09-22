<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/sucursal/', function () use ($app){

        $this->get('get/{id}', function ($req, $res, $args) {
			return $res->withJson($this->model->sucursal->get($args['id']));
		});

        $this->get('getAllDataTable', function($req, $res, $args){
			$sucursales = $this->model->sucursal->getAllDataTable();

			$data = [];
			if(!isset($_SESSION)) { session_start(); }
			foreach($sucursales->result as $sucursal) {
                $int = $sucursal->no_int != '' && $sucursal->no_int != NULL ? ', Int. '.$sucursal->no_int : '';
                $dir = 'Calle '.$sucursal->calle.' '.$sucursal->no_ext.$int.', Colonia '.$sucursal->colonia.', '.$sucursal->municipio.', '.$sucursal->estado.'. C.P. '.$sucursal->codigo_postal;
				$data[] = array(
					"id" => $sucursal->id,
					"nombre" => $sucursal->nombre,
					"direccion" => $dir,
					"telefono" => $sucursal->telefono,
				);
			}

			echo json_encode(array(
				'data' => $data
			));
			exit(0);
		});

        $this->get('getAll', function($req, $res, $args){
            return $res->withJson($this->model->sucursal->getAll()->result);
        });

		$this->post('add/',function($req,$res,$args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $data = [
                'calle' => $parsedBody['calle'],
                'no_ext' => $parsedBody['no_ext'],
                'no_int' => $parsedBody['no_int'],
                'colonia' => $parsedBody['colonia'],
                'municipio' => $parsedBody['municipio'],
                'estado' => $parsedBody['estado'],
                'codigo_postal' => $parsedBody['codigo_postal']
            ];                
            unset($parsedBody['calle'], $parsedBody['no_ext'], $parsedBody['no_int'], $parsedBody['colonia'], $parsedBody['municipio'], $parsedBody['estado'], $parsedBody['codigo_postal']);
            
            $direccion = $this->model->sucursal->addDir($data);
            if($direccion->response){
                $parsedBody['direccion_id'] = $direccion->result;
                $sucursal = $this->model->sucursal->add($parsedBody);
                if($sucursal->response){
                    $seg_log = $this->model->seg_log->add('Agrega sucursal', 'sucursal', $sucursal->result, 1);
                    if($seg_log->response){
                        $sucursal->state = $this->model->transaction->confirmaTransaccion();	
                        return $res->withJson($sucursal);
                    }else{
                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                    }
                }else{
                    $sucursal->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar la sucursal'));
                }
            }else{
                $direccion->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($direccion->SetResponse(false, 'No se pudo agregar la dirección de la sucursal'));
            }
		});

		$this->post('edit/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();

			$igual = true; $dirIgual = true; $editSuc = 0;
			$info = $this->model->sucursal->get($args['id'])->result;

            $data = [
				'nombre' => $parsedBody['nombre'],
				'telefono' => $parsedBody['telefono'],
			];
			
			foreach($data as $field => $value) { 
                if($info->$field != $value) { 
                    $igual = false; break; 
				} 
			}
			
			$dir = $this->model->sucursal->getDir($info->direccion_id)->result;
			$dataDir = [
				'calle' => $parsedBody['calle'],
				'no_ext' => $parsedBody['no_ext'],
				'no_int' => $parsedBody['no_int'],
				'colonia' => $parsedBody['colonia'],
				'municipio' => $parsedBody['municipio'],
				'estado' => $parsedBody['estado'],
				'codigo_postal' => $parsedBody['codigo_postal'],
			];

            foreach($dataDir as $field => $value) { 
				if($dir->$field != $value) { 
					$dirIgual = false; break; 
				} 
			}

			if(!$igual){
                $editSuc = $this->model->sucursal->edit($data, $args['id']);
                if($editSuc->response){
                    $seg_log = $this->model->seg_log->add('Modifica sucursal', 'sucursal', $args['id'], 1);
                    if(!$seg_log->response){
                        $seg_log->state = $this->model->transaction->regresaTransaccion();
                    }
                }else{
                    $editSuc->state = $this->model->transaction->regresaTransaccion();
                }
			}
            if(!$dirIgual){
                $editSuc = $this->model->sucursal->editDir($dataDir, $args['id']);
                if($editSuc->response){
                        $seg_log = $this->model->seg_log->add('Modifica dirección', 'dirección', $info->direccion_id, 1);
                        if(!$seg_log->response){
                            $seg_log->state = $this->model->transaction->regresaTransaccion();
                        }
                }else{
                    $editSuc->state = $this->model->transaction->regresaTransaccion();
                }
            }

            $this->model->transaction->confirmaTransaccion();
			return $res->withJson($editSuc);
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

        $this->get('cerrar', function($req, $res, $args) use ($app) {
			if(!isset($_SESSION)) { session_start(); }
			$userId = $_SESSION['usuario_id'];
			$this->model->seg_sesion->logout();
            $this->model->sucursal->cerrar();
            
			return $res->withRedirect('../login');
		});

	});

?>