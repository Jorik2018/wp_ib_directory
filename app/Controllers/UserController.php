<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use IB\directory\Util;
require_once __DIR__ . '/../Util/Utils.php';

class UserController extends Controller{

    const API_USER = 'api/user';

    public function init(){
        add_role(
            'supervisor',
            'Supervisor',
            array(
                'supervise'         => true
            )
        );   
    }

    public function rest_api_init(){
        register_rest_route(self::API_USER, '/', array(
            'methods' => 'GET',
            'callback' => array($this,'get')
        ));
        register_rest_route(self::API_USER, 'me', array(
            'methods' => 'GET',
            'callback' => array($this,'api_user_profile_get')
        ));
        register_rest_route(self::API_USER, 'profile', array(
            'methods' => 'PUT',
            'callback' => array($this,'profile_put')
        ));
        register_rest_route(self::API_USER, 'profile', array(
            'methods' => 'POST',
            'callback' => array($this,'profile_put')
        ));
        register_rest_route(self::API_USER, '(?P<id>\d+)/profile', array(
            'methods' => 'GET',
            'callback' => array($this,'api_user_profile_get')
        ));
    }

    function get(){
        $u=(array)wp_get_current_user();
        $u['id']=remove($u,'ID');
        return $u;
        /*return array('user'=>$u,
        'first_name'=>get_user_meta( $u->ID, 'first_name', true ),
        'last_name'=>get_user_meta( $u->ID, 'last_name', true ),
        'meta'=>get_user_meta( $u->ID ))*/;
    }

    function api_user_profile_get(){
        $u=(array)wp_get_current_user();
        $uid=$u['ID'];
        $u['people'] = get_userdata($uid);
        $u['names'] = get_user_meta( $uid, 'names', true );
        $u['firstSurname'] = get_user_meta( $uid, 'first_surname', true );
        $u['lastSurname'] = get_user_meta( $uid, 'last_surname', true );
        $u['sex'] = get_user_meta( $uid, 'sex', true );
        $u['id'] =$u['ID'];
        $data=$u['data'];
        if($data){
            $u['mail']=$data->user_email;
        }
        return $u;
    }

    function profile_put($request){
        $o=method_exists($request,'get_params')?$request->get_params():$request;
        $u=(array)wp_get_current_user();
        $uid=$u['ID'];
        update_user_meta( $uid, 'names', $o['names'] );
        update_user_meta( $uid, 'first_surname', $o['firstSurname'] );
        update_user_meta( $uid, 'last_surname', $o['lastSurname'] );
        update_user_meta( $uid, 'sex', $o['sex'] );
        $args = array(
            'ID'         => $uid,
            'user_email' => esc_attr( $o['mail'] )
        );
        wp_update_user( $args );
        return true;
    }

    function api_supervisor_func(){
        $results = $GLOBALS['wpdb']->get_results( "SELECT  um.user_id,u.display_name,meta_value as supervisor
        FROM `wpsy_usermeta` um
        INNER JOIN wpsy_users u ON u.ID=um.user_id
        WHERE `meta_key`='supervisor'", OBJECT );
        $current_user = wp_get_current_user();
        return  array($results,$current_user);
    }

    function edit_user_profile( $user ) {
        $results = $GLOBALS['wpdb']->get_results( "SELECT  um.user_id,u.display_name
        FROM `wpsy_usermeta` um
        INNER JOIN wpsy_users u ON u.ID=um.user_id
        WHERE `meta_key`='wpsy_capabilities' and `meta_value` like '%supervisor%'", OBJECT );

        $user_id=get_the_author_meta( 'supervisor', $user->ID );
        ?>
            <table class="form-table">
            <tr>
            <th><label for="postalcode"><?php _e("Supervisor"); ?></label></th>
                <td>
                    <select name="supervisor">
                        <option value="" <?=!$user_id?'selected="selected"':''?>  >--Select Option--</option>
                        <?
                        foreach ($results as $r){
                            ?>
                            
                            <option <?=$r->user_id==$user_id?'selected="selected"':''?> value="<?=$r->user_id?>" ><?=$r->display_name?></option>
                            
                            <?}
                        ?>
                    </select>
                </td>
            </tr>
            </table>
        <?php 
    }

    function edit_user_profile_update( $user_id ) {
        update_user_meta( $user_id, 'supervisor', $_POST['supervisor'] );
    }

}