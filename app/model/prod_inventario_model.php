<?php
	namespace App\Model;
	use App\Lib\Response;

class ProdInvModel {
	private $db;
	private $table = 'prod_inventario';
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

    public function getAllDataTable($desde, $hasta) {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("$this->table.id, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, DATE_FORMAT(fecha, '%d-%m-%Y') as fecha, CAST(fecha AS TIME) as hora")
            ->where("DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN '$desde' AND '$hasta'")
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
			$this->response->SetResponse(true, 'Registro de inventario realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro de inventario'); 
		}
			
		return $this->response;
	}
}
?>