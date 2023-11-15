<?php
	use Slim\App;
	use Slim\Http\Request;
	use Slim\Http\Response;

	return function (App $app) {
		$container = $app->getContainer();

		$app->get('/[{name}]', function (Request $request, Response $response, array $args) use ($container) {
			$this->logger->info("Slim-Skeleton '/' ".(isset($args['name'])?$args['name']:''));
			if(!isset($args['name'])) { $args['name'] = 'login'; }
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
				$params['venta'] = $this->model->venta->getByMd5($args['id']);
				$params['venta_id'] = $params['venta']->id;
				$params['folio'] = $params['venta']->identificador.'-'.$params['venta']->fechaFolio.'-'.$params['venta']->id;
				$data = ['en_uso' => 1];
				$this->model->venta->edit($data, $params['venta']->id);
				$params['nueva'] = false;
			}else{
				$params['nueva'] = true;
				$params['detalles'] = [];
				$params['pagos'] = [];
			}

			return $this->renderer->render($response, 'venta.phtml', $params);
		});

		$app->get('/venta/cambio/{id}', function(Request $request, Response $response, array $args) use ($container){
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
				$params['venta'] = $this->model->venta->getByMd5($args['id']);
				$params['venta_id'] = $params['venta']->id;
				$params['folio'] = $params['venta']->identificador.'-'.$params['venta']->fechaFolio.'-'.$params['venta']->id;
				$params['detalles'] = $this->model->venta_detalle->getByVenta($params['venta']->id)->result;
				$data = ['en_uso' => 1];
				$this->model->venta->edit($data, $params['venta']->id);
				foreach($params['detalles'] as $detalle){
					$prod = $this->model->producto->get($detalle->producto_id)->result;
					$detalle->es_kilo = $prod->es_kilo;
					if($prod->es_kilo){
						$detalle->es_kilo = $this->model->producto->getKiloBy($detalle->producto_id, 'producto_id')->result->cantidad;
					}
					$detalle->clave = $prod->clave;
					$detalle->concepto = '('.$prod->clave.') '.$prod->descripcion;
					if($prod->es_kilo == 0){
						$info_stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $detalle->producto_id)->result->final;
						$detalle->stock = number_format($info_stock, 1);
					}else{
						$info_kilo = $this->model->producto->getKiloBy($detalle->producto_id, 'producto_id')->result;
						$prod_origen = $info_kilo->producto_origen;
						$info_stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $prod_origen)->result->final;
						$detalle->stock = number_format($info_stock, 1);
					}
					$detalle->descripcion = $prod->descripcion;
				}
				$params['pagos'] = $this->model->venta_pago->getByVenta($params['venta']->id)->result;
				$params['nueva'] = false;
				return $this->renderer->render($response, 'venta_cambio.phtml', $params);
			}else{
				return $this->renderer->render($response, '404.phtml', $params);
			}

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

		$app->get('/cotizacion/cliente[/{id}]', function(Request $request, Response $response, array $args) use ($container){
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
			$params['vista'] = 'Cotización';
			$user = $_SESSION['usuario']->id;
			$permisos = $this->model->usuario->getAcciones($user, 0);
			$arrPermisos = getPermisos($permisos); 
			$params['permisos'] = $arrPermisos; 

			if(isset($args['id'])){
				$params['cotizacion'] = $this->model->cotizacion->getByMd5($args['id']);
				$params['cotizacion_id'] = $params['cotizacion']->id;
				$params['folio'] = $params['cotizacion']->identificador.'-'.$params['cotizacion']->fechaFolio.'-'.$params['cotizacion']->id;
				$params['detalles'] = $this->model->coti_detalle->getByCot($params['cotizacion']->id)->result;
				$data = ['en_uso' => 1];
				$this->model->cotizacion->edit($data, $params['cotizacion']->id);
				foreach($params['detalles'] as $detalle){
					$prod = $this->model->producto->get($detalle->producto_id)->result;
					$detalle->clave = $prod->clave;
					$detalle->concepto = '('.$prod->clave.') '.$prod->descripcion;
					$detalle->es_kilo = $prod->es_kilo;
					$detalle->stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $detalle->producto_id)->result->final;
				}
				$params['nueva'] = false;
			}else{
				$params['nueva'] = true;
				$params['detalles'] = [];
			}

			return $this->renderer->render($response, 'cotizacion.phtml', $params);
		});

		$app->get('/ticket/cotizacion/{ticket}', function(Request $request, Response $response, array $args) use ($container){
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
			$params['cotizacion'] = $this->model->cotizacion->getByMD5($args['ticket']);
			$fecha = explode('/', $params['cotizacion']->date);
			$params['folio'] = $params['cotizacion']->identificador.'-'.$fecha[0].$fecha[1].$fecha[2].'-'.$params['cotizacion']->id;
			$params['atendio'] = $this->model->usuario->get($params['cotizacion']->usuario_id)->result->nombre;
			$detalles = $this->model->coti_detalle->getByCot($params['cotizacion']->id)->result;
			$params['sucursal'] = $this->model->sucursal->get($_SESSION['sucursal_id'])->result;
			$params['cliente'] = $this->model->cliente->get($params['cotizacion']->cliente_id)->result;
			foreach($detalles as $detalle){
				$producto = $this->model->producto->get($detalle->producto_id)->result;
				$detalle->producto = '('.$producto->clave.') '.$producto->descripcion;
			}
			$params['detalles'] = $detalles;
			return $this->renderer->render($response, 'ticket_cot.phtml', $params);
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
				$params['vista'] = 'Reporte de Ventas Por Periodo';
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
				$params['vista'] = 'Reporte de Métodos de Pago';
				$info = $this->model->venta_pago->getPagosMesAnio($anio, $mes_select);
				$params['permisos'] = $arrPermisos;
				$params['mes_select'] = $mes_select;
				$params['anio'] = $anio;
				$params['metodos'] = $info->result;
				$template = 'ventas_metodo';
			}else if($args['nombre'] == 'producto'){
				$params['vista'] = 'Reporte de Productos';
				$info = $this->model->producto->getProdsMesAnio($anio, $mes_select);
				$params['permisos'] = $arrPermisos;
				$params['mes_select'] = $mes_select;
				$params['anio'] = $anio;
				$params['productos'] = $info->result;
				$template = 'ventas_producto';
			}else{
				$params['vista'] = 'Bitácora de Acciones';
				$info = $this->model->producto->getProdsMesAnio($anio, $mes_select);
				$params['permisos'] = $arrPermisos;
				$params['mes_select'] = $mes_select;
				$params['anio'] = $anio;
				$params['productos'] = $info->result;
				$template = 'bitacora';
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