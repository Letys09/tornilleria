<?php
	namespace App\Model;
	use App\Lib\Response;

class ProdDetInvModel {
	private $db;
	private $table = 'prod_det_inventario';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

    public function get($inventario_id){
        $this->response->result = $this->db
            ->from($this->table)
            ->where("prod_inventario_id", $inventario_id)
            ->fetchAll();
        return $this->response->SetResponse(true);
    }

	public function getAll(){
		$this->response->result = $this->db
			->from($this->table)
			->fetchAll();
		return $this->response;
	}

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro detalle de inventario realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el detalle de inventarioa'); 
		}
			
		return $this->response;
	}

	// Modificar prod_det_inventario
	public function edit($data, $prod_inventario_id) {
		date_default_timezone_set('America/Mexico_City');
		try{
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('prod_inventario_id', $prod_inventario_id)
				->execute();
			if($this->response->result!=0) { 
				$this->response->SetResponse(true, "Check inventario actualizado"); 
			}else { 
				$this->response->SetResponse(false, 'No se edito'); 
			}
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: Edit model $this->table");
		}
		return $this->response;
	}

	public function ajusteCostoInventario($data, $prod_det_inventario_id) {
		date_default_timezone_set('America/Mexico_City');
		try{
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('id', $prod_det_inventario_id)
				->execute();
			if($this->response->result!=0) { 
				$this->response->SetResponse(true, "Registro actualizado"); 
			}else { 
				$this->response->SetResponse(false, 'No se editó el registro'); 
			}
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: ajusteCostoInventario model $this->table");
		}
		return $this->response;
	}
}
?>