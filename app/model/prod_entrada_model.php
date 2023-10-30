<?php
	namespace App\Model;
	use App\Lib\Response;

class EntradaModel {
	private $db;
	private $table = 'prod_entrada';
	private $tableDet = 'prod_det_entrada';
	private $tableSuc = 'sucursal';
	private $tableProd = 'producto';
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
			->select(null)
			->select("DATE_FORMAT(fecha, '%d-%m-%Y') as date, CAST(fecha AS TIME) as hora, sucursal_id, folio, importe, descuento, subtotal, iva, total, sucursal.nombre")
			->where("$this->table.id", $id)
			->where("$this->table.status", 1)
			->fetch();
		return $this->response->SetResponse(true);
	}

	public function getAllDataTable(){
		$this->response->result = $this->db
			->from($this->table)
			->select("DATE_FORMAT(fecha, '%d-%m-%Y') as date, CAST(fecha AS TIME) as hora, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, sucursal.nombre")
			->innerJoin("sucursal ON sucursal.id = $this->table.sucursal_id")
			->innerJoin("usuario ON usuario.id = $this->table.usuario_id")
			->where("$this->table.sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.status", 1)
			->fetchAll();
		return $this->response;
	}

	public function getDet($id){
		$this->response->result = $this->db
			->from($this->tableDet)
			->select(null)
			->select("id, producto_id, cantidad, costo, total")
			->where("id", $id)
			->where("status", 1)
			->fetch();
		return $this->response->SetResponse(true);
	}

	public function getDetalles($id){
		$this->response->result = $this->db
			->from($this->tableDet)
			->select(null)
			->select("id, producto_id, cantidad, costo, total")
			->where("prod_entrada_id", $id)
			->where("status", 1)
			->fetchAll();
		return $this->response->SetResponse(true);
	}

	public function getLastCosto($sucursal_id, $producto_id){
		$SQL = $this->db
			->from($this->table)
			->select(null)
			->select("costo")
			->innerJoin("$this->tableDet ON $this->tableDet.prod_entrada_id = $this->table.id")
			->where("$this->table.sucursal_id", $sucursal_id)
			->where("$this->tableDet.producto_id", $producto_id)
			->fetch();
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true); 
		} else {
			$this->response->SetResponse(false); 
		}
			
		return $this->response;
	}

	public function add($data, $table){
		$SQL = $this->db
			->insertInto($table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Entrada de productos realizada.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar la entrada de productos.'); 
		}
			
		return $this->response;
	}

	public function edit($data, $id, $tabla){
		try{
			$this->response->result = $this->db
				->update($tabla, $data)
				->where("id", $id)
				->execute();

			if($this->response->result) { $this->response->SetResponse(true); }
			else { $this->response->SetResponse(false, 'No se actualizó el registro '.$id.' '.$tabla); }
		}catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar '.$tabla);
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
			else { $this->response->SetResponse(false, 'No se eliminó el registro '.$id.' '.$tabla); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: eliminar registro '.$tabla);
		}

		return $this->response;
	}
}
?>