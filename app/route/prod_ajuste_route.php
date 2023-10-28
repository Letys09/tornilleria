<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/prod_ajuste/', function () use ($app){

        $this->post('add/', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            $parsedBody = $req->getParsedBody();
            $usuario = $_SESSION['usuario_id']; $sucursal = $_SESSION['sucursal_id']; $producto_id = $parsedBody['prod_id_ajuste']; $fecha = date('Y-m-d H:i:s');
            $accion = $parsedBody['accion'];
          
            $dataAjuste = [
                'producto_id' => $producto_id,
                'usuario_id' => $usuario,
                'fecha' => $fecha,
                'tipo' => $parsedBody['motivo'],
                'cantidad' => $parsedBody['cantidad'],
                'comentarios' => $parsedBody['comentarios']
            ];
            $addAjuste = $this->model->prod_ajuste->add($dataAjuste);
            if($addAjuste->response){
                $inicial = $this->model->prod_stock->getStock($sucursal, $producto_id)->result->final;
                $txt = $accion == 1 ? 'alta de stock' : 'baja de stock';
                $tipo = $accion == 1 ? 1 : -1;
                $final = $accion == 1 ? floatval($inicial + $parsedBody['cantidad']) : floatval($inicial - $parsedBody['cantidad']);
                $origen_tipo = $accion == 1 ? 4 : 5;
                $dataStock = [
                    'usuario_id' => $usuario,
                    'sucursal_id' => $sucursal,
                    'producto_id' => $producto_id,
                    'tipo' => $tipo,
                    'inicial' => $inicial,
                    'cantidad' => $parsedBody['cantidad'],
                    'final' => $final,
                    'fecha' => $fecha,
                    'origen_tipo' => $origen_tipo,
                    'origen_id' => $addAjuste->result
                ];
                $addStock = $this->model->prod_stock->add($dataStock);
                if($addStock->response){
                    $seg_log = $this->model->seg_log->add('Ajuste de inventario '.$txt, 'prod_ajuste', $addAjuste->result, 1);
                    if($seg_log->response){
                        $addAjuste->state = $this->model->transaction->confirmaTransaccion();	
                        return $res->withJson($addAjuste);
                    }else{
                        $seg_log->state = $this->model->transaction->regresaTransaccion();	
                        return $res->withJson($seg_log->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                    }
                }else{
                    $addStock->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($addStock->SetResponse(false, 'No se pudo agregar el registro de stock'));
                }
            }else{
                $addAjuste->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($addAjuste->SetResponse(false, 'No se pudo agregar el registro del ajuste'));
            }
        });

	});

?>