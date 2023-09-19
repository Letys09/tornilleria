<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;
use Slim\Http\UploadedFile;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/usuario/', function () use ($app){
		$this->get('', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'text/html')->write('Soy una ruta de usuario');
		});

		$this->post('login', function($req, $res, $args){
			if(!isset($_SESSION)) { session_start(); }
			$parsedBody = $req->getParsedBody();
			$sucursal_id = $parsedBody['sucursal'];
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

				$infoSuc = $this->model->sucursal->get($sucursal_id)->result;
				$_SESSION['sucursal_nombre'] = $infoSuc->nombre;
				$_SESSION['sucursal_id'] = $sucursal_id;
				$_SESSION['foto'] = $this->model->usuario->getFoto($infoUser->id);
				
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
				$acceso = [ 'acceso' => date('Y-m-d H:i:s') ];
				$edit = $this->model->usuario->edit($acceso, $infoUser->id, 'usuario');
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
			$users = $parsedBody['user'];

			$usuario = $this->model->usuario->recovery($users);

			if($usuario->response){
				$infoUser = $usuario->result;
				
				$mail = $infoUser->email;
				$newPass = randomString(8);
				$newpassBD = strrev(md5(sha1(trim($newPass))));

				$nombre = $infoUser->nombre;	
				
				$contenido = '<h3><strong>Hola '.$nombre.'</strong></h3>';
				$contenido .= 'Su contraseña provisional es '.$newPass.'.<br>';
				$contenido .= 'Puede cambiar su contraseña una vez que inicie ssión en '.URL_ROOT;
				$contenido .= '<br><br><br>...............................................................................<br>';
				$contenido .="Este correo fue enviado desde una cuenta no monitoreada. Por favor no responda este correo.";
				$this->model->usuario->sendEmail($infoUser->email, 'Recuperación de contraseña', $contenido);
				$new = ['contrasena' => $newpassBD];
				$this->model->usuario->edit($new, $infoUser->id, 'usuario');

				$usuario->msg = 'Enviamos una nueva contraseña a '.$infoUser->email.' <br> Si no encuentra el mensaje, verifique su bandeja de correo no deseado o spam';

			}else{
				$usuario->msg = 'No encontramos al usuario con el username ingresado';
			}

			return $res->withJson($usuario);
		}); 

		$this->post('add/{tipo}',function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();
			if($args['tipo'] == 1){
				$direccion = [
					'calle' => $parsedBody['calle'],
					'no_ext' => $parsedBody['no_ext'],
					'no_int' => $parsedBody['no_int'],
					'colonia' => $parsedBody['colonia'],
					'municipio' => $parsedBody['municipio'],
					'estado' => $parsedBody['estado'],
				];
				$addDir = $this->model->usuario->add($direccion, 'direccion');
				if($addDir->response){
					$dir_id = $addDir->result; $existe = false;
					$passcode = $this->model->usuario->getCodigoAleatorio(4, true);
					$existe = $this->model->usuario->getByPasscode(strrev(md5(sha1($passcode))))->result;
					if(is_object($existe)){
						$passcode = $this->model->usuario->getCodigoAleatorio(4, true);
						$existe = $this->model->usuario->getByPasscode(strrev(md5(sha1($passcode))));
						if(is_object($existe)){
							$passcode = $this->model->usuario->getCodigoAleatorio(4, true);
							$existe = $this->model->usuario->getByPasscode(strrev(md5(sha1($passcode))));
						}else{
							$existe = false;
						}
					}else{
						$existe = false;
					}

					if(!$existe){
						$general = [
							'usuario_tipo_id' => $parsedBody['usuario_tipo_id'],
							'direccion_id' => $dir_id,
							'nombre' => $parsedBody['nombre'],
							'apellidos' => $parsedBody['apellidos'],
							'email' => $parsedBody['email'],
							'celular' => $parsedBody['celular'],
							'username' => $parsedBody['username'],
							'contrasena' => $parsedBody['contrasena'],
							'passcode' => strrev(md5(sha1($passcode))),
							'registro' => date('Y-m-d H:i:s'),
						];
						$add = $this->model->usuario->add($general, 'usuario');
						if($add->response){
							$usuario_id = $add->result;
							$permisos = $this->model->usuario->getPermisosPerfil($parsedBody['usuario_tipo_id'])->result;
							foreach($permisos as $permiso){
								$dataPermisos = ['usuario_id' => $usuario_id, 'fecha_asigno' => date('Y-m-d H:i:s'), 'seg_accion_id' => $permiso->seg_accion_id];
								$this->model->seg_permiso->add($dataPermisos);
							}

							$contenido = '<h3><strong>Bienvenido a '.SITE_NAME.'</strong></h3>';
							$contenido .= 'Se te ha agregado en el sistema <strong>'.SITE_NAME.'</strong>, con tu correo '.$parsedBody['email'].' y la contraseña que proporcionaste.<br>';
							$contenido .= 'Para cambiar de usuario rápidamente, puedes usar el siguiente <strong>Switch Code</strong> dentro del sistema: <strong>'.$passcode.'</strong> ';
							$contenido .= 'Recuerda que tu <strong>Switch Code</strong> es único e irrepetible y de uso exclusivamente personal. No debes compartirlo con nadie.';
							$this->model->usuario->sendEmail($parsedBody['email'], 'Bienvenido a '.SITE_NAME, $contenido);

							$seg_log = $this->model->seg_log->add('Agrega usuario', 'usuario', $usuario_id, 1);
							if($seg_log->response){
								$add->state = $this->model->transaction->confirmaTransaccion();	
								return $res->withJson($add);
							}else{
								$seg_log->state = $this->model->transaction->regresaTransaccion();	
								return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
							}
						}else{
							$add->state = $this->model->transaction->regresaTransaccion();
							if($add->errors->errorInfo[0] == 23000) {
								$add->error = 23000;
								return $res->withJson($add->SetResponse(false, 'El username ya está registrado, ingrese otro.'));
							}
							else return $res->withJson($add->SetResponse(false, 'No se pudo agregar el producto'));
						}
					}else{
						$existe->state = $this->model->transaction->regresaTransaccion();
						return $res->withJson($existe);
					}
				}else{
					$addDir->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($addDir);
				}
			}
		});

		$this->post('edit/{tipo}/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();

			if($args['tipo'] == 1){
				$igual = true; $dirIgual = true;
				$infoUser = $this->model->usuario->get($args['id'])->result;
				
				$general = [
					'usuario_tipo_id' => $parsedBody['usuario_tipo_id'],
					'nombre' => $parsedBody['nombre'],
					'apellidos' => $parsedBody['apellidos'],
					'email' => $parsedBody['email'],
					'celular' => $parsedBody['celular'],
					'username' => $parsedBody['username'],
				];

				foreach($general as $field => $value) { 
					if($infoUser->$field != $value) { 
						$igual = false; break; 
					} 
				}

				if($parsedBody['contrasena'] != ''){
					$contrasena = strrev(md5(sha1(trim($parsedBody['contrasena']))));
					$igual = false;
					$general['contrasena'] = $contrasena;
				}

				$dir = $this->model->usuario->getDir($infoUser->direccion_id)->result;
				$dataDir = [
					'calle' => $parsedBody['calle'],
					'no_ext' => $parsedBody['no_ext'],
					'no_int' => $parsedBody['no_int'],
					'colonia' => $parsedBody['colonia'],
					'municipio' => $parsedBody['municipio'],
					'estado' => $parsedBody['estado'],
					'codigo_postal' => $parsedBody['codigo_postal'],
				];

				foreach($dataDir as $field => $value) { 
					if($dir->$field != $value) { 
						$dirIgual = false; break; 
					} 
				}

				if(!$igual){
					if($infoUser->usuario_tipo_id != $parsedBody['usuario_tipo_id']){
						$permisos = $this->model->seg_permiso->getPermisosByUser($args['id'])->result;
						foreach($permisos as $permiso){
							$this->model->seg_permiso->delPermisoUser($permiso->id);
						}

						$nuevos = $this->model->usuario->getPermisosPerfil($parsedBody['usuario_tipo_id'])->result;
						foreach($nuevos as $nuevo){
							$dataPermisos = ['usuario_id' => $args['id'], 'fecha_asigno' => date('Y-m-d H:i:s'), 'seg_accion_id' => $nuevo->seg_accion_id];
							$this->model->seg_permiso->add($dataPermisos);
						}
					}
					$edit = $this->model->usuario->edit($general, $args['id'], 'usuario');
					if($edit->response){
						$seg_log = $this->model->seg_log->add('Modifica usuario', 'usuario', $args['id'], '1');
						if(!$seg_log->response){
							$seg_log->state = $this->model->transaction->regresaTransaccion();
						}
					}else{
						$edit->state = $this->model->transaction->regresaTransaccion();
					}
				}
				if(!$dirIgual){
					$edit = $this->model->usuario->edit($dataDir, $infoUser->direccion_id, 'direccion');
					if($edit->response){
						$seg_log = $this->model->seg_log->add('Modifica dirección', 'direccion', $infoUser->direccion_id, '1');
						if(!$seg_log->response){
							$seg_log->state = $this->model->transaction->regresaTransaccion();
						}
					}else{
						$edit->state = $this->model->transaction->regresaTransaccion();
					}
				}
				
				if($igual && $dirIgual){
					$edit = 'No existen datos diferentes a los antes registrados';
				}

				$this->model->transaction->confirmaTransaccion();
				return $res->withJson($edit);
			}
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
				$foto = $this->model->usuario->getFoto($usuario->id);
				$data[] = array(
					"id" => $usuario->id,
					"nombre" => $usuario->nombre,
					"apellidos" => $usuario->apellidos,
					"id_type_user" => $usuario->usuario_tipo_id,
					"type_user" => $usuario->tipo_usuario,
					"email" => $usuario->email,
					"celular" => $usuario->celular,
					"status" => $usuario->status == '1' ? 'Activo' : 'Inactivo',
					"data_id" => $usuario->id,
					"foto" => $foto,
				);
			}

			echo json_encode(array(
				'data' => $data
			));

			exit(0);
		});

		$this->post('estatusUser/{id}', function($request, $response, $arguments){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
				if(isset($arguments['id'])){
					$id = $arguments['id'];
					$accion = $parsedBody['status'] == 1 ? 'Alta' : 'Baja';
					$data = array('status' => $parsedBody['status']);
					
					$edit = $this->model->usuario->estatusUser($data, $arguments['id']);
					$obs = isset($parsedBody['observaciones']) ? $parsedBody['observaciones'] : '';
					$motivo = $parsedBody['status'] == 0 ? ' Motivo: '.$obs : '';
					if($edit->response){
						$seg_log = $this->model->seg_log->add($accion.' usuario.'.$motivo, 'usuario', $arguments['id'], '1');
						if($seg_log->response){
							$seg_log->state = $this->model->transaction->confirmaTransaccion();
						}else{
							$seg_log->state = $this->model->transaction->regresaTransaccion();
							return $response->withJson($seg_log);
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

		$this->get('getFoto/{id}', function($req, $res, $args){
            $this->response = new Response();        
            return $res->withHeader('Content-type', 'application/json')
                    ->write(json_encode($this->model->usuario->getFoto($args['id'])));
		});

		$this->post('addFoto', function ($req, $res, $args) {
            $this->response = new Response();
            $parsedBody = $req->getParsedBody();
            $directory = 'data/empleado';
            $uploadedFiles = $req->getUploadedFiles();
            
            $uploadedFile = $uploadedFiles['imagen'];
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $filename = $this->model->usuario->moveUploadedFileFoto($directory, $uploadedFile, $parsedBody);
                if($filename == '0'){
					$this->response->result = 0;
					return $this->response->SetResponse(false,'Extensión de archivo inválido, solo se aceptan imagenes en formato jpg');
                }else{
					$this->response->result = 1;
					$this->response->SetResponse(true,'Archivo cargado con exito: ' . $filename);
					return $res->withjson($this->response);
                }       
            }

            $this->response->result = 1;
            return $this->response->SetResponse(true,'Archivo cargado con exito: ' . $filename);
        });
		
		$this->post('createProfile', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();
			$nombre = $parsedBody['nombre'];

			$existe = $this->model->usuario->findPerfil($nombre);
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
			$nombre = $parsedBody['nombre'];

			$existe = $this->model->usuario->findPerfil($nombre);
			if(!$existe->response){
				$existe->state = $this->model->transaction->regresaTransaccion();
				return $response->withJson($existe);
			}else{
				$updateProfile = $this->model->usuario->updateTypeUser($args['id'], $parsedBody);
				if($updateProfile->response){
					$seg_log = $this->model->seg_log->add('Modifica tipo de usuario', 'usuario_tipo', $args['id'], 0);
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
			$data = array('usuario_tipo_id'   => $parsedBody['usuario_tipo_id'], 
						  'seg_accion_id'   => $parsedBody['seg_accion_id'],);
			if ($agrega == true) {
				return $res->withJson($this->model->usuario->updatePermitTypeUser($data));
			}else if ($agrega == false){
				$id = $parsedBody['usuario_tipo_id']; 
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

		$this->post('switchUser', function($request, $response, $arguments) {
			$this->response = new Response();
			$parsedBody = $request->getParsedBody();

			$existe = $this->model->usuario->getByPasscode(strrev(md5(sha1($parsedBody['code']))))->result;

			if(is_object($existe)){
				$user = $existe;
				if($user->id == $_SESSION['usuario']->id){
					$this->response->SetResponse(false, 'Debe ingresar el Switch Code de otro colaborador.');
				}else{
					if(!isset($_SESSION)) { session_start(); }

					if(isset($_SESSION['usuario'])){
						$userId = $_SESSION['usuario_id'];
						$this->model->seg_log->add('Cierra Sesion SwitchCode', 'usuario', $userId, 1);
						$data = [
							'status' => 0,
							'finalizada' => date('Y-m-d H:i:s'),
						];
						$this->model->seg_sesion->edit($data, $_SESSION['logID']);
					}

					$ultimo = ['acceso' => date('Y-m-d H:i:s')];
					$this->model->usuario->edit($ultimo, $user->id, 'usuario');
					$newModulos = array();
					$newModulos = $this->model->usuario->getPermisos($user->id);
					$this->model->usuario->addSessionLogin($user, $newModulos);

					// $foto="data/foto/".md5($user->id).".jpg";
					// $user->foto = file_exists($foto);

					$token = $this->model->seg_sesion->crearToken($user);
					$data = [
						'usuario_id' => $user->id,
						'ip_address' => $_SERVER['REMOTE_ADDR'],
						'user_agent' => $_SERVER['HTTP_USER_AGENT'],
						'iniciada' => date('Y-m-d H:i:s'),
						'token' => $token,
					];
					$this->model->seg_sesion->add($data);
					$_SESSION['usuario'] = $user;
					$_SESSION['usuario_id'] = $user->id;
					$_SESSION['permisos'] = $newModulos;
					$_SESSION['foto'] = $this->model->usuario->getFoto($user->id);

					$this->model->seg_log->add('Inicio de sesión SwitchCode', 'usuario', $user->id, 1);
					$this->response->username = $user->nombre;
					$this->response->SetResponse(true, 'Esta ventana se cerrará en 4 segundos');
				}
			}else{
				$this->response->SetResponse(false, 'No encontramos el usuario. Verifique Switch Code.');
			}

			return $response->withJson($this->response);
		});
		
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