<?php
	namespace App\Model;
	use App\Lib\Response;

class VentaDetModel {
	private $db;
	private $table = 'venta_detalle';
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

    public function getByVenta($venta_id){
        $this->response->result = $this->db
            ->from($this->table)
            ->where("venta_id", $venta_id)
            ->where("status", 1)
            ->fetchAll();
        return $this->response->SetResponse(true);
    }

	public function getBy($column, $dato){
		$this->response->result = $this->db
			->from($this->table)
			->where($column, $dato)
			->where("status", 1)
			->fetch();
		return $this->response->SetResponse(true);
	}

	public function getAll($desde, $hasta){
		$this->response->result = $this->db
		->from($this->table)
		->select(null)
		->select("DATE_FORMAT($this->table.fecha, '%d-%m-%Y') as date, CAST($this->table.fecha AS TIME) as hora, venta_id, cantidad, 
				  CONCAT_WS(' ', producto.clave, producto.descripcion) as producto, DATE_FORMAT(venta.fecha, '%d%m%Y') as venta_fecha, 
				  CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, 
				  CONCAT_WS(' ', cliente.nombre, cliente.apellidos) as cliente")
		->innerJoin("venta ON venta.id = venta_detalle.venta_id")
		->innerJoin("usuario ON usuario.id = venta.usuario_id")
		->innerJoin("cliente ON cliente.id = venta.cliente_id")
		->where("DATE_FORMAT($this->table.fecha, '%Y-%m-%d') BETWEEN '$desde' AND '$hasta'")
		->where("venta.sucursal_id", $_SESSION['sucursal_id'])
		->where("venta.status", 1)
		->where("$this->table.status", 1)
		->fetchAll();
	return $this->response->SetResponse(true);
	}

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro detalle de venta realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro detalle de venta'); 
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
				else { $this->response->SetResponse(false, 'No se actualizó el registro del detalle de venta'); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar detalle de venta');
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
			else { $this->response->SetResponse(false, 'No se eliminó el detalle de venta'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del detalle de venta");
		}
		return $this->response;
	}
}
?>