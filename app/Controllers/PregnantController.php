<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use function IB\directory\Util\cfield;
use function IB\directory\Util\camelCase;
use function IB\directory\Util\cdfield;
use function IB\directory\Util\t_error;

class PregnantController extends Controller
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
 
        register_rest_route( 'api/desarrollo-social','pregnant', array(
            'methods' => 'POST',
            'callback' => array($this,'post')
        ));

        register_rest_route( 'api/desarrollo-social','pregnant/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'pag')
        ));

        register_rest_route( 'api/desarrollo-social','pregnant/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this,'get')
        ));

        register_rest_route( 'api/desarrollo-social', 'pregnant/(?P<id>)',array(
            'methods' => 'DELETE',
            'callback' => array($this,'delete')
        ));

        register_rest_route('api/desarrollo-social', 'pregnant/bulk',array(
            'methods' => 'POST',
            'callback' => array($this,'bulk')
        ));


        register_rest_route('api/desarrollo-social', 'pregnant/bulk',array(
            'methods' => 'POST',
            'callback' => array($this,'bulk')
        ));
        register_rest_route('api/desarrollo-social', 'pregnant',array(
            'methods' => 'POST',
            'callback' => array($this,'post')
        ));
        register_rest_route('api/desarrollo-social', 'pregnant/(?P<id>\d+)',array(
            'methods' => 'GET',
            'callback' => array($this,'get')
        ));
        register_rest_route('api/desarrollo-social', 'pregnant/(?P<pregnant>\d+)/visit/number',array(
            'methods' => 'GET',
            'callback' => array($this,'visit_number_get')
        ));
        register_rest_route('api/desarrollo-social', 'pregnant/visit/(?P<id>\d+)',array(
            'methods' => 'GET',
            'callback' => array($this,'visit_get')
        ));
        register_rest_route('api/desarrollo-social', 'pregnant/(?P<id>\d+)',array(
            'methods' => 'DELETE',
            'callback' => array($this,'delete')
        ));
        register_rest_route('api/desarrollo-social', 'pregnant/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => array($this,'pag')
        ));
        register_rest_route('api/desarrollo-social', 'pregnant/visit/(?P<from>\d+)/(?P<to>\d+)',array(
            'methods' => 'GET',
            'callback' => array($this,'visit_pag')
        ));
        register_rest_route('api/desarrollo-social', 'pregnant/visit',array(
            'methods' => 'POST',
            'callback' => array($this,'visit_post')
        ));
    }

    function bulk($request) {
        global $wpdb;
        $rl=$request->get_params();
        file_put_contents("data2.json", json_encode($rl));
        $current_user = wp_get_current_user();
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
                $visits[$key]=visit_post($visit);
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

    public function get($request){    
        global $wpdb;
        //$data=method_exists($data,'get_params')?$data->get_params():$data;
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_gestante WHERE id=".$request['id']),ARRAY_A);
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
        $o['visits']= $this->visit_pag(array("gestanteId"=>$o['id']));
        return $o;
    }


    
    public function pag($request){
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
            LEFT JOIN grupoipe_master.ipress_red r ON r.codigo_red=g.red
            LEFT JOIN grupoipe_master.ipress_microred mr ON mr.codigo_cocadenado=g.microred
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



    public function visit_pag($request){
        global $wpdb;
        $from=$request['from'];
        $to=$request['to'];
        $gestanteId=(!is_array($request) && method_exists($request, 'get_param'))?$request->get_param('gestanteId'):$request['gestanteId'];
        


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

    public function delete($data){
        global $wpdb;
        $row = $wpdb->update('ds_gestante',array('canceled'=>1),array('id'=>$data['id']));
        return $row;
    }

    function visit_post(&$request) {
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
    
    function visit_get($data){
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

    function visit_number_get($request){
        global $wpdb;
        $max = $wpdb->get_row($wpdb->prepare("SELECT ifnull(max(`numero_visita`),0)+1 AS max FROM ds_gestante_visita WHERE gestante_id=".$request['pregnant']),ARRAY_A);
        return $max['max'];
    }
    
}