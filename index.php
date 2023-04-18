<?php
/**
 * Plugin Name: ISOBIT
 */
add_filter( 'woocommerce_prevent_admin_access', '__return_false' );

add_filter( 'woocommerce_disable_admin_bar', '__return_false' );

function cdfield(&$row,$key){
    if(is_numeric($row[$key])){
        $row[$key]=date("Y-m-d",$row[$key]/1000);
    }
    return $row;
}

function cdfield2(&$row,$key){
    if(is_numeric($row[$key])){
        $row[$key]=date("Y-m-d H:i:s",$row[$key]/1000);
    }
    return $row;
}

function cfield(&$row,$from,$to){
    if(array_key_exists($from,$row)){
        $row[$to]=$row[$from];
        unset($row[$from]);
    }
    return $row;
}

function remove(array &$arr, $key) {
    if (array_key_exists($key, $arr)) {
        $val = $arr[$key];
        unset($arr[$key]);
        return $val;
    }
    return null;
}

function t_error($msg=false){
    global $wpdb;
    $error=new WP_Error(500,$msg?$msg:$wpdb->last_error, array('status'=>500));
    $wpdb->query('ROLLBACK');
    return $error;
}

function api_region_func22() {
    global $wpdb;
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}users", OBJECT );
    return $results;
}

function api_region_func() {
    global $wpdb;
    $wpdb->last_error  = '';
    $results = $wpdb->get_results( "SELECT d.id_dpto id,d.nombre_dpto name, d.codigo_dpto code FROM drt_departamento d");
    return $results;
}

function api_cp_func() {
    global $wpdb;
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT distinct Ubigeo_Centropoblado AS id,Ubigeo_Centropoblado AS codccpp,Nombre_Centro_Poblado AS name FROM drt_ccpp 
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

function api_town_sample_get($request) {
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

function api_province_func($request) {
    global $wpdb;
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT d.nombre_prov name, d.codigo_prov code FROM drt_provincia d".($request['regionId']?"
        WHERE d.codigo_prov LIKE '".sprintf('%02d',$request['regionId'])."%'":""));
    if($wpdb->last_error )return t_error();
    return $results;
}

function api_district_func($request) {
    global $wpdb;
    $wpdb->last_error  = '';
    $results = $wpdb->get_results( "SELECT d.id_distrito id,d.nombre_dist name, d.codigo_dist code FROM drt_distrito d WHERE 
        d.codigo_dist LIKE '".$request['provinceId']."%'");
    if($wpdb->last_error)return t_error();
    return $results;
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

function api_search_func($data){
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

add_action( 'rest_api_init', function () {
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
});

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
    add_role(
        'emed_admin',
        'emed_admin',
        array(
            'EMED_ADMIN'         => true,
            'EMED_READ'         => true
        )
    );
     //   remove_role( 'emed_admin' );
  //  remove_role( 'emed_register' );
    add_role(
        'emed_register',
        'emed_register',
        array(
            'EMED_ADMIN'         => true,
            'EMED_READ'         => true
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

add_action( 'rest_api_init', function () {
    register_rest_route( 'api/covid','/search', array(
        'methods' => 'GET',
        'callback' => 'api_covid',
    ));
    register_rest_route( 'api/voting','/act', array(
        'methods' => 'POST',
        'callback' => 'api_region_func22',
    ));
    register_rest_route( 'api/voting','/act', array(
        'methods' => 'POST',
        'callback' => 'api_voting_act_func',
    ));
    register_rest_route( 'api/directory','/region', array(
        'methods' => 'GET',
        'callback' => 'api_region_func',
    ));
    register_rest_route( 'admin/directory/api','/region/(?P<from>\d+)/(?P<to>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_region_func',
    ));
    register_rest_route('admin/directory/api','/province/(?P<from>\d+)/(?P<to>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_province_func',
    ));
    register_rest_route('admin/directory/api','/district/(?P<from>\d+)/(?P<to>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_district_func',
    ));
        register_rest_route('admin/directory/api','/town/(?P<from>\d+)/(?P<to>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_cp_func',
    ));
    register_rest_route( 'api','/poll', array(
        'methods' => 'POST',
        'callback' => 'api_poll_post',
    ));
    register_rest_route( 'api/poll','/main', array(
        'methods' => 'POST',
        'callback' => 'api_poll_post',
    ));
    register_rest_route( 'api/poll','/bulk/(?P<poll>\d+)', array(
        'methods' => 'POST',
        'callback' => 'api_poll_bulk_func',
    ));
    register_rest_route('api/poll','/people', array(
        'methods' => 'POST',
        'callback' => 'api_poll_people_post',
    ));
    register_rest_route('api/poll', '/people/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'api_poll_people_delete'
    ));
    register_rest_route( 'api/poll','/search/(?P<from>\d+)/(?P<to>\d+)', array(
        'methods' => 'POST',
        'callback' => 'api_search_func',
    ));
    register_rest_route('api/poll', '/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_poll_get'
    ));
    register_rest_route('api/poll', '/sample/(?P<from>\d+)/(?P<to>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_town_sample_get'
    ));
    register_rest_route('api/poll','/(?P<from>\d+)/(?P<to>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_poll_pag',
    ));
    register_rest_route('api/poll','/people/(?P<from>\d+)/(?P<to>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_poll_people_pag',
    ));
    register_rest_route('api/poll', '/people/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'api_poll_people_get',
    ));
    register_rest_route( 'api/poll','/supervisor', array(
        'methods' => 'GET',
        'callback' => 'api_supervisor_func',
    ));
});

function api_sivico_pag($request) {
    global $wpdb;$edb=2;
    $from=$request['from'];
    $to=$request['to'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS d.*,(SELECT count(o.id) FROM ds_sivico_people o Where o.canceled=0 AND o.master_id=d.id) AS peoples,
    (SELECT count(o.id) FROM ds_sivico_agreement o Where canceled=0 AND o.master_id=d.id) AS agreements FROM ds_sivico d Where canceled=0 OR uid=".$current_user->ID." ORDER BY id desc LIMIT ". $from.', '. $to, OBJECT );
    $count = $wpdb->get_var('SELECT FOUND_ROWS()');
    if($wpdb->last_error )return t_error();
    return array('data'=>$results,'size'=>$count);
}

function camelCase($string, $capitalizeFirstCharacter = false) {

    $str = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $string)));

    if (!$capitalizeFirstCharacter) {
        $str[0] = strtolower($str[0]);
    }

    return $str;
}

