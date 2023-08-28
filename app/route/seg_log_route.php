<?php
	use App\Lib\Response;

    $app->group('/seg_log/', function () {
        $this->get('', function ($req, $res, $args) {
              return $res->withHeader('Content-type', 'text/html')
                         ->write('Soy ruta de SegLog');
        });
        
        
        $this->get('getLog/{from}/{to}', function($req, $res, $args){
            $registros = $this->model->seg_log->getLog($args['from'], $args['to']);    
            $data=[];
    
            foreach($registros as $registro){
                $adicional = "";
                if($registro->descripcion == 'Inicio de sesión'){
                    $fkSession = $registro->seg_session_id ;
                    $session = $this->model->seg_log->getSession($fkSession);
                    $info = parse_user_agent($session->user_agent);
                    $adicional = $info['browser'].' en '.$info['platform'].' ['.$session->ip_address.']';
                }else if($registro->descripcion == 'Agrega usuario' || $registro->descripcion == 'Modifica usuario' || $registro->descripcion == 'Asigna permiso' || $registro->descripcion == 'Elimina permiso'){
                    $usuario = $this->model->usuario->get($registro->registro)->result;
                    $adicional = $usuario->nombre.' '.$usuario->apellidos;
                }
              
                $data[] =array(
                    "fecha" => $registro->fecha,
                    "usuario" => $registro->login,
                    "descripcion" => $registro->descripcion,
                    "adicional" => $adicional,
                );
            }
    
            echo json_encode(array(
                'data' => $data
            ));
    
            exit(0);
        });
    });

    function parse_user_agent( $u_agent = null ) {
        if( is_null($u_agent) ) {
            if(isset($_SERVER['HTTP_USER_AGENT'])) {
                $u_agent = $_SERVER['HTTP_USER_AGENT'];
            }
        }
    
        $platform = null;
        $browser  = null;
        $version  = null;
    
        $empty = array( 'platform' => $platform, 'browser' => $browser, 'version' => $version );
    
        if( !$u_agent ) return $empty;
    
        if( preg_match('/\((.*?)\)/im', $u_agent, $parent_matches) ) {
    
            preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|iPhone|iPad|Linux|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|Nintendo\ (WiiU?|3DS)|Xbox(\ One)?)
                    (?:\ [^;]*)?
                    (?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);
    
            $priority           = array( 'Android', 'Xbox One', 'Xbox' );
            $result['platform'] = array_unique($result['platform']);
            if( count($result['platform']) > 1 ) {
                if( $keys = array_intersect($priority, $result['platform']) ) {
                    $platform = reset($keys);
                } else {
                    $platform = $result['platform'][0];
                }
            } elseif( isset($result['platform'][0]) ) {
                $platform = $result['platform'][0];
            }
        }
    
        if( $platform == 'linux-gnu' ) {
            $platform = 'Linux';
        } elseif( $platform == 'CrOS' ) {
            $platform = 'Chrome OS';
        }
    
        preg_match_all('%(?P<browser>Camino|Kindle(\ Fire\ Build)?|Firefox|Iceweasel|Safari|MSIE|Trident/.*rv|AppleWebKit|Chrome|IEMobile|Opera|OPR|Silk|Lynx|Midori|Version|Wget|curl|NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
                (?:\)?;?)
                (?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
            $u_agent, $result, PREG_PATTERN_ORDER);
    
    
        // If nothing matched, return null (to avoid undefined index errors)
        if( !isset($result['browser'][0]) || !isset($result['version'][0]) ) {
            return $empty;
        }
    
        $browser = $result['browser'][0];
        $version = $result['version'][0];
    
        // $find = create_function('$search, &$key, $res' , '$xkey = array_search(strtolower($search), array_map("strtolower", $res["browser"]));
        //     if( $xkey !== false ) {
        //         $key = $xkey;
    
        //         return true;
        //     }
    
        //     return false;');

        $find = function($search, &$key, $res) {
            $xkey = array_search(strtolower($search), array_map("strtolower", $res["browser"]));
            if( $xkey !== false ) {
                $key = $xkey;
    
                return true;
            }
    
            return false;
        };
    
        $key = 0;
        if( $browser == 'Iceweasel' ) {
            $browser = 'Firefox';
        } elseif( $find('Playstation Vita', $key, $result) ) {
            $platform = 'PlayStation Vita';
            $browser  = 'Browser';
        } elseif( $find('Kindle Fire Build', $key, $result) || $find('Silk', $key, $result) ) {
            $browser  = $result['browser'][$key] == 'Silk' ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if( !($version = $result['version'][$key]) || !is_numeric($version[0]) ) {
                $version = $result['version'][array_search('Version', $result['browser'])];
            }
        } elseif( $find('NintendoBrowser', $key, $result) || $platform == 'Nintendo 3DS' ) {
            $browser = 'NintendoBrowser';
            $version = $result['version'][$key];
        } elseif( $find('Kindle', $key, $result) ) {
            $browser  = $result['browser'][$key];
            $platform = 'Kindle';
            $version  = $result['version'][$key];
        } elseif( $find('OPR', $key, $result) ) {
            $browser = 'Opera Next';
            $version = $result['version'][$key];
        } elseif( $find('Opera', $key, $result) ) {
            $browser = 'Opera';
            $find('Version', $key, $result);
            $version = $result['version'][$key];
        } elseif( $find('Midori', $key, $result) ) {
            $browser = 'Midori';
            $version = $result['version'][$key];
        } elseif( $find('Chrome', $key, $result) ) {
            $browser = 'Chrome';
            $version = $result['version'][$key];
        } elseif( $browser == 'AppleWebKit' ) {
            if( ($platform == 'Android' && !($key = 0)) ) {
                $browser = 'Android Browser';
            } elseif( strpos($platform, 'BB') === 0 ) {
                $browser  = 'BlackBerry Browser';
                $platform = 'BlackBerry';
            } elseif( $platform == 'BlackBerry' || $platform == 'PlayBook' ) {
                $browser = 'BlackBerry Browser';
            } elseif( $find('Safari', $key, $result) ) {
                $browser = 'Safari';
            }
    
            $find('Version', $key, $result);
    
            $version = $result['version'][$key];
        } elseif( $browser == 'MSIE' || strpos($browser, 'Trident') !== false ) {
            if( $find('IEMobile', $key, $result) ) {
                $browser = 'IEMobile';
            } else {
                $browser = 'MSIE';
                $key     = 0;
            }
            $version = $result['version'][$key];
        } elseif( $key = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser'])) ) {
            $key = reset($key);
    
            $platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $key);
            $browser  = 'NetFront';
        }
    
        return array( 'platform' => $platform, 'browser' => $browser, 'version' => $version );
    
    }


?>