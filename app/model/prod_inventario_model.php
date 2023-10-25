<?php
	namespace App\Model;
	use App\Lib\Response;

class ProdInvModel {
	private $db;
	private $table = 'prod_inventario';
	private $tableProdDetInv = 'prod_det_inventario';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

    public function get($id){
        $resultado = $this->db
            ->from($this->table)
            ->where("id", $id)
            ->fetch();
		if($resultado){
			$this->response->result = $resultado;
			$this->response->SetResponse(true); 
		} else {
			$this->response->SetResponse(false);
		}
		return $this->response;
    }

    public function getByMd5($id){
        $resultado = $this->db
            ->from($this->table)
			->select("DATE_FORMAT(fecha, '%d-%m-%Y') AS date, DATE_FORMAT(fecha, '%d%m%Y') AS date2, CAST(fecha AS TIME) AS hora")
            ->where("MD5(id)", $id)
            ->fetch();
		if($resultado){
			$this->response->result = $resultado;
			$this->response->SetResponse(true); 
		} else {
			$this->response->SetResponse(false);
		}
		return $this->response;
    }

    public function getAllDataTable($sucursal_id, $desde, $hasta) {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("$this->table.id, estado_inventario, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, DATE_FORMAT(fecha, '%d-%m-%Y') as fecha, CAST(fecha AS TIME) as hora")
            ->where("DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN '$desde' AND '$hasta'")
			->where("$this->table.sucursal_id", $sucursal_id)
			->where("$this->table.status", 1)
			->fetchAll(); 
					
		return $this->response->SetResponse(true);
	}

	// verificar si existe inventario fisico abierto
	public function getInventarioActivo($sucursal_id){
        $resultado = $this->db
            ->from($this->table)
            ->where("sucursal_id", $sucursal_id)
			->where("estado_inventario", 2)
			->where("status", 1)
            ->fetch();
		if($resultado){
			$this->response->result = $resultado;
			$this->response->SetResponse(true, 'Inventario ya fue abierto'); 
		} else {
			$this->response->SetResponse(false, 'No existe inventario abierto');
		}
		return $this->response;
    }

	public function getCheckInventario($suc_id, $prod_id){
        $this->response->result = $this->db
            ->from($this->table)
			->select(null)
			->select("$this->tableProdDetInv.check_inventario")
			->innerJoin("$this->tableProdDetInv ON $this->tableProdDetInv.prod_inventario_id = $this->table.id")
            ->where("$this->tableProdDetInv.producto_id", $prod_id)
            ->where("$this->table.sucursal_id", $suc_id)
            ->where("$this->table.status", 1)
			->orderBy("$this->tableProdDetInv.id DESC")
            ->fetch();
        if($this->response->result) { 
			$this->response->SetResponse(true); 
		}else { 
			$this->response->SetResponse(false, 'No existe registro');
		}

        return $this->response;
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

	// Modificar prod_inventario
	public function edit($data, $id) {
		date_default_timezone_set('America/Mexico_City');
		try{
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('id', $id)
				->execute();
			if($this->response->result!=0) { 
				$this->response->SetResponse(true, "id actualizado: $id"); 
			}else { 
				$this->response->SetResponse(false, 'No se edito la asistencia'); 
			}
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: Edit model $this->table");
		}
		return $this->response;
	}
}
?>