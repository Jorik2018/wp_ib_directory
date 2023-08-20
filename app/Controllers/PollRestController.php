<?php

namespace IB\cv\Controllers;

use WPMVC\MVC\Controller;
use IB\cv\Util;
require_once __DIR__ . '/../Util/Utils.php';

class SivicoRestController extends Controller
{

    public function init(){
        add_role(
            'pregnant_admin',
            'pregnant_admin',
            array(
                'PREGNANT_ADMIN'         => true,
                'PREGNANT_READ'         => true
            )
        );
        add_role(
            'pregnant_register',
            'pregnant_register',
            array(
                'PREGNANT_REGISTER'         => true,
                'PREGNANT_READ'         => true
            )
        );
    }

    public function rest_api_init()
    {
        register_rest_route( '/admin/desarrollo-social/api','/sivico/bulk', array(
            'methods' => 'POST',
            'callback' => 'api_sivico_bulk_func',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/(?P<id>\d+)',array(
            'methods' => 'GET',
            'callback' => 'api_sivico_get',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/(?P<id>\d+)',array(
            'methods' => 'DELETE',
            'callback' => 'api_sivico_delete',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => 'api_sivico_pag',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico',array(
            'methods' => 'POST',
            'callback' => 'api_sivico_post',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/people',array(
            'methods' => 'POST',
            'callback' => 'api_sivico_people_post',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/search/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => 'api_sivico_search',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/people/(?P<id>\d+)',array(
            'methods' => 'GET',
            'callback' => 'api_sivico_people_get',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/people/(?P<id>\d+)',array(
            'methods' => 'DELETE',
            'callback' => 'api_sivico_people_delete',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/people/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => 'api_sivico_people_pag',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/agreement/(?P<id>\d+)',array(
            'methods' => 'GET',
            'callback' => 'api_sivico_agreement_get',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/agreement/(?P<id>\d+)',array(
            'methods' => 'DELETE',
            'callback' => 'api_sivico_agreement_delete',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/agreement/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => 'api_sivico_agreement_pag',
        ));
        register_rest_route('/admin/desarrollo-social/api', '/sivico/agreement',array(
            'methods' => 'POST',
            'callback' => 'api_sivico_agreement_post',
        ));       
    }
    

    function api_poll_bulk_func($request) {
        global $wpdb;
        $rl=$request->get_params();
        $poll=remove($rl,'poll');
        file_put_contents("data2.json", json_encode($rl));
        $current_user=wp_get_current_user();
        $aux=array();
        foreach ($rl as &$o) {
            $o['poll']=$poll;
            $aux[]=api_poll_post($o);
        }
        return $aux;
    }
    
    function api_poll_post($request) {
        global $wpdb;
        $current_user = wp_get_current_user();
        $o=method_exists($request,'get_params')?$request->get_params():$request;
        $poll=remove($o,'poll');
        remove($o,'departamento');
        $tmpId=remove($o,'tmpId');
        $peoples=remove($o,'people');
        $o['uid']=$current_user->ID;
        $inserted=false;
        $wpdb->query('START TRANSACTION');
        if($o['id']>0)
            $updated = $wpdb->update('encuesta_'.$poll,$o,array('id'=>$o['id']));
        else{
            unset($o['id']);
            if($tmpId)$o['offline']=$tmpId;
            $updated = $wpdb->insert('encuesta_'.$poll,$o);
            $o['id']=$wpdb->insert_id;
            $inserted=1;
        }
        if(false === $updated)return t_error();
        if($inserted&&$tmpId){
            $updated = $wpdb->update('encuesta_people_'.$poll,array('encuesta_id'=>$o['id']),array('encuesta_id'=>-$tmpId));
            if(false===$updated)return t_error();
        }
        if($tmpId){
            $o['tmpId']=$tmpId;
            $o['synchronized']=1;
        }
        if($peoples){
            foreach($peoples as $key=>&$people){
                $people['encuesta_id']=$o['id'];
                $people['poll']=$poll;
                $peoples[$key]=api_poll_people_post($people);
            }
            $o['people']=$peoples;
        }
        $wpdb->query('COMMIT');
        return $o;
    }
    
    function api_poll_people_post($request) {
        global $wpdb;
        $current_user = wp_get_current_user();
        $o=method_exists($request,'get_params')?$request->get_params():$request;
        remove($o,'people');
        remove($o,'parent');
        $poll=remove($o,'poll');
        $tmpId=remove($o,'tmpId');
        unset($o['synchronized']);
        $o['uid']=$current_user->ID;
        if(!($o['encuesta_id']>0))return t_error('El miembro de la familia debe relacionarse a una encuesta de hogar valida. ENCUESTA_ID= '.$o['encuesta_id']);
        if($o['id']>0)
            $updated=$wpdb->update('encuesta_people_'.$poll,$o,array(id=>$o['id']));
        else{
            unset($o['id']);
            if($tmpId)$o['offline']=$tmpId;
            $updated=$wpdb->insert('encuesta_people_'.$poll,$o);
            $o['id']=$wpdb->insert_id;
            $inserted=1;
        }
        if(false===$updated)return t_error();
        if($tmpId){
            $o['tmpId']=$tmpId;
            $o['synchronized']=1;
        }
        return $o;
    }
    
    function api_poll_people_delete($request){
        global $wpdb;
        $poll=$request->get_param('poll');
        $wpdb->last_error='';
        $row = $wpdb->update('encuesta_people_'.$poll,array('canceled'=>1),array('id'=>$request['id']));
        if($wpdb->last_error )return t_error();
        return $row;
    }
    function api_poll_get($request){
        global $wpdb;
        $current_user = wp_get_current_user();
        $poll=$request['poll'];
        $wpdb->last_error='';
        $o= $wpdb->get_row($wpdb->prepare("SELECT * FROM encuesta_$poll WHERE id=".$request['id']));
        if($wpdb->last_error )return t_error();
        return $o;
    }
    
    function api_poll_people_get($request){
        global $wpdb;
        $poll=$request['poll'];
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM encuesta_people_".$poll." WHERE id=".$request['id']));
    }
    
    function api_poll_pag($request){
        global $wpdb;
        $current_user=wp_get_current_user();
        $from=$request['from'];
        $to=$request['to'];
        $poll=$request->get_param('poll');
        $wpdb->last_error='';
        $results=$wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM encuesta_$poll d Where uid=".$current_user->ID." ORDER BY id desc LIMIT ". $from.', '. $to, OBJECT );
        if($wpdb->last_error)return t_error();
        $count=$wpdb->get_var('SELECT FOUND_ROWS()');
        return array('data'=>$results,'size'=>$count);
    }
    
    function api_poll_people_pag($data){
        global $wpdb;
        $q=$data->get_param('query');
        if($q)$q='%'.$q.'%';
        $encuesta=$data->get_param('encuesta_id');
        $poll=$data->get_param('poll');
        $from=$data['from'];
        $to=$data['to'];
        $wpdb->last_error='';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM encuesta_people_".$poll." d  WHERE canceled=0 ".($q?" AND fullname like '".$q."'":"")
        .($encuesta?'AND d.encuesta_id='.$encuesta:'').' '.($to?'  LIMIT '. $from.', '.$to:''), OBJECT );
        if($wpdb->last_error )return t_error();
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');
        return array('data'=>$results,'size'=>$count);
    }
    

}