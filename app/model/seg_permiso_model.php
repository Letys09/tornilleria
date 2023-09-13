<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class SegPermisoModel {
		private $db;
		private $table = 'seg_permiso';
		private $tableA = 'seg_accion';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		public function get($id) {
			$this->response->result = $this->db
				->from($this->table)
				->where('id', $id)
				->fetch();

			if($this->response->result)	return $this->response->SetResponse(true);
			else	return $this->response->SetResponse(false, 'no existe el registro');
		}

		public function getByUsuario($usuario_id, $seg_modulo_id=0) {
			$this->response->result = $this->db
				->from($this->table)
				->leftJoin("$this->tableA on $this->tableA.id = seg_accion_id")
				->where("usuario_id", $usuario_id)
				->where("seg_modulo_id".($seg_modulo_id==0? ">": "=").$seg_modulo_id)
				->where('status', 1)
				->fetchAll();

			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->leftJoin("$this->tableA on $this->tableA.id = seg_accion_id")
				->where("usuario_id", $usuario_id)
				->where("seg_modulo_id".($seg_modulo_id==0? ">": "=").$seg_modulo_id)
				->where('status', 1)
				->fetch()
				->Total;

			return $this->response->SetResponse(true);
		}

		public function getAll() {
			$this->response->result = $this->db
				->from($this->table)
				->fetchAll();

			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->fetch()
				->Total;

			return $this->response->SetResponse(true);
		}

		public function add($data) {
			try {
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();

				if($this->response->result != 0) $this->response->SetResponse(true, 'id del registro: '.$this->response->result);    
				else { $this->response->SetResponse(false, 'no se inserto el registro'); }

			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: add model $this->table");
			}

			return $this->response;
		}

		public function edit($data, $id) {
			try {
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();

				if($this->response->result!=0) { $this->response->SetResponse(true, "id actualizado: $id"); }
				else { $this->response->SetResponse(false, 'no se edito el registro'); }

			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: edit model $this->table");
			}

			return $this->response;
		}

		public function del($id) {
			try {
				$this->response->result = $this->db
					->deleteFrom($this->table)
					->where('id_permiso', $id)
					->execute();

				if($this->response->result!=0)	$this->response->SetResponse(true, 'id eliminado: '.$id);
				else	$this->response->SetResponse(false, 'no se elimino el registro');

			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: del model.... $this->table");
			}

			return $this->response;
		}
		
		public function delUserAccion($usuario_id, $seg_accion_id) {
			try {
				$this->response->result = $this->db
					->deleteFrom($this->table)
					->where('usuario_id', $usuario_id)
					->where('seg_accion_id', $seg_accion_id)
					->execute();

				if($this->response->result!=0)	$this->response->SetResponse(true, 'id eliminado');
				else	$this->response->SetResponse(false, 'no se elimino el registro');

			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: del model $this->table");
			}

			return $this->response;
		}

		public function getPermisos($accionesModulo) {
			$this->response->result = $this->db
			  ->from($this->table)
			  ->where('usuario_id', $_SESSION['usuario_id'])
			  ->where('seg_accion_id in '.$accionesModulo)
			  ->orderBy('seg_accion_id')
			  ->fetchAll();
		
			  return $this->response->SetResponse(true);
		  }
	}
?>