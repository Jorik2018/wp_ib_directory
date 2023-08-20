<?php
declare(strict_types=1);

namespace IB\cv;

use WPMVC\Bridge;

add_filter( 'woocommerce_prevent_admin_access', '__return_false' );

add_filter( 'woocommerce_disable_admin_bar', '__return_false' );

class Main extends Bridge
{
    public function api_covid(){
        return 2;
    }

    public function return_view()
    {
        return $this->mvc->view->get( 'view.key' );
    }

    public function init()
    {
        $this->add_action( 'rest_api_init','RestController@init' );
        $this->add_action( 'rest_api_init','CancerRestController@rest_api_init');
        $this->add_action( 'init','CancerRestController@init');
        $this->add_action( 'rest_api_init','PregnantRestController@rest_api_init');
        $this->add_action( 'init','PregnantRestController@init');
        $this->add_action( 'rest_api_init','DocumentRestController@init');
        $this->add_action( 'plugins_loaded', 'AdminController@activate' );
    }

    public function on_admin()
    {
        $this->add_action('admin_menu', 'AdminController@init');
    }
}