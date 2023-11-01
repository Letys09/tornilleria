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
						  IF($this->table.tabla = 'venta_detalle', CONCAT_WS(' ', prod_det.clave, prod_det.descripcion), 
						  IF($this->table.tabla = 'producto', CONCAT_WS(' ', producto.clave, producto.descripcion), 
						  IF($this->table.tabla = 'venta_pago', CONCAT('Folio venta',' ', suc.identificador, '-', DATE_FORMAT(venta_p.fecha, '%d%m%Y'), '-',
						  						   venta_pago.venta_id, ',  ', 'Monto $', venta_pago.monto), 
						  IF($this->table.tabla = 'sucursal', sucursal.nombre, 
						  IF($this->table.tabla = 'usuario', CONCAT_WS(' ', usuario.nombre, usuario.apellidos), 
						  IF($this->table.tabla = 'prod_ajuste', CONCAT('Producto',' ', prod_a.clave, ' ', prod_a.descripcion, ', ', 'Cantidad', ' ',
						  						   prod_ajuste.cantidad, ', ', 'Comentarios', ' ', prod_ajuste.comentarios), 
						  IF($this->table.tabla = 'venta', CONCAT('Folio venta',' ', suc_venta.identificador, '-', DATE_FORMAT(venta.fecha, '%d%m%Y'), '-', venta.id), 
						  IF($this->table.tabla = 'prod_inventario', CONCAT('Folio venta',' ', suc_venta.identificador, '-', DATE_FORMAT(venta.fecha, '%d%m%Y'), '-', venta.id), 
						  ' '))))))))) as adicional						  
						")
				->leftJoin("cliente ON cliente.id = $this->table.registro")
				->leftJoin("venta_detalle ON venta_detalle.id = $this->table.registro")
				->leftJoin("producto AS prod_det ON prod_det.id = venta_detalle.producto_id")
				->leftJoin("producto ON producto.id = $this->table.registro")
				->leftJoin("venta_pago ON venta_pago.id = $this->table.registro")
				->leftJoin("sucursal AS suc ON suc.id = $this->table.sucursal_id")
				->leftJoin("venta AS venta_p ON venta_p.id = venta_pago.venta_id")
				->leftJoin("sucursal ON sucursal.id = $this->table.registro")
				->leftJoin("prod_ajuste ON prod_ajuste.id = $this->table.registro")
				->leftJoin("producto AS prod_a ON prod_a.id = prod_ajuste.producto_id")
				->leftJoin("venta ON venta.id = $this->table.registro")
				->leftJoin("sucursal AS suc_venta ON suc_venta.id = venta.sucursal_id")
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

		public function addByApp($descripcion, $tabla, $registro, $mostrar=0, $user, $sesion){
			$data = array(
				'usuario_id ' => intval($user), 
				'seg_session_id ' => intval($sesion), 
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