function api_pregnant_pag($request) {
    global $wpdb;$edb=2;
    $from=$request['from'];
    $to=$request['to'];
    $numeroDNI=method_exists($request,'get_param')?$request->get_param('numeroDNI'):$request['numeroDNI'];
    $fullName=method_exists($request,'get_param')?$request->get_param('fullName'):$request['fullName'];
    $red=method_exists($request,'get_param')?$request->get_param('red'):$request['red'];
    $microred=method_exists($request,'get_param')?$request->get_param('microred'):$request['microred'];
    $microredName=method_exists($request,'get_param')?$request->get_param('microredName'):$request['microredName'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';



    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS g.*,r.red as nameRed,mr.microred as nameMicroRed,COUNT(v.id) AS visits FROM ds_gestante g ".
        "LEFT JOIN ds_gestante_visita v ON v.gestante_id=g.id 
        LEFT JOIN grupoipe_project.MAESTRO_RED r ON r.codigo_red=g.red
        LEFT JOIN grupoipe_project.MAESTRO_MICRORED mr ON mr.codigo_cocadenado=g.microred
        WHERE g.canceled=0 ".(isset($numeroDNI)?" AND g.numero_dni like '%$numeroDNI%' ":"")
            .(isset($fullName)?" AND CONCAT(g.apellido_paterno,g.apellido_materno,g.nombres) like '%$fullName%' ":"")
            .(isset($red)?" AND g.red like '%$red%' ":"")
            .(isset($microred)?" AND g.microred like '%$microred%' ":"")
            .(isset($microredName)?" AND UPPER(mr.microred) like UPPER('%$microredName%') ":"").
        "GROUP BY g.id ".
        "ORDER BY id desc LIMIT ". $from.', '. $to, ARRAY_A );
    
    if($wpdb->last_error )return t_error();
    foreach ($results as &$r){
        cfield($r,'numero_dni','numeroDNI');
        if(isset($r['nameRed']))$r['red']=array('code'=>$r['red'],'name'=>$r['nameRed']);
        if(isset($r['nameMicroRed']))$r['microred']=array('code'=>$r['microred'],'name'=>$r['nameMicroRed']);
        cfield($r,'estado_civil','estadoCivil');
        cfield($r,'emergency_microred','emergencyMicrored');
        cfield($r,'grado_instruccion','gradoInstruccion');
    }
    $count = $wpdb->get_var('SELECT FOUND_ROWS()');
    if($wpdb->last_error )return t_error();
    return array('data'=>$results,'size'=>$count);
}

function api_pregnant_get($data){
    global $wpdb;
    //$data=method_exists($data,'get_params')?$data->get_params():$data;
    $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_gestante WHERE id=".$data['id']),ARRAY_A);
    if($wpdb->last_error )return t_error();
    foreach(['establecimiento_salud', 'codigo_EESS', 'codigo_CCPP','emergency_red','emergency_microred' ,'descripcion_sector', 'descripcion_direccion', 'numero_DNI', 'apellido_paterno',
    'apellido_materno', 'fecha_nacimiento', 'estado_civil', 'grado_instruccion', 'gestante_numero_celular', 'gestante_familia_celular', 
    'gestante_numero', 'gestante_paridad', 'gestante_FUR', 'gestante_FPP', 'gestante_edad_gestacional_semanas', 'gestante_riesgo_obstetrico', 
    'lugar_IPRESS', 'lugar_diagnostico', 'lugar_fecha_emergencia', 'lugar_fecha_referida', 'migracion_IPRESS', 
    'migracion_observacion', 'migracion_estado', 'migracion_fecha_retorno', 'user_register', 'user_modificacion'] as &$k){
        cfield($o,$k,camelCase($k));
    }
    cfield($o,'codigo_eess','codigoEESS');
    cfield($o,'numero_dni','numeroDNI');
    cfield($o,'codigo_ccpp','codigoCCPP');
    cfield($o,'gestante_fur','gestanteFUR');
    cfield($o,'gestante_fpp','gestanteFPP');
    cdfield($o,'gestanteFUR');
    cdfield($o,'gestanteFPP');
    $o['ext']=array();
    $o['visits']=api_pregnant_visit_pag(array("gestanteId"=>$o['id']));
    return $o;
}

function api_pregnant_bulk($request) {
    global $wpdb;
    $rl=$request->get_params();
    file_put_contents("data2.json", json_encode($rl));
    $current_user = wp_get_current_user();
    $aux=array();
    foreach ($rl as &$o) {
        $aux[]=api_pregnant_post($o);
    }
    return $aux;
}

