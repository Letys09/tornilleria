<?php
	namespace App\Model;
	use App\Lib\Response;

class UsuarioModel {
	private $db;
	private $table = 'usuario';
	private $tableTU = 'usuario_tipo';
	private $tableSesion = 'seg_session';
	private $tableModulo = 'seg_modulo';
	private $tableAccion = 'seg_accion';
	private $tablePermiso = 'seg_permiso';
	private $tableSPP  = 'seg_permiso_perfil';
	private $response;
	
	public function __CONSTRUCT($db) {
		date_default_timezone_set('America/Mexico_City');
		if(!isset($_SESSION)) { session_start(); }
		$this->db = $db;
		$this->response = new Response();
	}

	public function login($user, $contrasena){
		$contrasena = strrev(md5(sha1(trim($contrasena))));

		$usuario = $this->db
			->from($this->table)
			->select(null)
			->select("$this->table.id, $this->table.nombre, $this->table.usuario_tipo_id as typeUser, sucursal.nombre as sucursal")
			->where('username', $user)
			->where('contrasena', $contrasena)
			->where("$this->table.status", 1)
			->fetch();
			
		$this->response->result = $usuario;
		if($this->response->result){
			$this->response->SetResponse(true, 'datos correctos'); 
		} else {
			$this->response->SetResponse(false, 'Usuario o contraseña incorrectos.'); 
		}

		return $this->response;					
	}

	public function recovery($users){

		$usuario = $this->db
			->from($this->table)
			->select(null)->select("$this->table.id, CONCAT_WS(' ',$this->table.nombre, $this->table.apellidos) as nombre, $this->table.email, $this->table.iniciar")
			->where('login',$users)
			->where('status',1)
			->where('usuario_tipo_id != 2')
			->fetch();
		$this->response->result = $usuario;
		if($this->response->result){
			$this->response->SetResponse(true, 'Datos correctos');
		} else{
			$this->response->SetResponse(false, 'El usuario no existe');
		}
		return $this->response; 
	}

	public function getUserByUsername($login){
		$SQL = $this->db 
				->from($this->table)
				->where('login', $login)
				->fetch();
		$this->response->result = $SQL;
		if(($this->response->result) ){
			$this->response->SetResponse(true);
		} else {
			$this->response->SetResponse(false);
		}
		return $this->response;

	}

	public function getByPass($pass, $id){
		$usuario = $this->db
			->from($this->table)
			->where('id', $id)
			->where('contrasena', $pass)
			->fetch();
			
		$this->response->result = $usuario;
		if($this->response->result){
			$this->response->SetResponse(true, 'datos correctos'); 
		} else {
			$this->response->SetResponse(false, 'La contraseña actual no coincide.'); 
		}

		return $this->response;		
	}

	public function addPersona($data){
	
		$data['contrasena'] = strrev(md5(sha1(trim($data['contrasena']))));
		
		$SQL = $this->db
			->insertInto($this->table, $data)
			->execute();
		
		$this->response->result = $SQL;
		if($this->response->result){
			$this->response->SetResponse(true, 'Usuario agregado.'); 
		} else {
			$this->response->SetResponse(false, 'No se ingresó correctamente el usuario.'); 
		}
			
		return $this->response;
	}

