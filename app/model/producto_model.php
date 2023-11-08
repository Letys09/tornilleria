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
	private $tableRango = 'prod_rango';
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
            ->select("$this->table.id, prod_unidad_medida_id, $this->table.prod_categoria_id, prod_categoria.prod_categoria_id as categoria, prod_area_id, clave, descripcion, medida, costo, minimo, venta, venta_kilo, es_kilo, clave_sat")
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
			->select("id, clave, descripcion, medida")
			->where("CONCAT_WS(' ', $this->table.descripcion, $this->table.clave, $this->table.medida) LIKE '%$param%'")
			->where("$this->table.es_kilo",0)
			->where("status", 1)
			->fetchAll();
		return $this->response->setResponse(true);
	}

	public function getProdsBy($param){
		$this->response->result = $this->db
			->from("$this->table")
			->select(null)
			->select("id, descripcion, clave, medida")
			->where("CONCAT_WS(' ', $this->table.descripcion, $this->table.clave, $this->table.medida) LIKE '%$param%'")
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
			->select("$this->table.descripcion, medida, $this->tableRango.prod_precio_id as id, $this->tableRango.menudeo, $this->tableRango.medio, $this->tableRango.mayoreo, $this->tablePrecio.menudeo as precio_menudeo, $this->tablePrecio.medio as precio_medio, $this->tablePrecio.mayoreo as precio_mayoreo, $this->tablePrecio.distribuidor as precio_distribuidor")
			->innerJoin("$this->tableRango ON $this->tableRango.producto_id = $this->table.id")
			->innerJoin("$this->tablePrecio ON $this->tablePrecio.id = $this->tableRango.prod_precio_id")
			->where($sub)
			->where($area)
			->where("$this->table.status", 1)
			->where("$this->tableRango.sucursal_id", $_SESSION['sucursal_id'])
			->orderBy("$this->table.descripcion ASC")
			->fetchAll();
		return $this->response;
	}

    public function getKiloBy($prod_id, $column) {
		$this->response->result = $this->db
			->from($this->tableKilo)
            ->select(null)
            ->select("$this->tableKilo.id, producto_id, producto_origen, cantidad")
			->where("$this->tableKilo.$column", $prod_id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$prod_id); }

		return $this->response;
	}

	public function getPrecios($id){
		$this->response->result = $this->db
			->from($this->tablePrecio)
            ->select(null)
            ->select("menudeo, medio, mayoreo, distribuidor")
			->where("id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$id); }

		return $this->response;
	}

	public function getRangos($suc_id, $prod_id){
		$this->response->result = $this->db
			->from($this->tableRango)
            ->select(null)
            ->select("$this->tableRango.id, $this->tableRango.prod_precio_id, $this->tableRango.menudeo, $this->tableRango.medio, $this->tableRango.mayoreo, prod_precio.menudeo as precio_menudeo, prod_precio.medio as precio_medio, prod_precio.mayoreo as precio_mayoreo, prod_precio.distribuidor as precio_distribuidor")
			->innerJoin("$this->tablePrecio ON $this->tablePrecio.id = $this->tableRango.prod_precio_id")
			->where("$this->tableRango.sucursal_id", $suc_id)
			->where("$this->tableRango.producto_id", $prod_id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$prod_id); }

		return $this->response;
	}

    public function getAllDataTable() {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			// ->select("$this->table.id, clave, sub.nombre as sub, cat.nombre as cat, descripcion, medida, minimo, es_kilo")
			->select("$this->table.id, clave, prod_area.nombre as area, descripcion, medida, minimo, es_kilo")
			->innerJoin("prod_categoria sub ON sub.id = $this->table.prod_categoria_id")
			->innerJoin("prod_categoria cat ON cat.id = sub.prod_categoria_id")
			// ->where("$this->table.es_kilo != 1")
			->where("$this->table.status", 1)
			->fetchAll(); 
					
		return $this->response->SetResponse(true);
	}

	public function getAllProdsVenta() {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("$this->table.id, clave, prod_area.nombre as area, descripcion, medida, minimo, es_kilo, venta")
			->innerJoin("prod_categoria sub ON sub.id = $this->table.prod_categoria_id")
			->innerJoin("prod_categoria cat ON cat.id = sub.prod_categoria_id")
			->where("$this->table.status", 1)
			// ->orderBy("venta DESC")
			->fetchAll(); 
					
		return $this->response->SetResponse(true);
	}

	public function getAllProds($busqueda, $sucursal, $inicial, $limite) {
		if($busqueda != '_') { $busqueda = array_reduce(array_filter(explode(' ', $busqueda), function($bus) { return strlen($bus) > 0; }), function($imp, $bus) { return $imp .= "+".str_replace('/', '_', $bus)."*"; }); }
		$tbl_name = "temporal_tbl_".time()."_".random_int(0, 999999);
		$this->response->result = $this->db->getPdo()->query("CALL tbl_busqueda('$busqueda', $sucursal, $inicial, $limite, '$tbl_name');")->fetchAll();
		$this->response->filtered = count($this->db->getPdo()->query("CALL tbl_busqueda('$busqueda', $sucursal, 0, 10000, '$tbl_name');")->fetchAll());

		$this->response->total =  $this->db->getPdo()->query(
			"SELECT COUNT(*) AS total
				FROM $this->table
				WHERE 
					status = 1;"
		)->fetch()->total;

		$this->response->busqueda = $busqueda; 

		return $this->response->SetResponse(true);
	}

	public function getUnidades(){
		$this->response->result = $this->db
			->from("prod_unidad_medida")
			->where("clave != 'KGM'")
			->where("status", 1)
			->fetchAll();
		return $this->response->SetResponse(true);
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

	public function getLast(){
		$this->response->result = $this->db
			->from($this->table)
			->where('status', 2)
			->fetch();
		return $this->response->SetResponse(true);
	}

	public function getProductos(){
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("id, clave, descripcion, medida, minimo")
			->where("es_kilo", 0)
			->where("status", 1)
			->orderBy('clave')
			->fetchAll();
		return $this->response->SetResponse(true);
	}

	public function getProdsMesAnio($year, $month){
		$this->response->result = $this->db
		->from($this->table)
		->select(NULL)
		->select("clave, venta")
		->innerJoin("venta_detalle ON venta_detalle.producto_id = producto.id")
		->where("DATE_FORMAT(venta_detalle.fecha, '%Y') = '$year'")
		->where("DATE_FORMAT(venta_detalle.fecha, '%m') = '$month'")
		->groupBy("clave")
		->orderBy("$this->table.venta DESC")
		->limit(10)
		->fetchAll();

		return $this->response->SetResponse(true);
	}

	// Obtener todos los productos app
	public function getAllApp($pagina, $limite, $busqueda) {
		$inicial = $pagina * $limite;
		$busqueda = $busqueda==null ? "_" : $busqueda;
		$resultado = $this->db
			->from($this->table)
			->select(null)->select("$this->table.id, $this->table.clave, $this->table.descripcion, $this->table.medida")
			->where("CONCAT_WS(' ', $this->table.clave, $this->table.descripcion, $this->table.medida) LIKE '%$busqueda%'")
			->where("$this->table.es_kilo", 0)
			->where("$this->table.status", 1)
			->limit("$inicial, $limite")
			->orderBy("$this->table.descripcion ASC")
			->fetchAll();
		$this->response->result = $resultado;
		return $this->response->SetResponse(true);
	}

	public function getProductoByCode($clave){
		$this->response->result = $this->db
			->from($this->table)
			->where("$this->table.clave", $clave)
			->where("$this->table.status", "1")
			->fetch();
		if($this->response->result) { 
			$this->response->SetResponse(true);
		}else {
			$this->response->SetResponse(false, 'No existe un producto con esa clave');
		}
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
}
?>