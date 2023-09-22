<?php
	namespace App\Model;
	use App\Lib\Response;

class ClienteSaldoModel {
	private $db;
	private $table = 'cliente_saldo';
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
            ->where("$this->table.id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$id); }

		return $this->response;
	}

  	public function getByCli($cliente_id){
        $this->response->result = $this->db
			->from($this->table)
            ->where("$this->table.cliente_id", $cliente_id)
            ->orderBy("id DESC")
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe saldo a favor para el cliente '.$cliente_id); }

		return $this->response;
    }

	public function add($data){
		try {
			$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
			$this->response->result = $SQL;
			if($this->response->result){
				$this->response->SetResponse(true, 'Registro agregado '); 
			} else {
				$this->response->SetResponse(false, 'No se pudo agregar el registro '); 
			}
		}catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: agregar saldo');
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
				else { $this->response->SetResponse(false, 'No se actualizo el registro '.$id); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar saldo');
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
			else { $this->response->SetResponse(false, 'No se eliminó el registro'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del saldo");
		}
		return $this->response;
	}
}
?>