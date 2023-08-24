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

        public function add($descripcion, $registro, $tipo){
			if (!isset($_SESSION)) {
				session_start();
			}
			if(isset($_SESSION['user'])){
                $user = $_SESSION['user'];
				$sesion = $_SESSION['logID'];
                $enterprise = $_SESSION['enterprise'];
			}
	
			$data = array(
				'fk_id_usuario' => $user, 
				'fk_session' => $sesion, 
				'fecha' => date('Y-m-d H:i:s'), 
				'descripcion' => $descripcion, 
				'registro' => $registro, 
				'tipo' => $tipo,
				'fk_empresarial' => $enterprise);
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
					->select("$this->table.fecha, $this->table.fk_session, $this->table.fk_id_usuario, $this->table.descripcion, $this->table.registro, $this->tableUsuario.login")
					->leftJoin($this->tableUsuario.' on '.$this->tableUsuario.'.id_usuario ='.$this->table.'.fk_id_usuario')
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