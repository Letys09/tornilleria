<?php
use App\Lib\Auth,
	App\Lib\Response;

    date_default_timezone_set('America/Mexico_City');

	$app->group('/coti_detalle/', function () use ($app){

        $this->get('get/{id}', function($req, $res, $args){
            $venta_id = $args['id'];
            $venta = $this->model->venta->get($venta_id)->result;
            $venta->detalles = $this->model->venta_detalle->getByVenta($venta_id)->result;
            $venta->pagos = $this->model->venta_pago->getByVenta($venta_id)->result;

            return $res->withJson($venta);
        });

        $this->post('add/', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $cotizacion_id = $parsedBody['cotizacion_id'];
            $producto_id = $parsedBody['producto_id'];
            $fecha = date('Y-m-d H:i:s'); 
            $dataDet = [
                'cotizacion_id' => $cotizacion_id,
                'producto_id' => $producto_id,
                'cantidad' => $parsedBody['cantidad'],
                'precio' => $parsedBody['precio'],
                'importe' => $parsedBody['importe'],
                'total' => $parsedBody['total'],
            ];
            $addDet = $this->model->coti_detalle->add($dataDet);
            if($addDet->response){
                $det_id = $addDet->result;
                $seg_log = $this->model->seg_log->add('Agrega detalle a cotización', 'coti_detalle', $det_id, 1);
                $addDet->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($addDet);
            }else{
                $addDet->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addDet);
            }
		});

        $this->post('edit/{id}', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $req->getParsedBody();
            $fecha = date('Y-m-d H:i:s');

            $detIgual = true;
            $info_det = $this->model->coti_detalle->get($args['id'])->result;
            $detalle = [
                'cantidad' => $parsedBody['cantidad'],
                'precio' => $parsedBody['precio'],
                'importe' => $parsedBody['importe'],
                'descuento' => $parsedBody['descuento'],
                'total' => $parsedBody['total'],
            ];

            foreach($detalle as $field => $value) { 
                if($info_det->$field != $value) { 
                    $detIgual = false; break; 
                } 
            }

            if(!$detIgual){
                $edit_det = $this->model->coti_detalle->edit($detalle, $args['id']);
                if($edit_det->response){
                    $seg_log = $this->model->seg_log->add('Modifica detalle de cotización', 'coti_detalle', $args['id'], 1);
                }else{
                    $edit_det->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($edit_det->setResponse(false, 'No se editó la información del detalle de cotización'));
                }
            }
			
            if($detIgual){
                $edit_detalle = ['code' => 1, 'msg' => 'No existen datos diferentes a los antes registrados'];
                return $res->withJson($edit_detalle);
            }else{
                $edit_detalle = ['response' => true, 'msg' => 'Registro '.$args['id'].' actualizado'];
                $this->model->transaction->confirmaTransaccion();
                return $res->withJson($edit_detalle);
            }
		});

        $this->post('del/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $del_detalle = $this->model->coti_detalle->del($args['id']);
            if($del_detalle->response){
                $seg_log = $this->model->seg_log->add('Elimina detalle de cotización', 'coti_detalle', $args['id'], 1);
                if($seg_log->response){
                    $del_detalle->state = $this->model->transaction->confirmaTransaccion();	
                    return $res->withJson($del_detalle);
                }else{
                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                }
            }else{
                $del_detalle->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($del_detalle->SetResponse(false, 'No se pudo eliminar el detalle de la cotización'));
            }
        });

	});

?>