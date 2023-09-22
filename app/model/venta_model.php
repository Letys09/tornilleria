<?php
	namespace App\Model;
	use App\Lib\Response;

class VentaModel {
	private $db;
	private $table = 'venta';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

    public function get($id){
        $this->response->result = $this->db
            ->from($this->table)
            ->where("id", $id)
            ->fetch();
        return $this->response->SetResponse(true);
    }

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro de venta realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro de venta'); 
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
			else { $this->response->SetResponse(false, 'No se eliminó la venta'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del venta");
		}
		return $this->response;
	}
}
?>