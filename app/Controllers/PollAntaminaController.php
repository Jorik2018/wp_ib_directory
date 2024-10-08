<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use function IB\directory\Util\cfield;
use function IB\directory\Util\camelCase;
use function IB\directory\Util\cdfield;
use function IB\directory\Util\t_error;

class PollAntaminaController extends Controller
{

    public function init(){
        add_role(
            'supervisor',
            'Supervisor',
            array(
                'supervise'         => true
            )
        );
    }

    public function rest_api_init(){return;
        register_rest_route( 'api','/poll', array(
            'methods' => 'POST',
            'callback' => array($this,'api_poll_post')
        ));
        register_rest_route( 'api/poll','/main', array(
            'methods' => 'POST',
            'callback' => array($this,'api_poll_post')
        ));
        register_rest_route( 'api/poll','/bulk/(?P<poll>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this,'api_poll_bulk_func')
        ));
        register_rest_route('api/poll','/people', array(
            'methods' => 'POST',
            'callback' => array($this,'api_poll_people_post')
        ));
        register_rest_route('api/poll', '/people/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this,'api_poll_people_delete')
        ));
        register_rest_route( 'api/poll','/search/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this,'api_search_func')
        ));
        register_rest_route('api/poll', '/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'api_poll_get')
        ));
        register_rest_route('api/poll', '/sample/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'api_town_sample_get')
        ));
        register_rest_route('api/poll','/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'api_poll_pag')
        ));
        register_rest_route('api/poll','/people/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'api_poll_people_pag')
        ));
        register_rest_route('api/poll', '/people/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'api_poll_people_get')
        ));
        register_rest_route( 'api/poll','/supervisor', array(
            'methods' => 'GET',
            'callback' => array($this,'api_supervisor_func')
        ));
        register_rest_route('/api/directory', '/people',array(
            'methods' => 'GET',
            'callback' => array($this,'api_directory_people_get')
        ));
        register_rest_route( 'api','/poll', array(
            'methods' => 'POST',
            'callback' => array($this,'api_poll_post')
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

    function api_poll_search_func($data){
        global $wpdb;$edb=2;
        $from=$data['from'];
        $to=$data['to'];$wpdb->last_error  = '';
        $results = $wpdb->get_results('SELECT * FROM encuesta'.$edb.'_people d'.($to?'  LIMIT '. $from.', '. ($to-$from):''), OBJECT );
        if($wpdb->last_error )return t_error();
        return $results;
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

    function save_ib_user_profile_fields( $user_id ) {
        update_user_meta( $user_id, 'supervisor', $_POST['supervisor'] );
    }

    function api_covid($request) {
        global $wpdb;
        $r=$request->get_params();
        $current_user = wp_get_current_user();
            // create curl resource
            $ch = curl_init();

            // set url
            curl_setopt($ch, CURLOPT_URL, "http://web.regionancash.gob.pe/admin/desarrollo-social/api/covid/vaccine-covid/0/10?query=".$r['query']);

            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // $output contains the output string
            $output = curl_exec($ch);

            // close curl resource to free up system resources
            curl_close($ch);    
            die($output);
    }

}