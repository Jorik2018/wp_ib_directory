<?php

namespace IB\cv\Controllers;

use WPMVC\MVC\Controller;
use IB\cv\Util;
require_once __DIR__ . '/../Util/Utils.php';

class EmedRestController extends Controller
{

    public function init(){
        
    }

    public function rest_api_init(){
        register_rest_route('api', '/user', array(
            'methods' => 'GET',
            'callback' => 'api_user_get',
        ));
        register_rest_route('api/user', '/me', array(
            'methods' => 'GET',
            'callback' => 'api_user_profile_get',
        ));
        register_rest_route('api/user', '/profile', array(
            'methods' => 'PUT',
            'callback' => 'api_user_profile_put',
        ));
        register_rest_route('api/user', '/profile', array(
            'methods' => 'POST',
            'callback' => 'api_user_profile_put',
        ));
        register_rest_route('api/user', '/(?P<id>\d+)/profile', array(
            'methods' => 'GET',
            'callback' => 'api_user_profile_get',
        ));
    }

   
function api_voting_act_func($request) {
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

function api_user_get(){
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
    $u['names']=get_user_meta( $uid, 'billing_first_name', true );
    $u['id'] =$u['ID'];
    $v=$u['data'];
    if($v)
    $u['mail']=$v->user_email;
    return $u;
}

function api_user_profile_put($request){
    //global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $u=(array)wp_get_current_user();
    $uid=$u['ID'];
    update_user_meta( $uid, 'billing_first_name', $o['names'] );
    $args = array(
        'ID'         => $uid,
        'user_email' => esc_attr( $o['mail'] )
    );
    wp_update_user( $args );
    return true;
}

function wporg_simple_role() {
    add_role(
        'simple_role',
        'Simple Role',
        array(
            'read'         => true,
            'edit_posts'   => true,
            'upload_files' => true,
        )
    );
    add_role(
        'supervisor',
        'Supervisor',
        array(
            'supervise'         => true
        )
    );

    add_role(
        'mci_admin',
        'mci_admin',
        array(
            'MCI_ADMIN'         => true,
            'MCI_READ'         => true
        )
    );
    add_role(
        'mci_register',
        'mci_register',
        array(
            'MCI_ADMIN'         => true,
            'MCI_READ'         => true
        )
    );

}
 
add_action( 'init', 'wporg_simple_role' );

add_action( 'show_user_profile', 'ib_user_profile_fields' );

add_action( 'edit_user_profile', 'ib_user_profile_fields' );

function api_supervisor_func(){
    $results = $GLOBALS['wpdb']->get_results( "SELECT  um.user_id,u.display_name,meta_value as supervisor
FROM `wpsy_usermeta` um
INNER JOIN wpsy_users u ON u.ID=um.user_id
WHERE `meta_key`='supervisor'", OBJECT );
    $current_user = wp_get_current_user();
   
    return  array($results,$current_user);
}

function ib_user_profile_fields( $user ) {
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
<?php }

add_action( 'personal_options_update', 'save_ib_user_profile_fields' );

add_action( 'edit_user_profile_update', 'save_ib_user_profile_fields' );

function save_ib_user_profile_fields( $user_id ) {
    update_user_meta( $user_id, 'supervisor', $_POST['supervisor'] );
}

function api_red_pag($request) {
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

function api_cie_pag($request) {
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

function api_eess_pag($request) {
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

}