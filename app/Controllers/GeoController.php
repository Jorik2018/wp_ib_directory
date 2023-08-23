<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use IB\directory\Util;
require_once __DIR__ . '/../Util/Utils.php';

class GeoController extends Controller
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
 
        register_rest_route('/api', '/vehicle',array(
            'methods' => 'GET',
            'callback' => 'api_vehicle_get',
        ));
        register_rest_route('/api', '/location',array(
            'methods' => 'POST',
            'callback' => 'api_location_post',
        ));
        register_rest_route('/api', '/locations',array(
            'methods' => 'POST',
            'callback' => 'api_locations_post',
        ));
        register_rest_route('/api/geo', '/location',array(
            'methods' => 'GET',
            'callback' => 'api_geo_location_get',
        ));
        
        register_rest_route('/api/geo', '/location/vehicle',array(
            'methods' => 'GET',
            'callback' => 'api_geo_location_vehicle_get',
        ));
        
        
        register_rest_route('/api/geo', '/path',array(
            'methods' => 'POST',
            'callback' => 'api_geo_path_post',
        ));
        register_rest_route('/api/geo', '/path',array(
            'methods' => 'GET',
            'callback' => 'api_geo_path_get',
        ));
    }
    
    function api_location_post($request) {

    global $wpdb;

    $current_user = wp_get_current_user();
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $o['uid']=$current_user->ID;
    //cdfield($o,'time');
    cfield($o,'altitudeAccuracy','altitude_accuracy');
    $tmpId=remove($o,'tmp');
    unset($o['synchronized']);
    $wpdb->query('START TRANSACTION');
    if($o['id']>0)
    $updated = $wpdb->update('location',$o,array('id'=>$o['id']));
    else{
    unset($o['id']);
    if($tmpId)$o['offline']=$tmpId;
    $updated = $wpdb->insert('location',$o);
    $o['id']=$wpdb->insert_id;
    $inserted=1;
    }
    if(false === $updated)return t_error();
    if($tmpId){
    $o['tmp']=$tmpId;
    $o['synchronized']=1;
    }
    $wpdb->query('COMMIT');
    return $o;
    }

    function api_locations_post($request) {
    global $wpdb;
    $current_user = wp_get_current_user();
    $rl=method_exists($request,'get_params')?$request->get_params():$request;
    $aux=array();
    foreach ($rl as &$o) {
    $aux[]=api_location_post($o);
    }
    return $aux;
    }

    function api_geo_location_get($data){
    global $wpdb;
    $row = $wpdb->get_results($wpdb->prepare("SELECT * FROM location where DATE(FROM_UNIXTIME(time/1000))=DATE(%s) AND plate=%s ORDER BY location.time DESC",
    $data->get_param('date'),$data->get_param('plate')
    ),ARRAY_A);
    return $row;
    }

    function api_geo_location_vehicle_get($data){
    global $wpdb;
    $row = $wpdb->get_results($wpdb->prepare("select * from (

        SELECT l.*,@rn:=IF(@prev COLLATE utf8mb4_unicode_ci <> plate, 0,@rn+1) rn, @prev:=plate prev
        FROM `location` l,(SELECT @rn := 0,@prev:='') rn

        order by plate, time desc) t WHERE rn=0"),ARRAY_A);
        return $row;
    }

    function api_geo_path_post($request) {
    global $wpdb;
    $current_user = wp_get_current_user();
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $wpdb->query('TRUNCATE TABLE path');
    $points=$o['points'];
    foreach($points as $p){
    $pp=array('parent'=>1,lat=>$p[0],lon=>$p[1]);
    $updated = $wpdb->insert('path',$pp);
    }
    return $o;
    }

    function api_geo_path_get($data){
    global $wpdb;
    $row = $wpdb->get_results($wpdb->prepare("SELECT * FROM path"),ARRAY_A);
    return $row;
    }

    function api_vehicle_get($data){
    global $wpdb;
    $row = $wpdb->get_results($wpdb->prepare("SELECT * FROM vehicle"),ARRAY_A);
    return $row;
    }

    function api_geo_path_delete($data){
    global $wpdb;
    return $wpdb->query('TRUNCATE TABLE tablename');
    }
    
}