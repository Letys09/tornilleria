<?php
	namespace App\Model;
	use App\Lib\Response;

class CotiDetalleModel {
	private $db;
	private $table = 'coti_detalle';
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

    public function getByCot($cot_id){
        $this->response->result = $this->db
            ->from($this->table)
            ->select(null)
            ->select("id, producto_id, cantidad, precio, importe, descuento, total")
			->where("cotizacion_id", $cot_id)
			->where("status", 1)
			->fetchAll();
		return $this->response->SetResponse(true);
    }

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro de detalle realizado cotización'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro de detalle cotización'); 
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
				else { $this->response->SetResponse(false, 'No se actualizó el registro del detalle de cotización'); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar detalle de cotización');
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
			else { $this->response->SetResponse(false, 'No se eliminó el detalle de la cotizaación'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del detalle cotización");
		}
		return $this->response;
	}
}
?>