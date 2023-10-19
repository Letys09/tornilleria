<?php
	namespace App\Model;
	use App\Lib\Response;

class SucursalModel {
	private $db;
	private $table = 'sucursal';
	private $tableDir = 'direccion';
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
            ->select("direccion_id, nombre, telefono, calle, no_ext, no_int, colonia, municipio, estado, codigo_postal")
            ->innerJoin("$this->tableDir ON $this->tableDir.id = $this->table.direccion_id")
			->where("$this->table.id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro '.$id); }

		return $this->response;
	}

    public function getAllDataTable() {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("$this->table.id, nombre, $this->tableDir.calle, $this->tableDir.no_ext, $this->tableDir.no_int, $this->tableDir.colonia, $this->tableDir.municipio, 
                    $this->tableDir.estado, $this->tableDir.codigo_postal, telefono")
			->where("status", 1)
			->fetchAll(); 
					
		return $this->response->SetResponse(true);
	}

	public function getAll(){
		$this->response->result = $this->db
			->from("$this->table")
			->select(null)
			->select("id, nombre")
			->where("status", 1)
			->fetchAll();
		return $this->response->setResponse(true);
	}

	public function getIdentificador(){
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("identificador")
			->where("status", 1)
			->orderBy("id DESC")
			->fetch();
		return $this->response->SetResponse(true);
	}

	public function add($data){

		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Sucursal agregada.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar la sucursal.'); 
		}
			
		return $this->response;
	}

	public function edit($data, $id){
		try {
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('id', $id)
				->execute();

				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'No se actualizo el registro del usuario '); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar sucursal');
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
			else { $this->response->SetResponse(false, 'No se eliminó lasucursal'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del sucursal");
		}
		return $this->response;
	}

    public function getDir($id) {
		$this->response->result = $this->db
			->from($this->tableDir)
            ->select(null)
            ->select("calle, no_ext, no_int, colonia, municipio, estado, codigo_postal")
			->where("id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe la dirección '.$id); }

		return $this->response;
	}

    public function addDir($data){
        		
		$SQL = $this->db
			->insertInto($this->tableDir, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Dirección agregada.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar la dirección de la sucursal.'); 
		}
			
		return $this->response;
	}

    public function editDir($data, $id){
		try {
			$this->response->result = $this->db
				->update($this->tableDir, $data)
				->where('id', $id)
				->execute();

				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'No se actualizo la dirección '.$id); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar dirección');
		}

		return $this->response;
	}

	public function cerrar(){
		session_unset();
		session_regenerate_id(true);
		session_destroy();

		return $this->response;
	}
	
}
?>