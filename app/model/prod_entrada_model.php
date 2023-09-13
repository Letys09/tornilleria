<?php
	namespace App\Model;
	use App\Lib\Response;

class EntradaModel {
	private $db;
	private $table = 'prod_entrada';
	private $tableDet = 'prod_det_entrada';
	private $tableSuc = 'sucursal';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

	public function getAllDataTable(){
		$this->response->result = $this->db
			->from($this->table)
			->select("DATE_FORMAT(fecha, '%d-%m-%Y') as date, CAST(fecha AS TIME) as hora, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, sucursal.nombre")
			->innerJoin("sucursal ON sucursal.id = $this->table.sucursal_id")
			->innerJoin("usuario ON usuario.id = $this->table.usuario_id")
			->where("$this->table.status", 1)
			->fetchAll();
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
}
?>