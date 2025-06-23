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
			$params['fromCotizacion'] = false;

			if(isset($args['id'])){
				$params['venta'] = $this->model->venta->getByMd5($args['id']);
				$venta_id = $params['venta']->id;
				$params['venta_id'] = $venta_id;
				$params['folio'] = $params['venta']->folio;
				$data = ['en_uso' => 1];
				$this->model->venta->edit($data, $venta_id);
				$params['nueva'] = false;
				$info_cot = $this->model->cotizacion->getByVenta($venta_id);
				if($info_cot->response) $params['fromCotizacion'] = true;
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
				$params['folio'] = $params['venta']->folio;
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

		$app->get('/segunda/venta', function(Request $request, Response $response, array $args) use ($container){
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
			$params['vista'] = 'Segunda Venta';
			$user = $_SESSION['usuario']->id;
			$permisos = $this->model->usuario->getAcciones($user, 0);
			$arrPermisos = getPermisos($permisos); 
			$params['permisos'] = $arrPermisos; 
			return $this->renderer->render($response, 'venta_nueva.phtml', $params);
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
			$params['folio'] = $params['venta']->folio;
			$params['atendio'] = $this->model->usuario->get($params['venta']->usuario_id)->result->nombre;
			if($params['venta']->tipo == 1){
				$recibido = 0; $cambio = 0;
				$info_pago = $this->model->venta_pago->getByVenta($params['venta']->id)->result;
				foreach($info_pago as $pago){
					$recibido += $pago->monto_recibido;
					$cambio += $pago->cambio;
					$params['venta']->metodo = $pago->forma_pago;
				}
				$params['venta']->recibido = number_format($recibido, 2, '.', ',');
				$params['venta']->cambio = number_format($cambio, 2, '.', ',');
			}
			$detalles = $this->model->venta_detalle->getByVenta($params['venta']->id)->result;
			$params['sucursal'] = $this->model->sucursal->get($_SESSION['sucursal_id'])->result;
			$params['cliente'] = $this->model->cliente->get($params['venta']->cliente_id)->result;
			foreach($detalles as $detalle){
				$producto = $this->model->producto->get($detalle->producto_id)->result;
				$detalle->producto = '('.$producto->clave.') '.$producto->descripcion;
			}
			$params['detalles'] = $detalles;
			if($params['venta']->tipo == 2){
				$params['pagos'] = [];
				$pagos = $this->model->venta_pago->getByVenta($params['venta']->id);
				$params['pagos'] = $pagos->result;
			}
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
				$params['folio'] = $params['cotizacion']->folio;
				$params['detalles'] = $this->model->coti_detalle->getByCot($params['cotizacion']->id)->result;
				$data = ['en_uso' => 1];
				$this->model->cotizacion->edit($data, $params['cotizacion']->id);
				foreach($params['detalles'] as $detalle){
					$prod = $this->model->producto->get($detalle->producto_id)->result;
					$detalle->clave = $prod->clave;
					$detalle->concepto = '('.$prod->clave.') '.$prod->descripcion;
					$detalle->es_kilo = $prod->es_kilo;
					$detalle->stock = '0.0';
					$info_stock = $this->model->prod_stock->getStock($_SESSION['sucursal_id'], $detalle->producto_id);
					if($info_stock->response){
						if(is_object($info_stock->result)) $detalle->stock = $info_stock->result->final;
					}
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
			$params['folio'] = $params['cotizacion']->folio;
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
			if( strlen($mes_select) == 1 ){
				$mes_select = '0'.$mes_select;
			}
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
			}else if($args['nombre'] == 'log'){
				$params['vista'] = 'Bitácora de Acciones';
				$params['permisos'] = $arrPermisos;
				$template = 'bitacora';
			}else if($args['nombre'] == 'corte_caja'){
				$params['vista'] = 'Corte de Caja';
				$params['permisos'] = $arrPermisos;
				$info_ventas = $this->model->venta_pago->getPagosByDate(date('Y-m-d'))->result;
				$total = 0; $efectivo = 0; $banco = 0;
				foreach($info_ventas as $info_v){
					$total += $info_v->monto;
					if($info_v->forma_pago == 1) $efectivo += $info_v->monto;
					else if($info_v->forma_pago == 3 || $info_v->forma_pago == 4) $banco += $info_v->monto;
				}
				
				$params['total'] = number_format($total, 2, ".", ","); $params['efectivo'] = number_format($efectivo, 2, ".", ","); $params['banco'] = number_format($banco, 2, ".", ",");
				$template = 'corte_caja';
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