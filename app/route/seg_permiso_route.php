<?php
	use App\Lib\Response;
 
	/*** Grupo bajo la ruta seg_permiso ***/
	$app->group('/seg_permiso/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de seg_permiso');
		});
		
		/*** Ruta para obtener los datos de seg_permiso por medio del ID ***/
		$this->get('get/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->get($arguments['id']));
		});

		/*** Ruta para obtener los datos de los seg_permiso ***/
		$this->get('getAll/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->getAll());
		});

		/*** Ruta para agregar permisos a usuario ***/
		$this->post('add/', function ($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$parsedBody['fecha_asigno'] = date('Y-m-d H:i:s');

				$addPermiso = $this->model->seg_permiso->add($parsedBody);
				$info = $this->model->seg_accion->get($parsedBody['seg_accion_id'])->result;
				$info_modulo = $this->model->seg_modulo->get($info->seg_modulo_id)->result;
				if($addPermiso->response){
					$seg_log = $this->model->seg_log->add('Asigna permiso '.$info->nombre.' del módulo '.$info_modulo->nombre, 'usuario', $parsedBody['usuario_id'], 1);
					if($seg_log->response){
						$seg_log->state = $this->model->transaction->confirmaTransaccion();
					}else{
						$seg_log->state = $this->model->transaction->regresaTransaccion();
						return $response->withJson($seg_log);
					}
				}else{
					$addPermiso->state = $this->model->transaction->regresaTransaccion();
					return $response->withJson($addPermiso);
				}
			return $response->withJson($addPermiso);
		});

		/*** Ruta para modificar un seg_permiso ***/
		$this->put('edit/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->edit($request->getParsedBody(), $arguments['id']));
		});

		/*** Ruta para dar de baja un permiso de usuario ***/
		$this->put('del/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->del($arguments['id']));
		});
		
		$this->delete('del/{user}/{accion}', function ($reques, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
				$delPermiso = $this->model->seg_permiso->delUserAccion($arguments['user'], $arguments['accion']);
				$info = $this->model->seg_accion->get($arguments['accion'])->result;
				$info_modulo = $this->model->seg_modulo->get($info->seg_modulo_id)->result;
				if($delPermiso->response){
					$seg_log = $this->model->seg_log->add('Elimina permiso '.$info->nombre.' del módulo '.$info_modulo->nombre, 'usuario', $arguments['user'], 1);
					if($seg_log->response){
						$seg_log->state = $this->model->transaction->confirmaTransaccion();
					}else{
						$seg_log->state = $this->model->transaction->regresaTransaccion();
						return $response->withJson($seg_log);
					}
				}else{
					$delPermiso->state = $this->model->transaction->regresaTransaccion();
					return $response->withJson($delPermiso);
				}
			return $response->withJson($delPermiso);
		});
	});
?>