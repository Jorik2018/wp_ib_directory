<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use function IB\directory\Util\cfield;
use function IB\directory\Util\camelCase;
use function IB\directory\Util\cdfield;
use function IB\directory\Util\t_error;

class DirectoryController extends Controller
{

    public function init(){}

    public function rest_api_init(){
        register_rest_route( 'api/directory','region', array(
            'methods' => 'GET',
            'callback' => array($this,'region_get')
        ));
        register_rest_route( 'api/directory','region/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'region_get')
        ));
        register_rest_route('api/directory','province/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'province_get')
        ));
        register_rest_route('api/directory','district/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'district_get')
        ));
        register_rest_route('api/directory','town/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'cp_get')
        ));
    }

    function region_get() {
        global $wpdb;
        $wpdb->last_error  = '';
        $results = $wpdb->get_results( "SELECT d.id_dpto id,d.nombre_dpto name, d.codigo_dpto code FROM drt_departamento d");
        if($wpdb->last_error )return t_error();
        return $results;
    }
    
    function cp_get() {
        global $wpdb;
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT distinct Ubigeo_Centropoblado AS id,
        Ubigeo_Centropoblado AS codccpp,
        Nombre_Centro_Poblado AS name FROM drt_ccpp 
             order by Ubigeo_Distrito,3");
        /*$results = $wpdb->get_results("SELECT 
            concat(ubigeo,codccpp) id,
            codccpp,
            centro_pob name
            FROM `urbano` 
            UNION
            SELECT 
            concat(ubigeo,codccpp),
            codccpp,
            centro_pob
            FROM `rural`
            union 
            SELECT distinct ccpp_cod id,codccpp,nombccpp name FROM muestra_pataz 
             order by 1");*/
        if($wpdb->last_error)return t_error();
        return $results;
    }
    
    function api_town_get($request) {
        global $wpdb;
        $wpdb->last_error='';
        $results = $wpdb->get_results("SELECT substr(zona,1,1) tipo, 
        manzana id, 
        ccpp_cod as code, nombccpp as name, sufzona, sufzona, codmzna, sufmzna, 
        encuesta, hog_ccpp hogares, hog_ccpp vivienda FROM muestra_pataz      
            
            UNION
            SELECT
            'R',
            codigo,
            concat(ubigeo,codccpp) code,
            centro_pob name,
            codzona,
            sufzona,
            codmzna,
            sufmzna,
            encuesta,
            hogares,
            vivienda
            FROM rural
            UNION
            SELECT 'U',
            codigo,
            concat(ubigeo,codccpp),
            centro_pob,
            null,
            null,
            null,
            null,
            encuesta,
            hogares,
            vivienda
            FROM urbano"
        );
        if($wpdb->last_error)return t_error();
        return $results;
    }
    
    function province_get($request) {
        global $wpdb;
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT d.nombre_prov name, d.codigo_prov code FROM drt_provincia d".($request['regionId']?"
            WHERE d.codigo_prov LIKE '".sprintf('%02d',$request['regionId'])."%'":""));
        if($wpdb->last_error )return t_error();
        return $results;
    }
    
    function district_get($request) {
        global $wpdb;
        $wpdb->last_error  = '';
        $results = $wpdb->get_results( "SELECT d.nombre_dist name, d.codigo_dist code FROM drt_distrito d WHERE 
            d.codigo_dist LIKE '".$request['provinceId']."%'");
        if($wpdb->last_error)return t_error();
        return $results;
    }
    
}