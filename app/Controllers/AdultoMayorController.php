<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use function IB\directory\Util\cfield;
use function IB\directory\Util\camelCase;
use function IB\directory\Util\cdfield;
use function IB\directory\Util\t_error;
use function \wp_get_current_user;

class AdultoMayorController extends Controller
{

    public function init(){
        add_role(
            'adulto_mayor_admin',
            'adulto_mayor_admin',
            array(
                'DESARROLLO_SOCIAL_ADULTO_MAYOR_ADMIN'         => true,
                'DESARROLLO_SOCIAL_ADULTO_MAYOR_READ'         => true
            )
        );
        add_role(
            'adulto_mayor_register',
            'adulto_mayor_register',
            array(
                'CDESARROLLO_SOCIAL_ADULTO_MAYOR_ADMIN'         => true,
                'DESARROLLO_SOCIAL_ADULTO_MAYOR_READ'         => true
            )
        );
    }

    public function rest_api_init()
    {
 
        register_rest_route( 'api/desarrollo-social','/adulto-mayor', array(
            'methods' => 'POST',
            'callback' => array($this,'post')
        ));

        register_rest_route( 'api/desarrollo-social','/adulto-mayor/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'pag')
        ));

        register_rest_route( 'api/desarrollo-social','/adulto-mayor/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'get')
        ));

        register_rest_route( 'api/desarrollo-social', '/adulto-mayor/(?P<ids>[0-9,]+)',array(
            'methods' => 'DELETE',
            'callback' => array($this,'delete')
        ));

        register_rest_route('api/desarrollo-social', '/adulto-mayor/bulk',array(
            'methods' => 'POST',
            'callback' => array($this,'bulk')
        ));

    }

    function bulk($request) {
        global $wpdb;
        $rl=$request->get_params();
        $aux=array();
        foreach ($rl as &$o) {
            $aux[]=$this->post($o);
        }
        return $aux;
    }

    public function post($request){
        global $wpdb;
        $o=method_exists($request,'get_params')?$request->get_params():$request;
        $current_user = wp_get_current_user();
        cdfield($o,'fecha_nacimiento');
        cdfield($o,'fecha_visita');
        cdfield($o,'fecha_dosaje_glucosa');
        cdfield($o,'fecha_dosaje_hemoglobina');
        cdfield($o,'fecha_dosaje_lipidos');
        cdfield($o,'fecha_control_pa');
        cdfield($o,'fecha_papanicolao');
        cdfield($o,'fecha_mamografia');
        if(isset($o['apellidos_nombres']))$o['apellidos_nombres']=strtoupper($o['apellidos_nombres']);

        cdfield($o,'fur');
        remove($o,'district_name');
        remove($o,'canceled');
        $tmpId=remove($o,'tmpId');
        unset($o['synchronized']);
        $original_db = $wpdb->dbname;
        $wpdb->select('grupoipe_erp');
        $inserted=0;
        if($o['id']>0){
            $o['update_uid']=$current_user->ID;
            $o['update_user']=$current_user->user_login;
            $o['update_date']=current_time('mysql', 1);
            $updated=$wpdb->update('mon_adultomayor',$o,array('id'=>$o['id']));
        }else{
            unset($o['id']);
            $o['insert_uid']=$current_user->ID;
            $o['insert_user']=$current_user->user_login;
            $o['insert_date']=current_time('mysql', 1);
            if($tmpId)$o['offline']=$tmpId;
            $updated=$wpdb->insert('mon_adultomayor',$o);
            $o['id']=$wpdb->insert_id;
            $inserted=1;
        }
        $wpdb->select($original_db);
        if(false === $updated)return t_error();
        if($tmpId){
            $o['tmpId']=$tmpId;
            $o['synchronized']=1;
        }
        return $o; 
    }

    public function get($request){    
        global $wpdb;
        $original_db = $wpdb->dbname;
        $wpdb->select('grupoipe_erp');
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM mon_adultomayor WHERE id=".$request['id']),ARRAY_A);
        $wpdb->select($original_db);
        if($wpdb->last_error )return t_error();
        return $o;
    }

    public function pag($request){
        global $wpdb;
        $from=$request['from'];
        $to=$request['to'];
        $apellidos_nombres=method_exists($request,'get_param')?$request->get_param('apellidos_nombres'):$request['apellidos_nombres'];
        $dni=method_exists($request,'get_param')?$request->get_param('dni'):$request['dni'];
        $province=method_exists($request,'get_param')?$request->get_param('province'):$request['province'];
        $establecimiento=method_exists($request,'get_param')?$request->get_param('establecimiento'):$request['establecimiento'];
        $current_user = wp_get_current_user();
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS o.* FROM grupoipe_erp.mon_adultomayor o ".
            "WHERE o.canceled=0 ".
            (isset($apellidos_nombres)?" AND o.apellidos_nombres LIKE '%$apellidos_nombres%' ":"").
            (isset($dni)?" AND o.dni LIKE '%$dni%' ":"").
            (isset($establecimiento)?" AND o.establecimiento LIKE '%$establecimiento%' ":"").
            (isset($province)?" AND o.province LIKE '%$province%' ":"").
            ($to>0?("LIMIT ". $from.', '. $to):""), ARRAY_A );
        
        if($wpdb->last_error )return t_error();
        return $to>0?array('data'=>$results,'size'=>$wpdb->get_var('SELECT FOUND_ROWS()')):$results;
    }

    public function delete($data){
        global $wpdb;
        $original_db = $wpdb->dbname;
        $wpdb->select('grupoipe_erp');
        $wpdb->query('START TRANSACTION');
        $result = array_map(function ($id) use ($wpdb) {
            return $wpdb->update('mon_adultomayor', array('canceled' => 1, 'delete_date' => current_time('mysql')), array('id' => $id));
        }, explode(",", $data['id']));
        $success = !in_array(false, $result, true);
        if ($success) {
            $wpdb->query('COMMIT');
        } else {
            $wpdb->query('ROLLBACK');
        }
        $wpdb->select($original_db);
        return $success;
    }
}