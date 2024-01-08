<?php 
use App\Lib\Auth,
    App\Lib\Response;

    date_default_timezone_set('America/Mexico_City');

    $app->group('/prod_det_inventario/', function() use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('getByInv/{id}', function($req, $res, $args){
            $detalles = $this->model->prod_det_inventario->get($args['id'])->result;
            foreach($detalles as $detalle){
                $info_prod = $this->model->producto->get($detalle->producto_id)->result;
                $detalle->clave = $info_prod->clave;
                $detalle->descripcion = $info_prod->descripcion;
                $detalle->medida = $info_prod->medida;
            }

            return $res->withJson($detalles);
        });

        $this->get('ajusteCostoInventario', function($req, $res, $args){
            $detalles = $this->model->prod_det_inventario->getAll()->result;
            foreach($detalles as $detalle){
                $cantidad = $detalle->diferencia;
                if($cantidad != 0){
                    $info_prod = $this->model->producto->get($detalle->producto_id)->result;
                    $costo = $info_prod->costo;
                    $monto = $cantidad*$costo;
                    $data = ['monto' => $monto];
                    $update = $this->model->prod_det_inventario->ajusteCostoInventario($data, $detalle->id);
                    print_r($update);
                    print_r($cantidad.'::'.$costo.'::'.$monto);
                    print_r('<hr>');
                }
            }
        });
    });
?>