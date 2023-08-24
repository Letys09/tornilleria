<?php
	use Slim\App;
	use Slim\Http\Request;
	use Slim\Http\Response;

	return function (App $app) {
		$container = $app->getContainer();

		$app->get('/[{name}]', function (Request $request, Response $response, array $args) use ($container) {
			$this->logger->info("Slim-Skeleton '/' ".(isset($args['name'])?$args['name']:''));
			if(!isset($args['name'])) { $args['name'] = 'login'; }
			
			if(!isset($_SESSION)) { session_start(); }
			if((isset($_SESSION['usuario']))) {
				if($args['name'] == '') {
					return $this->view->render($response, 'login.phtml', $args);
				}else if($args['name'] == 'bienvenida'){
					$user = $_SESSION['usuario']->id_usuario;
					$permisos = $this->model->usuario->getAcciones($user, 0);
					$arrPermisos = getPermisos($permisos); 
					$params = array('vista' => ucfirst($args['name']), 'permisos' => $arrPermisos, 'todo' => $this);
					return $this->view->render($response, 'bienvenida.phtml', $params);
				}else{
					$params = array('vista' => ucfirst($args['name']));
					try{
							$user = $_SESSION['usuario']->id_usuario;
							$permisos = $this->model->usuario->getAcciones($user, 0);
							$arrPermisos = getPermisos($permisos); 
							$params = array('vista' => ucfirst($args['name']), 'permisos' => $arrPermisos, 'todo' => $this);
							return $this->view->render($response, "$args[name].phtml", $params);
					} catch (Throwable | Exception $e) {}
				}
				return $this->renderer->render($response, "$args[name].phtml", $args);
			} elseif($args['name']!='login') {
				return $this->response->withRedirect(URL_ROOT.'/login');
			} else {
				return $this->renderer->render($response, 'login.phtml', $args);
			}
		});
	};


	function getPermisos($arrPermisos) {
		$res = array();
		foreach($arrPermisos as $permisos) {
			$res[] = $permisos->id_accion;
		}
		return $res;
	}
?>