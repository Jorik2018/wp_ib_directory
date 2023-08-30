<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use function IB\directory\Util\cfield;
use function IB\directory\Util\camelCase;
use function IB\directory\Util\cdfield;
use function IB\directory\Util\t_error;

class SivicoController extends Controller
{

    public function init(){}

    public function rest_api_init()
    {
        register_rest_route('api/desarrollo-social', 'red/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => array($this,'red_pag')
        ));
        register_rest_route('api/desarrollo-social', 'cie/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => array($this,'cie_pag')
        ));
        register_rest_route('api/desarrollo-social', 'microred/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => array($this,'microred_pag') 
        ));
        register_rest_route('api/desarrollo-social', 'establishment/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => array($this,'eess_pag')
        ));       
    }

    function red_pag($request) {
        global $wpdb;
        //$wpdb = new wpdb('grupoipe_wp980','20penud21.*.','grupoipe_vetatrem','localhost');
        //$wpdb->show_errors();
        $from=$request['from'];
        $to=$request['to'];
        
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM grupoipe_project.MAESTRO_RED d ORDER BY red".($to?"LIMIT ". $from.', '. $to:""),ARRAY_A );
        if($wpdb->last_error )return new WP_Error(500,$wpdb->last_error, array( 'status' => 500 ) );
        foreach ($results as &$r){
            foreach ($r as $key => &$value){
                $v=$r[$key];
                $r[strtolower($key)]=$v;
            }
            $r['name']=$r['red'];
            $r['code']=$r['codigo_red'];
        }
        $count=$wpdb->get_var('SELECT FOUND_ROWS()');
        return $request['to']?array('data'=>$results,'size'=>$count):$results;
    }
    
    function microred_pag($request) {
        global $wpdb;
        //$wpdb = new wpdb('grupoipe_wp980','20penud21.*.','grupoipe_vetatrem','localhost');
        //$wpdb->show_errors();
        $from=$request['from'];
        $to=$request['to'];
        if(!$to)$to=10000;
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM grupoipe_project.MAESTRO_MICRORED d WHERE 1=1 "
            .($request->get_param('red')?"AND codigo_red=".$request->get_param('red'):"")." ORDER BY microred LIMIT ". $from.', '. $to,ARRAY_A );
        if($wpdb->last_error )return new WP_Error(500,$wpdb->last_error, array( 'status' => 500 ) );
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');
        foreach ($results as &$r){
            foreach ($r as $key => &$value){
                $v=$r[$key];
                //unset($r[$key]);
                $r[strtolower($key)]=$v;
            }
            $r['name']=$r['microred'];
            $r['code']=$r['codigo_cocadenado'];
            
        }
        return $request['to']?array('data'=>$results,'size'=>$count):$results;
    }
    
    function cie_pag($request) {
        global $wpdb;
        $from=$request['from'];
        $to=$request['to'];
        if(!$to)$to=100000;
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM grupoipe_wp980.drt_cie d WHERE 1=1 ".
        ($request->get_param('microred')?("AND Codigo_Cocadenado=".$request->get_param('microred')):"")." ORDER BY Descripcion_Item LIMIT ". $from.', '. $to, ARRAY_A  );
        if($wpdb->last_error )return t_error();
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');
        foreach ($results as &$r){
            foreach ($r as $key => &$value){
                $r[strtolower($key)]=$r[$key];
            }
            $r['name']=$r['Descripcion_Item'];
        }
        return $request['to']?array('data'=>$results,'size'=>$count):$results;
    }
    
    function eess_pag($request) {
        global $wpdb;
        $from=$request['from'];
        $to=$request['to'];
        if(!$to)$to=10000;
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS CONCAT(LPAD(codigo_disa, 2, 0),codigo_red,codigo_microrred) AS microredCode,codigo_unico as code,Nombre_del_establecimiento AS name,ubigeo, CASE WHEN categoria IN ('I-4', 'II-1' ,'II-2') THEN 1 ELSE 0 END AS type,categoria FROM drt_renipress d WHERE 1=1 AND CONCAT(LPAD(codigo_disa, 2, 0),codigo_red,codigo_microrred) like '02%' ".
        ($request->get_param('microred')?("AND CONCAT(LPAD(codigo_disa, 2, 0),codigo_red,codigo_microrred)='".$request->get_param('microred'))."'":"")." ORDER BY  nombre_del_establecimiento LIMIT ". $from.', '. $to, ARRAY_A  );
        if($wpdb->last_error )return t_error();
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');
        return $request['to']?array('data'=>$results,'size'=>$count):$results;
    }
    
    function api_sivico_search($request){
        global $wpdb;
        $from=$request['from'];
        $q=$request['query'];
        $to=$request['to'];
        $wpdb->last_error  = '';
        $q='%'.($q?str_replace(" ","%",$q):'').'%';
        $q='%';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS d.*,sp.*,concat(sp.surnames,sp.names) fullName FROM ds_sivico d 
        LEFT OUTER JOIN ds_sivico_people sp ON sp.master_id=d.id AND sp.canceled=0 where d.canceled=0 
        AND (
        (sp.code is null OR sp.code LIKE '$q')
        OR (sp.names is null OR concat(sp.surnames,sp.names) LIKE '$q')
        OR (d.informante is null OR d.informante LIKE '$q')
        )
        ".($to?" LIMIT $from,$to":''), OBJECT );
        if($wpdb->last_error )return t_error();
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');
        return $request['to']?array(
            'q'=>$q,
            'sql',"SELECT SQL_CALC_FOUND_ROWS * FROM ds_sivico d 
        LEFT OUTER JOIN ds_sivico_people sp ON sp.master_id=d.id AND sp.canceled=0 where d.canceled=0 
        AND (
        (sp.code is null OR sp.code LIKE '$q')
        OR (sp.names is null OR concat(sp.surnames,sp.names) LIKE '$q')
        OR (d.informante is null OR d.informante LIKE '$q')
        )
        ".($to?" LIMIT $from,$to":''),
            'data'=>$results,'size'=>$count):$results;
    }
    
}