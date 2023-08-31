<?php
	namespace App\Model;
	use App\Lib\Response;

class ClienteModel {
	private $db;
	private $table = 'cliente';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

    public function get($id) {
		$this->response->result = $this->db
			->from($this->table)
            ->select(null)
            ->select("id, nombre, apellidos, correo, telefono")
			->where("$this->table.id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$id); }

		return $this->response;
	}

    public function getAllDataTable() {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("id, nombre, apellidos, correo, telefono, saldo_favor")
			->where("$this->table.status", 1)
			->fetchAll(); 
					
		return $this->response->SetResponse(true);
	}

	public function add($data){
		try {
            $data['registro'] = date('Y-m-d H:i:s');
			$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
			$this->response->result = $SQL;
			if($this->response->result){
				$this->response->SetResponse(true, 'Cliente agregado.'); 
			} else {
				$this->response->SetResponse(false, 'No se pudo agregar al cliente.'); 
			}
		}catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: agregar cliente');
		}
			
		return $this->response;
	}

	public function edit($data, $id){
		try {
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('id', $id)
				->execute();

				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'No se actualizo el registro del producto '); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar Producto');
		}

		return $this->response;
	}

	public function del($id) {
		try {
            $data['status'] = 0;
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('id', $id)
				->execute();
			if($this->response->result) { $this->response->SetResponse(true); }
			else { $this->response->SetResponse(false, 'No se eliminó al cliente'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del cliente");
		}
		return $this->response;
	}
}
?>