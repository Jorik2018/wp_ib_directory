<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use function IB\directory\Util\cfield;
use function IB\directory\Util\camelCase;
use function IB\directory\Util\cdfield;
use function IB\directory\Util\t_error;

class CancerController extends Controller
{

    public function init(){
        add_role(
            'cancer_admin',
            'cancer_admin',
            array(
                'CANCER_ADMIN'         => true,
                'CANCER_READ'         => true
            )
        );
        add_role(
            'cancer_register',
            'cancer_register',
            array(
                'CANCER_ADMIN'         => true,
                'CANCER_READ'         => true
            )
        );

        add_role(
            'inventory_register',
            'inventory_register',
            array(
                'INVENTORY_ADMIN'         => true,
                'INVENTORY_READ'         => true
            )
        );
        add_role(
            'inventory_register',
            'inventory_register',
            array(
                'INVENTORY_ADMIN'         => true,
                'INVENTORY_READ'         => true
            )
        );
    }

    public function rest_api_init()
    {
 
        register_rest_route( 'api/desarrollo-social','/cancer', array(
            'methods' => 'POST',
            'callback' => array($this,'post')
        ));

        register_rest_route( 'api/desarrollo-social','/cancer/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'pag')
        ));

        register_rest_route( 'api/desarrollo-social','/cancer/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'get')
        ));

        register_rest_route( 'api/desarrollo-social', '/cancer/(?P<id>)',array(
            'methods' => 'DELETE',
            'callback' => array($this,'delete')
        ));

        register_rest_route('api/desarrollo-social', '/cancer/bulk',array(
            'methods' => 'POST',
            'callback' => array($this,'bulk')
        ));

    }

    function bulk($request) {
        global $wpdb;
        $rl=$request->get_params();
        $aux=array();
        foreach ($rl as &$o) {
            $aux[]=$this-post($o);
        }
        return $aux;
    }

    public function post($request){
        global $wpdb;
        $o=method_exists($request,'get_params')?$request->get_params():$request;
        $current_user = wp_get_current_user();
        cfield($o,'emedId','emed_id');
        cdfield($o,'fecha_nacimiento');
        cdfield($o,'fur');
        remove($o,'district_name');
        $tmpId=remove($o,'tmpId');
        unset($o['synchronized']);
        $inserted=0;
        if($o['id']>0){
            $o['uid_update']=$current_user->ID;
            $o['user_update']=$current_user->user_login;
            $o['update_date']=current_time('mysql', 1);
            $updated=$wpdb->update('ds_cancer',$o,array('id'=>$o['id']));
        }else{
            unset($o['id']);
            $o['uid_insert']=$current_user->ID;
            $o['user_insert']=$current_user->user_login;
            $o['insert_date']=current_time('mysql', 1);
            if($tmpId)$o['offline']=$tmpId;
            $updated=$wpdb->insert('ds_cancer',$o);
            $o['id']=$wpdb->insert_id;
            $inserted=1;
        }
        if(false === $updated)return t_error();
        if($tmpId){
            $o['tmpId']=$tmpId;
            $o['synchronized']=1;
        }
        return $o; 
    }

    public function get($request){    
        global $wpdb;
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_cancer WHERE id=".$request['id']),ARRAY_A);
        if($wpdb->last_error )return t_error();
        cfield($o,'emed_id','emedId');
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
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS o.* FROM ds_cancer o ".
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
        $row = $wpdb->update('ds_cancer',array('canceled'=>1),array('id'=>$data['id']));
        return $row;
    }
}