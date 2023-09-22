<?php
use App\Lib\Auth,
	App\Lib\Response;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/cliente_saldo/', function () use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('getByCli/{id}', function($req, $res, $args){
            $saldo = $this->model->cliente_saldo->getByCli($args['id']);
            if(!is_object($saldo)){
                $saldo = ['response' => false, 'msg' => 'No hay saldo a favor para el cliente'];
            }
            return $res->withJson($saldo);
        });

        $this->post('add/', function($req, $res, $args){
            $parsedBody = $req->getParsedBody();
            $saldo_anterior = $this->model->cliente_saldo->getByCli($parsedBody['cliente_id']);
            if(is_object($saldo_anterior->result)){
                $parsedBody['saldo'] = $saldo_anterior->result->saldo+$parsedBody['cantidad'];
            }else{
                $parsedBody['saldo'] = $parsedBody['cantidad'];
            }
            $add_saldo = $this->model->cliente_saldo->add($parsedBody);
            return $res->withJson($add_saldo);            
		});

	});

?>