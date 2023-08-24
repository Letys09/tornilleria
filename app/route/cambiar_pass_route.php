<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Middleware\AccesoMiddleware;
	date_default_timezone_set('America/Mexico_City');

	$app->group('/password/', function () {
		$this->get('', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'text/html')->write('Soy una ruta de password');
		});

        // if($_SESSION['usuario']){
            $this->post('cambiarContrasena/{id}', function($req, $res, $args){
                $this->model->transaction->iniciaTransaccion();
                $parsedBody = $req->getParsedBody();
                $id = $args['id'];
                $contrasena = $parsedBody['contrasena'];
                $actual = strrev(md5(sha1(trim($contrasena)))); 
                $nueva = $parsedBody['contrasenaNue'];

                $data = [ 'contrasena' => strrev(md5(sha1(trim($nueva))))];

                $user = $this->model->usuario->getByPass($actual, $id);
                if($user->response){
                    $updatePass = $this->model->usuario->editPersona($data, $id);
                    if($updatePass->response){
                        $seg_log = $this->model->seg_log->add('Cambio de Contraseña', $id, '0');
                        if($seg_log->response){
                            $seg_log->state = $this->model->transaction->confirmaTransaccion();
                        }else{
                            $seg_log->state = $this->model->transaction->regresaTransaccion();
                        }
                    }
                }else{
                    $user->state = $this->model->transaction->regresaTransaccion();
                }

                return $res->withJson($user);
            });
        // }

	});
?>