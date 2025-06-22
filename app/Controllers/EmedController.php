<?php

namespace IB\directory\Controllers;

use WP_Error;
use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use function IB\directory\Util\cfield;
use function IB\directory\Util\camelCase;
use function IB\directory\Util\cdfield;
use function IB\directory\Util\t_error;
use function IB\directory\Util\get_param;
use function IB\directory\Util\toCamelCase;


class EmedController extends Controller
{

    public function init()
    {
        remove_role('emed_admin');
        remove_role('emed_register');
        remove_role('emed_inst');
        remove_role('emed_read');
        add_role(
            'emed_read',
            'emed_read',
            array(
                'EMED_READ' => true
            )
        );
        
        add_role(
            'emed_admin',
            'emed_admin',
            array(
                'EMED_REGISTER' => true,
                'EMED_ADMIN' => true,
                'EMED_READ' => true,
                'EMED_DET' => true
            )
        );
        add_role(
            'emed_register',
            'emed_register',
            array(
                'EMED_REGISTER' => true,
                'EMED_READ' => true,
                'EMED_DET' => true
            )
        );
        add_role(
            'emed_inst',
            'emed_inst',
            array(
                'EMED_REGISTER' => true,
                'EMED_ADMIN' => true,
                'EMED_READ' => true
            )
        );
    }

