<?php
	namespace App\Model;
	use App\Lib\Response;

class ProductoModel {
	private $db;
	private $table = 'producto';
	private $tableCat = 'prod_categoria';
	private $tableArea = 'prod_area';
	private $tableKilo = 'prod_kilo';
	private $tablePrecio = 'prod_precio';
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
            ->select("$this->table.id, $this->table.prod_categoria_id, prod_categoria.prod_categoria_id as categoria, prod_area_id, $this->table.nombre, descripcion, codigo, marca, costo, minimo, venta_kilo, es_kilo, menudeo, medio, mayoreo")
            ->innerJoin("prod_categoria ON prod_categoria.id = $this->table.prod_categoria_id ")
			->where("$this->table.id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro producto '.$id); }

		return $this->response;
	}

	public function getBy($param){
		$this->response->result = $this->db
			->from("$this->table")
			->select(null)
			->select("id, nombre, descripcion, codigo, marca")
			->where("CONCAT_WS(' ', $this->table.nombre, $this->table.descripcion, $this->table.codigo, $this->table.marca) LIKE '%$param%'")
			->where("$this->table.es_kilo",0)
			->where("status", 1)
			->fetchAll();
		return $this->response->setResponse(true);
	}

	public function getAll($cat, $sub, $area){
		if($cat == 0){
			$sub = true;
		}else if($sub == 0){
			$categorias = $this->db
				->from($this->tableCat)
				->select(null)
				->select("id")
				->where("prod_categoria_id", $cat)
				->fetchAll();

			foreach($categorias as $categoria){
				$arrayCat[] = $categoria->id;
			}
			$sub = "$this->table.prod_categoria_id in(". implode(",", $arrayCat).")";
		}else $sub = "$this->table.prod_categoria_id = ".$sub;

		if($area != 0) $area = "$this->table.prod_area_id = $area";
		else $area = true;
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("$this->table.id, $this->table.nombre, $this->tablePrecio.menudeo, $this->tablePrecio.medio, $this->tablePrecio.mayoreo, $this->tablePrecio.distribuidor")
			->innerJoin("$this->tablePrecio ON $this->tablePrecio.producto_id = $this->table.id")
			->where($sub)
			->where($area)
			->where("$this->table.es_kilo", 0)
			->where("$this->table.status", 1)
			->orderBy("$this->table.nombre ASC")
			->fetchAll();
		return $this->response;
	}

    public function getKiloBy($prod_id, $column) {
		$this->response->result = $this->db
			->from($this->tableKilo)
            ->select(null)
            ->select("id, producto_id, producto_origen, cantidad, precio")
			->where("$this->tableKilo.$column", $prod_id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$prod_id); }

		return $this->response;
	}

    public function getPrecios($prod_id) {
		$this->response->result = $this->db
			->from($this->tablePrecio)
            ->select(null)
            ->select("id, prod_precio.menudeo, prod_precio.medio, prod_precio.mayoreo, prod_precio.distribuidor")
			->where("$this->tablePrecio.producto_id", $prod_id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$prod_id); }

		return $this->response;
	}

    public function getAllDataTable() {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("$this->table.id, codigo, sub.nombre as sub, cat.nombre as cat, $this->table.nombre, marca, minimo")
			->innerJoin("prod_categoria sub ON sub.id = $this->table.prod_categoria_id")
			->innerJoin("prod_categoria cat ON cat.id = sub.prod_categoria_id")
			->where("$this->table.es_kilo != 1")
			->where("$this->table.status", 1)
			->fetchAll(); 
					
		return $this->response->SetResponse(true);
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
				$this->response->SetResponse(false, 'No se pudo agregar el registro.'); 
			}
		}catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: agregar registro');
		}
			
		return $this->response;
	}

	public function edit($table, $campo, $data, $id){
		try {
			$this->response->result = $this->db
				->update($table, $data)
				->where($campo, $id)
				->execute();

				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'No se actualizó el registro del producto '); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar Producto');
		}

		return $this->response;
	}

	public function del($table, $id) {
		try {
            $data['status'] = 0;
			$this->response->result = $this->db
				->update($table, $data)
				->where('id', $id)
				->execute();
			if($this->response->result) { $this->response->SetResponse(true); }
			else { $this->response->SetResponse(false, 'No se eliminó el Producto'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del Producto");
		}
		return $this->response;
	}

    public function getCodigo(){
        $this->response->result = $this->db
            ->from("$this->table")
            ->select(null)
            ->select('id')
            ->orderBy('id DESC')
            ->fetch();
        return $this->response;
    }

    public function getCat(){
        $this->response->result = $this->db
            ->from("$this->tableCat")
            ->select(null)
            ->select('id, nombre')
            ->where('prod_categoria_id IS NULL')
            ->where("status", 1)
            ->fetchAll();
        return $this->response;
    }

	public function getByName($table, $column, $param){
		$this->response->result = $this->db
			->from($table)
			->where("$column = '$param'")
			->where("status", 1)
			->orderBy("id DESC")
			->fetch();
		return $this->response->SetResponse(true);
	}

    public function getSubC($cat){
        $this->response->result = $this->db
            ->from("$this->tableCat")
            ->select(null)
            ->select('id, nombre')
            ->where('prod_categoria_id', $cat)
            ->where("status", 1)
            ->fetchAll();
        return $this->response;
    }

    public function getArea(){
        $this->response->result = $this->db
            ->from("$this->tableArea")
            ->select(null)
            ->select('id, nombre')
            ->where("status", 1)
            ->fetchAll();
        return $this->response;
    }

	public function getAllSub(){
        $this->response->result = $this->db
            ->from("$this->tableCat")
            ->select(null)
            ->select('id, nombre')
            ->where('prod_categoria_id IS NOT NULL')
            ->where("status", 1)
            ->fetchAll();
        return $this->response;
    }
}
?>