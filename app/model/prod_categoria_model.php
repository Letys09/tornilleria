<?php
	namespace App\Model;
	use App\Lib\Response;

class ProdCategoriaModel {
	private $db;
	private $table = 'prod_categoria';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

    public function get($id){
        $this->response->result = $this->db
            ->from("$this->table")
            ->select(null)
            ->select('id, nombre')
            ->where("id", $id)
            ->where("status", 1)
            ->fetch();
        return $this->response;
    }

    public function getCat(){
        $this->response->result = $this->db
            ->from("$this->table")
            ->select(null)
            ->select('id, nombre')
            ->where('prod_categoria_id IS NULL')
            ->where("status", 1)
			->orderBy("nombre ASC")
            ->fetchAll();
        return $this->response;
    }

    public function getSubC($cat){
        $this->response->result = $this->db
            ->from("$this->table")
            ->select(null)
            ->select('id, nombre')
            ->where('prod_categoria_id', $cat)
            ->where("status", 1)
            ->fetchAll();
        return $this->response;
    }

	public function getAllSub(){
        $this->response->result = $this->db
            ->from("$this->table")
            ->select(null)
            ->select('id, nombre')
            ->where('prod_categoria_id IS NOT NULL')
            ->where("status", 1)
            ->fetchAll();
        return $this->response;
    }

	public function add($data, $table){
		try {
			$SQL = $this->db
			->insertInto($table, $data)
			->execute();
		
			$this->response->result = $SQL;
			if($this->response->result){
				$this->response->SetResponse(true, 'Registro agregado.'); 
			} else {
				$this->response->SetResponse(false, 'No se pudo agregar el registro '.$table); 
			}
		}catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: agregar registro');
		}
			
		return $this->response;
	}

	public function edit($data, $id){
		try {
			$this->response->result = $this->db
				->update($this->table, $data)
				->where("id", $id)
				->execute();

				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'No se actualizó el registro del producto '); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar Producto');
		}

		return $this->response;
	}
}
?>