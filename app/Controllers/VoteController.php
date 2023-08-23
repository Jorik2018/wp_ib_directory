<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use IB\directory\Util;
require_once __DIR__ . '/../Util/Utils.php';

class VoteRestController extends Controller
{

    public function rest_api_init(){
        register_rest_route( 'api/voting','/act', array(
            'methods' => 'POST',
            'callback' => array($this,'voting_post')
        ));
    }
   
    function voting_post($request) {
        global $wpdb;
        $r=$request->get_params();
        unset($r['lugar']);
        $current_user = wp_get_current_user();
        $r['registrador']=$current_user->user_login;
        $r['registrador_id']=$current_user->ID;
        $results = $wpdb->get_results( "SELECT * FROM acta d WHERE d.mesa=".$r['mesa']);
        $id=0;
        if(!empty($results)){ 
            foreach($results as $row){ 
                $id=$row->id;  
                $updated = $wpdb->update('acta', $r, array(id=>$id) );
                $r['id']=$id;
            }
        }else{
            $updated = $wpdb->insert('acta',$r);
            $r['id']=$wpdb->insert_id;
        }
        if(false === $updated)return t_error();
        return $r;
    }

}