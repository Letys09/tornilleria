<?php
	namespace App\Model;
	use App\Lib\Response;


	class SegLogModel {
		private $db;
		private $table = 'seg_log';
		private $tableSession = 'seg_session';
		private $tableUsuario = 'usuario';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		public function get($desde, $hasta){
			$result = $this->db
				->from($this->table)
				->select(null)
				->select("DATE_FORMAT($this->table.fecha, '%d-%m-%Y') as fecha, CAST($this->table.fecha AS TIME) as hora, 
						  $this->table.descripcion, $this->table.registro, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario,
						  IF($this->table.tabla = 'cliente', CONCAT_WS(' ', cliente.nombre, cliente.apellidos),
						  IF($this->table.tabla = 'venta_detalle', CONCAT(prod_det.clave, ' ', prod_det.descripcion, ' Folio venta ', v_detalle.folio), 
						  IF($this->table.tabla = 'coti_detalle', CONCAT(prod_det_cot.clave, ' ', prod_det_cot.descripcion, ' Folio cotización ', detalle_coti.folio),
						  IF($this->table.tabla = 'prod_entrada', IF(prod_entrada.folio = '', 'Folio no registrado', CONCAT('Folio ', prod_entrada.folio)),
						  IF($this->table.tabla = 'venta_pago', CONCAT('$ ', venta_pago.monto, ' Método de pago ', CASE WHEN '1' THEN 'Efectivo' 
						  						   WHEN '3' THEN 'Tarjeta' WHEN '4' THEN 'Transferencia' ELSE '' END, ' Folio venta ', v_pago.folio),
						  IF($this->table.tabla = 'producto', CONCAT_WS(' ', producto.clave, producto.descripcion),
						  IF($this->table.tabla = 'usuario', CONCAT(user.nombre, ' ', user.apellidos, ', Email ', user.email),
						  IF($this->table.tabla = 'prod_ajuste', CONCAT(ajuste_prod.clave, ' ', ajuste_prod.descripcion, ' Cantidad ', prod_ajuste.cantidad,
						  						  ' Comentarios ', prod_ajuste.comentarios), 
						  IF($this->table.tabla = 'sucursal', sucursal.nombre,
						  IF($this->table.tabla = 'cotizacion', CONCAT('Folio cotización ', cotizacion.folio),
						  IF($this->table.tabla = 'venta', CONCAT('Folio venta ', venta.folio),
						  IF($this->table.tabla = 'prod_precio', CONCAT('Producto ', p_precio.clave,' ', p_precio.descripcion),
						  IF($this->table.tabla = 'prod_categoria', prod_categoria.nombre,
						  IF($this->table.tabla = 'prod_det_entrada', CONCAT_WS(' ', 'Producto ', det_ent_producto.clave, det_ent_producto.descripcion),
						  IF($this->table.tabla = 'prod_rango', CONCAT_WS(' ', 'Producto ', p_rango.clave, p_rango.descripcion),
						  ' '))))))))))))))) as adicional						  
						")
				->leftJoin("usuario ON usuario.id = $this->table.usuario_id")
				->leftJoin("cliente ON cliente.id = $this->table.registro")
				->leftJoin("venta_detalle ON venta_detalle.id = $this->table.registro")
				->leftJoin("producto AS prod_det ON prod_det.id = venta_detalle.producto_id")
				->leftJoin("venta AS v_detalle ON v_detalle.id = venta_detalle.venta_id")
				->leftJoin("coti_detalle ON coti_detalle.id = $this->table.registro")
				->leftJoin("producto AS prod_det_cot ON prod_det_cot.id = coti_detalle.producto_id")
				->leftJoin("cotizacion AS detalle_coti ON detalle_coti.id = coti_detalle.cotizacion_id")
				->leftJoin("prod_entrada ON prod_entrada.id = $this->table.registro")
				->leftJoin("venta_pago ON venta_pago.id = $this->table.registro")
				->leftJoin("venta AS v_pago ON v_pago.id = venta_pago.venta_id")
				->leftJoin("producto ON producto.id = $this->table.registro")
				->leftJoin("usuario AS user ON user.id = $this->table.registro")
				->leftJoin("prod_ajuste ON prod_ajuste.id = $this->table.registro")
				->leftJoin("producto AS ajuste_prod ON ajuste_prod.id = prod_ajuste.producto_id")
				->leftJoin("sucursal ON sucursal.id = $this->table.registro")
				->leftJoin("cotizacion ON cotizacion.id = $this->table.registro")
				->leftJoin("venta ON venta.id = $this->table.registro")
				->leftJoin("prod_precio ON prod_precio.id = $this->table.registro")
				->leftJoin("producto AS p_precio ON p_precio.id = prod_precio.producto_id")
				->leftJoin("prod_categoria ON prod_categoria.id = $this->table.registro")
				->leftJoin("prod_det_entrada ON prod_det_entrada.id = $this->table.registro")
				->leftJoin("producto AS det_ent_producto ON det_ent_producto.id = prod_det_entrada.producto_id")
				->leftJoin("prod_rango ON prod_rango.id = $this->table.registro")
				->leftJoin("producto AS p_rango ON p_rango.id = prod_rango.producto_id")
				->where("DATE_FORMAT($this->table.fecha, '%Y-%m-%d') BETWEEN '$desde' AND '$hasta'")
				->where("$this->table.sucursal_id", $_SESSION['sucursal_id'])
				->where("mostrar", 1)
				->fetchAll();

			return $result;
		}

        public function add($descripcion, $tabla, $registro, $mostrar=0){
			if (!isset($_SESSION)) {
				session_start();
			}
			if(isset($_SESSION['usuario_id'])){
                $user = $_SESSION['usuario_id'];
				$sesion = $_SESSION['logID'];
			}
	
			$data = array(
				'usuario_id ' => $user, 
				'seg_session_id ' => $sesion, 
				'sucursal_id' => $_SESSION['sucursal_id'], 
				'fecha' => date('Y-m-d H:i:s'), 
				'descripcion' => $descripcion, 
				'tabla' => $tabla, 
				'registro' => $registro, 
				'mostrar' => $mostrar);
			try {
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();

				if($this->response->result != 0){
					$this->response->SetResponse(true, 'id_seg_log del registro: '.$this->response->result);
				}
				else { $this->response->SetResponse(false, 'No se inserto el registro en el log'); }

			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, 'catch: add model seg_log');
			}

			return $this->response;
		}

		public function addByApp($descripcion, $tabla, $registro, $mostrar=0, $user, $sesion, $sucursal){
			$data = array(
				'usuario_id' => intval($user), 
				'seg_session_id' => intval($sesion), 
				'sucursal_id' => intval($sucursal), 
				'fecha' => date('Y-m-d H:i:s'), 
				'descripcion' => $descripcion, 
				'tabla' => $tabla, 
				'registro' => $registro, 
				'mostrar' => $mostrar);
			try {
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();

				if($this->response->result != 0){
					$this->response->SetResponse(true, 'id_seg_log del registro: '.$this->response->result);
				}
				else { $this->response->SetResponse(false, 'No se inserto el registro en el log'); }

			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, 'catch: add model seg_log');
			}

			return $this->response;
		}

		public function getSession($id){
			$session = $this->db
					->from($this->tableSession)
					->select(null)
					->select("$this->tableSession.ip_address, $this->tableSession.user_agent")
					->where("$this->tableSession.id = $id")
					->fetch();
			return $session;
		}
	}
?>