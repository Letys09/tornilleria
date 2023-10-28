<?php
	namespace App\Model;
	use App\Lib\Response;

class AjusteModel {
	private $db;
	private $table = 'prod_ajuste';
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
			->where("status", 1)
			->fetch();
		return $this->response->SetResponse(true);
	}

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Baja de productos realizada.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo dar de baja el inventario.'); 
		}
			
		return $this->response;
	}
}
?>