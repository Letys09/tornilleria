<?php
	namespace App\Model;
	use App\Lib\Response;

class VentaPagoModel {
	private $db;
	private $table = 'venta_pago';
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
			->select("DATE_FORMAT(fecha, '%d-%m-%Y') as date, CAST(fecha AS time) as hora, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario")
            ->where("venta_id", $venta_id)
            ->where("$this->table.status", 1)
            ->fetchAll();
        return $this->response->SetResponse(true);
    }

	public function getTotal($venta_id){
		$total = $this->db
			->from($this->table)
			->select(null)
			->select("SUM(monto) as total")
			->where("venta_id", $venta_id)
			->where("status", 1)
			->fetch();
		if($total) {
			$this->response->result = $total;
			$this->response->SetResponse(true);
		}else $this->response->SetResponse(false, 'No hay pagos en la venta');

		return $this->response; 
	}

	public function getAll($desde, $hasta){
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("DATE_FORMAT($this->table.fecha, '%d-%m-%Y') as date, CAST($this->table.fecha AS TIME) as hora, venta_id, forma_pago, monto,
					  DATE_FORMAT(venta.fecha, '%d%m%Y') as venta_fecha, CONCAT_WS(' ', usuario.nombre, usuario.apellidos) as usuario, 
					  CONCAT_WS(' ', cliente.nombre, cliente.apellidos) as cliente")
			->innerJoin("venta ON venta.id = venta_pago.venta_id")
			->innerJoin("usuario ON usuario.id = venta_pago.usuario_id")
			->innerJoin("cliente ON cliente.id = venta.cliente_id")
			->where("DATE_FORMAT($this->table.fecha, '%Y-%m-%d') BETWEEN '$desde' AND '$hasta'")
			->where("venta.sucursal_id", $_SESSION['sucursal_id'])
			->where("venta.status", 1)
			->where("$this->table.status", 1)
			->fetchAll();
		return $this->response->SetResponse(true);
	}

	public function getPagosMesAnio($year, $month){
		$this->response->result = $this->db
		->from($this->table)
		->select(NULL)
		->select("forma_pago,
				  CASE 
					WHEN forma_pago = 1 THEN 'Efectivo' 
					WHEN forma_pago = 3 THEN 'Tarjeta'
					WHEN forma_pago = 4 THEN 'Transferencia'
					ELSE ''
				  END AS metodo, 
				  SUM(monto) AS monto")
		->innerJoin("venta ON venta.id = venta_pago.venta_id")
		->where("DATE_FORMAT($this->table.fecha, '%Y') = '$year'")
		->where("DATE_FORMAT($this->table.fecha, '%m') = '$month'")
		->where("venta.sucursal_id", $_SESSION['sucursal_id'])
		->where("$this->table.status", 1)
		->groupBy("forma_pago")
		->fetchAll();

		return $this->response->SetResponse(true);
	}

	public function getPagosByDate($date){
		$this->response->result = $this->db
		->from($this->table)
		->select(NULL)
		->select("forma_pago,
				  CASE 
					WHEN forma_pago = 1 THEN 'Efectivo' 
					WHEN forma_pago = 3 THEN 'Tarjeta'
					WHEN forma_pago = 4 THEN 'Transferencia'
					ELSE ''
				  END AS metodo, 
				  SUM(monto) AS monto")
		->innerJoin("venta ON venta.id = venta_pago.venta_id")
		->where("DATE_FORMAT($this->table.fecha, '%Y-%m-%d') = '$date'")
		->where("venta.sucursal_id", $_SESSION['sucursal_id'])
		->where("$this->table.status", 1)
		->groupBy("forma_pago")
		->fetchAll();

		return $this->response->SetResponse(true);
	}

	public function add($data){
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Registro pago de venta realizado.'); 
		} else {
			$this->response->SetResponse(false, 'No se pudo agregar el registro pago de venta'); 
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
				else { $this->response->SetResponse(false, 'No se actualizó el registro del pago'); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: editar pago');
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
			else { $this->response->SetResponse(false, 'No se eliminó el pago de venta'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: del pago de venta");
		}
		return $this->response;
	}
}
?>