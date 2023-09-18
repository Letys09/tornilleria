<?php
	namespace App\Model;
	use App\Lib\Response;

class ClienteModel {
	private $db;
	private $table = 'cliente';
	private $regimen = 'regimen_fiscal';
	private $fiscales = 'cli_datos_fiscales';
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
            ->select("$this->table.id, $this->table.cli_datos_fiscales_id, nombre, apellidos, correo, telefono, descuento, cli_datos_fiscales.regimen_fiscal, rfc, razon_social, codigo_postal")
			->innerJoin("cli_datos_fiscales ON cli_datos_fiscales.id = $this->table.cli_datos_fiscales_id")
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
			->select("id, nombre, apellidos, correo, telefono, descuento, saldo_favor")
			->where("$this->table.status", 1)
			->fetchAll(); 
					
		return $this->response->SetResponse(true);
	}

	public function regimen() {
		$this->response->result = $this->db
			->from($this->regimen)
			->fetchAll();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No hay registros'); }

		return $this->response;
	}

	public function getFiscales($id) {
		$this->response->result = $this->db
			->from($this->fiscales)
            ->select(null)
            ->select("rfc, razon_social, codigo_postal, regimen_fiscal")
			->where("$this->fiscales.id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$id); }

		return $this->response;
	}

	public function add($data, $table){
		try {
			$SQL = $this->db
			->insertInto($table, $data)
			->execute();
		
			$this->response->result = $SQL;
			if($this->response->result){
				$this->response->SetResponse(true, 'Registro agregado '.$table); 
			} else {
				$this->response->SetResponse(false, 'No se pudo agregar el registro '.$table); 
			}
		}catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: agregar cliente');
		}
			
		return $this->response;
	}

	public function edit($data, $id, $table){
		try {
			$this->response->result = $this->db
				->update($table, $data)
				->where('id', $id)
				->execute();

				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'No se actualizo el registro '.$table.' '.$id); }

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