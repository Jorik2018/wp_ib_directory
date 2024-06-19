<?php

use WPMVC\Config;

/**
 * This file will load configuration file and init Main class.
 *
 * @author WordPress MVC <https://www.wordpress-mvc.com/>
 * @license MIT
 * @package wpmvc
 * @version 1.0.4
 */

$GLOBALS['PREFIX_LENGTHS_PSR4'] = array('I' => array ('IB\\directory\\' => 13));
$GLOBALS['PREFIX_DIRS_PSR4'] = array('IB\\directory\\' => array (0 => __DIR__ . '/../..' . '/app'));
$GLOBALS['CLASS_MAP'] = array(
    'IB\\directory\\Controllers\\AdminController' => __DIR__ . '/../..' . '/app/Controllers/AdminController.php',
    'IB\\directory\\Controllers\\CancerController' => __DIR__ . '/../..' . '/app/Controllers/CancerController.php',
    'IB\\directory\\Controllers\\DirectoryController' => __DIR__ . '/../..' . '/app/Controllers/DirectoryController.php',
    'IB\\directory\\Main' => __DIR__ . '/../..' . '/app/Main.php'
);

$GLOBALS['PLUGIN']='DIRECTORY';
//require_once
require( __DIR__ . '/../../autoload.php' );

$config = include( plugin_dir_path( __FILE__ ) . '../Config/app.php' );

$plugin_namespace = $config['namespace'];

$plugin_name = strtolower( explode( '\\' , $plugin_namespace )[0] );

$plugin_class = $plugin_namespace . '\Main';
//die('-----'.get_parent_class( $plugin_class ));
$plugin_reflection = new ReflectionClass( /*get_parent_class*/( $plugin_class ) );

// Global class init
$$plugin_name = new $plugin_class( new Config( $config ) );

// Unset
unset($plugin_reflection);
unset($plugin_namespace);
unset($plugin_name);
unset($plugin_class);
unset($config);