    public function rest_api_init()
    {
        register_rest_route('api/desarrollo-social', '/emed/bulk', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk')
        ));
        register_rest_route('api/desarrollo-social', '/emed', array(
            'methods' => 'POST',
            'callback' => array($this, 'post'),
            'permission_callback' => function () {
                return current_user_can('EMED_REGISTER');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/(?P<pregnant>\d+)/visit/number', array(
            'methods' => 'GET',
            'callback' => array($this, 'visit_number_get')
        ));
        register_rest_route('api/desarrollo-social', '/emed/visit/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'visit_get'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete'),
            'permission_callback' => function () {
                return current_user_can('EMED_ADMIN');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'pag'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/resource/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'resource_pag'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/resource', array(
            'methods' => 'POST',
            'callback' => array($this, 'resource_post'),
            'permission_callback' => function () {
                return current_user_can('EMED_REGISTER');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/action', array(
            'methods' => 'POST',
            'callback' => array($this, 'action_post'),
            'permission_callback' => function () {
                return current_user_can('EMED_REGISTER');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/action/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'action_get'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/action/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'action_delete'),
            'permission_callback' => function () {
                return current_user_can('EMED_ADMIN');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/action/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'action_pag'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/damage-ipress', array(
            'methods' => 'POST',
            'callback' => array($this, 'damage_ipress_post'),
            'permission_callback' => function () {
                return current_user_can('EMED_REGISTER');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/damage-ipress/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'damage_ipress_get'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/damage-ipress/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'damage_ipress_delete'),
            'permission_callback' => function () {
                return current_user_can('EMED_ADMIN');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/damage-ipress/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'damage_ipress_pag'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/damage-salud/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'damage_salud_pag'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/damage-salud', array(
            'methods' => 'POST',
            'callback' => array($this, 'damage_salud_post'),
            'permission_callback' => function () {
                return current_user_can('EMED_REGISTER');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/damage-salud/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'damage_salud_get'),
            'permission_callback' => function () {
                return current_user_can('EMED_READ');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/damage-salud/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'damage_salud_delete'),
            'permission_callback' => function () {
                return current_user_can('EMED_ADMIN');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/file', array(
            'methods' => 'POST',
            'callback' => array($this, 'file_post'),
            'permission_callback' => function () {
                return current_user_can('EMED_REGISTER');
            }
        ));
        register_rest_route('api/desarrollo-social', '/emed/file/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'file_pag')
        ));
        register_rest_route('api/desarrollo-social', '/emed/file/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'file_delete')
        ));
    }

    function bulk($request)
    {
        global $wpdb;
        $rl = get_param($request);
        file_put_contents("data2.json", json_encode($rl));
        $current_user = wp_get_current_user();
        $aux = array();
        foreach ($rl as &$o) {
            $aux[] = $this->post($o);
        }
        return $aux;
    }

    function damage_salud_get($data)
    {
        global $wpdb;
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_emed_damage_salud WHERE id=" . $data['id']), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        cfield($o, 'emed_id', 'emedId');
        return $o;
    }

    function damage_salud_delete($data)
    {
        global $wpdb;
        $row = $wpdb->update('ds_emed_damage_salud', array('canceled' => 1), array('id' => $data['id']));
        return $row;
    }

    function damage_ipress_get($data)
    {
        global $wpdb;
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_emed_damage_ipress WHERE id=" . $data['id']), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        cfield($o, 'emed_id', 'emedId');
        return $o;
    }

    function damage_ipress_delete($data)
    {
        global $wpdb;
        $row = $wpdb->update('ds_emed_damage_ipress', array('canceled' => 1), array('id' => $data['id']));
        return $row;
    }

    function action_get($data)
    {
        global $wpdb;
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_emed_action WHERE id=" . $data['id']), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        cfield($o, 'emed_id', 'emedId');
        return $o;
    }

    function action_delete($data)
    {
        global $wpdb;
        $row = $wpdb->update('ds_emed_action', array('canceled' => 1), array('id' => $data['id']));
        return $row;
    }

    function file_post(&$request)
    {
        global $wpdb;
        $o = get_param($request);
        $current_user = wp_get_current_user();
        cfield($o, 'emedId', 'emed_id');
        //cdfield($o,'fecha');
        $tmpId = remove($o, 'tmpId');
        unset($o['synchronized']);
        $inserted = 0;
        if ($o['id'] > 0) {
            /*$o['uid_update']=$current_user->ID;
            $o['user_update']=$current_user->user_login;
            $o['update_date']=current_time('mysql', 1);
            $updated=$wpdb->update('ds_emed_file',$o,array('id'=>$o['id']));*/
        } else {
            unset($o['id']);
            $o['uid_insert'] = $current_user->ID;
            $o['user_insert'] = $current_user->user_login;
            $o['insert_date'] = current_time('mysql', 1);
            if ($tmpId) $o['offline'] = $tmpId;
            //process src
            $updated = $wpdb->insert('ds_emed_file', $o);
            $o['id'] = $wpdb->insert_id;

            $inserted = 1;
        }
        if (false === $updated) return t_error();
        if ($tmpId) {
            $o['tmpId'] = $tmpId;
            $o['synchronized'] = 1;
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

    function file_pag($request)
    {
        global $wpdb;
        $from = $request['from'];
        $to = $request['to'];
        $emed = get_param($request, 'emed');
        $current_user = wp_get_current_user();
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS g.* FROM ds_emed_file g " .
            "WHERE g.canceled=0 " .
            (isset($emed) ? " AND g.emed_id like '$emed' " : "") .
            ($to > 0 ? ("LIMIT " . $from . ', ' . $to) : ""), ARRAY_A);

        if ($wpdb->last_error) return t_error();
        return $to > 0 ? array('data' => $results, 'size' => $wpdb->get_var('SELECT FOUND_ROWS()')) : $results;
    }

    function file_delete($data)
    {
        global $wpdb;
        $row = $wpdb->update('ds_emed_file', array('canceled' => 1), array('id' => $data['id']));
        //remove file 
        return $row;
    }

    function pag($request)
    {
        global $wpdb;
        $from = $request['from'];
        $to = $request['to'];
        $numeroDNI = get_param($request, 'numeroDNI');
        $category = get_param($request, 'category');
        $type = get_param($request, 'type');
        $description = get_param($request, 'description');
        $referencia = get_param($request, 'referencia');
        $datetime = get_param($request, 'datetime');
        list($datetimeFrom, $datetimeTo) = explode('|', $datetime);
        $datetimeFrom = !empty($datetimeFrom) ? $datetimeFrom : null;
        $datetimeTo = !empty($datetimeTo) ? $datetimeTo : null;
        $code = get_param($request, 'code');
        $detail = get_param($request, 'detail');
        $current_user = wp_get_current_user();
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS g.*,(g.uid_insert = $current_user->ID) AS editable FROM ds_emed g " .
            "WHERE g.canceled=0 " .
            (isset($numeroDNI) ? " AND g.numero_dni like '%$numeroDNI%' " : "") .
            (isset($category) ? " AND g.category like '%$category%' " : "") .
            ($description  ? " AND g.description  like '%" . str_replace(' ', '%', $description) . "%' " : "") .
            ($type ? " AND g.type like '%$type%' " : "") .
            ($code ? " AND g.code like '%$code%' " : "") .
            ($referencia ? " AND g.referencia like '%$referencia%' " : "") .
            ($detail ? " AND g.detail like '%$detail%' " : "") .
            ($datetimeFrom ? " AND Date(g.date) >= '$datetimeFrom' " : "") .
            ($datetimeTo ? " AND date(g.date) <= '$datetimeTo' " : "") .
            "ORDER BY g.id DESC " .
            ($to > 0 ? ("LIMIT " . $from . ', ' . $to) : ""), ARRAY_A);

        if ($wpdb->last_error) return t_error();
        foreach ($results as &$r) {
            $r['editable']=(bool) $r['editable'];
            cfield($r, 'numero_dni', 'numeroDNI');
            cfield($r, 'estado_civil', 'estadoCivil');
            cfield($r, 'emergency_microred', 'emergencyMicrored');
            cfield($r, 'grado_instruccion', 'gradoInstruccion');
        }
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');
        if ($wpdb->last_error) return t_error();
        return array('data' => $results, 'size' => $count);
    }

    function post(&$request)
    {
        global $wpdb;
        $o = get_param($request);

        $current_user = wp_get_current_user();
        remove($o, 'editable');
        $onlyUpload = remove($o, 'onlyUpload');
        $migration = remove($o, 'migration');
        if ($onlyUpload) return array('success' => true);
        foreach (
            [
                'establecimiento_salud',
                'codigo_EESS',
                'emergency_red',
                'emergency_microred',
                'descripcion_sector',
                'descripcion_direccion',
                'numero_DNI',
                'apellido_paterno',
                'apellido_materno',
                'fecha_nacimiento',
                'estado_civil',
                'grado_instruccion',
                'gestante_numero_celular',
                'gestante_familia_celular',
                'gestante_numero',
                'gestante_edad_gestacional_semanas',
                'gestante_riesgo_obstetrico',
                'lugar_IPRESS',
                'lugar_diagnostico',
                'lugar_fecha_emergencia',
                'lugar_fecha_referida',
                'migracion_IPRESS',
                'migracion_observacion',
                'migracion_estado',
                'migracion_fecha_retorno',
                'user_insert',
                'user_update'
            ] as &$k
        ) {
            cfield($o, camelCase($k), $k);
        }
        cfield($o, 'codigoEESS', 'codigo_EESS');
        unset($o['codigo_eess']);
        unset($o['codigo_ccpp']);
        cfield($o, 'codigoCCPP', 'codigo_ccpp');
        cdfield($o, 'gestante_FUR');
        cdfield($o, 'gestante_FPP');
        cdfield($o, 'lugar_fecha_emergencia');
        cdfield($o, 'lugar_fecha_referida');
        cdfield($o, 'date');
        $tmpId = remove($o, 'tmpId');
        unset($o['agreement']);
        unset($o['synchronized']);
        $action = remove($o, 'action');
        $damage_ipress = remove($o, 'damage_ipress');
        $damage_salud = remove($o, 'damage_salud');
        remove($o, 'files');
        $inserted = false;
        $wpdb->query('START TRANSACTION');
        if ($o['id'] > 0) {
            $o['update_date'] = current_time('mysql', 1);
            $o['user_update'] = $current_user->user_login;
            $o['uid_update'] = $current_user->ID;
            $updated = $wpdb->update('ds_emed', $o, array('id' => $o['id']));
        } else {
            $o['uid_insert'] = $current_user->ID;
            $o['insert_date'] = current_time('mysql', 1);
            $o['user_insert'] = $current_user->user_login;
            unset($o['id']);
            if ($tmpId) $o['offline'] = $tmpId;
            $updated = $wpdb->insert('ds_emed', $o);
            $o['id'] = $wpdb->insert_id;
            $inserted = true;
        }
        if (false === $updated) return t_error();
        //Si se ha insertado pero tenia registros temporales grabados esos ahora deberan tener el id final real
        if ($inserted && $tmpId) {
            $updated = $wpdb->update('ds_emed_file', array('emed_id' => $o['id']), array('emed_id' => -$tmpId));
            if (false === $updated) return t_error();
            $updated = $wpdb->update('ds_emed_action', array('emed_id' => $o['id']), array('emed_id' => -$tmpId));
            if (false === $updated) return t_error();
            $updated = $wpdb->update('ds_emed_damage_ipress', array('emed_id' => $o['id']), array('emed_id' => -$tmpId));
            if (false === $updated) return t_error();
            $updated = $wpdb->update('ds_emed_damage_salud', array('emed_id' => $o['id']), array('emed_id' => -$tmpId));
            if (false === $updated) return t_error();
        }
        if ($tmpId) {
            $o['tmpId'] = $tmpId;
            $o['synchronized'] = 1;
        }
        if ($action) {
            foreach ($action as $key => &$row) {
                $row['emedId'] = $o['id'];
                $action[$key] = $this->action_post($row);
            }
            $o['action'] = $action;
        }
        if ($damage_ipress) {
            foreach ($damage_ipress as $key => &$row) {
                $row['emedId'] = $o['id'];
                $damage_ipress[$key] = $this->damage_ipress_post($row);
            }
            $o['damage_ipress'] = $damage_ipress;
        }
        if ($damage_salud) {
            foreach ($damage_salud as $key => &$row) {
                $row['emedId'] = $o['id'];
                $damage_salud[$key] = $this->damage_salud_post($row);
            }
            $o['damage_salud'] = $damage_salud;
        }
        $wpdb->query('COMMIT');
        return $o;
    }

    function action_post(&$request)
    {
        global $wpdb;
        $o = get_param($request);
        $current_user = wp_get_current_user();
        cfield($o, 'emedId', 'emed_id');
        cdfield($o, 'fecha');
        $tmpId = remove($o, 'tmpId');
        unset($o['synchronized']);
        $inserted = 0;
        if ($o['id'] > 0) {
            $o['uid_update'] = $current_user->ID;
            $o['user_update'] = $current_user->user_login;
            $o['update_date'] = current_time('mysql', 1);
            $updated = $wpdb->update('ds_emed_action', $o, array('id' => $o['id']));
        } else {
            unset($o['id']);
            $o['uid_insert'] = $current_user->ID;
            $o['user_insert'] = $current_user->user_login;
            $o['insert_date'] = current_time('mysql', 1);
            if ($tmpId) $o['offline'] = $tmpId;
            $updated = $wpdb->insert('ds_emed_action', $o);
            $o['id'] = $wpdb->insert_id;
            $inserted = 1;
        }
        if (false === $updated) return t_error();
        if ($tmpId) {
            $o['tmpId'] = $tmpId;
            $o['synchronized'] = 1;
        }
        return $o;
    }

    function damage_ipress_post(&$request)
    {
        global $wpdb;
        $o = get_param($request);
        $current_user = wp_get_current_user();
        cfield($o, 'emedId', 'emed_id');
        //cdfield($o,'fecha');
        $tmpId = remove($o, 'tmpId');
        unset($o['synchronized']);
        $inserted = 0;
        if ($o['id'] > 0) {
            $o['uid_update'] = $current_user->ID;
            $o['user_update'] = $current_user->user_login;
            $o['update_date'] = current_time('mysql', 1);
            $updated = $wpdb->update('ds_emed_damage_ipress', $o, array('id' => $o['id']));
        } else {
            unset($o['id']);
            $o['uid_insert'] = $current_user->ID;
            $o['user_insert'] = $current_user->user_login;
            $o['insert_date'] = current_time('mysql', 1);
            if ($tmpId) $o['offline'] = $tmpId;
            $updated = $wpdb->insert('ds_emed_damage_ipress', $o);
            $o['id'] = $wpdb->insert_id;
            $inserted = 1;
        }
        if (false === $updated) return t_error();
        if ($tmpId) {
            $o['tmpId'] = $tmpId;
            $o['synchronized'] = 1;
        }
        return $o;
    }

    function damage_salud_post(&$request)
    {
        global $wpdb;
        $o = get_param($request);
        $current_user = wp_get_current_user();
        cfield($o, 'emedId', 'emed_id');
        cdfield($o, 'fecha');
        $tmpId = remove($o, 'tmpId');
        unset($o['synchronized']);
        $inserted = 0;
        if ($o['id'] > 0) {
            $o['uid_update'] = $current_user->ID;
            $o['user_update'] = $current_user->user_login;
            $o['update_date'] = current_time('mysql', 1);
            $updated = $wpdb->update('ds_emed_damage_salud', $o, array('id' => $o['id']));
        } else {
            unset($o['id']);
            $o['uid_insert'] = $current_user->ID;
            $o['user_insert'] = $current_user->user_login;
            $o['insert_date'] = current_time('mysql', 1);
            if ($tmpId) $o['offline'] = $tmpId;
            $updated = $wpdb->insert('ds_emed_damage_salud', $o);
            $o['id'] = $wpdb->insert_id;
            $inserted = 1;
        }
        if (false === $updated) return t_error();
        if ($tmpId) {
            $o['tmpId'] = $tmpId;
            $o['synchronized'] = 1;
        }
        return $o;
    }

    function delete($data)
    {
        $current_user = wp_get_current_user();
        if ($current_user->has_cap('EMED_ADMIN')) {
            global $wpdb;
            $row = $wpdb->update('ds_emed', array('canceled' => 1), array('id' => $data['id']));
            return $row;
        } else {
            return new WP_Error('rest_forbidden', __('Unauthorized'));
        }
    }

    function get($data)
    {
        global $wpdb;
        $id = get_param($data,'id');
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_emed WHERE id=" . $id), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        $current_user = wp_get_current_user();
        $o['editable'] = $o['uid_insert'] == $current_user->ID;
        $o['files'] = $this->file_pag(array("emed" => $o['id']));
        $o['action'] = $this->action_pag(array("emed" => $o['id']));
        $o['damage_ipress'] = $this->damage_ipress_pag(array("emed" => $o['id']));
        $o['damage_salud'] = $this->damage_salud_pag(array("emed" => $o['id']));
        return $o;
    }

    function action_pag($request)
    {
        global $wpdb;
        $from = $request['from'];
        $to = $request['to'];
        $emed_id = get_param($request, 'emed');
        $current_user = wp_get_current_user();
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS o.* FROM ds_emed_action o " .
            "WHERE o.canceled=0 " . (isset($emed_id) ? " AND o.emed_id=$emed_id " : "") .
            "ORDER BY o.id DESC " .
            ($to > 0 ? ("LIMIT " . $from . ', ' . $to) : ""), ARRAY_A);

        if ($wpdb->last_error) return t_error();
        return $to > 0 ? array('data' => $results, 'size' => $wpdb->get_var('SELECT FOUND_ROWS()')) : $results;
    }