function api_pregnant_post(&$request) {
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    $onlyUpload=remove($o,'onlyUpload');
    $migration=remove($o,'migration');
    if($onlyUpload)return array('success'=>true);
    foreach(['establecimiento_salud', 'codigo_EESS', 'codigo_CCPP','emergency_red','emergency_microred' ,'descripcion_sector', 'descripcion_direccion', 'numero_DNI', 'apellido_paterno',
    'apellido_materno', 'fecha_nacimiento', 'estado_civil', 'grado_instruccion', 'gestante_numero_celular', 'gestante_familia_celular', 
    'gestante_numero', 'gestante_paridad', 'gestante_FUR', 'gestante_FPP', 'gestante_edad_gestacional_semanas', 'gestante_riesgo_obstetrico', 
    'lugar_IPRESS', 'lugar_diagnostico', 'lugar_fecha_emergencia', 'lugar_fecha_referida', 'migracion_IPRESS', 
    'migracion_observacion', 'migracion_estado', 'migracion_fecha_retorno', 'user_register', 'user_modificacion'] as &$k){
        cfield($o,camelCase($k),$k);
    }
    cfield($o,'codigoEESS','codigo_EESS');
    unset($o['codigo_eess']);

    cdfield($o,'gestante_FUR');
    cdfield($o,'fecha_nacimiento');
    cdfield($o,'gestante_FPP');
    cdfield($o,'lugar_fecha_emergencia');
    cdfield($o,'lugar_fecha_referida');
    cdfield($o,'migracion_fecha');
    
    $tmpId=remove($o,'tmpId');
    unset($o['agreement']);
    unset($o['synchronized']);
    $visits=remove($o,'visits');
    $agreements=remove($o,'agreements');
    //quitar donde se guarda la imagen del familiograma
    unset($o['ext']);
    
    $o['updated_date']=current_time('mysql', 1);
    if($migration){
        $o['migracion_fecha']=current_time('mysql', 1);
        
    }
    $inserted=false;
    $wpdb->query('START TRANSACTION');
    if($o['id']>0){
        $o['user_register']=$current_user->user_login;
        $o['uid_update']=$current_user->ID;
        $updated = $wpdb->update('ds_gestante',$o,array('id'=>$o['id']));
    }else{
        $o['uid_insert']=$current_user->ID;
        $o['user_modificacion']=$current_user->user_login;
        unset($o['id']);
        if($tmpId)$o['offline']=$tmpId;
        $updated = $wpdb->insert('ds_gestante',$o);
        $o['id']=$wpdb->insert_id;
        $inserted=true;
    }
    if(false===$updated)return t_error();
    if($migration){
        //Aqui se graba el regitro migracion
        
    }
    //Si se ha insertado pero tenia registros temporales grabados esos ahora deberan tener el id final real
    if($inserted&&$tmpId){
        $updated = $wpdb->update('ds_sivico_people',array('master_id'=>$o['id']),array('master_id'=>-$tmpId));
        if(false===$updated)return t_error();
        $updated = $wpdb->update('ds_sivico_agreement',array('master_id'=>$o['id']),array('master_id'=>-$tmpId));
        if(false===$updated)return t_error();
    }
    if($tmpId){
        $o['tmpId']=$tmpId;
        $o['synchronized']=1;
    }
    if($visits){
        foreach($visits as $key=>&$visit){
            $visit['pregnantId']=$o['id'];
            $visits[$key]=api_pregnant_visit_post($visit);
        }
        $o['visits']=$visits;
    }
    if($agreements){
        foreach($agreements as $key=>&$agreement){
            $agreement['masterId']=$o['id'];
            $agreements[$key]=api_sivico_agreement_post($agreement);
        }
        $o['agreements']=$agreements;
    }
    $wpdb->query('COMMIT');
    return $o;
}

function api_pregnant_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_gestante',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