	public function editPersona($data, $id){
		try {
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('id', $id)
				->execute();

				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'No se actualizo el registro del usuario '); }

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: edit model config');
		}

		return $this->response;
	}

	public function addSesion($data){
		$resultado = $this->db
			->insertInto($this->tableSesion, $data)
			->execute();

		return $resultado;
	}

	public function getUserPermisos($id){
		$this->response->result = $this->db
			->from($this->tablePermiso)
			->select(null)->select('seg_accion_id')
			->where('usuario_id', $id)
			->fetchAll();

		return $this->response;
	}

	public function get($id) {
		$this->response->result = $this->db
			->from($this->table)
			->where('id', $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'no existe el registro'); }

		return $this->response;
	}
	
	public function getAll($p, $l) {
		$inicio = $p*$l;
		$this->response->result = $this->db
			->from($this->table)
			->limit("$inicio, $l")
			->orderBy('id DESC')
			->fetchAll();
		
		$this->response->total = $this->db
			->from($this->table)
			->select('COUNT(*) Total')
			->fetch()
			->Total;
		
		return $this->response->SetResponse(true);
	}

	public function tiposUsuarios(){
		$this->response->admin = $this->db
		->from($this->table)
		->select('COUNT(*) admin')
		->where('usuario_tipo_id', 1)
		->where('status', 1)
		->fetch()
		->admin;

	$this->response->mensajeros = $this->db
		->from($this->table)
		->select('COUNT(*) mensajeros')
		->where('usuario_tipo_id', 2)
		->where('status', 1)
		->fetch()
		->mensajeros;

	$this->response->clientes = $this->db
		->from($this->table)
		->select('COUNT(*) clientes')
		->where('usuario_tipo_id', 3)
		->where('status', 1)
		->fetch()
		->clientes;

	$this->response->directivos = $this->db
		->from($this->table)
		->select('COUNT(*) directivos')
		->where('usuario_tipo_id', 4)
		->where('status', 1)
		->fetch()
		->directivos;

	$this->response->comisionistas = $this->db
		->from($this->table)
		->select('COUNT(*) comisionistas')
		->where('usuario_tipo_id', 5)
		->where('status', 1)
		->fetch()
		->comisionistas;

	$this->response->mecanicos = $this->db
		->from($this->table)
		->select('COUNT(*) mecanicos')
		->where('usuario_tipo_id', 7)
		->where('status', 1)
		->fetch()
		->mecanicos;

	$this->response->rescatistas = $this->db
		->from($this->table)
		->select('COUNT(*) rescatistas')
		->where('usuario_tipo_id', 8)
		->where('status', 1)
		->fetch()
		->rescatistas;

	$this->response->operadores = $this->db
		->from($this->table)
		->select('COUNT(*) operadores')
		->where('usuario_tipo_id', 9)
		->where('status', 1)
		->fetch()
		->operadores;

	$this->response->coordinadores = $this->db
		->from($this->table)
		->select('COUNT(*) coordinadores')
		->where('usuario_tipo_id', 10)
		->where('status', 1)
		->fetch()
		->coordinadores;
		
	$this->response->bicicleta = $this->db
		->from($this->table)
		->select('COUNT(*) bicicleta')
		->where('usuario_tipo_id', 11)
		->where('status', 1)
		->fetch()
		->bicicleta;

		return $this->response->SetResponse(true);
	}

	public function getAllDataTable() {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("usuario.id, usuario_tipo_id, usuario.nombre, apellidos, email, usuario.status, $this->tableTU.nombre as tipo_usuario")
			// ->innerJoin("$this->tableTU ON id_tipo_usuario = $this->table.usuario_tipo_id")
			// ->where('usuario_tipo_id != 3')
			->fetchAll(); 
					
		return $this->response->SetResponse(true);
	}

	public function getPermisos($usuario){ 
		$newModulos = array();
		$modulos = $this->getModulos();
		
		foreach ($modulos as $modulo) {
			$acciones = $this->getAcciones($usuario, $modulo->id);
			$contador = count($acciones);
			$accionesUrl = 0;
			if($contador>0){
				$modulo->acciones = $acciones;
				foreach ($acciones as $accion)
					if($accion->url != '') $accionesUrl++;
	
				$newModulos[] = $modulo;  
			}
			$modulo->accionesUrl = $accionesUrl;
		}

		return $newModulos;
	}

	public function getModulos(){
		return $this->db
			->from($this->tableModulo)
			->fetchAll();
	}

	public function getAcciones($usuario_id, $seg_modulo_id){
		return $this->db
			->from($this->tablePermiso)
			->select(null)->select("DISTINCT $this->tableAccion.id, $this->tableAccion.nombre, $this->tableAccion.url, $this->tableAccion.icono as iconoA, $this->tableAccion.id_html")
			->innerJoin("$this->tableAccion on $this->tableAccion.id = $this->tablePermiso.seg_accion_id")
			->innerJoin("$this->tableModulo on $this->tableModulo.id = $this->tableAccion.seg_modulo_id")
			->where("$this->tablePermiso.usuario_id", $usuario_id)
			->where(intval($seg_modulo_id)>0? "$this->tableAccion.seg_modulo_id = $seg_modulo_id": "TRUE")
			->where("$this->tableAccion.status", 1)
			->orderBy('id asc')
			->fetchAll();
	}

	// public function lockdownIndividual($data, $id) {
	// 	$accion = $data['iniciar'] == 1 ? 'desbloqueo' : 'bloqueo';
	// 	try {
	// 		$this->response->result = $this->db
	// 			->update($this->table, $data)
	// 			->where('id', $id)
	// 			->execute();

	// 			if($this->response->result) { 
	// 				$this->response->SetResponse(true); 
	// 			}else { 
	// 				$this->response->SetResponse(false, 'No se '.$accion.' sistema para '.$id); 
	// 			}

	// 	} catch(\PDOException $ex) {
	// 		$this->response->errors = $ex;
	// 		$this->response->SetResponse(false, 'catch: edit model usuario');
	// 	}

	// 	return $this->response;
	// }

	public function estatusUser($data, $id){
		$accion = $data['status'] == 1 ? 'activo' : 'desactivo';
		try {
			$this->response->result = $this->db
				->update($this->table, $data)
				->where('id', $id)
				->execute();

				if($this->response->result) { 
					$this->response->SetResponse(true); 
				}else { 
					$this->response->SetResponse(false, 'No se '.$accion.' al usuario '.$id); 
				}

		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: edit model usuario');
		}

		return $this->response;
	}

	public function getPermisosPerfil($id) {
		try {
			$this->response->result = $this->db
				->from($this->tableSPP)
				->where('fk_perfil' , $id)
				->fetchAll();
			if($this->response->result) { 
				$this->response->SetResponse(true); 
			}else { 
				$this->response->SetResponse(false, 'no existe el registro'); 
			}
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: edit model $this->tableSPP");
		}
		return $this->response;
	}

	public function getTypeUser() {
		$this->response->result = $this->db
			->from($this->tableTU)
			->where('status', 1)
			->fetchAll();
		if($this->response->result) { 
			$this->response->SetResponse(true); 
		}else { 
			$this->response->SetResponse(false, 'no existe el registro'); 
		}
		return $this->response;
	}

	public function findPerfil($data){
		$this->response->result = $this->db
			->from($this->tableTU)
			->where('descripcion', $data)
			->where('status', 1)
			->fetch();
		if($this->response->result) { 
			$this->response->SetResponse(false, 'Este tipo de usuario ya existe'); 
		} else { 
			$this->response->SetResponse(true); 
		}
		return $this->response;
	}

	public function updateTypeUser($id, $data) {
		try {
			$this->response->result = $this->db
				->update($this->tableTU, $data)
				->where('id_tipo_usuario', $id)
				->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'no se actualizo el registro'); }
		} catch(\PDOException $ex) {
			$this->response->result = $data;
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: edit model cliente');
		}
		return $this->response;
	}

	public function createProfile($data) {
		try{
			$this->response->result = $this->db
				->insertInto($this->tableTU, $data)
				->execute();	
			$this->response->SetResponse(true);
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->descripcion = $data;
			$this->response->SetResponse(false, "catch: add model $this->tableTU");
		}
		return $this->response;
	}

	public function updatePermitTypeUser($data) {
		try{
			$this->response->result = $this->db
				->insertInto($this->tableSPP, $data)
				->execute();	
			$this->response->SetResponse(true);
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: add model $this->tableSPP");
		}
		return $this->response;
	}

	public function DeleteTypeUser($data,$id) {
		try {
			$this->response->result = $this->db
				->deleteFrom($this->tableSPP)
				->where('fk_perfil', $id)
				->where('seg_accion_id', $data)
				->execute();
			if($this->response->result) { $this->response->SetResponse(true); }
			else { $this->response->SetResponse(false, 'no se actualizo el registro'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: add model $this->tableSPP");
		}
		return $this->response;
	}
	
}
?>