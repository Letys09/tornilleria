<?php
use App\Lib\Auth,
	App\Lib\Response;

    date_default_timezone_set('America/Mexico_City');

	$app->group('/cotizacion/', function () use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('get/{id}', function($req, $res, $args){
            $cotizacion_id = $args['id'];
            $cotizacion = $this->model->cotizacion->get($cotizacion_id)->result;
            $cotizacion->detalles = $this->model->coti_detalle->getByCot($cotizacion_id)->result;

            return $res->withJson($venta);
        });

        $this->get('getAllDataTable', function($req, $res, $args){
            $cotizaciones = $this->model->cotizacion->getAllDataTable()->result;
            $data = [];
            foreach($cotizaciones as $cotizacion){
                $fecha = explode('-', $cotizacion->date);
                $fecha = $fecha[0].$fecha[1].$fecha[2];
                $data[] = array(
                    "id" => $cotizacion->id,
                    "fecha" => $cotizacion->date,
                    "hora" => $cotizacion->hora,
                    "folio" => $cotizacion->identificador.'-'.$fecha.'-'.$cotizacion->id, 
                    "usuario" => $cotizacion->usuario, 
                    "cliente_id" => $cotizacion->cliente_id,
                    "cliente" => $cotizacion->cliente, 
                    "total" => $cotizacion->total, 
                );
            }
            echo json_encode(array(
                'data' => $data
            ));

			exit(0);
        });

        $this->post('add/', function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $detalles = $parsedBody['detalles']; unset($parsedBody['detalles']);
            $fecha = date('Y-m-d H:i:s'); $vigencia = date('Y-m-d'); $cliente_id = $parsedBody['cliente_id'];
            $dataCoti = [
                'sucursal_id' => $_SESSION['sucursal_id'],
                'usuario_id' => $_SESSION['usuario_id'],
                'cliente_id' => $cliente_id,
                'fecha' => $fecha,
                'total' => $parsedBody['total'],
                'comentarios' => $parsedBody['comentarios'],
                'vigencia' => $vigencia
            ];
            $addCoti = $this->model->cotizacion->add($dataCoti);
            if($addCoti->response){
                $cotizacion_id = $addCoti->result;
                foreach($detalles as $detalle){
                    $producto_id = $detalle['producto_id'];
                    $info_prod = $this->model->producto->get($producto_id)->result;
                    $total = floatval($detalle['cantidad'] * $detalle['precio']);
                    $dataDet = [
                        'cotizacion_id' => $cotizacion_id,
                        'producto_id' => $producto_id,
                        'cantidad' => $detalle['cantidad'],
                        'precio' => $detalle['precio'],
                        'total' => $total,
                    ];
                    $addDet = $this->model->coti_detalle->add($dataDet);
                    if($addDet->response){
                        $det_id = $addDet->result;
                        $seg_log = $this->model->seg_log->add('Agrega detalle de cotización', 'coti_detalle', $det_id, 0);
                    }else{
                        $addDet->state = $this->model->transaction->regresaTransaccion();
                        return $res->withJson($addDet);
                    }
                }   

                $seg_log = $this->model->seg_log->add('Nueva cotización', 'cotización', $cotizacion_id, 1);
                $addCoti->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($addCoti);
            }else{
                $addCoti->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($addCoti);
            }
		});

        $this->post('edit/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $detalles = $parsedBody['detalles'];
            unset($parsedBody['detalles']);
            $fecha = date('Y-m-d H:i:s');
            $cotizacion = [
                'subtotal' => $parsedBody['total'],
                'total' => $parsedBody['total'],
                'comentarios' => $parsedBody['comentarios'],
                'fecha_actualiza' => $fecha,
            ];
            $edit = $this->model->cotizacion->edit($cotizacion, $args['id']);
            if($edit->response){
                $this->model->seg_log->add('Edita cotización', 'cotizacion', $args['id'], 1);
                $edit->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($edit);
            }else{
                $edit->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($edit);
            }
        });

        $this->post('cancelar/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $coti_id = $args['id'];
            $detalles = $this->model->coti_detalle->getByCot($coti_id)->result;
            foreach($detalles as $detalle){
                $det_id = $detalle->id;
                $del_det = $this->model->coti_detalle->del($det_id);
                if(!$del_det->response){
                    $del_det->state = $this->model->transaction->regresaTransaccion();
                    return $res->withJson($del_det);
                }
            }
            $del = $this->model->cotizacion->del($coti_id);
            if($del->response){
                $this->model->seg_log->add('Cancela cotización', 'cotizacion', $coti_id, 1);
                $del->state = $this->model->transaction->confirmaTransaccion();
                return $res->withJson($del);
            }else{
                $del->state = $this->model->transaction->regresaTransaccion();
                return $res->withJson($del);
            }
        });

        $this->post('convertirVenta/{id}', function($req, $res, $args){
            
        }); 

	});

?>