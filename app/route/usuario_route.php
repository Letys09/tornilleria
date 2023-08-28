<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/usuario/', function () use ($app){
		$this->get('', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'text/html')->write('Soy una ruta de usuario');
		});

		$this->post('login', function($req, $res, $args){
			if(!isset($_SESSION)) { session_start(); }
			$parsedBody = $req->getParsedBody();
			$user = $parsedBody['user'];
			$contrasena = $parsedBody['contrasena'];
			$date = date("Y-m-d H:i:s");

			$usuario = $this->model->usuario->login($user, $contrasena);
			if($usuario->response){
				$infoUser = $usuario->result;
				$acciones = $this->model->usuario->getPermisos($infoUser->id, 1);
				$_SESSION['usuario'] = $infoUser;
				$_SESSION['usuario_id'] = $infoUser->id;
				$_SESSION['permisos'] = $acciones;
				
				$browser = $_SERVER['HTTP_USER_AGENT'];
				$ipAddr = $_SERVER['REMOTE_ADDR'];

				$token = $this->model->seg_sesion->crearToken($infoUser->id);
					
				$data = [
					'usuario_id' => $infoUser->id,
					'ip_address' => $ipAddr,
					'user_agent' => $browser,
					'token' => $token,
					'iniciada'    => $date,
				];
				$sesion = $this->model->seg_sesion->add($data);
				if($sesion){
					$_SESSION['logID'] = $sesion->result;
					$_SESSION['token'] = $token;
					$seg_log = $this->model->seg_log->add('Inicio de sesión', 'usuario', $infoUser->id, 1);
				}
			}
			return $res->withJson($usuario);
		});

		$this->get('logout', function($req, $res, $args) use ($app) {
			if(!isset($_SESSION)) { session_start(); }
			$userId = $_SESSION['usuario_id'];
			$this->model->seg_sesion->logout();

			return $res->withRedirect('../login');
		});

		$this->get('get/{id}', function ($req, $res, $args) {
			return $res->withJson($this->model->usuario->get($args['id']));
		});

		$this->post('recovery',function($req, $res, $args){

			$parsedBody = $req->getParsedBody();
			$users = $parsedBody['users'];

			$usuario = $this->model->usuario->recovery($users);

			if($usuario->response){
				$infoUser = $usuario->result;
				
				$mail = $infoUser->email;
				$newPass = randomString(8);
				$newpassBD = strrev(md5(sha1(trim($newPass)))); //<-----------

				$nombre = $infoUser->nombre;			

				$disc = "\n\n\n-------------------------------------\n";
				$disc .="Este correo fue enviado desde una cuenta no monitoreada. Por favor no respondas este correo.";
				$to = $mail;
				$subject = "Recuperación de Contraseña";
				$body = "Hola ". $nombre ."\n\nTu contraseña provisional es $newPass \nPuedes cambiarla en cuanto inicies sesión en ".URL_ROOT;
				$body = $body.$disc;
				$header = "From: ".SITE_NAME." <notifica@blinkmensajeros.com>\r\n";
				$resultMail = mail($to, $subject, $body, $header); 
				//$arrRes = array('error' => false, 'msg' => 'Te enviamos una nueva contraseña a tu correo. Si no encuentras el mensaje, verifica tu bandeja de correo no deseado o spam.');
			
				$usuario->msg = 'Te enviamos una nueva contraseña a tu correo. <br> Si no encuentras el mensaje, verifica tu bandeja de correo no deseado o spam';

			}else{
				$usuario->msg = 'No encontramos al usuario con el username ingresado';
			}

			return $res->withJson($usuario);
		}); 

		$this->post('addPersona',function($req,$res,$args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();
			$login = $parsedBody['login'];
			$tipo_usuario = $parsedBody['usuario_tipo_id'];
			$parsedBody['fecha_registro'] = date('Y-m-d H:i:s');
			$ingreso = date('Y-m-d');
			
			$UserName = $this->model->usuario->getUserByUsername($login);

			// if($UserName->response && $parsedBody['usuario_tipo_id'] != '2' ){
			if($UserName->response){
				$UserName->setResponse(false,'El nombre de usuario ya existe');
				$UserName->state= $this->model->transaction->regresaTransaccion();
				return $res->withJson($UserName);
			}else{
				$UserName = $this->model->usuario->addPersona($parsedBody);
				if($UserName->response){
					$id_usuario_agregado = $UserName->result;
						$UserName->state = $this->model->transaction->confirmaTransaccion();
						$Userid = $UserName->setResponse(true, $id_usuario_agregado);		
				}else{
					$UserName->state = $this->model->transaction->regresaTransaccion();
				}
			} 
			return $res->withJson($Userid);
		});

		$this->post('editPersona/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();

			$igual = true;
			$infoUser = $this->model->usuario->get($args['id'])->result;
			if($parsedBody['contrasena'] != ''){
				$parsedBody['contrasena'] = strrev(md5(sha1(trim($parsedBody['contrasena']))));
			}else{
				$parsedBody['contrasena'] = $infoUser->contrasena;
			}
			
			foreach($parsedBody as $field => $value) { 
				if($infoUser->$field != $value) { 
					$igual = false; break; 
				} 
			}

			
			$dataDom = [
				'calle' => $parsedBody['calle'],
				'colonia' => $parsedBody['colonia'],
				'exterior' => $parsedBody['numero'],
				'interior' => $parsedBody['num_int'],
				'municipio' => $parsedBody['del_muni'],
				'estado' => $parsedBody['ciudad'],
			];

			if(!$igual){
					$editUsuario = $this->model->usuario->editPersona($parsedBody, $args['id']);
					if($editUsuario->response){
							$seg_log = $this->model->seg_log->add('Modifica usuario', $args['id'], '1');
							if($seg_log->response){
								$seg_log->state = $this->model->transaction->confirmaTransaccion();
							}else{
								$seg_log->state = $this->model->transaction->regresaTransaccion();
							}
					}else{
						$editUsuario->state = $this->model->transaction->regresaTransaccion();
					}
			}else{
				$editUsuario = 'No existen datos diferentes a los antes registrados';
			}

			return $res->withJson($editUsuario);
		});

		$this->get('tiposUsuarios', function($req, $res, $args){
			$tipos = $this->model->usuario->tiposUsuarios();
			return $res->withJson($tipos);
		});

		$this->get('getAll/{p}/{l}', function ($req, $res, $args) {
			return $res->withJson($this->model->usuario->getAll($args['p'], $args['l']));
		});

		$this->get('getAllDataTable', function($req, $res, $args){
			$usuarios = $this->model->usuario->getAllDataTable();

			$data = [];
			if(!isset($_SESSION)) { session_start(); }
			foreach($usuarios->result as $usuario) {
				$data[] = array(
					"id" => $usuario->id,
					"nombre" => $usuario->nombre,
					"apellidos" => $usuario->apellidos,
					"id_type_user" => $usuario->usuario_tipo_id,
					"type_user" => $usuario->tipo_usuario,
					"email" => $usuario->email,
					"status" => $usuario->status == '1' ? 'Activo' : 'Inactivo',
					"foto" => "",
				);
			}

			echo json_encode(array(
				'data' => $data
			));

			exit(0);
		});

		$this->put('estatusUser/{id}', function($request, $response, $arguments){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
				if(isset($arguments['id'])){
					$id = $arguments['id'];
					$accion = $parsedBody['status'] == 1 ? 'Alta' : 'Baja';
					$data = array('status' => $parsedBody['status']);
					
					$edit = $this->model->usuario->estatusUser($data, $arguments['id']);
					if($edit->response){
						if($parsedBody['status'] == 0){
							$data = array(
								'fk_empleado' => $arguments['id'],
								'fecha' => $parsedBody['baja'],
								'ultimo_dia' => $parsedBody['baja'],
								'motivo' => $parsedBody['motivo'],
								'observaciones' => $parsedBody['observaciones'],
							);
								$seg_log = $this->model->seg_log->add($accion.' usuario', $arguments['id'], '1');
								if($seg_log->response){
									$seg_log->state = $this->model->transaction->confirmaTransaccion();
								}else{
									$seg_log->state = $this->model->transaction->regresaTransaccion();
									return $response->withJson($seg_log);
								}
						}else{
							$seg_log = $this->model->seg_log->add($accion.' usuario', $arguments['id'], '1');
							if($seg_log->response){
								$seg_log->state = $this->model->transaction->confirmaTransaccion();
							}else{
								$seg_log->state = $this->model->transaction->regresaTransaccion();
								return $response->withJson($seg_log);
							}
						}
					}else{
						$edit->state = $this->model->transaction->regresaTransaccion();
						return $response->withJson($edit);
					}
				}
			return $response->withJson($edit);
		});

		$this->get('getUserPermisos/{id}', function($req, $res, $args){
			$permisos = $this->model->usuario->getUserPermisos($args['id'])->result;
			return $res->withJson($permisos);
		});

		$this->get('getPermisosPerfil/{id}', function ($req, $res, $args) {
			return $res->withJson($this->model->usuario->getPermisosPerfil($args['id']));
		});

		$this->get('getTypeUser', function ($req, $res, $args) {
			return $res->withJson($this->model->usuario->getTypeUser());
		});
		
		$this->post('createProfile', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();
			$desc = $parsedBody['descripcion'];

			$existe = $this->model->usuario->findPerfil($desc);
			if(!$existe->response){
				$existe->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($existe);
			}else{
				$addProfile = $this->model->usuario->createProfile($parsedBody);
				if($addProfile->response){
					$addProfile->state = $this->model->transaction->confirmaTransaccion();
				}else{
					$addProfile->state = $this->model->transaction->regresaTransaccion();
				}
			}
			return $res->withJson($addProfile);
 		});
		
		$this->put('updateTypeUser/{id}', function ($req, $response, $args) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();
			$desc = $parsedBody['descripcion'];

			$existe = $this->model->usuario->findPerfil($desc);
			if(!$existe->response){
				$existe->state = $this->model->transaction->regresaTransaccion();
				return $response->withJson($existe);
			}else{
				$updateProfile = $this->model->usuario->updateTypeUser($args['id'], $parsedBody);
				if($updateProfile->response){
					$seg_log = $this->model->seg_log->add('Modifica tipo de usuario', $args['id'], '2');
					if($seg_log->response){
						$seg_log->state = $this->model->transaction->confirmaTransaccion();
					}else{
						$seg_log->state = $this->model->transaction->regresaTransaccion();
						return $response->withJson($seg_log);
					}
				}else{
					$updateProfile->state = $this->model->transaction->regresaTransaccion();
				}
			}
			return $response->withJson($updateProfile);
		});

		$this->put('delTypeUser/{id}', function ($req, $response, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();

			$delProfile = $this->model->usuario->updateTypeUser($args['id'], $parsedBody);
			if($delProfile->response){
				$seg_log = $this->model->seg_log->add('Elimina tipo de usuario', $args['id'], '3');
				if($seg_log->response){
					$seg_log->state = $this->model->transaction->confirmaTransaccion();
				}else{
					$seg_log->state = $this->model->transaction->regresaTransaccion();
					return $response->withJson($seg_log);
				}
			}else{
				$delProfile->state = $this->model->transaction->regresaTransaccion();
			}
			return $response->withJson($delProfile);
		});

		$this->post('updatePermitTypeUser', function ($req, $res, $args) {
			$parsedBody = $req->getParsedBody();
			$agrega = $parsedBody['agrega']; 
			$data = array('fk_perfil'   => $parsedBody['fk_perfil'], 
						  'seg_accion_id'   => $parsedBody['seg_accion_id'],);
			if ($agrega == true) {
				return $res->withJson($this->model->usuario->updatePermitTypeUser($data));
			}else if ($agrega == false){
				$id = $parsedBody['fk_perfil']; 
				$seg_accion_id = $parsedBody['seg_accion_id']; 
				return $res->withJson($this->model->usuario->DeleteTypeUser($seg_accion_id, $id));
			}
 		});

		$this->post('renovarToken/', function($request, $response, $arguments) {
			if(!isset($_SESSION)) { session_start(); }
			$data = [
				'token' => $this->model->seg_sesion->crearToken($_SESSION['usuario_id']),
				'finished' => date('Y-m-d H:i:s'),
			];
			
			return $response->withJson($this->model->seg_sesion->edit($data, $_SESSION['logID']));
		})->add( new MiddlewareToken() );
		
	});


	function randomString($tam=8){
		$source = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		if($tam>0){
			$rstr = "";
			$source = str_split($source,1);
			for($i=1; $i<=$tam; $i++){
				mt_srand((double)microtime() * 1000000);
				$num = mt_rand(1,count($source));
				$rstr .= $source[$num-1];
			}
		}
		return $rstr;
	}

?>