    function damage_ipress_pag($request)
    {
        global $wpdb;
        $from = $request['from'];
        $to = $request['to'];
        $emed = get_param($request, 'emed');
        $current_user = wp_get_current_user();
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS o.* FROM ds_emed_damage_ipress o " .
            "WHERE o.canceled=0 " . (isset($emed) ? " AND o.emed_id like '$emed' " : "") .
            "ORDER BY o.id DESC " .
            ($to > 0 ? ("LIMIT " . $from . ', ' . $to) : ""), ARRAY_A);

        if ($wpdb->last_error) return t_error();
        return $to > 0 ? array('data' => $results, 'size' => $wpdb->get_var('SELECT FOUND_ROWS()')) : $results;
    }

    function damage_salud_pag($request)
    {
        global $wpdb;
        $from = $request['from'];
        $to = $request['to'];
        $emed = get_param($request, 'emed');
        $current_user = wp_get_current_user();
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS g.* FROM ds_emed_damage_salud g " .
            "WHERE g.canceled=0 " . (isset($emed) ? " AND g.emed_id like '$emed' " : "") .
            ($to > 0 ? ("LIMIT " . $from . ', ' . $to) : ""), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        return $to > 0 ? array('data' => $results, 'size' => $wpdb->get_var('SELECT FOUND_ROWS()')) : $results;
    }

}
