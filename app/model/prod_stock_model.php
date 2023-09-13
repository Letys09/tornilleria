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