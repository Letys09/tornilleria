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
			if(isset($_SESSION['logID'])){
				$sesion_info = $this->model->seg_sesion->get($_SESSION['logID']);
				if($sesion_info->response){
					$sesion = $sesion_info->result;
					if($sesion->finalizada != null){
						unset($_SESSION['logID']);
						return $this->response->withRedirect(URL_ROOT.'/login');
					}
				}
			}
			if((isset($_SESSION['usuario']))) {
				if($args['name'] == '') {
					return $this->view->render($response, 'login.phtml', $args);
				}else if($args['name'] == 'venta'){
					$user = $_SESSION['usuario']->id;
					$permisos = $this->model->usuario->getAcciones($user, 0);
					$arrPermisos = getPermisos($permisos); 
					$params = array('vista' => ucfirst($args['name']), 'permisos' => $arrPermisos, 'todo' => $this, 'modulos' => $_SESSION['permisos']);
					return $this->view->render($response, 'venta.phtml', $params);
				}else{
					$params = array('vista' => ucfirst($args['name']));
					try{
							$user = $_SESSION['usuario']->id;
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

		$app->get('/venta/cliente[/{id}]', function(Request $request, Response $response, array $args) use ($container){
			if(isset($_SESSION['logID'])){
				$sesion_info = $this->model->seg_sesion->get($_SESSION['logID']);
				if($sesion_info->response){
					$sesion = $sesion_info->result;
					if($sesion->finalizada != null){
						unset($_SESSION['logID']);
						return $this->response->withRedirect(URL_ROOT.'/login');
					}
				}
			}
			$params['vista'] = 'Venta';
			$user = $_SESSION['usuario']->id;
			$permisos = $this->model->usuario->getAcciones($user, 0);
			$arrPermisos = getPermisos($permisos); 
			$params['permisos'] = $arrPermisos; 

			if(isset($args['id'])){
				$params['id'] = $args['id'];
				$params['venta'] = $this->model->venta->getByMd5($args['id']);
				$params['detalles'] = $this->model->venta_detalle->getByVenta($params['venta']->id)->result;
				foreach($params['detalles'] as $detalle){
					$prod = $this->model->producto->get($detalle->producto_id)->result;
					$detalle->concepto = '('.$prod->clave.') '.$prod->descripcion;
					$detalle->es_kilo = $prod->es_kilo;
				}
				$params['pagos'] = $this->model->venta_pago->getByVenta($params['venta']->id)->result;
				// print_r($params['id']);
				// print_r('<hr>');
				// print_r($params['venta']);
				// print_r('<hr>');
				// print_r($params['detalles']);
				// print_r('<hr>');
				// print_r($params['pagos']);exit();
				$params['nueva'] = false;
			}else{
				$params['nueva'] = true;
				$params['detalles'] = [];
				$params['pagos'] = [];
			}
			// print_r($params);exit();

			return $this->renderer->render($response, 'venta.phtml', $params);
		});

		$app->get('/ventas/dia[/{dia}]', function(Request $request, Response $response, array $args) use ($container){
			if(isset($_SESSION['logID'])){
				$sesion_info = $this->model->seg_sesion->get($_SESSION['logID']);
				if($sesion_info->response){
					$sesion = $sesion_info->result;
					if($sesion->finalizada != null){
						unset($_SESSION['logID']);
						return $this->response->withRedirect(URL_ROOT.'/login');
					}
				}
			}
			$params['vista'] = 'Ventas del día';
			$user = $_SESSION['usuario']->id;
			$permisos = $this->model->usuario->getAcciones($user, 0);
			$arrPermisos = getPermisos($permisos); 
			$params['permisos'] = $arrPermisos; 
			return $this->renderer->render($response, 'ventas_dia.phtml', $params);
		});

		$app->get('/ticket/{ticket}', function(Request $request, Response $response, array $args) use ($container){
			if(isset($_SESSION['logID'])){
				$sesion_info = $this->model->seg_sesion->get($_SESSION['logID']);
				if($sesion_info->response){
					$sesion = $sesion_info->result;
					if($sesion->finalizada != null){
						unset($_SESSION['logID']);
						return $this->response->withRedirect(URL_ROOT.'/login');
					}
				}
			}
			$params['venta'] = $this->model->venta->getByMD5($args['ticket']);
			$fecha = explode('/', $params['venta']->date);
			$params['folio'] = $params['venta']->identificador.'-'.$fecha[0].$fecha[1].$fecha[2].'-'.$params['venta']->id;
			$params['atendio'] = $this->model->usuario->get($params['venta']->usuario_id)->result->nombre;
			if($params['venta']->tipo == 1){
				$params['venta']->metodo = $this->model->venta_pago->getByVenta($params['venta']->id)->result[0]->forma_pago;
			}
			$detalles = $this->model->venta_detalle->getByVenta($params['venta']->id)->result;
			$params['sucursal'] = $this->model->sucursal->get($_SESSION['sucursal_id'])->result;
			$params['cliente'] = $this->model->cliente->get($params['venta']->cliente_id)->result;
			foreach($detalles as $detalle){
				$producto = $this->model->producto->get($detalle->producto_id)->result;
				$detalle->producto = '('.$producto->clave.') '.$producto->descripcion;
			}
			$params['detalles'] = $detalles;
			// print_r($params['venta']);exit();
			return $this->renderer->render($response, 'ticket.phtml', $params);
		});

		$app->get('/reporte/{nombre}[/{mes}[/{anio}]]', function(Request $request, Response $response, array $args) use ($container){
			if(isset($_SESSION['logID'])){
				$sesion_info = $this->model->seg_sesion->get($_SESSION['logID']);
				if($sesion_info->response){
					$sesion = $sesion_info->result;
					if($sesion->finalizada != null){
						unset($_SESSION['logID']);
						return $this->response->withRedirect(URL_ROOT.'/login');
					}
				}
			}
			$mes_select = isset($args['mes']) ? $args['mes'] : date('m'); 
			$anio = isset($args['anio']) ? $args['anio'] : date('Y'); 
			$user = $_SESSION['usuario']->id;
			$permisos = $this->model->usuario->getAcciones($user, 0);
			$arrPermisos = getPermisos($permisos); 
			if($args['nombre'] == 'periodo'){
				$params['vista'] = 'Periodo';
				$info = $this->model->venta->getVentasMesAnio($anio, $mes_select);
				$params['permisos'] = $arrPermisos;
				$params['mes_select'] = $mes_select;
				$params['anio'] = $anio;
				$params['ventas'] = $info->result;
				$params['numVentas'] = $info->total;
				$params['contado'] = $info->contado;
				$params['credito'] = $info->credito;
				$params['general'] = $info->general;
				$params['frecuente'] = $info->frecuente;
				$template = 'ventas_periodo';
			}else if($args['nombre'] == 'metodo'){
				$params['vista'] = 'Forma pago';
				$info = $this->model->venta_pago->getPagosMesAnio($anio, $mes_select);
				$params['permisos'] = $arrPermisos;
				$params['mes_select'] = $mes_select;
				$params['anio'] = $anio;
				$params['metodos'] = $info->result;
				$template = 'ventas_metodo';
			}else{
				$params['vista'] = 'Producto';
				$info = $this->model->producto->getProdsMesAnio($anio, $mes_select);
				$params['permisos'] = $arrPermisos;
				$params['mes_select'] = $mes_select;
				$params['anio'] = $anio;
				$params['productos'] = $info->result;
				$template = 'ventas_producto';
			}
			return $this->renderer->render($response, 'rpt_'.$template.'.phtml', $params);
		});

	};


	function getPermisos($arrPermisos) {
		$res = array();
		foreach($arrPermisos as $permisos) {
			$res[] = $permisos->id;
		}
		return $res;
	}
?>