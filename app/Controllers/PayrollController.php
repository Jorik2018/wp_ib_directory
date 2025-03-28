<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use function IB\directory\Util\cfield;
use function IB\directory\Util\camelCase;
use function IB\directory\Util\cdfield;
use function IB\directory\Util\t_error;

class PayrollController extends Controller
{

    public function init()
    {
        add_role(
            'payroll_admin',
            'payroll_admin',
            array(
                'PAYROLL_ADMIN'         => true,
                'PAYROLL_READ'         => true
            )
        );
        add_role(
            'payroll_register',
            'payroll_register',
            array(
                'PAYROLL_REGISTER'         => true,
                'PAYROLL_READ'         => true
            )
        );
    }

    public function rest_api_init()
    {

        register_rest_route('api/payroll', 'pregnant', array(
            'methods' => 'POST',
            'callback' => array($this, 'post')
        ));

        register_rest_route('api/payroll', 'pregnant/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'pag')
        ));

        register_rest_route('api/payroll', 'pregnant/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get')
        ));

        register_rest_route('api/payroll', 'pregnant/(?P<id>)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete')
        ));

        register_rest_route('api/payroll', 'pregnant/bulk', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk')
        ));


        register_rest_route('api/payroll', 'pregnant/bulk', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk')
        ));
        register_rest_route('api/payroll', 'pregnant', array(
            'methods' => 'POST',
            'callback' => array($this, 'post')
        ));
        register_rest_route('api/payroll', 'pregnant/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get')
        ));
        register_rest_route('api/payroll', 'pregnant/(?P<pregnant>\d+)/visit/number', array(
            'methods' => 'GET',
            'callback' => array($this, 'visit_number_get')
        ));
        register_rest_route('api/payroll', 'pregnant/visit/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'visit_get')
        ));
        register_rest_route('api/payroll', 'pregnant/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete')
        ));
        register_rest_route('api/payroll', 'pregnant/(?P<from>\d+)/(?P<to>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'pag')
        ));
        register_rest_route('api/payroll', 'people', array(
            'methods' => 'POST',
            'callback' => array($this, 'people')
        ));
        register_rest_route('api/payroll', 'chd', array(
            'methods' => 'POST',
            'callback' => array($this, 'chd')
        ));
    }

    function people($request)
    {
        global $wpdb;
        $original_db = $wpdb->dbname;
        $o = method_exists($request, 'get_params') ? $request->get_params() : $request;
        $employee = $o['employee'];
        if (!isset($o['items'])) {

            $data = $wpdb->get_results($wpdb->prepare("SELECT pc.concept,pc.amount,p.month,pc.concept_type_id,pc.concept_id 
            FROM grupoipe_erp.rem_payroll_concept pc 
            INNER JOIN grupoipe_erp.rem_payroll p ON p.id=pc.payroll_id
            WHERE pc.people_id=%s and p.year=%d
            ORDER BY  pc.concept_type_id, pc.concept_id DESC", $employee['id'], $o['year']), ARRAY_A);

            if ($wpdb->last_error) return t_error();

            $aggregatedData = [];

            foreach ($data as $row) {
                $concept = $row["concept"];
                
                $type = (int)$row["concept_type_id"];
                $month = (int)$row["month"];
                $amount = (float)$row["amount"];

                // Generar una clave única para el concepto y tipo
                $key = $concept . ':' . $type;

                // Si no existe el concepto con este tipo, inicializa
                if (!isset($aggregatedData[$key])) {
                    $aggregatedData[$key] = [
                        "concept" => $concept,
                        "conceptId" => $row["concept_id"],
                        "type" => $type
                    ];
                }

                // Agregar el monto al mes correspondiente, acumulando si ya existe
                if (!isset($aggregatedData[$key][$month])) {
                    $aggregatedData[$key][$month] = 0;
                }
                $aggregatedData[$key][$month] += $amount;
            }

            // Convertir a una lista numérica y devolverla
            return array_values($aggregatedData);
        }
        $wpdb->select('grupoipe_erp');
        $items = $o['items'];
        
        $payrolls = $wpdb->get_results($wpdb->prepare("SELECT id,month FROM grupoipe_erp.rem_payroll WHERE year=%d", $o['year']), ARRAY_A);
        $payroll_ids = array_column($payrolls, 'id');

        if (!empty($payroll_ids)) {
            // Crear placeholders para la consulta IN
            $placeholders = implode(',', array_fill(0, count($payroll_ids), '%d'));
            // Construir la consulta de eliminación
            $query = $wpdb->prepare("
                DELETE FROM grupoipe_erp.rem_payroll_concept 
                WHERE people_id = %s AND payroll_id IN ($placeholders)
            ", array_merge([$employee['id']], $payroll_ids));
            // Ejecutar la consulta
            $wpdb->query($query);
        }
        
        
        if ($wpdb->last_error) return t_error();
        $payroll_map = [];
        foreach ($payrolls as $payroll) {
            $payroll_map[$payroll['month']] = $payroll['id'];
        }
        $sql = array();

        $concept_map = [];

        foreach ($items as $item) {
            $item['concept'] = strtoupper($item['concept']);
            $concept_key = $item['concept'] . '-' . $item['type'];
            for ($i = 1; $i <= 12; $i++) {
                $v = $item[$i];
                if ($v) {
                    $payroll_id = $payroll_map[$i];
                    if (!$payroll_id) {
                        $updated = $wpdb->insert('rem_payroll', array('year' => $o['year'], 'month' => $i));
                        if (false === $updated) return t_error();
                        $payroll_map[$i] = ($payroll_id = $wpdb->insert_id);
                    }
                    $concept_id = $concept_map[$concept_key];
                    if (!$concept_id) {
                        $c = $wpdb->get_row($wpdb->prepare("SELECT id,name,type_id FROM grupoipe_erp.rem_concept 
                    WHERE name = %s AND type_id = %s LIMIT 1", $item['concept'], $item['type']), ARRAY_A);
                        if ($wpdb->last_error) return t_error();
                        if ($c) {
                            $concept_map[$concept_key] = ($concept_id = $c['id']);
                        } else {
                            $updated = $wpdb->insert('rem_concept', array(
                                'name' => $item['concept'],
                                'type_id' => $item['type'],
                                'abbreviation' => $item['concept']
                            ));
                            if (false === $updated) return t_error();
                            $concept_map[$concept_key] = ($concept_id = $wpdb->insert_id);
                        }
                    }
                    $payroll_concept = array(
                        'payroll_id' => $payroll_id,
                        'people_id' => $employee['id'],
                        'concept_id' => $concept_id,
                        'type' => $item['type'],
                        'amount' => $v,
                        'concept' => $item['concept'],
                        'concept_type_id' => $item['type']
                    );
                    $updated = $wpdb->insert('rem_payroll_concept', $payroll_concept);
                    if (false === $updated) return t_error();
                    $sql[] = $payroll_concept;
                }
            }
        }
        $wpdb->select($original_db);
        return  $sql;
    }

    function findLastExperienceBeforeYear($experiences, $año)
    {
        $referenceDate = strtotime("$año-12-31");
        $lastExperience = null;
        foreach ($experiences as $experience) {
            $experienceTimestamp = strtotime($experience['start_date']);
            // Check if the experience date is before the reference date
            if ($experienceTimestamp < $referenceDate) {
                // Update lastExperience if it's the most recent one found
                if (!$lastExperience || $experienceTimestamp > strtotime($lastExperience['start_date'])) {
                    $lastExperience = $experience;
                }
            }
        }
        return $lastExperience; // Return the last experience found
    }

    function chd($request)
    {
        global $wpdb;
        $o = method_exists($request, 'get_params') ? $request->get_params() : $request;
        $employee = $o['employee'];
        $year = isset($o['year']) ? $o['year'] : 0;
        $employee = $wpdb->get_row($wpdb->prepare("SELECT * FROM grupoipe_erp.hr_employee WHERE id=%d", $employee['id']), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        $people = $wpdb->get_row($wpdb->prepare("SELECT * FROM grupoipe_erp.drt_people WHERE id=%d", $employee['people_id']), ARRAY_A);
        $people['ruc'] = $employee['ruc'];
        if ($wpdb->last_error) return t_error();
        $experiences = $wpdb->get_results($wpdb->prepare("SELECT * FROM grupoipe_erp.hr_experience WHERE employee_id=%d", $employee['id']), ARRAY_A);
        if ($wpdb->last_error) return t_error();

        //date_default_timezone_set('America/New_York');
        $currentDate = new \DateTime();
        $formattedDate = $currentDate->format('d \D\e F Y');
        $formattedDate = strtoupper($formattedDate);

        $mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($mysqli->connect_error) {
            die("Error de conexión: " . $mysqli->connect_error);
        }
        $data = [];
        $map=[];
        $query = "SELECT pc.concept,pc.amount,p.month,pc.concept_type_id,p.year 
        FROM grupoipe_erp.rem_payroll_concept pc 
        INNER JOIN grupoipe_erp.rem_payroll p ON p.id=pc.payroll_id 
        WHERE pc.people_id=" . $employee['id'] .
            ($year > 0 ? " AND p.year=" . $year : "")
            . " ORDER BY p.year,pc.concept_type_id, pc.concept_id DESC";

        $last_concept = "";
        $last_year = "";
        $last_tipomov = 0;
        ini_set('serialize_precision', 14);
        if ($stmt = $mysqli->prepare($query)) {
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($concept, $amount, $month, $id_tipomov, $year);

            $row = [];
            $summary_row = [];
            $last_ingresos = array_fill(0, 15, 0);
            while ($stmt->fetch()) {
                // Si cambia el año, guarda los datos anteriores en el arreglo principal.
                if ($last_year != $year) {
                    $last_tipomov = 0;
                    if ($last_year != "") {
                        // Agrega el último concepto al detalle del año anterior.
                        $year_data['detail'][] = $row;
                        $data[] = $year_data; // Agrega el bloque completo del año al resultado.
                    }
                    // Inicializa datos para el nuevo año.



                    //Buscar la experiencia q esta fecha ini antes del año actual del 
                    $experience = $this->findLastExperienceBeforeYear($experiences, $year);
                    if ($experience === null) {
                        $experience = array('position' => '---', 'dependency' => '---');
                    }
                    $year_data = [
                        'fullName' => $people['names'] . ' ' . $people['first_surname'] . ' ' . $people['last_surname'],
                        'dependence' => 'DIRECCION REGIONAL DE SALUD ANCASH',
                        'subDependence' => $experience['dependency'],
                        'position' => $experience['position'],
                        'code' => $people['code'],
                        'ruc' => $people['ruc'],
                        'year' => $year,
                        'detail' => [],
                        'date' => 'HUARAZ, ' . $formattedDate
                    ];
                    $last_year = $year;
                    $last_concept = "";
                }

                // Si cambia el concepto, guarda el concepto anterior y empieza uno nuevo.
                if ($last_concept != $concept) {
                    if ($last_concept != "") {
                        $year_data['detail'][] = $row; // Agrega el concepto anterior al detalle.
                    }
                    $row = array_fill(0, 14, null); // Inicializa un nuevo concepto con índices de 0 a 12.
                    $row[0] = $concept; // Coloca el concepto en la primera posición.
                    $last_concept = $concept;
                }
                if ($last_tipomov  != $id_tipomov) {
                    if ($last_tipomov != null) {
                        $this->replaceZerosWithNull($summary_row);
                        $year_data['detail'][] = $summary_row;
                        //aqui deberia agregarse la diferencia de sumary INGRESOS - DESCUENTOS
                        if ($summary_row[0] === 'DESCUENTOS') {
                            $last_ingresos=$map['INGRESOS'];
                            $difference_row = array_fill(0, 15, 0);
                            $difference_row[14] = 2;
                            $difference_row[0] = 'TOTAL PAGO';
                            for ($i = 1; $i <= 12; $i++) {
                                $difference_row[$i] = ($last_ingresos[$i] ?? 0) - ($summary_row[$i] ?? 0);
                            }
                            $this->replaceZerosWithNull($difference_row);
                            $year_data['detail'][] = $difference_row;
                        } else if($summary_row[0] === 'INGRESOS'){
                            //$last_ingresos = $summary_row;
                            $map['INGRESOS']=$summary_row;
                        }
                    }
                    $summary_row = array_fill(0, 15, 0);
                    if ($id_tipomov == 1 || $id_tipomov == 4) {
                        $summary_row[0] = 'INGRESOS';
                        $summary_row[13] = $id_tipomov;
                        $summary_row[14] = 2;
                    } else if ($id_tipomov == 2 || $id_tipomov == 5) {
                        $summary_row[0] = 'DESCUENTOS';
                        $summary_row[13] = $id_tipomov;
                        $summary_row[14] = 2;
                    } else if ($id_tipomov == 3 || $id_tipomov == 6) {
                        $summary_row[0] = 'APORTACIONES';
                        $summary_row[13] = $id_tipomov;
                        $summary_row[14] = 2;
                    }

                    $last_tipomov = $id_tipomov;
                }
                // Asigna el monto al mes correspondiente.
                if ($month >= 1 && $month <= 12) {
                    $row[$month] = number_format($amount, 2, '.', ''); //$amount;
                    $summary_row[$month] += $amount;
                }
            }

            // Agrega los datos restantes del último concepto y año.
            if (!empty($row)) {
                $year_data['detail'][] = $row;
            }
            if (!empty($summary_row)) {
                $this->replaceZerosWithNull($summary_row);
                $year_data['detail'][] = $summary_row;
                //aqui deberia agregarse la diferencia de sumary INGRESOS - DESCUENTOS
                if ($summary_row[0] === 'DESCUENTOS') {
                    $difference_row = array_fill(0, 15, 0);
                    $difference_row[0] = 'TOTAL PAGO';
                    for ($i = 1; $i <= 12; $i++) {
                        $difference_row[$i] = ($last_ingresos[$i] ?? 0) - ($summary_row[$i] ?? 0);
                    }
                    $this->replaceZerosWithNull($difference_row);
                    $year_data['detail'][] = $difference_row;
                }else if($summary_row[0] === 'INGRESOS'){
                    //$last_ingresos = $summary_row;
                    $map['INGRESOS']=$summary_row;
                }
            }
            if (!empty($year_data)) {
                $data[] = $year_data;
            }

            $stmt->close();
        }

        $mysqli->close();




        return $data;

        // Crear el archivo como contenido de un string
        $fileContents = json_encode($data);
        $externalApiUrl = 'http://web.regionancash.gob.pe/api/jreport/';
        $filename = 'data.json'; // Nombre del archivo "virtual"
        // Crear cuerpo en formato multipart/form-data
        $boundary = wp_generate_password(24, false);
        $body = "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"$filename\"\r\n";
        $body .= "Content-Type: application/json\r\n\r\n";
        $body .= $fileContents . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"filename\"\r\n\r\n";
        $body .= $filename . "\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Disposition: form-data; name=\"template\"\r\n\r\n";
        $body .= 'hc';
        $body .= "\r\n--$boundary--";

        // Configurar las cabeceras
        $headers = [
            "Authorization" => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
            "Content-Type"  => "multipart/form-data; boundary=$boundary",
            "Accept"        => "*/*",
        ];

        // Enviar solicitud usando wp_remote_post
        $response = wp_remote_post($externalApiUrl, [
            'body'    => $body,
            'headers' => $headers,
            'timeout' => 30, // Tiempo de espera ajustable
        ]);

        // Manejo de errores
        if (is_wp_error($response)) {
            return new \WP_REST_Response(['error' => 'Error conectando con la API externa'], 500);
        }

        // Devolver respuesta de la API externa
        $responseHeaders = wp_remote_retrieve_headers($response);
        $responseBody = wp_remote_retrieve_body($response);
        $responseCode = wp_remote_retrieve_response_code($response);

        if ($responseCode !== 200) {
            return new \WP_REST_Response([
                'error'  => 'Error en la API externa',
                'status' => $responseCode,
                'body'   => $responseBody,
            ], 500);
        }

        // Configurar las cabeceras para la descarga del binario
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="archivo.pdf"');
        header('Content-Length: ' . strlen($responseBody));
        /*foreach ($responseHeaders as $header => $value) {
            header($header . ': ' . $value); // Agrega encabezados adicionales de la API externa si necesario
        }*/

        // Imprimir el cuerpo de respuesta para iniciar la descarga
        echo $responseBody;
        exit;
    }

    function replaceZerosWithNull(&$row)
    {
        foreach ($row as $key => $value) {
            if ($value === 0) {
                $row[$key] = null;
            }
        }
    }

    function bulk($request)
    {
        global $wpdb;
        $rl = $request->get_params();
        file_put_contents("data2.json", json_encode($rl));
        $current_user = wp_get_current_user();
        $aux = array();
        foreach ($rl as &$o) {
            $aux[] = $this->post($o);
        }
        return $aux;
    }

    public function post($request)
    {
        global $wpdb;
        $o = method_exists($request, 'get_params') ? $request->get_params() : $request;
        $current_user = wp_get_current_user();
        $onlyUpload = remove($o, 'onlyUpload');
        $migration = remove($o, 'migration');
        if ($onlyUpload) return array('success' => true);
        foreach (
            [
                'establecimiento_salud',
                'codigo_EESS',
                'codigo_CCPP',
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
                'gestante_paridad',
                'gestante_FUR',
                'gestante_FPP',
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
                'user_register',
                'user_modificacion'
            ] as &$k
        ) {
            cfield($o, camelCase($k), $k);
        }
        cfield($o, 'codigoEESS', 'codigo_EESS');
        unset($o['codigo_eess']);

        cdfield($o, 'gestante_FUR');
        cdfield($o, 'fecha_nacimiento');
        cdfield($o, 'gestante_FPP');
        cdfield($o, 'lugar_fecha_emergencia');
        cdfield($o, 'lugar_fecha_referida');
        cdfield($o, 'migracion_fecha');

        $tmpId = remove($o, 'tmpId');
        unset($o['agreement']);
        unset($o['synchronized']);
        $visits = remove($o, 'visits');
        $agreements = remove($o, 'agreements');
        //quitar donde se guarda la imagen del familiograma
        unset($o['ext']);

        $o['updated_date'] = current_time('mysql', 1);
        if ($migration) {
            $o['migracion_fecha'] = current_time('mysql', 1);
        }
        $inserted = false;
        $wpdb->query('START TRANSACTION');
        if ($o['id'] > 0) {
            $o['user_register'] = $current_user->user_login;
            $o['uid_update'] = $current_user->ID;
            $updated = $wpdb->update('ds_gestante', $o, array('id' => $o['id']));
        } else {
            $o['uid_insert'] = $current_user->ID;
            $o['user_modificacion'] = $current_user->user_login;
            unset($o['id']);
            if ($tmpId) $o['offline'] = $tmpId;
            $updated = $wpdb->insert('ds_gestante', $o);
            $o['id'] = $wpdb->insert_id;
            $inserted = true;
        }
        if (false === $updated) return t_error();
        if ($migration) {
            //Aqui se graba el regitro migracion

        }
        //Si se ha insertado pero tenia registros temporales grabados esos ahora deberan tener el id final real
        if ($inserted && $tmpId) {
            $updated = $wpdb->update('ds_sivico_people', array('master_id' => $o['id']), array('master_id' => -$tmpId));
            if (false === $updated) return t_error();
            $updated = $wpdb->update('ds_sivico_agreement', array('master_id' => $o['id']), array('master_id' => -$tmpId));
            if (false === $updated) return t_error();
        }
        if ($tmpId) {
            $o['tmpId'] = $tmpId;
            $o['synchronized'] = 1;
        }
        if ($visits) {
            foreach ($visits as $key => &$visit) {
                $visit['pregnantId'] = $o['id'];
                $visits[$key] = visit_post($visit);
            }
            $o['visits'] = $visits;
        }
        if ($agreements) {
            foreach ($agreements as $key => &$agreement) {
                $agreement['masterId'] = $o['id'];
                $agreements[$key] = api_sivico_agreement_post($agreement);
            }
            $o['agreements'] = $agreements;
        }
        $wpdb->query('COMMIT');
        return $o;
    }

    public function get($request)
    {
        global $wpdb;
        //$data=method_exists($data,'get_params')?$data->get_params():$data;
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_gestante WHERE id=" . $request['id']), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        foreach (
            [
                'establecimiento_salud',
                'codigo_EESS',
                'codigo_CCPP',
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
                'gestante_paridad',
                'gestante_FUR',
                'gestante_FPP',
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
                'user_register',
                'user_modificacion'
            ] as &$k
        ) {
            cfield($o, $k, camelCase($k));
        }
        cfield($o, 'codigo_eess', 'codigoEESS');
        cfield($o, 'numero_dni', 'numeroDNI');
        cfield($o, 'codigo_ccpp', 'codigoCCPP');
        cfield($o, 'gestante_fur', 'gestanteFUR');
        cfield($o, 'gestante_fpp', 'gestanteFPP');
        cdfield($o, 'gestanteFUR');
        cdfield($o, 'gestanteFPP');
        $o['ext'] = array();
        $o['visits'] = $this->visit_pag(array("gestanteId" => $o['id']));
        return $o;
    }



    public function pag($request)
    {
        global $wpdb;
        $edb = 2;
        $from = $request['from'];
        $to = $request['to'];
        $numeroDNI = method_exists($request, 'get_param') ? $request->get_param('numeroDNI') : $request['numeroDNI'];
        $fullName = method_exists($request, 'get_param') ? $request->get_param('fullName') : $request['fullName'];
        $red = method_exists($request, 'get_param') ? $request->get_param('red') : $request['red'];
        $microred = method_exists($request, 'get_param') ? $request->get_param('microred') : $request['microred'];
        $microredName = method_exists($request, 'get_param') ? $request->get_param('microredName') : $request['microredName'];
        $current_user = wp_get_current_user();
        $wpdb->last_error  = '';

        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS g.*,r.red as nameRed,mr.microred as nameMicroRed,COUNT(v.id) AS visits FROM ds_gestante g " .
            "LEFT JOIN ds_gestante_visita v ON v.gestante_id=g.id 
            LEFT JOIN grupoipe_project.MAESTRO_RED r ON r.codigo_red=g.red
            LEFT JOIN grupoipe_project.MAESTRO_MICRORED mr ON mr.codigo_cocadenado=g.microred
            WHERE g.canceled=0 " . (isset($numeroDNI) ? " AND g.numero_dni like '%$numeroDNI%' " : "")
            . (isset($fullName) ? " AND CONCAT(g.apellido_paterno,g.apellido_materno,g.nombres) like '%$fullName%' " : "")
            . (isset($red) ? " AND g.red like '%$red%' " : "")
            . (isset($microred) ? " AND g.microred like '%$microred%' " : "")
            . (isset($microredName) ? " AND UPPER(mr.microred) like UPPER('%$microredName%') " : "") .
            "GROUP BY g.id " .
            "ORDER BY id desc LIMIT " . $from . ', ' . $to, ARRAY_A);

        if ($wpdb->last_error) return t_error();
        foreach ($results as &$r) {
            cfield($r, 'numero_dni', 'numeroDNI');
            if (isset($r['nameRed'])) $r['red'] = array('code' => $r['red'], 'name' => $r['nameRed']);
            if (isset($r['nameMicroRed'])) $r['microred'] = array('code' => $r['microred'], 'name' => $r['nameMicroRed']);
            cfield($r, 'estado_civil', 'estadoCivil');
            cfield($r, 'emergency_microred', 'emergencyMicrored');
            cfield($r, 'grado_instruccion', 'gradoInstruccion');
        }
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');
        if ($wpdb->last_error) return t_error();
        return array('data' => $results, 'size' => $count);
    }


    public function visit_pag($request)
    {
        global $wpdb;
        $from = $request['from'];
        $to = $request['to'];
        $gestanteId = method_exists($request, 'get_param') ? $request->get_param('gestanteId') : $request['gestanteId'];
        $current_user = wp_get_current_user();
        $wpdb->last_error  = '';
        $results = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS * FROM ds_gestante_visita d Where canceled=0 " . ($gestanteId ? "AND gestante_id=$gestanteId" : "") . " ORDER BY id desc " . ($to ? "LIMIT " . $from . ', ' . $to : ""), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        foreach ($results as &$r) {
            cfield($r, 'fecha_visita', 'fechaVisita');
            cfield($r, 'numero_visita', 'number');
            cfield($r, 'gestante_id', 'gestanteId');
        }
        $count = $wpdb->get_var('SELECT FOUND_ROWS()');
        if ($wpdb->last_error) return t_error();

        return $to ? array('data' => $results, 'size' => $count) : $results;
    }

    public function delete($data)
    {
        global $wpdb;
        $row = $wpdb->update('ds_gestante', array('canceled' => 1), array('id' => $data['id']));
        return $row;
    }

    function visit_post(&$request)
    {
        global $wpdb;
        $o = method_exists($request, 'get_params') ? $request->get_params() : $request;
        $current_user = wp_get_current_user();
        cdfield($o, 'fechaVisita');

        cfield($o, 'pregnantId', 'gestante_id');
        cfield($o, 'fechaVisita', 'fecha_visita');
        cfield($o, 'number', 'numero_visita');
        cdfield($o, 'fechaProxVisita');
        cfield($o, 'fechaProxVisita', 'fecha_prox_visita');
        unset($o['people']);
        unset($o['ext']);
        $tmpId = remove($o, 'tmpId');
        unset($o['synchronized']);
        $o['uid'] = $current_user->ID;

        $inserted = 0;
        if ($o['id'] > 0) {
            $o['updated_date'] = current_time('mysql', 1);
            $updated = $wpdb->update('ds_gestante_visita', $o, array('id' => $o['id']));
        } else {
            unset($o['id']);
            $max = $wpdb->get_row($wpdb->prepare("SELECT ifnull(max(`numero_visita`),0)+1 AS max FROM ds_gestante_visita WHERE gestante_id=" . $o['gestante_id']), ARRAY_A);
            $o['numero_visita'] = $max['max'];
            $o['user_register'] = $current_user->user_login;
            $o['inserted_date'] = current_time('mysql', 1);
            if ($tmpId) $o['offline'] = $tmpId;
            $updated = $wpdb->insert('ds_gestante_visita', $o);
            $o['id'] = $wpdb->insert_id;
            $inserted = 1;
        }
        if (false === $updated) return t_error();
        if ($inserted && $tmpId) {
            $updated = $wpdb->update('ds_sivico_agreement', array('people_id' => $o['id']), array('people_id' => -$tmpId));
            if (false === $updated) return t_error();
        }
        if ($tmpId) {
            $o['tmpId'] = $tmpId;
            $o['synchronized'] = 1;
        }

        cfield($o, 'numero_visita', 'numeroVisita');
        return $o;
    }

    function visit_get($data)
    {
        global $wpdb;
        //$data=method_exists($data,'get_params')?$data->get_params():$data;
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM ds_gestante_visita WHERE id=" . $data['id']), ARRAY_A);
        if ($wpdb->last_error) return t_error();
        cfield($o, 'fecha_visita', 'fechaVisita');
        cdfield($o, 'fechaProxVisita');
        cfield($o, 'fecha_prox_visita', 'fechaProxVisita');
        cfield($o, 'numero_visita', 'number');
        cfield($o, 'gestante_id', 'pregnantId');
        cdfield($o, 'fechaVisita');
        return $o;
    }

    function visit_number_get($request)
    {
        global $wpdb;
        $max = $wpdb->get_row($wpdb->prepare("SELECT ifnull(max(`numero_visita`),0)+1 AS max FROM ds_gestante_visita WHERE gestante_id=" . $request['pregnant']), ARRAY_A);
        return $max['max'];
    }
}
