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
    $table_name =  'ds_emed'; // Adds the WordPress prefix
    $charset_collate= $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        code varchar(20) DEFAULT NULL,
        offline int(11) DEFAULT NULL,
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
        microred int(11) DEFAULT NULL,
        lon double DEFAULT NULL,
        lat double DEFAULT NULL,
        ambulancias int(11) DEFAULT NULL,
        personal int(11) DEFAULT NULL,
        brigadistas int(11) DEFAULT NULL,
        equipo_tecnico int(11) DEFAULT NULL,
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
        uid_insert int(11) NOT NULL,
        uid_update int(11) DEFAULT NULL,
        insert_date datetime NOT NULL,
        update_date datetime DEFAULT NULL,
        user_insert varchar(50) NOT NULL,
        user_update varchar(50) DEFAULT NULL,
        canceled tinyint(1) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    $db_name = 'grupoipe_regexa_ecr';
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    $wpdb->query($sql);
    $sql = "CREATE TABLE IF NOT EXISTS $db_name.ipress_red (
        ID int(255) DEFAULT NULL,
        Codigo_Red varchar(255) NOT NULL,
        Red varchar(255) DEFAULT NULL
    )";
    $wpdb->query($sql);
    $sql = "CREATE TABLE IF NOT EXISTS drt_provincia (
        id_pais int(11) NOT NULL,
        id_dpto int(11) NOT NULL,
        id_prov int(11) NOT NULL,
        nombre_prov varchar(100) NOT NULL,
        abreviatura_prov varchar(30) DEFAULT NULL,
        codigo_prov varchar(4) DEFAULT NULL,
        government_id bigint(20) DEFAULT NULL,
        PRIMARY KEY (id_dpto,id_pais,id_prov)
      )";
    $wpdb->query($sql);
    $sql = "CREATE TABLE IF NOT EXISTS ds_emed_action (
          id bigint(20) NOT NULL,
          offline bigint(20) DEFAULT NULL,
          emed_id bigint(20) NOT NULL,
          fecha date NOT NULL,
          hora time NOT NULL,
          descripcion text NOT NULL,
          uid_insert int(11) NOT NULL,
          uid_update int(11) DEFAULT NULL,
          insert_date datetime NOT NULL,
          update_date datetime DEFAULT NULL,
          user_insert varchar(50) NOT NULL,
          user_update varchar(50) DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
    )";
    $wpdb->query($sql);
    $sql="CREATE TABLE IF NOT EXISTS ds_emed_damage_ipress (
          id bigint(20) NOT NULL,
          offline bigint(20) NOT NULL,
          emed_id bigint(20) NOT NULL,
          red int(11) NOT NULL,
          microred int(11) NOT NULL,
          ipress varchar(100) NOT NULL,
          category varchar(100) NOT NULL,
          status varchar(100) NOT NULL,
          remark varchar(200) NOT NULL,
          uid_insert int(11) NOT NULL,
          uid_update int(11) DEFAULT NULL,
          insert_date datetime NOT NULL,
          update_date datetime DEFAULT NULL,
          user_insert varchar(50) NOT NULL,
          user_update varchar(50) DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
    )";
    $wpdb->query($sql);
    $sql="CREATE TABLE IF NOT EXISTS ds_emed_damage_salud (
          id bigint(20) NOT NULL,
          offline bigint(20) NOT NULL,
          emed_id bigint(20) NOT NULL,
          code varchar(10) NOT NULL,
          nombre_completo varchar(100) NOT NULL,
          edad varchar(20) DEFAULT NULL,
          diagnostico text,
          gravedad varchar(200) DEFAULT NULL,
          situacion varchar(200) DEFAULT NULL,
          observacion varchar(200) DEFAULT NULL,
          uid_insert int(11) NOT NULL,
          uid_update int(11) DEFAULT NULL,
          insert_date datetime NOT NULL,
          update_date datetime DEFAULT NULL,
          user_insert varchar(50) NOT NULL,
          user_update varchar(50) DEFAULT NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
      )";
      $wpdb->query($sql);
      $sql="CREATE TABLE IF NOT EXISTS ds_emed_file (
          id bigint(20) NOT NULL,
          offline bigint(20) NOT NULL,
          emed_id bigint(20) NOT NULL,
          src varchar(100) NULL,
          uid_insert int(11) NOT NULL,
          insert_date int(11) NOT NULL,
          user_insert varchar(50) NULL,
          canceled tinyint(1) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
      )";
      $wpdb->query($sql);
      //add_option('jal_db_version', $this->version);
}

register_activation_hook(__FILE__, 'directory_install');

require_once(__DIR__ . '/app/Boot/bootstrap.php');
