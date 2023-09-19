<?php
	namespace App\Model;
	use App\Lib\Response;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	use Slim\Http\UploadedFile;

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
			->select("$this->table.id, $this->table.nombre, $this->table.usuario_tipo_id as typeUser, sucursal.id as id_sucursal, sucursal.nombre as sucursal")
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

	public function recovery($user){

		$usuario = $this->db
			->from($this->table)
			->select(null)->select("$this->table.id, CONCAT_WS(' ',$this->table.nombre, $this->table.apellidos) as nombre, $this->table.email")
			->where('username', $user)
			->where('status', 1)
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

	public function getByPasscode($passcode){
		$this->response->result = $this->db
			->from($this->table)
			->where('passcode', $passcode)
			->where('status', 1)
			->fetch();
		return $this->response;
	}

	public function getCodigoAleatorio($longitud, $numerico = false) {
		$universo = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
		if($numerico) $universo = "1234567890";
		$longUniverso = strlen($universo);
		$codigo = "";
		while(strlen($codigo) < $longitud) {
			$codigo .= substr($universo, rand(0, $longUniverso-1), 1);
		}
		
		return $codigo;
	}

	public function add($data, $table){
		if($table == 'usuario') $data['contrasena'] = strrev(md5(sha1(trim($data['contrasena']))));
		try {
			$SQL = $this->db
			->insertInto($table, $data)
			->execute();

			$this->response->result = $SQL;
			if($this->response->result){
				$this->response->SetResponse(true, 'Registro agregado '.$table); 
			} else {
				$this->response->SetResponse(false, 'No se pudo agregar el registro '.$table); 
			}
		}catch(\PDOException $ex){
			$this->response->errors = $ex;
			$this->response->setResponse(false, 'catch: agregar registro');
		}		
		return $this->response;
	}

	public function edit($data, $id, $table){
		try {
			$this->response->result = $this->db
				->update($table, $data)
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
			->select(null)
			->select("$this->table.id, sucursal_id, usuario_tipo_id, direccion_id, nombre, apellidos, email, celular, username, calle, no_ext, no_int, colonia, municipio, estado, codigo_postal")
			->innerJoin("direccion ON direccion.id = $this->table.direccion_id")
			->where("$this->table.id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'no existe el registro'); }

		return $this->response;
	}

	public function getDir($id) {
		$this->response->result = $this->db
			->from("direccion")
			->select(null)
			->select("calle, no_ext, no_int, colonia, municipio, estado, codigo_postal")
			->where("id", $id)
			->fetch();

		if($this->response->result) { $this->response->SetResponse(true); }
		else { $this->response->SetResponse(false, 'No existe el registro'); }

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

	public function getAllDataTable() {
		$this->response->result = $this->db
			->from($this->table)
			->select(null)
			->select("usuario.id, usuario_tipo_id, usuario.nombre, apellidos, email, celular, usuario.status, $this->tableTU.nombre as tipo_usuario")
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
			->where("status", 1)
			->orderBy("orden ASC")
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

	public function estatusUser($data, $id){
		$accion = $data['status'] == 1 ? 'activó' : 'desactivó';
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
			$this->response->SetResponse(false, 'catch: cambiar estatus de usuario');
		}

		return $this->response;
	}

	public function getPermisosPerfil($id) {
		try {
			$this->response->result = $this->db
				->from($this->tableSPP)
				->where('usuario_tipo_id' , $id)
				->fetchAll();
			if($this->response->result) { 
				$this->response->SetResponse(true); 
			}else { 
				$this->response->SetResponse(false, 'No existe el registro'); 
			}
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: $this->tableSPP");
		}
		return $this->response;
	}

	public function getTypeUser() {
		$this->response->result = $this->db
			->from($this->tableTU)
			->select(null)
			->select("id, nombre")
			->where('status', 1)
			->fetchAll();
		if($this->response->result) { 
			$this->response->SetResponse(true); 
		}else { 
			$this->response->SetResponse(false, 'No hay registros'); 
		}
		return $this->response;
	}

	public function getFoto($id){
		$archivo = '';
        $base_url = 'data/empleado/';
        $file = 'foto'.$id.'.jpg';
        if(file_exists($base_url.$file)){
            $archivo = true;
        }else{
            $archivo = false;
        }

        return $archivo; 
	}

	function moveUploadedFileFoto($directory, UploadedFile $uploadedFile, $data){
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        if($extension == 'jpg' || $extension == 'jpeg' ){
            $extension = 'jpg';
        }else{
            return '0';
        }
        $basename = 'foto'.$data['usuario_id'];
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

	public function findPerfil($data){
		$this->response->result = $this->db
			->from($this->tableTU)
			->where('nombre', $data)
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
				->where('id', $id)
				->execute();
				if($this->response->result) { $this->response->SetResponse(true); }
				else { $this->response->SetResponse(false, 'No se actualizo el registro'); }
		} catch(\PDOException $ex) {
			$this->response->result = $data;
			$this->response->errors = $ex;
			$this->response->SetResponse(false, 'catch: edit tipo usuario');
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
			$this->response->SetResponse(false, "catch: crear tipo usuario $this->tableTU");
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
			$this->response->SetResponse(false, "catch: update permisos tipo usuario $this->tableSPP");
		}
		return $this->response;
	}

	public function DeleteTypeUser($data,$id) {
		try {
			$this->response->result = $this->db
				->deleteFrom($this->tableSPP)
				->where('usuario_tipo_id', $id)
				->where('seg_accion_id', $data)
				->execute();
			if($this->response->result) { $this->response->SetResponse(true); }
			else { $this->response->SetResponse(false, 'No se actualizo el registro'); }
		} catch(\PDOException $ex) {
			$this->response->errors = $ex;
			$this->response->SetResponse(false, "catch: eliminar tipo de usuario $this->tableSPP");
		}
		return $this->response;
	}

	public function sendEmail($emailAddress, $subject, $body, $files=[]) {
		require_once './core/defines.php';
		if(!isset($_SESSION)) { session_start(); }
		$mail = new PHPMailer(true);
		try {$mail->SMTPDebug = 0;
			$mail->isSMTP();
			$mail->SMTPOptions = array(
				'ssl'=> array(
					'verify_peer' => false,
					'verify_peer_name'=> false,
					'allow_self_signed' => true
				)
			);
			$mail->SMTPAuth = true;
			$mail->SMTPSecure = 'tls';
			$mail->Host = 'smtp.gmail.com';
			$mail->Username = $_SESSION['mail_username'];
			$mail->Password = $_SESSION['mail_pwd'];
			$mail->Port = 587;
			
			$mail->setFrom($_SESSION['mail_username'], SITE_NAME);
			$mail->addAddress($emailAddress);
			
			$mail->isHTML(true);
			$mail->CharSet = 'UTF-8';
			$mail->Subject = $subject;
			$mail->Body    = $body;

			for($x=0;$x<count($files);$x++) {
				$filename = explode('/', $files[$x]);
				$filename = $filename[count($filename)-1];
				$mail->AddAttachment($files[$x], $filename);
			}

			$mail->send();

			unset($mail->Username, $mail->Password);
			$this->response->SetResponse($mail);
		}
		catch (Exception $e) {
			$this->response->SetResponse(false, $e);
		}

		return $this->response;
	}

	public function addSessionLogin(){
		$browser = $_SERVER['HTTP_USER_AGENT'];
		$ipAddr = $_SERVER['REMOTE_ADDR'];

		if (!isset($_SESSION)) { session_start(); }
		$_SESSION['ip']  = $ipAddr;
		$_SESSION['navegador']  = $browser;
	}
	
}
?>