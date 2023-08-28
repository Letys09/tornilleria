<?php
	use App\Lib\Response;
 
	/*** Grupo bajo la ruta seg_sesion ***/
	$app->group('/seg_sesion/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de seg_sesion');
		});
		
		/*** Ruta para obtener los datos de seg_sesion por medio del ID ***/
		$this->get('get/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_sesion->get($arguments['id']));
		});

		/*** Ruta para obtener los datos de los seg_sesion ***/
		$this->get('getAll/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_sesion->getAll());
		});
		
		/*** Ruta para obtener los datos de los seg_sesion de un mismo usuario ***/
		$this->get('getByUsuario/{usuario_id}[/{since}/{to}]', function ($request, $response, $arguments) {
			$arguments['since'] = isset($arguments['since'])? $arguments['since']: null;
			$arguments['to'] = isset($arguments['to'])? $arguments['to']: null;
			return $response->withJson($this->model->seg_sesion->getAll($arguments['usuario_id'], $arguments['since'], $arguments['to']));
		});

		/*** Ruta para agregar un seg_sesion ***/
		$this->post('add/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_sesion->add($request->getParsedBody()));
		});

		/*** Ruta para modificar un seg_sesion ***/
		$this->put('edit/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_sesion->edit($request->getParsedBody(), $arguments['id']));
		});

		/*** Ruta para dar de baja un seg_sesion ***/
		$this->put('del/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_sesion->del($arguments['id']));
		});
	});
?>