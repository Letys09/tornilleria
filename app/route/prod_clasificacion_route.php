<?php
use App\Lib\Auth,
	App\Lib\Response,
	App\Lib\MiddlewareToken,
	App\Middleware\AccesoMiddleware;

	date_default_timezone_set('America/Mexico_City');

	$app->group('/prod_clasificacion/', function () use ($app){

        $this->get('getAreas', function($req, $res, $args){
            return $res->withJson($this->model->prod_clasificacion->getAreas()->result);
        });

        $this->get('getCategorias', function($req, $res, $args){
            return $res->withJson($this->model->prod_clasificacion->getCategorias()->result);
        });

        $this->get('getSubcategorias/{cat}', function($req, $res, $args){
            return $res->withJson($this->model->prod_clasificacion->getSubcategorias($args['cat'])->result);
        });

		$this->get('getCatByArea/{area_id}', function($req, $res, $args){
			return $res->withJson($this->model->prod_clasificacion->getCatByArea($args['area_id']));
		});

        $this->post('add/{tipo}',function($req, $res, $args){
			$this->model->transaction->iniciaTransaccion();
            $info = $req->getParsedBody();
			if($args['tipo'] == 'area'){
				$areas = $info['areas'];
				unset($info['areas']);
				foreach($areas as $area){
					$data = array(
						'nombre' => $area
					);
					$area = $this->model->prod_clasificacion->add($data, 'prod_area');
					$seg_log = $this->model->seg_log->add('Agrega área', 'prod_area', $area->result, 0);
				}		
				$area->state = $this->model->transaction->confirmaTransaccion();
				return $res->withJson($area);
			}else if($args['tipo'] == 'categoria'){
                $categorias = $info['categorias'];
				unset($info['categorias']);
				foreach($categorias as $categoria){
					$data = array(
						'nombre' => $categoria
					);
					$categoria = $this->model->prod_clasificacion->add($data, 'prod_categoria');
					$seg_log = $this->model->seg_log->add('Agrega categoría', 'prod_categoria', $categoria->result, 0);
				}		
				$categoria->state = $this->model->transaction->confirmaTransaccion();
				return $res->withJson($categoria);
            }else{
				$subcategorias = $info['subcategorias'];
				unset($info['subcategorias']);
				foreach($subcategorias as $subcat){
					$data = array(
						'prod_categoria_id' => $info['categoria_id'],
						'nombre' => $subcat
					);
					$subcat = $this->model->prod_clasificacion->add($data, 'prod_categoria');
					$seg_log = $this->model->seg_log->add('Agrega subcategoria', 'prod_categoria', $subcat->result, 0);
				}		
	
				$subcat->state = $this->model->transaction->confirmaTransaccion();
				return $res->withJson($subcat);
			}
		});

        $this->post('del/{tipo}/{id}', function($req, $res, $args){
            $this->model->transaction->iniciaTransaccion();
            if($args['tipo'] == 'area') {
                $tabla = 'prod_area';
                $txt = 'Elimina área';
            }else if($args['tipo'] == 'categoria'){
                $tabla = 'prod_categoria';
                $txt = 'Elimina categoría';
            }else{
                $tabla = 'prod_categoria';
                $txt = 'Elimina subcategoría';
            }
            $update = $this->model->prod_clasificacion->del($args['id'], $tabla);
            if($update->response){
                $seg_log = $this->model->seg_log->add($txt, $tabla, $args['id'], 1);
                if($seg_log->response){
                    $update->state = $this->model->transaction->confirmaTransaccion();	
                    return $res->withJson($update);
                }else{
                    $seg_log->state = $this->model->transaction->regresaTransaccion();	
                    return $res->withJson($sucursal->SetResponse(false, 'No se pudo agregar el registro de bitácora'));
                }
            }else{
                $update->state = $this->model->transaction->regresaTransaccion();	
                return $res->withJson($update->SetResponse(false, 'No se pudo eliminar el registro'));
            }
        });
	});

?>