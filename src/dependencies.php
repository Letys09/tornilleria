<?php
	
		$container = $app->getContainer();

		// view renderer
		$container['renderer'] = function ($c) {
			$settings = $c->get('settings')['renderer'];
			return new \Slim\Views\PhpRenderer($settings['template_path']);
		};

		// rpt renderer
		$container['rpt_renderer'] = function ($c) {
			$settings = $c->get('settings')['rpt_renderer'];
			return new \Slim\Views\PhpRenderer($settings['template_path']);
		};

		// monolog
		$container['logger'] = function ($c) {
			$settings = $c->get('settings')['logger'];
			$logger = new \Monolog\Logger($settings['name']);
			$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
			$logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
			return $logger;
		};

		// Database
			$container['db'] = function($c) {
				$connectionString = $c->get('settings')['connectionString'];
				
				$pdo = new PDO($connectionString['dns'], $connectionString['user'], $connectionString['pass']);

				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

				return new \Envms\FluentPDO\Query($pdo);
				
			};
			
			// Register component view 
			$container['view'] = function ($container) {
				return new \Slim\Views\PhpRenderer('../templates/');
			};

			// Models
			$container['model'] = function($c) {
				return (object)[
					'usuario' 				  => new App\Model\UsuarioModel($c->db),
					'seg_modulo'			  => new App\Model\SegModuloModel($c->db),
					'seg_accion' 			  => new App\Model\SegAccionModel($c->db),
					'seg_permiso'			  => new App\Model\SegPermisoModel($c->db),
					'seg_sesion'			  => new App\Model\SegSesionModel($c->db),
					'transaction' 			  => new App\Lib\Transaction($c->db),
					'seg_log' 				  => new App\Model\SegLogModel($c->db),
					'sucursal' 				  => new App\Model\SucursalModel($c->db),
					'producto' 				  => new App\Model\ProductoModel($c->db),
					'cliente' 				  => new App\Model\ClienteModel($c->db),
					'cliente_saldo' 		  => new App\Model\ClienteSaldoModel($c->db),
					'prod_entrada' 			  => new App\Model\EntradaModel($c->db),
					'prod_stock' 		      => new App\Model\StockModel($c->db),
					'prod_baja' 			  => new App\Model\BajaModel($c->db),
					'prod_inventario'		  => new App\Model\ProdInvModel($c->db),
					'prod_det_inventario'	  => new App\Model\ProdDetInvModel($c->db),
					'venta' 				  => new App\Model\VentaModel($c->db),
					'venta_detalle'			  => new App\Model\VentaDetModel($c->db),
					'venta_pago'			  => new App\Model\VentaPagoModel($c->db),
				];
			};
?>