function api_pregnant_visit_pag($request) {
    global $wpdb;
    $from=$request['from'];
    $to=$request['to'];
    $gestanteId=method_exists($request,'get_param')?$request->get_param('gestanteId'):$request['gestanteId'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM ds_gestante_visita d Where canceled=0 ".($gestanteId?"AND gestante_id=$gestanteId":"")." ORDER BY id desc ".($to?"LIMIT ". $from.', '. $to:""),ARRAY_A);
    if($wpdb->last_error )return t_error();
    foreach ($results as &$r){
        cfield($r,'fecha_visita','fechaVisita');
        cfield($r,'numero_visita','number');
        cfield($r,'gestante_id','gestanteId');
    }
    $count = $wpdb->get_var('SELECT FOUND_ROWS()');
    if($wpdb->last_error )return t_error();
    
    return $to?array('data'=>$results,'size'=>$count):$results;
}

function api_pregnant_visit_post(&$request) {
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    cdfield($o,'fechaVisita');
    
    cfield($o,'pregnantId','gestante_id');
    cfield($o,'fechaVisita','fecha_visita');
    cfield($o,'number','numero_visita');
    cdfield($o,'fechaProxVisita');
    cfield($o,'fechaProxVisita','fecha_prox_visita');
    unset($o['people']);
    unset($o['ext']);
    $tmpId=remove($o,'tmpId');
    unset($o['synchronized']);
    $o['uid']=$current_user->ID;
   
    $inserted=0;
    if($o['id']>0){
        $o['updated_date']=current_time('mysql', 1);
        $updated=$wpdb->update('ds_gestante_visita',$o,array('id'=>$o['id']));
    }else{
        unset($o['id']);
        $max = $wpdb->get_row($wpdb->prepare("SELECT ifnull(max(`numero_visita`),0)+1 AS max FROM ds_gestante_visita WHERE gestante_id=".$o['gestante_id']),ARRAY_A);
        $o['numero_visita']=$max['max'];
        $o['user_register']=$current_user->user_login;
        $o['inserted_date']=current_time('mysql', 1);
        if($tmpId)$o['offline']=$tmpId;
        $updated=$wpdb->insert('ds_gestante_visita',$o);
        $o['id']=$wpdb->insert_id;
        $inserted=1;
    }
    if(false === $updated)return t_error();
    if($inserted&&$tmpId){
        $updated = $wpdb->update('ds_sivico_agreement',array('people_id'=>$o['id']),array('people_id'=>-$tmpId));
        if(false===$updated)return t_error();
    }
    if($tmpId){
        $o['tmpId']=$tmpId;
        $o['synchronized']=1;
    }
    
    cfield($o,'numero_visita','numeroVisita');
    return $o;
}

function api_pregnant_visit_get($data){
    global $wpdb;
    //$data=method_exists($data,'get_params')?$data->get_params():$data;
    $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_gestante_visita WHERE id=".$data['id']),ARRAY_A);
    if($wpdb->last_error )return t_error();
    cfield($o,'fecha_visita','fechaVisita');
    cdfield($o,'fechaProxVisita');
    cfield($o,'fecha_prox_visita','fechaProxVisita');
    cfield($o,'numero_visita','number');
    cfield($o,'gestante_id','pregnantId');
    cdfield($o,'fechaVisita');
    return $o;
}


function api_sivico_people_pag($request) {
    global $wpdb;
    $from=$request['from'];
    $to=$request['to'];
    $sivico=method_exists($request,'get_param')?$request->get_param('masterId'):$request['masterId'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM ds_sivico_people d Where canceled=0 ".($sivico?"AND master_id=$sivico":"")." ORDER BY id desc ".($to?"LIMIT ". $from.', '. $to:""),ARRAY_A);
    $count = $wpdb->get_var('SELECT FOUND_ROWS()');
    if($wpdb->last_error )return t_error();
    return $to?array('data'=>$results,'size'=>$count,i=>$sivico):$results;
}

function api_sivico_agreement_pag($request) {
    global $wpdb;
    $from=$request['from']; 
    $to=$request['to'];
    $sivico=method_exists($request,'get_param')?$request->get_param('masterId'):$request['masterId'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM ds_sivico_agreement d Where canceled=0 ".($sivico?"AND master_id=$sivico":"")." ORDER BY id desc ".($to?"LIMIT ". $from.', '. $to:""),ARRAY_A);
    $count = $wpdb->get_var('SELECT FOUND_ROWS()');
    if($wpdb->last_error )return t_error();
    return $to?array('data'=>$results,'size'=>$count):$results;
}

function api_sivico_get($data){
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_sivico WHERE id=".$data['id']),ARRAY_A);
    
    cfield($row,'area_residencia','areaResidencia');
    cfield($row,'disponibilidad_prox_visita','disponibilidadProxVisita');
    cfield($row,'medio_transporte','medioTransporte');
    cfield($row,'proxima_visita','proximaVisita');
    cfield($row,'disponibilidad_prox_visita','disponibilidadProxVisita');
    cfield($row,'residencias_anteriores','residenciasAnteriores');
    cfield($row,'responsable_visita','responsableVisita');
    cfield($row,'resultado_visita','resultadoVisita');
    cfield($row,'tiempo_a_eess','tiempoAEess');
    cfield($row,'tiempo_domicilio','tiempoDomicilio');
    cfield($row,'updated_date','updatedDate');
    $row['ext']=array();
    $row['peoples']=api_sivico_people_pag(array("masterId"=>$row['id']));
    $row['agreements']=api_sivico_agreement_pag(array("masterId"=>$row['id']));
    return $row;
}

function api_sivico_people_get($data){
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_sivico_people WHERE id=".$data['id']),ARRAY_A);
    cfield($row,'master_id','masterId');
    cfield($row,'civil_status','civilStatus');
    cfield($row,'degree_instruction','degreeInstruction');
    cfield($row,'occupation_condition','occupationCondition');
    cfield($row,'health_insurance','healthInsurance');
    return $row;
}

function api_sivico_people_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_sivico_people',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

function api_sivico_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_sivico',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

function api_sivico_agreement_get($data){
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_sivico_agreement WHERE canceled=0 AND id=".$data['id']),ARRAY_A);
    cfield($row,'master_id','masterId');
    cfield($row,'people_id','peopleId');
    cfield($row,'visit_1','visit1');
    cfield($row,'visit_2','visit2');
    cfield($row,'visit_3','visit3');
    cfield($row,'visit_4','visit4');
    return $row;
}

function api_sivico_agreement_delete($data){
    global $wpdb;
    $row = $wpdb->delete('ds_sivico_agreement',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

function api_sivico_people_post(&$request) {
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    cfield($o,'masterId','master_id');
    cfield($o,'civilStatus','civil_status');
    cfield($o,'degreeInstruction','degree_instruction');
    cfield($o,'occupationCondition','occupation_condition');
    cfield($o,'healthInsurance','health_insurance');
    cdfield($o,'birthdate');
    unset($o['people']);
    unset($o['ext']);
    $tmpId=remove($o,'tmpId');
    unset($o['synchronized']);
    $o['uid']=$current_user->ID;
    $inserted=0;
    $o['updated_date']=current_time('mysql', 1);
    if($o['id']>0)
        $updated=$wpdb->update('ds_sivico_people',$o,array('id'=>$o['id']));
    else{
        unset($o['id']);
        if($tmpId)$o['offline']=$tmpId;
        $updated=$wpdb->insert('ds_sivico_people',$o);
        $o['id']=$wpdb->insert_id;
        $inserted=1;
    }
    if(false === $updated)return t_error();
    if($inserted&&$tmpId){
        $updated = $wpdb->update('ds_sivico_agreement',array('people_id'=>$o['id']),array('people_id'=>-$tmpId));
        if(false===$updated)return t_error();
    }
    if($tmpId){
        $o['tmpId']=$tmpId;
        $o['synchronized']=1;
    }
    return $o;
}

function api_sivico_agreement_post(&$request){
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    cfield($o,'peopleId','people_id');
    cfield($o,'visit1','visit_1');
    cdfield($o,'visit_1');
    cfield($o,'visit2','visit_2');
    cdfield($o,'visit_2');
    cfield($o,'visit3','visit_3');
    cdfield($o,'visit_3');
    cfield($o,'visit4','visit_4');
    cdfield($o,'visit_4');
    cfield($o,'masterId','master_id');
    unset($o['people']);
    unset($o['ext']);
    $tmpId=remove($o,'tmpId');
    unset($o['synchronized']);
    unset($o['synchronized']);
    $o['uid']=$current_user->ID;
    if($o['id']>0)
        $updated=$wpdb->update('ds_sivico_agreement',$o,array('id'=>$o['id']));
    else{
        unset($o['id']);
        if($tmpId)$o['offline']=$tmpId;
        $updated=$wpdb->insert('ds_sivico_agreement',$o);
        $o['id']=$wpdb->insert_id;
    }
    if(false===$updated)return t_error();
    if($tmpId){
        $o['tmpId']=$tmpId;
        $o['synchronized']=1;
    }
    return $o;
}

function api_sivico_post(&$request) {
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    $onlyUpload=remove($o,'onlyUpload');
    if($onlyUpload)return array('success'=>true);
    cfield($o,'areaResidencia','area_residencia');
    cfield($o,'tiempoAEess','tiempo_a_eess');
    cfield($o,'medioTransporte','medio_transporte');
    cfield($o,'tiempoDomicilio','tiempo_domicilio');
    cfield($o,'residenciasAnteriores','residencias_anteriores');
    cfield($o,'disponibilidadProxVisita','disponibilidad_prox_visita');
    cfield($o,'responsableVisita','responsable_visita');
    cfield($o,'resultadoVisita','resultado_visita');
    cfield($o,'updatedDate','updated_date');
    cdfield($o,'updated_date');
    cfield($o,'proximaVisita','proxima_visita');
    cdfield($o,'proxima_visita');
    cdfield($o,'fecha');
    cdfield($o,'c23');
    cfield($o,'tiempoDomicilio','tiempo_domicilio');
    $tmpId=remove($o,'tmpId');
    unset($o['agreement']);
    unset($o['synchronized']);
    $peoples=remove($o,'peoples');
    $agreements=remove($o,'agreements');
    //quitar donde se guarda la imagen del familiograma
    unset($o['ext']);
    $o['uid']=$current_user->ID;
    $o['updated_date']=current_time('mysql', 1);
    $inserted=false;
    $wpdb->query('START TRANSACTION');
    if($o['id']>0)
        $updated = $wpdb->update('ds_sivico',$o,array('id'=>$o['id']));
    else{
        unset($o['id']);
        if($tmpId)$o['offline']=$tmpId;
        $updated = $wpdb->insert('ds_sivico',$o);
        $o['id']=$wpdb->insert_id;
        $inserted=true;
    }
    if(false===$updated)return t_error();
    if($inserted&&$tmpId){
        $updated = $wpdb->update('ds_sivico_people',array('master_id'=>$o['id']),array('master_id'=>-$tmpId));
        if(false===$updated)return t_error();
        $updated = $wpdb->update('ds_sivico_agreement',array('master_id'=>$o['id']),array('master_id'=>-$tmpId));
        if(false===$updated)return t_error();
    }
    if($tmpId){
        $o['tmpId']=$tmpId;
        $o['synchronized']=1;
    }
    if($peoples){
        foreach($peoples as $key=>&$people){
            $people['masterId']=$o['id'];
            $peoples[$key]=api_sivico_people_post($people);
        }
        $o['peoples']=$peoples;
    }
    if($agreements){
        foreach($agreements as $key=>&$agreement){
            $agreement['masterId']=$o['id'];
            $agreements[$key]=api_sivico_agreement_post($agreement);
        }
        $o['agreements']=$agreements;
    }
    $wpdb->query('COMMIT');
    return $o;
}

function api_sivico_bulk_func($request) {
    global $wpdb;
    $rl=$request->get_params();
    file_put_contents("data2.json", json_encode($rl));
    $current_user = wp_get_current_user();
    $aux=array();
    foreach ($rl as &$o) {
        $aux[]=api_sivico_post($o);
    }
    return $aux;
}

function api_microred_pag($request) {
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

function api_pregnant_visit_number_get($request){
    global $wpdb;
    $max = $wpdb->get_row($wpdb->prepare("SELECT ifnull(max(`numero_visita`),0)+1 AS max FROM ds_gestante_visita WHERE gestante_id=".$request['pregnant']),ARRAY_A);
    return $max['max'];
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

add_action('rest_api_init',function(){
    register_rest_route('/admin/desarrollo-social/api', '/red/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_red_pag',
	));
	
	register_rest_route('/admin/desarrollo-social/api', '/cie/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_cie_pag', 
	));
	
	register_rest_route('/admin/desarrollo-social/api', '/microred/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_microred_pag', 
	));
	register_rest_route('/admin/desarrollo-social/api', '/establishment/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_eess_pag',
	));
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
	

	register_rest_route('/admin/desarrollo-social/api', '/pregnant/bulk',array(
		'methods' => 'POST',
		'callback' => 'api_pregnant_bulk',
	));
	register_rest_route('/admin/desarrollo-social/api', '/pregnant',array(
		'methods' => 'POST',
		'callback' => 'api_pregnant_post',
	));
	register_rest_route('/admin/desarrollo-social/api', '/pregnant/(?P<id>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_pregnant_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/pregnant/(?P<pregnant>\d+)/visit/number',array(
		'methods' => 'GET',
		'callback' => 'api_pregnant_visit_number_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/pregnant/visit/(?P<id>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_pregnant_visit_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/pregnant/(?P<id>\d+)',array(
		'methods' => 'DELETE',
		'callback' => 'api_pregnant_delete',
	));
	register_rest_route('/admin/desarrollo-social/api', '/pregnant/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_pregnant_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/pregnant/visit/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_pregnant_visit_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/pregnant/visit',array(
		'methods' => 'POST',
		'callback' => 'api_pregnant_visit_post',
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
});

function api_file_upload_post(){
    $dir_subida = $_SERVER['DOCUMENT_ROOT'].'/uploads/';
    $file=$_FILES['file'];
    mkdir( $dir_subida, 0777, true );
    $file['tempFile']=time(). "_".basename($file['name']);
    $file['success']=move_uploaded_file($file['tmp_name'],$dir_subida .$file['tempFile']);
    return $file;
}

add_action('rest_api_init',function(){
	register_rest_route('/api/file', '/upload',array(
		'methods' => 'POST',
		'callback' => 'api_file_upload_post',
	));
});

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

add_action('rest_api_init',function(){
    register_rest_route('/api/directory', '/people',array(
		'methods' => 'GET',
		'callback' => 'api_directory_people_get',
	));
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
});

function api_emed_pag($request) {
    global $wpdb;$edb=2;
    $from=$request['from'];
    $to=$request['to'];
    $numeroDNI=method_exists($request,'get_param')?$request->get_param('numeroDNI'):$request['numeroDNI'];
    $category=method_exists($request,'get_param')?$request->get_param('category'):$request['category'];
    $type=method_exists($request,'get_param')?$request->get_param('type'):$request['type'];
    $detail=method_exists($request,'get_param')?$request->get_param('detail'):$request['detail'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    


    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS g.* FROM ds_emed g ".
        "WHERE g.canceled=0 ".
        (isset($numeroDNI)?" AND g.numero_dni like '%$numeroDNI%' ":"").
        (isset($category)?" AND g.category like '%$category%' ":"").
        (isset($type)?" AND g.type like '%$type%' ":"").
        (isset($detail)?" AND g.detail like '%$detail%' ":"").
        "ORDER BY g.id DESC ".
        ($to>0?("LIMIT ". $from.', '. $to):""), ARRAY_A );
    
    if($wpdb->last_error )return t_error();
    foreach ($results as &$r){
        cfield($r,'numero_dni','numeroDNI');
        cfield($r,'estado_civil','estadoCivil');
        cfield($r,'emergency_microred','emergencyMicrored');
        cfield($r,'grado_instruccion','gradoInstruccion');
    }
    $count = $wpdb->get_var('SELECT FOUND_ROWS()');
    if($wpdb->last_error )return t_error();
    return array('data'=>$results,'size'=>$count);
}

function api_emed_post(&$request) {
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    $onlyUpload=remove($o,'onlyUpload');
    $migration=remove($o,'migration');
    if($onlyUpload)return array('success'=>true);
    foreach(['establecimiento_salud', 'codigo_EESS', 'codigo_CCPP','emergency_red','emergency_microred' ,'descripcion_sector', 'descripcion_direccion', 'numero_DNI', 'apellido_paterno',
    'apellido_materno', 'fecha_nacimiento', 'estado_civil', 'grado_instruccion', 'gestante_numero_celular', 'gestante_familia_celular', 
    'gestante_numero','gestante_edad_gestacional_semanas', 'gestante_riesgo_obstetrico', 
    'lugar_IPRESS', 'lugar_diagnostico', 'lugar_fecha_emergencia', 'lugar_fecha_referida', 'migracion_IPRESS', 
    'migracion_observacion', 'migracion_estado', 'migracion_fecha_retorno', 'user_insert', 'user_update'] as &$k){
        cfield($o,camelCase($k),$k);
    }
    cfield($o,'codigoEESS','codigo_EESS');
    unset($o['codigo_eess']);
    cfield($o,'codigoCCPP','codigo_CCPP');
    cfield($o,'codigo_ccpp','codigo_CCPP');
    unset($o['codigo_ccpp']);
    
    cdfield($o,'gestante_FUR');
    cdfield($o,'gestante_FPP');
    cdfield($o,'lugar_fecha_emergencia');
    cdfield($o,'lugar_fecha_referida');
    cdfield($o,'date');
    $tmpId=remove($o,'tmpId');
    unset($o['agreement']);
    unset($o['synchronized']);
    $action=remove($o,'action');
    $damage_ipress=remove($o,'damage_ipress');
    $damage_salud=remove($o,'damage_salud');
    remove($o,'files');
    $inserted=false;
    $wpdb->query('START TRANSACTION');
    if($o['id']>0){
        $o['update_date']=current_time('mysql', 1);
        $o['user_update']=$current_user->user_login;
        $o['uid_update']=$current_user->ID;
        $updated = $wpdb->update('ds_emed',$o,array('id'=>$o['id']));
    }else{
        $o['uid_insert']=$current_user->ID;
        $o['insert_date']=current_time('mysql', 1);
        $o['user_insert']=$current_user->user_login;
        unset($o['id']);
        if($tmpId)$o['offline']=$tmpId;
        $updated = $wpdb->insert('ds_emed',$o);
        $o['id']=$wpdb->insert_id;
        $inserted=true;
    }
    if(false===$updated)return t_error();
    //Si se ha insertado pero tenia registros temporales grabados esos ahora deberan tener el id final real
    if($inserted&&$tmpId){
        $updated = $wpdb->update('ds_emed_file',array('emed_id'=>$o['id']),array('emed_id'=>-$tmpId));
        if(false===$updated)return t_error();
        $updated = $wpdb->update('ds_emed_action',array('emed_id'=>$o['id']),array('emed_id'=>-$tmpId));
        if(false===$updated)return t_error();
        $updated = $wpdb->update('ds_emed_damage_ipress',array('emed_id'=>$o['id']),array('emed_id'=>-$tmpId));
        if(false===$updated)return t_error();
        $updated = $wpdb->update('ds_emed_damage_salud',array('emed_id'=>$o['id']),array('emed_id'=>-$tmpId));
        if(false===$updated)return t_error();
    }
    if($tmpId){
        $o['tmpId']=$tmpId;
        $o['synchronized']=1;
    }
    if($action){
        foreach($action as $key=>&$row){
            $row['emedId']=$o['id'];
            $action[$key]=api_emed_action_post($row);
        }
        $o['action']=$action;
    }
    if($damage_ipress){
        foreach($damage_ipress as $key=>&$row){
            $row['emedId']=$o['id'];
            $damage_ipress[$key]=api_emed_damage_ipress_post($row);
        }
        $o['damage_ipress']=$damage_ipress;
    }
    if($damage_salud){
        foreach($damage_salud as $key=>&$row){
            $row['emedId']=$o['id'];
            $damage_salud[$key]=api_emed_damage_salud_post($row);
        }
        $o['damage_salud']=$damage_salud;
    }
    $wpdb->query('COMMIT');
    return $o;
}

function api_emed_action_post(&$request){
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    cfield($o,'emedId','emed_id');
    cdfield($o,'fecha');
    $tmpId=remove($o,'tmpId');
    unset($o['synchronized']);
    $inserted=0;
    if($o['id']>0){
        $o['uid_update']=$current_user->ID;
        $o['user_update']=$current_user->user_login;
        $o['update_date']=current_time('mysql', 1);
        $updated=$wpdb->update('ds_emed_action',$o,array('id'=>$o['id']));
    }else{
        unset($o['id']);
        $o['uid_insert']=$current_user->ID;
        $o['user_insert']=$current_user->user_login;
        $o['insert_date']=current_time('mysql', 1);
        if($tmpId)$o['offline']=$tmpId;
        $updated=$wpdb->insert('ds_emed_action',$o);
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

function api_emed_damage_ipress_post(&$request){
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    cfield($o,'emedId','emed_id');
    //cdfield($o,'fecha');
    $tmpId=remove($o,'tmpId');
    unset($o['synchronized']);
    $inserted=0;
    if($o['id']>0){
        $o['uid_update']=$current_user->ID;
        $o['user_update']=$current_user->user_login;
        $o['update_date']=current_time('mysql', 1);
        $updated=$wpdb->update('ds_emed_damage_ipress',$o,array('id'=>$o['id']));
    }else{
        unset($o['id']);
        $o['uid_insert']=$current_user->ID;
        $o['user_insert']=$current_user->user_login;
        $o['insert_date']=current_time('mysql', 1);
        if($tmpId)$o['offline']=$tmpId;
        $updated=$wpdb->insert('ds_emed_damage_ipress',$o);
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

function api_emed_damage_salud_post(&$request){
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    cfield($o,'emedId','emed_id');
    cdfield($o,'fecha');
    $tmpId=remove($o,'tmpId');
    unset($o['synchronized']);
    $inserted=0;
    if($o['id']>0){
        $o['uid_update']=$current_user->ID;
        $o['user_update']=$current_user->user_login;
        $o['update_date']=current_time('mysql', 1);
        $updated=$wpdb->update('ds_emed_damage_salud',$o,array('id'=>$o['id']));
    }else{
        unset($o['id']);
        $o['uid_insert']=$current_user->ID;
        $o['user_insert']=$current_user->user_login;
        $o['insert_date']=current_time('mysql', 1);
        if($tmpId)$o['offline']=$tmpId;
        $updated=$wpdb->insert('ds_emed_damage_salud',$o);
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

function api_emed_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_emed',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

function api_emed_get($data){
    global $wpdb;
    //$data=method_exists($data,'get_params')?$data->get_params():$data;
    $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_emed WHERE id=".$data['id']),ARRAY_A);
    if($wpdb->last_error )return t_error();
    $o['files']=api_emed_file_pag(array("emed"=>$o['id']));
    $o['action']=api_emed_action_pag(array("emed"=>$o['id']));
    $o['damage_ipress']=api_emed_damage_ipress_pag(array("emed"=>$o['id']));
    $o['damage_salud']=api_emed_damage_salud_pag(array("emed"=>$o['id']));
    return $o;
}

function api_emed_action_pag($request) {
    global $wpdb;
    $from=$request['from'];
    $to=$request['to'];
    $emed_id=method_exists($request,'get_param')?$request->get_param('emed'):$request['emed'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS o.* FROM ds_emed_action o ".
        "WHERE o.canceled=0 ".(isset($emed_id)?" AND o.emed_id=$emed_id ":"").
        "ORDER BY o.id DESC ".
        ($to>0?("LIMIT ". $from.', '. $to):""), ARRAY_A );
    
    if($wpdb->last_error )return t_error();
    return $to>0?array('data'=>$results,'size'=>$wpdb->get_var('SELECT FOUND_ROWS()')):$results;
}

function api_emed_damage_ipress_pag($request) {
    global $wpdb;
    $from=$request['from'];
    $to=$request['to'];
    $emed=method_exists($request,'get_param')?$request->get_param('emed'):$request['emed'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS o.* FROM ds_emed_damage_ipress o ".
        "WHERE o.canceled=0 ".(isset($emed)?" AND o.emed_id like '$emed' ":"").
        "ORDER BY o.id DESC ".
        ($to>0?("LIMIT ". $from.', '. $to):""), ARRAY_A );
    
    if($wpdb->last_error )return t_error();
    return $to>0?array('data'=>$results,'size'=>$wpdb->get_var('SELECT FOUND_ROWS()')):$results;
}

function api_emed_damage_salud_pag($request) {
    global $wpdb;
    $from=$request['from'];
    $to=$request['to'];
    $emed=method_exists($request,'get_param')?$request->get_param('emed'):$request['emed'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS g.* FROM ds_emed_damage_salud g ".
        "WHERE g.canceled=0 ".(isset($emed)?" AND g.emed_id like '$emed' ":"").
        ($to>0?("LIMIT ". $from.', '. $to):""), ARRAY_A );
    
    if($wpdb->last_error )return t_error();
    return $to>0?array('data'=>$results,'size'=>$wpdb->get_var('SELECT FOUND_ROWS()')):$results;
}

function api_emed_bulk($request) {
    global $wpdb;
    $rl=$request->get_params();
    file_put_contents("data2.json", json_encode($rl));
    $current_user = wp_get_current_user();
    $aux=array();
    foreach ($rl as &$o) {
        $aux[]=api_pregnant_post($o);
    }
    return $aux;
}

function api_emed_damage_salud_get($data){
    global $wpdb;
    $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_emed_damage_salud WHERE id=".$data['id']),ARRAY_A);
    if($wpdb->last_error )return t_error();
    cfield($o,'emed_id','emedId');
    return $o;
}

function api_emed_damage_salud_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_emed_damage_salud',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

function api_emed_damage_ipress_get($data){
    global $wpdb;
    $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_emed_damage_ipress WHERE id=".$data['id']),ARRAY_A);
    if($wpdb->last_error )return t_error();
    cfield($o,'emed_id','emedId');
    return $o;
}

function api_emed_damage_ipress_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_emed_damage_ipress',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

function api_emed_action_get($data){
    global $wpdb;
    $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_emed_action WHERE id=".$data['id']),ARRAY_A);
    if($wpdb->last_error )return t_error();
    cfield($o,'emed_id','emedId');
    return $o;
}

function api_emed_action_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_emed_action',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

function api_emed_file_post(&$request){
    global $wpdb;
    $o=method_exists($request,'get_params')?$request->get_params():$request;
    $current_user = wp_get_current_user();
    cfield($o,'emedId','emed_id');
    //cdfield($o,'fecha');
    $tmpId=remove($o,'tmpId');
    unset($o['synchronized']);
    $inserted=0;
    if($o['id']>0){
        /*$o['uid_update']=$current_user->ID;
        $o['user_update']=$current_user->user_login;
        $o['update_date']=current_time('mysql', 1);
        $updated=$wpdb->update('ds_emed_file',$o,array('id'=>$o['id']));*/
    }else{
        unset($o['id']);
        $o['uid_insert']=$current_user->ID;
        $o['user_insert']=$current_user->user_login;
        $o['insert_date']=current_time('mysql', 1);
        if($tmpId)$o['offline']=$tmpId;
        //process src
        $updated=$wpdb->insert('ds_emed_file',$o);
        $o['id']=$wpdb->insert_id;
        
        $inserted=1;
    }
    if(false === $updated)return t_error();
    if($tmpId){
        $o['tmpId']=$tmpId;
        $o['synchronized']=1;
    }
    return $o; 
    /*
    function api_file_upload_post(){
    $dir_subida = $_SERVER['DOCUMENT_ROOT'].'/uploads/';
    $file=$_FILES['file'];
    mkdir( $dir_subida, 0777, true );
    $file['tempFile']=time(). "_".basename($file['name']);
    $file['success']=move_uploaded_file($file['tmp_name'],$dir_subida .$file['tempFile']);
    return $file;
}
    */
}

function api_emed_file_pag($request) {
    global $wpdb;
    $from=$request['from'];
    $to=$request['to'];
    $emed=method_exists($request,'get_param')?$request->get_param('emed'):$request['emed'];
    $current_user = wp_get_current_user();
    $wpdb->last_error  = '';
    $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS g.* FROM ds_emed_file g ".
        "WHERE g.canceled=0 ".
        (isset($emed)?" AND g.emed_id like '$emed' ":"").
        ($to>0?("LIMIT ". $from.', '. $to):""), ARRAY_A );
    
    if($wpdb->last_error )return t_error();
    return $to>0?array('data'=>$results,'size'=>$wpdb->get_var('SELECT FOUND_ROWS()')):$results;
}

function api_emed_file_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_emed_file',array('canceled'=>1),array('id'=>$data['id']));
    //remove file 
    return $row;
}

/*Start cancer*/

function api_cancer_pag($request) {
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

function api_cancer_get($data){
    global $wpdb;
    $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_cancer WHERE id=".$data['id']),ARRAY_A);
    if($wpdb->last_error )return t_error();
    cfield($o,'emed_id','emedId');
    return $o;
}

function api_cancer_bulk($request) {
    global $wpdb;
    $rl=$request->get_params();
    $aux=array();
    foreach ($rl as &$o) {
        $aux[]=api_cancer_post($o);
    }
    return $aux;
}

function api_cancer_post(&$request){
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

function api_cancer_delete($data){
    global $wpdb;
    $row = $wpdb->update('ds_cancer',array('canceled'=>1),array('id'=>$data['id']));
    return $row;
}

add_action('rest_api_init',function(){
	register_rest_route('/admin/desarrollo-social/api', '/emed/bulk',array(
		'methods' => 'POST',
		'callback' => 'api_emed_bulk',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed',array(
		'methods' => 'POST',
		'callback' => 'api_emed_post',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/(?P<id>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/(?P<pregnant>\d+)/visit/number',array(
		'methods' => 'GET',
		'callback' => 'api_emed_visit_number_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/visit/(?P<id>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_visit_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/(?P<id>\d+)',array(
		'methods' => 'DELETE',
		'callback' => 'api_emed_delete',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/resource/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_resource_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/resource',array(
		'methods' => 'POST',
		'callback' => 'api_emed_resource_post',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/action',array(
		'methods' => 'POST',
		'callback' => 'api_emed_action_post',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/action/(?P<id>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_action_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/action/(?P<id>\d+)',array(
		'methods' => 'DELETE',
		'callback' => 'api_emed_action_delete',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/action/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_action_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/damage-ipress',array(
		'methods' => 'POST',
		'callback' => 'api_emed_damage_ipress_post',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/damage-ipress/(?P<id>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_damage_ipress_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/damage-ipress/(?P<id>\d+)',array(
		'methods' => 'DELETE',
		'callback' => 'api_emed_damage_ipress_delete',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/damage-ipress/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_damage_ipress_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/damage-salud/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_damage_salud_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/damage-salud',array(
		'methods' => 'POST',
		'callback' => 'api_emed_damage_salud_post',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/damage-salud/(?P<id>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_damage_salud_get',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/damage-salud/(?P<id>\d+)',array(
		'methods' => 'DELETE',
		'callback' => 'api_emed_damage_salud_delete',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/file',array(
		'methods' => 'POST',
		'callback' => 'api_emed_file_post',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/file/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_emed_file_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/emed/file/(?P<id>\d+)',array(
		'methods' => 'DELETE',
		'callback' => 'api_emed_file_delete',
	));
	
	register_rest_route('/admin/desarrollo-social/api', '/cancer/(?P<from>\d+)/(?P<to>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_cancer_pag',
	));
	register_rest_route('/admin/desarrollo-social/api', '/cancer/(?P<id>\d+)',array(
		'methods' => 'GET',
		'callback' => 'api_cancer_get',
	));	
	register_rest_route('/admin/desarrollo-social/api', '/cancer/bulk',array(
		'methods' => 'POST',
		'callback' => 'api_cancer_bulk',
	));
	register_rest_route('/admin/desarrollo-social/api', '/cancer',array(
		'methods' => 'POST',
		'callback' => 'api_cancer_post',
	));
	register_rest_route('/admin/desarrollo-social/api', '/cancer/(?P<id>\d+)',array(
		'methods' => 'DELETE',
		'callback' => 'api_cancer_delete',
	));
});

?>