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


		public function getLog($from, $to){
			$result = $this->db
					->from($this->table)
					->select(null)
					->select("$this->table.fecha, $this->table.seg_session_id , $this->table.usuario_id , $this->table.descripcion, $this->table.registro, $this->tableUsuario.login")
					->leftJoin($this->tableUsuario.' ON '.$this->tableUsuario.'.id ='.$this->table.'.usuario_id ')
					->where("DATE_FORMAT($this->table.fecha, '%Y-%m-%d') BETWEEN '$from' AND '$to'")
					->fetchAll();

			return $result;
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