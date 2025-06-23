<?php
	namespace App\Model;
	use App\Lib\Response;

class VentaModel {
	private $db;
	private $table = 'venta';
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
			->select("DATE_FORMAT(fecha, '%d%m%Y') as dateFolio")
            ->where("id", $id)
            ->fetch();
        return $this->response->SetResponse(true);
    }

	public function getAllDataTable($fecha){
		$this->response->result = $this->db
			->from($this->table)
			->select("sucursal.identificador, DATE_FORMAT(fecha, '%d-%m-%Y') as date, CAST(fecha AS TIME) as hora, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, CONCAT_WS(' ', cliente.nombre, cliente.apellidos) as cliente, cliente.saldo_favor as saldo")
			->where("tipo", 2)
			->where("$this->table.sucursal_id", $_SESSION['sucursal_id'])
			->where("DATE_FORMAT(fecha, '%Y-%m-%d') = '$fecha'")
			->where("$this->table.status", 2)
			->fetchAll();
		return $this->response->SetResponse(true);
	}

	public function getByMD5($venta_id) {
		return $this->db
			->from($this->table)
			->select("sucursal.identificador, DATE_FORMAT(fecha, '%d/%m/%Y') as date, CAST(fecha AS TIME) as hora, DATE_FORMAT(fecha, '%d%m%Y') as fechaFolio,
						CONCAT(cliente.nombre, ' ', cliente.apellidos, ', ', cliente.correo, ', ', cliente.telefono) AS cliente")
			->where("MD5($this->table.id)", $venta_id)
			->where("$this->table.status != 0")
			->fetch();
	}

	public function getAllByDay($dia){
		$this->response->result = $this->db
			->from($this->table)
			->select("sucursal.identificador, DATE_FORMAT(fecha, '%d-%m-%Y') as date, CAST(fecha AS TIME) as hora, 
						CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, 
						CONCAT_WS(' ', cliente.nombre, cliente.apellidos) AS cliente")
			->where("DATE_FORMAT(fecha, '%Y-%m-%d') = '$dia' OR DATE_FORMAT(fecha_finaliza, '%Y-%m-%d') = '$dia'")
			// ->where("tipo", 1)
			->where("$this->table.sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.status", 1)
			->fetchAll();
		if($this->response->result) return $this->response->SetResponse(true);
		else return $this->response->SetResponse(false, 'No hay visitas del día '.$dia);
	}

	public function getAll($desde, $hasta){
		$this->response->result = $this->db
			->from($this->table)
			->select("sucursal.identificador, CONCAT_WS(' ', cliente.nombre, cliente. apellidos) as cliente, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, 
					DATE_FORMAT(fecha, '%d-%m-%Y') as date, CAST(fecha AS TIME) as hora, DATE_FORMAT(fecha_finaliza, '%d-%m-%Y') as date_fin,
					tipo, total")
			->where("DATE_FORMAT(fecha, '%Y-%m-%d') BETWEEN '$desde' AND '$hasta'")
			->where("DATE_FORMAT(fecha_finaliza, '%Y-%m-%d') BETWEEN '$desde' AND '$hasta'")
			->where("$this->table.sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.status in(1,2)")
			->orderBy("$this->table.id DESC")
			->fetchAll();
		return $this->response->SetResponse(true);
	}

	public function getVentasMesAnio($year, $month) {
		$this->response = new Response();
		$this->response->result = $this->db
			->from($this->table)
			->select(NULL)->select("DATE_FORMAT($this->table.fecha, '%Y-%m-%d') AS fecha, SUM(total) AS total")
			->where("DATE_FORMAT($this->table.fecha, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha, '%m') = $month")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%m') = $month")
			->where("sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.status", 1)
			->groupBy("DATE_FORMAT($this->table.fecha, '%Y-%m-%d')")
			->fetchAll();

		$this->response->total = $this->db
			->from($this->table)
			->select(NULL)->select("COUNT($this->table.id) as total")
			->where("DATE_FORMAT($this->table.fecha, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha, '%m') = $month")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%m') = $month")
			->where("sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.status", 1)
			->fetch()
			->total;

		$this->response->contado = $this->db
			->from($this->table)
			->select(NULL)->select("COUNT($this->table.id) as contado")
			->where("DATE_FORMAT($this->table.fecha, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha, '%m') = $month")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%m') = $month")
			->where("sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.tipo", 1)
			->where("$this->table.status", 1)
			->fetch()
			->contado;

		$this->response->credito = $this->db
			->from($this->table)
			->select(NULL)->select("COUNT($this->table.id) as credito")
			->where("DATE_FORMAT($this->table.fecha, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha, '%m') = $month")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%m') = $month")
			->where("sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.tipo", 2)
			->where("$this->table.status", 1)
			->fetch()
			->credito;

		$this->response->general = $this->db
			->from($this->table)
			->select(NULL)->select("COUNT($this->table.id) as general")
			->where("DATE_FORMAT($this->table.fecha, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha, '%m') = $month")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%m') = $month")
			->where("sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.cliente_id", 1)
			->where("$this->table.status", 1)
			->fetch()
			->general;

		$this->response->frecuente = $this->db
			->from($this->table)
			->select(NULL)->select("COUNT($this->table.id) as frecuente")
			->where("DATE_FORMAT($this->table.fecha, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha, '%m') = $month")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%Y') = $year")
			->where("DATE_FORMAT($this->table.fecha_finaliza, '%m') = $month")
			->where("sucursal_id", $_SESSION['sucursal_id'])
			->where("$this->table.cliente_id != 1")
			->where("$this->table.status", 1)
			->fetch()
			->frecuente;

		return $this->response->SetResponse(true);
	}

	public function enUso($id){
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select('en_uso')
			->where("id", $id)
			->fetch();
		return $this->response->SetResponse(true);
	}

	public function desbloquear($id){
		try {
            $data['en_uso'] = 0;
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('id', $id)
				->execute();
			if($this->response->result) { $this->response->SetResponse(true); }
			else { $this->response->SetResponse(false, 'No se desbloqueó la venta'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: desbloquear venta");
		}
		return $this->response;
	}

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro de venta realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro de venta'); 
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
			else $this->response->SetResponse(false, 'No se pudo actualizar la venta');
		} catch(\PDOException $ex){
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: actualizar venta');
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
			else { $this->response->SetResponse(false, 'No se eliminó la venta'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del venta");
		}
		return $this->response;
	}

	public function getPendiente($cliente_id){
		$this->response->result = $this->db
			->from("$this->table")
			->select(null)
			->select("id, total")
			->where("sucursal_id", $_SESSION['sucursal_id'])
			->where("cliente_id", $cliente_id)
			->where("$this->table.tipo", 2)
			->where("$this->table.status", 2)
			->fetchAll();
		if($this->response->result) return $this->response->SetResponse(true);
		else return $this->response->SetResponse(false);
	}
}
?>