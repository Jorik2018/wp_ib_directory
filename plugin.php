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
  //$wpdb->prefix .
  $table_name =  'ds_emed';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE $table_name (
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

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  dbDelta($sql);

  $sql = "CREATE TABLE IF NOT EXISTS drt_provincia (
        id_pais int NOT NULL,
        id_dpto int NOT NULL,
        id_prov int NOT NULL,
        nombre_prov varchar(100) NOT NULL,
        abreviatura_prov varchar(30) DEFAULT NULL,
        codigo_prov varchar(4) DEFAULT NULL,
        government_id bigint(20) DEFAULT NULL,
        PRIMARY KEY (id_dpto,id_pais,id_prov)
      )";

  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ds_emed_action (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          offline bigint(20) DEFAULT NULL,
          emed_id bigint(20) NOT NULL,
          fecha date NOT NULL,
          hora time NOT NULL,
          descripcion text NOT NULL,
          uid_insert int NOT NULL,
          uid_update int DEFAULT NULL,
          insert_date datetime NOT NULL,
          update_date datetime DEFAULT NULL,
          user_insert varchar(50) NOT NULL,
          user_update varchar(50) DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
    )";

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
    )";
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
          uid_insert int NOT NULL,
          uid_update int DEFAULT NULL,
          insert_date datetime NOT NULL,
          update_date datetime DEFAULT NULL,
          user_insert varchar(50) NOT NULL,
          user_update varchar(50) DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
    )";

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
          uid_insert int NOT NULL,
          uid_update int DEFAULT NULL,
          insert_date datetime NOT NULL,
          update_date datetime DEFAULT NULL,
          user_insert varchar(50) NOT NULL,
          user_update varchar(50) DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
      )";
  $wpdb->query($sql);
  
  $sql = "CREATE TABLE IF NOT EXISTS ds_emed_file (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          offline bigint(20) NOT NULL,
          emed_id bigint(20) NOT NULL,
          src varchar(100) NULL,
          uid_insert int NOT NULL,
          insert_date int NOT NULL,
          user_insert varchar(50) NULL,
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

  $sql = "CREATE TABLE IF NOT EXISTS drt_ccpp (
    ID int,
    Ubigeo_Distrito varchar(255) DEFAULT NULL,
    Ubigeo_Centropoblado varchar(255) DEFAULT NULL,
    Provincia varchar(255) DEFAULT NULL,
    Distrito varchar(255) DEFAULT NULL,
    Nombre_Centro_Poblado varchar(255) DEFAULT NULL,
    Codigo_Unico varchar(255) DEFAULT NULL,
    Establecimiento varchar(255) DEFAULT NULL,
    Micro_Red varchar(255) DEFAULT NULL,
    Red varchar(255) DEFAULT NULL,
    Este varchar(255) DEFAULT NULL,
    Norte varchar(255) DEFAULT NULL,
    Latitud varchar(255) DEFAULT NULL,
    Longitud varchar(255) DEFAULT NULL,
    Ambito varchar(255) DEFAULT NULL,
    PRIMARY KEY (ID)
    )";
  $wpdb->query($sql);
  //add_option('jal_db_version', $this->version);

  $original_db_name = $wpdb->dbname;

  $db_name = 'grupoipe_regexa_ecr';

  $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
  $wpdb->query($sql);

  $sql = "USE $db_name";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ipress_red (
        ID int DEFAULT NULL,
        Codigo_Red varchar(255) NOT NULL,
        Red varchar(255) DEFAULT NULL,
        PRIMARY KEY (ID)
      )";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ipress_microred (
        ID  int,
        codigo_DISA  varchar(2) NOT NULL,
        Codigo_Red  varchar(255) DEFAULT NULL,
        Codigo_Microrred  varchar(255) DEFAULT NULL,
        Codigo_Cocadenado  varchar(255) NOT NULL,
        Microred  varchar(255) DEFAULT NULL,
        PRIMARY KEY (ID)
    )";
  $wpdb->query($sql);

  $sql = "CREATE TABLE IF NOT EXISTS ipress_eess (
    ID int(255),
    Codigo_Red varchar(255) DEFAULT NULL,
    Codigo_Microred varchar(255) DEFAULT NULL,
    Codigo_Cocadenado varchar(255) DEFAULT NULL,
    Codigo_Unico varchar(255) NOT NULL,
    Nombre_Establecimiento varchar(255) DEFAULT NULL,
    UBIGEO varchar(255) DEFAULT NULL,
      PRIMARY KEY (ID)
    )";
  $wpdb->query($sql);

  $sql = "USE $original_db_name";
  $wpdb->query($sql);
}

register_activation_hook(__FILE__, 'directory_install');

require_once(__DIR__ . '/app/Boot/bootstrap.php');
