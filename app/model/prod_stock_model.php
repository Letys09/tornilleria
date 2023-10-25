<?php
	namespace App\Model;
	use App\Lib\Response;

class StockModel {
	private $db;
	private $table = 'prod_stock';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

	public function getStock($suc_id, $prod_id){
        $this->response->result = $this->db
            ->from($this->table)
            ->select("DATE_FORMAT(fecha, '%d/%m/%Y') as date")
            ->where("sucursal_id = $suc_id")
            ->where('producto_id', $prod_id)
            ->where("$this->table.status", 1)
            ->orderBy('id DESC')
            ->fetch();

        if($this->response->result) { $this->response->SetResponse(true); }
        else { $this->response->SetResponse(false, 'No existe registro del producto '.$prod_id.' en la sucursal '.$suc_id); }

        return $this->response;
    }

	public function getAllDataTable($id, $desde, $hasta){
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("$this->table.id, tipo, inicial, cantidad, final, DATE_FORMAT(fecha, '%d-%m-%Y') as fecha, CAST(fecha AS TIME) as hora, motivo, origen_tipo, origen_id, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario")
			->where("$this->table.sucursal_id", $_SESSION['sucursal_id'])
			->where("producto_id", $id)
			->where("DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN '$desde' AND '$hasta'")
			->where("$this->table.status", 1)
			->orderBy("fecha DESC")
			->fetchAll();
		return $this->response->SetResponse(true);
	}

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro de stock realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro del stock.'); 
		}
			
		return $this->response;
	}
}
?>