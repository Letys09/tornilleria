<?php
	namespace App\Model;
	use App\Lib\Response;

class CotizacionModel {
	private $db;
	private $table = 'cotizacion';
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

	public function getAllDataTable(){
		$this->response->result = $this->db
			->from($this->table)
			->select("sucursal.identificador, DATE_FORMAT(fecha, '%d-%m-%Y') as date, CAST(fecha AS TIME) as hora, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, CONCAT_WS(' ', cliente.nombre, cliente.apellidos) as cliente")
			->where("$this->table.sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.status", 1)
			->fetchAll();
		return $this->response->SetResponse(true);
	}

	public function getByMD5($venta_id) {
		return $this->db
			->from($this->table)
			->select("sucursal.identificador, DATE_FORMAT(fecha, '%d/%m/%Y') as date, CAST(fecha AS TIME) as hora, DATE_FORMAT(fecha, '%d%m%Y') as fechaFolio")
			->where("MD5($this->table.id)", $venta_id)
			->where("$this->table.status != 0")
			->fetch();
	}

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro de cotización realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro de cotización'); 
		}
			
		return $this->response;
	}

	public function edit($data, $id){
		try {
			$this->response->result = $this->db
			->update($this->table, $data)
			->where('id', $id)
			->execute();

			if($this->response->result) $this->response->SetResponse(true);
			else $this->response->SetResponse(false, 'No se pudo actualizar la cotización');
		} catch(\PDOException $ex){
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: actualizar cotización');
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
			else { $this->response->SetResponse(false, 'No se eliminó la cotización'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del cotización");
		}
		return $this->response;
	}
}
?>