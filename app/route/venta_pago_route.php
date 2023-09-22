<?php
use App\Lib\Auth,
	App\Lib\Response;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/venta_pago/', function () use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('getByVenta/{id}', function($req, $res, $args){
            $pagos = $this->model->venta_pago->getByVenta($args['id']);
            if(!is_object($pagos)){
                $pagos = ['response' => false, 'msg' => 'No hay pagos para la venta'];
            }
            return $res->withJson($pagos);
        });

        $this->post('add/', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $venta_id = $parsedBody['venta_id'];
            $fecha = date('Y-m-d H:i:s');
            foreach($pagos as $pago){
                $dataPago = [
                    'venta_id' => $venta_id,
                    'usuario' => $usuario_id,
                    'fecha' => $fecha, 
                    'monto' => $parsedBody['monto'],
                    'forma_pago' => $parsedBody['forma_pago']
                ];
                $addPago = $this->model->venta_pago->add($dataPago);
                if(!$addPago->response){
                    $addPago->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($addPago);
                }else{
                    $seg_log = $this->model->seg_log->add('Agrega pago', 'venta_pago', $addPago->result, 1);
                    $addPago->state = $this->model->transaction->confirmaTransaccion();
                    return $res->withJson($addPago);
                }
            }
            
		});

        $this->post('edit/{id}', function($req, $res, $args){
            $pagoIgual = true;
            $info_pago = $this->model->venta_pago->get($args['id'])->result;
            $pago = [
                'monto' => $parsedBody['monto'],
                'forma_pago' => $parsedBody['forma_pago'],
            ];

            foreach($pago as $field => $value) { 
                if($info_pago->$field != $value) { 
                    $pagoIgual = false; break; 
                } 
            }

            if(!$pagoIgual){
                $edit_det = $this->model->venta_pago->edit($pago, $args['id']);
                if($edit_det->response){
                    $seg_log = $this->model->seg_log->add('Modifica pago', 'venta_pago', $args['id'], 1);
                }else{
                    $edit_det->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($edit_det->setResponse(false, 'No se editó la información del pago'));
                }
            }

            if($pagoIgual){
                $edit_pago = ['code' => 1, 'msg' => 'No existen datos diferentes a los antes registrados'];
                return $res->withJson($edit_pago);
            }else{
                $edit_pago = ['response' => true, 'msg' => 'Registro '.$args['id'].' actualizado'];
                $this->model->transaction->confirmaTransaccion();
                return $res->withJson($edit_pago);
            }
        });

        $this->post('del/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $update = $this->model->venta_pago->del($args['id']);
            if($update->response){
                $seg_log = $this->model->seg_log->add('Elimina pago', 'venta_pago', $args['id'], 1);
                if($seg_log->response){
                    $update->state = $this->model->transaction->confirmaTransaccion();	
                    return $res->withJson($update);
                }else{
                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                }
            }else{
                $update->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($update->SetResponse(false, 'No se pudo eliminar el pago'));
            }
        });

	});

?>