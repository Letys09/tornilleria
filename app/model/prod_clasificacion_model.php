<?php
	namespace App\Model;
	use App\Lib\Response;

class ProdClasModel {
	private $db;
	private $tableArea = 'prod_area';
	private $tableCategoria = 'prod_categoria';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

    public function getAreas(){
        $this->response->result = $this->db
            ->from($this->tableArea)
            ->where("status", 1)
            ->orderBy("nombre ASC")
            ->fetchAll();
        if($this->response->result) return $this->response->SetResponse(true);
        else return $this->response->SetResponse(false, 'No hay áreas registradas');
    }

    public function getCategorias(){
        $this->response->result = $this->db
            ->from("$this->tableCategoria")
            ->select(null)
            ->select('id, nombre')
            ->where('prod_categoria_id IS NULL')
            ->where("status", 1)
			->orderBy("nombre ASC")
            ->fetchAll();
        return $this->response;
    }

    public function getSubcategorias($categoria){
        $this->response->result = $this->db
            ->from("$this->tableCategoria")
            ->select(null)
            ->select('id, nombre')
            ->where('prod_categoria_id', $categoria)
            ->where("status", 1)
			->orderBy("nombre ASC")
            ->fetchAll();
        return $this->response;
    }

	// SELECT DISTINCT
	// 	cat.id as categoria,
	// 	cat.nombre as cat_nombre,
	// 	prod_area_id
	// FROM producto
	// INNER JOIN prod_categoria ON prod_categoria.id = producto.prod_categoria_id
	// INNER JOIN prod_categoria AS cat ON cat.id = prod_categoria.prod_categoria_id
	// WHERE prod_area_id = 1
	// GROUP BY producto.prod_categoria_id, cat.nombre;
	public function getCatByArea($area_id){
		$this->response->result = $this->db
			->from("producto")
			->select(null)
			->select("DISTINCT cat.id as categoria_id, cat.nombre as cat_nombre")
			->innerJoin("prod_categoria ON prod_categoria.id = producto.prod_categoria_id")
			->innerJoin("prod_categoria AS cat ON cat.id = prod_categoria.prod_categoria_id")
			->where("prod_area_id", $area_id)
			->groupBy("producto.prod_categoria_id")
			->fetchAll();
		if($this->response->result) return $this->response->SetResponse(true);
		else return $this->response->SetResponse(false, 'No hay categorías registradas en el pasillo');
	}

	public function add($data, $table){
		$SQL = $this->db
			->insertInto($table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro.'); 
		}
			
		return $this->response;
	}

    public function del($id, $tabla){
        try {
            $data['status'] = 0;
			$this->response->result = $this->db
				->update($tabla, $data)
				->where('id', $id)
				->execute();
			if($this->response->result) { $this->response->SetResponse(true); }
			else { $this->response->SetResponse(false, 'No se eliminó el registro'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del clasificación");
		}
		return $this->response;
    }
}
?>