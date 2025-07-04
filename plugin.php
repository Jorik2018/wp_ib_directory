<?php
/*
Plugin Name: ib-directory
Plugin URI: 
Description: 
Version: 1.0.0
Author: 
Author URI: 
License: 
License URI: 
Text Domain: ib-directory
Domain Path: /assets/lang
Requires PHP: 5.4
*/
//------------------------------------------------------------
//
// NOTE:
//
// Try NOT to add any code line in this file.
//
// Use "app\Main.php" to add your hooks.
//
//------------------------------------------------------------
function directory_install()
{
  global $wpdb;

  $db = get_option("db_erp");

  //$wpdb->prefix .
  $charset_collate = $wpdb->get_charset_collate();

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $sql = "CREATE TABLE IF NOT EXISTS ds_emed (
        id int NOT NULL AUTO_INCREMENT,
        code varchar(20) DEFAULT NULL,
        offline int DEFAULT NULL,
        category varchar(10) NOT NULL,
        type varchar(50) NOT NULL,
        detail varchar(100) DEFAULT NULL,
        description varchar(500) DEFAULT NULL,
        date date DEFAULT NULL,
        time time DEFAULT NULL,
        region varchar(255) DEFAULT NULL,
        province varchar(255) DEFAULT NULL,
        district varchar(255) DEFAULT NULL,
        codigo_ccpp varchar(255) DEFAULT NULL,
        ccpp varchar(100) DEFAULT NULL,
        referencia varchar(200) DEFAULT NULL,
        microred int DEFAULT NULL,
        lon double DEFAULT NULL,
        lat double DEFAULT NULL,
        ambulancias int DEFAULT NULL,
        personal int DEFAULT NULL,
        brigadistas int DEFAULT NULL,
        equipo_tecnico int DEFAULT NULL,
        fuente_institucion varchar(100) DEFAULT NULL,
        fuente_nombre_completo varchar(100) DEFAULT NULL,
        fuente_cargo varchar(100) DEFAULT NULL,
        fuente_celular varchar(100) DEFAULT NULL,
        fuente_responsable_ipress varchar(100) DEFAULT NULL,
        fuente_responsable_nombre_completo varchar(100) DEFAULT NULL,
        fuente_responsable_cargo varchar(100) DEFAULT NULL,
        fuente_responsable_celular varchar(100) DEFAULT NULL,
        fuente_verifica_emed varchar(100) DEFAULT NULL,
        fuente_verifica_nombre_completo varchar(100) DEFAULT NULL,
        fuente_verifica_cargo varchar(100) DEFAULT NULL,
        fuente_verifica_celular varchar(100) DEFAULT NULL,
        uid_insert int NOT NULL,
        uid_update int DEFAULT NULL,
        insert_date datetime NOT NULL,
        update_date datetime DEFAULT NULL,
        user_insert varchar(50) NOT NULL,
        user_update varchar(50) DEFAULT NULL,
        canceled tinyint(1) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
  dbDelta($sql);

  $original_db_name = $wpdb->dbname;
  $sql = "USE $db";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ds_gestante (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          `offline` bigint(20) DEFAULT NULL,
          red varchar(255) DEFAULT NULL,
          microred varchar(255) DEFAULT NULL,
          establecimiento_salud varchar(255) DEFAULT NULL,
          codigo_eess varchar(255) DEFAULT NULL,
          region varchar(255) DEFAULT NULL,
          province varchar(255) DEFAULT NULL,
          district varchar(255) DEFAULT NULL,
          ccpp varchar(255) DEFAULT NULL,
          codigo_ccpp varchar(255) DEFAULT NULL,
          sector varchar(255) DEFAULT NULL,
          `address` varchar(255) DEFAULT NULL,
          numero_dni varchar(255) DEFAULT NULL,
          apellido_paterno varchar(80) NOT NULL,
          apellido_materno varchar(80) NOT NULL DEFAULT '-',
          nombres varchar(255) DEFAULT NULL,
          fecha_nacimiento date DEFAULT NULL,
          estado_civil varchar(255) DEFAULT NULL,
          grado_instruccion varchar(255) DEFAULT NULL,
          gestante_numero_celular varchar(255) DEFAULT NULL,
          gestante_familia_celular varchar(255) DEFAULT NULL,
          gestante_numero varchar(255) DEFAULT NULL,
          gestante_paridad varchar(255) DEFAULT NULL,
          gestante_fur date DEFAULT NULL,
          gestante_fpp date DEFAULT NULL,
          gestante_edad_gestacional_semanas varchar(255) DEFAULT NULL,
          gestante_riesgo_obstetrico varchar(255) DEFAULT NULL,
          emergency_microred varchar(6) DEFAULT NULL,
          emergency_red varchar(2) DEFAULT NULL,
          lugar_ipress varchar(255) DEFAULT NULL,
          lugar_diagnostico varchar(255) DEFAULT NULL,
          lugar_fecha_emergencia date DEFAULT NULL,
          lugar_fecha_referida date DEFAULT NULL,
          migra_red varchar(6) DEFAULT NULL,
          migra_microred varchar(6) DEFAULT NULL,
          migracion_ipress varchar(255) DEFAULT NULL,
          migracion_observacion varchar(255) DEFAULT NULL,
          migracion_estado varchar(255) DEFAULT NULL,
          migracion_fecha date DEFAULT NULL,
          lon double DEFAULT NULL,
          lat double DEFAULT NULL,
          uid_insert int DEFAULT NULL,
          uid_update int DEFAULT NULL,
          updated_date datetime DEFAULT NULL,
          user_register varchar(255) DEFAULT NULL,
          user_modificacion varchar(50) DEFAULT NULL,
          delete_uid int DEFAULT NULL,
          delete_user varchar(100) DEFAULT NULL,
          delete_date datetime DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
  ) $charset_collate;";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ds_gestante_visita (
          id int NOT NULL AUTO_INCREMENT,
          `offline` bigint(20) DEFAULT NULL,
          gestante_id int NOT NULL,
          numero_visita int DEFAULT NULL,
          fecha_visita datetime NOT NULL,
          fecha_prox_visita date DEFAULT NULL,
          detalle varchar(255) DEFAULT NULL,
          user_register varchar(255) DEFAULT NULL,
          `uid` int NOT NULL,
          `lat` double DEFAULT NULL,
          `lon` double DEFAULT NULL,
          updated_date datetime DEFAULT NULL,
          inserted_date datetime NOT NULL,
          `type` varchar(2) DEFAULT NULL,
          delete_uid int DEFAULT NULL,
          delete_user varchar(100) DEFAULT NULL,
          delete_date datetime DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
  ) $charset_collate;";
  $wpdb->query($sql);

  //add_option('jal_db_version', $this->version);
  $sql = "CREATE TABLE IF NOT EXISTS ds_emed_action (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          offline bigint(20) DEFAULT NULL,
          emed_id bigint(20) NOT NULL,
          fecha date NOT NULL,
          hora time NOT NULL,
          descripcion text NOT NULL,
          insert_uid int DEFAULT NULL,
          insert_user varchar(100) DEFAULT NULL,
          insert_date datetime DEFAULT NULL,
          update_uid int DEFAULT NULL,
          update_user varchar(100) DEFAULT NULL,
          update_date datetime DEFAULT NULL,
          delete_uid int DEFAULT NULL,
          delete_user varchar(100) DEFAULT NULL,
          delete_date datetime DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
  ) $charset_collate;";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ds_vea_materno (
          id int NOT NULL AUTO_INCREMENT,
          offline int DEFAULT NULL,
          semana int NOT NULL,
          red varchar(200) NOT NULL,
          microred varchar(200) NOT NULL,
          codigo_eess varchar(200) NOT NULL,
          n1 int NOT NULL,
          n2 int NOT NULL,
          n3 int DEFAULT NULL COMMENT 'Nº Gestantes evaluadas por médico en 2° atención prenatal',
          n4 int DEFAULT NULL COMMENT 'N° Gestantes que recibieron Teleorientacion o Teleinterconsulta	',
          n5 int DEFAULT NULL COMMENT 'N° Gestantes/puérperas que recibieron vacuna anti COVID-19, registradas',
          n6 int DEFAULT NULL COMMENT 'N° gestantes con Dx (+) para COVId-19, con seguimiento',
          n7 int DEFAULT NULL COMMENT 'N° Gestantes con FPP de su jurisdicción en la semana',
          n8 int DEFAULT NULL COMMENT 'N° de gestantes con entrevista del tercer plan de parto efectivo a traves de visita domiciliaria ',
          n9 int DEFAULT NULL COMMENT 'N° Gestantes con anemia diagnosticada',
          n10 int DEFAULT NULL COMMENT 'N° Gestantes con anemia recuperadas',
          n11 int DEFAULT NULL COMMENT 'N° Gestantes con cambio domiciliario reportadas.',
          n12 int DEFAULT NULL COMMENT 'N° Ambulancias operativas, con sistema de comunicación activo',
          n13 int DEFAULT NULL COMMENT 'N° Partos inminentes con monitoreo atendidos en IPRESS de nivel I-1 al I-3',
          n14 int DEFAULT NULL COMMENT 'N° Emergencias obstetricas atendidas según guía de EON ',
          n15 int DEFAULT NULL COMMENT 'N° Emergencias obstetricas referidas',
          n16 int DEFAULT NULL COMMENT 'N° Usuarias nuevas en Planificación Familiar',
          resources text COMMENT 'INSUMOS Y EQUIPOS',
          ipress_1 text COMMENT 'IPRESS con insumos para tamizaje a gestantes (PAQUETE BASICO), abastecidos en meses según CPM',
          ipress_2 text COMMENT 'IPRESS con Claves Obstetricas implementados (Clave roja, azul y amarilla)',
          ipress_3 text COMMENT 'IPRESS con Sulfato Ferroso, abastecidos.',
          ipress_4 text COMMENT 'IPRESS con insumos de PPFF abastecidos en todos los métodos.',
          ipress_5 text COMMENT 'IPRESS con formato HIS abastecidos',
          ipress_6 text COMMENT 'IPRESS con formato FUA abastecidos',
          observations text COMMENT 'OBSERVACIONES',

          uid_insert int DEFAULT NULL,
          user_insert varchar(100) DEFAULT NULL,
          insert_date datetime DEFAULT NULL,

          uid_update int DEFAULT NULL,
          user_update varchar(100) DEFAULT NULL,
          update_date datetime DEFAULT NULL,

          uid_delete int DEFAULT NULL,
          user_delete varchar(100) DEFAULT NULL,
          delete_date datetime DEFAULT NULL,

          canceled bit(1) DEFAULT 0,
          PRIMARY KEY (id)
    ) $charset_collate;";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ds_emed_damage_ipress (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          offline bigint(20) NOT NULL,
          emed_id bigint(20) NOT NULL,
          red int NOT NULL,
          microred int NOT NULL,
          ipress varchar(100) NOT NULL,
          category varchar(100) NOT NULL,
          status varchar(100) NOT NULL,
          remark varchar(200) NOT NULL,
          insert_uid int DEFAULT NULL,
          insert_user varchar(100) DEFAULT NULL,
          insert_date datetime DEFAULT NULL,
          update_uid int DEFAULT NULL,
          update_user varchar(100) DEFAULT NULL,
          update_date datetime DEFAULT NULL,
          delete_uid int DEFAULT NULL,
          delete_user varchar(100) DEFAULT NULL,
          delete_date datetime DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
    ) $charset_collate;";

  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ds_emed_damage_salud (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          offline bigint(20) NOT NULL,
          emed_id bigint(20) NOT NULL,
          code varchar(10) NOT NULL,
          nombre_completo varchar(100) NOT NULL,
          edad varchar(20) DEFAULT NULL,
          diagnostico text,
          gravedad varchar(200) DEFAULT NULL,
          situacion varchar(200) DEFAULT NULL,
          observacion varchar(200) DEFAULT NULL,
          insert_uid int DEFAULT NULL,
          insert_user varchar(100) DEFAULT NULL,
          insert_date datetime DEFAULT NULL,
          update_uid int DEFAULT NULL,
          update_user varchar(100) DEFAULT NULL,
          update_date datetime DEFAULT NULL,
          delete_uid int DEFAULT NULL,
          delete_user varchar(100) DEFAULT NULL,
          delete_date datetime DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
      ) $charset_collate;";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ds_emed_file (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          offline bigint(20) NOT NULL,
          emed_id bigint(20) NOT NULL,
          src varchar(100) NULL,
          insert_uid int NOT NULL,
          insert_date int NOT NULL,
          insert_user varchar(50) NULL,
          delete_uid int NULL,
          delete_date int NULL,
          delete_user varchar(50) NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
      )";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS drt_departamento (
    id_pais int NOT NULL,
    id_dpto int NOT NULL,
    nombre_dpto varchar(100) NOT NULL,
    abreviatura_dpto varchar(20) DEFAULT NULL,
    codigo_dpto varchar(2) DEFAULT NULL,
    PRIMARY KEY (id_pais,id_dpto)
    )";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS drt_cie (
    code varchar(25) NOT NULL,
    Descripcion_Item varchar(255) DEFAULT NULL,
    Fg_Tipo varchar(255) DEFAULT NULL,
    Descripcion_Tipo_Item varchar(255) DEFAULT NULL,
    Fg_Estado varchar(255) DEFAULT NULL,
    PRIMARY KEY (code)
    )";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS matm_persona (
    id int NOT NULL AUTO_INCREMENT,
    nacionalidad varchar(50) DEFAULT NULL,
    ubigeo char(6) DEFAULT NULL,
    ubigeo_ccpp char(4) DEFAULT NULL,
    documento_tipo varchar(10) NOT NULL,
    documento_nro varchar(20) NOT NULL,
    ape_paterno varchar(50) NOT NULL,
    ape_materno varchar(50) NOT NULL,
    nombres varchar(100) NOT NULL,
    estado_civil varchar(20) DEFAULT NULL,
    ape_casado varchar(50) DEFAULT NULL,
    sexo char(1) NOT NULL,
    fecha_nacimiento date NOT NULL,
    direccion varchar(150) DEFAULT NULL,
    celular varchar(20) DEFAULT NULL,
    correo varchar(100) DEFAULT NULL,
    idioma_predominante varchar(20) DEFAULT NULL,
    cod_familia varchar(255) DEFAULT NULL,
    lat varchar(255) DEFAULT NULL,
    lon varchar(255) DEFAULT NULL,
    insert_uid int DEFAULT NULL,
    insert_user varchar(100) DEFAULT NULL,
    insert_date datetime DEFAULT NULL,
    update_uid int DEFAULT NULL,
    update_user varchar(100) DEFAULT NULL,
    update_date datetime DEFAULT NULL,
    delete_uid int DEFAULT NULL,
    delete_user varchar(100) DEFAULT NULL,
    delete_date datetime DEFAULT NULL,
    canceled tinyint(1) DEFAULT '0',
    PRIMARY KEY (id)
  ) $charset_collate;";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS mon_atenciones (
    id int NOT NULL AUTO_INCREMENT,
    persona_id bigint(20) NOT NULL,
    Codigo_Unico varchar(10) DEFAULT NULL,
    Id_Cita varchar(3) DEFAULT NULL,
    Fecha_Atencion date DEFAULT NULL,
    Codigo_Item varchar(11) DEFAULT NULL,
    Id_Correlativo_Item int DEFAULT NULL,
    Valor_Lab varchar(3) DEFAULT NULL,
    Id_Correlativo_Lab int DEFAULT NULL,
    Peso decimal(5,2) DEFAULT NULL,
    Talla decimal(5,2) DEFAULT NULL,
    Hemoglobina decimal(4,2) DEFAULT NULL,
    lat varchar(255) DEFAULT NULL,
    lon varchar(255) DEFAULT NULL,
    insert_uid int DEFAULT NULL,
    insert_user varchar(100) DEFAULT NULL,
    insert_date datetime DEFAULT NULL,
    update_uid int DEFAULT NULL,
    update_user varchar(100) DEFAULT NULL,
    update_date datetime DEFAULT NULL,
    delete_uid int DEFAULT NULL,
    delete_user varchar(100) DEFAULT NULL,
    delete_date datetime DEFAULT NULL,
    canceled tinyint(1) DEFAULT '0',
    PRIMARY KEY (id)
  ) $charset_collate;";
  $wpdb->query($sql);

  if ($wpdb->last_error) {
    $log_file = __DIR__ . '/error_log_plugin.txt'; // Puedes cambiar la ruta
    $mensaje = '[' . date('Y-m-d H:i:s') . '] ' . $wpdb->last_error . PHP_EOL;
    file_put_contents($log_file, $mensaje, FILE_APPEND);
  }
  $sql = "USE $original_db_name";
  $wpdb->query($sql);
  if ($wpdb->last_error) {
    $log_file = __DIR__ . '/error_log_plugin.txt'; // Puedes cambiar la ruta
    $mensaje = '[' . date('Y-m-d H:i:s') . '] ' . $wpdb->last_error . PHP_EOL;
    file_put_contents($log_file, $mensaje, FILE_APPEND);
  }
}

register_activation_hook(__FILE__, 'directory_install');

require_once(__DIR__ . '/app/Boot/bootstrap.php');
