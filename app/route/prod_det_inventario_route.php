<?php 
use App\Lib\Auth,
    App\Lib\Response;

    date_default_timezone_set('America/Mexico_City');

    $app->group('/prod_det_inventario/', function() use ($app){
        $sucursal_id = isset($_SESSION['sucursal_id']) ? $_SESSION['sucursal_id'] : 0;
        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;

        $this->get('get/{id}', function($req, $res, $args){

        });

        $this->post('add/', function($req, $res, $args){
            
        });
    });
?>