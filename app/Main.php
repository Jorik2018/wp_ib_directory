<?php
declare(strict_types=1);

namespace IB\directory;

use WPMVC\Bridge;
require_once __DIR__ . '/Util/Utils.php';

add_filter( 'woocommerce_prevent_admin_access', '__return_false' );

add_filter( 'woocommerce_disable_admin_bar', '__return_false' );



/**
 * Modify the JWT authentication response.
 *
 * @param array $response The response data.
 * @param WP_User $user The authenticated user object.
 * @return array The modified response data.
 */
define('WPMVC_PLUGIN_FILE', __FILE__);

class Main extends Bridge {

    public function api_covid() {
        return 2;
    }

    public function return_view() {
        return $this->mvc->view->get( 'view.key' );
    }

    public function init() {
      
        register_activation_hook(WPMVC_PLUGIN_FILE, [$this, 'install']);
        $this->add_action( 'rest_api_init','EmedController@rest_api_init');
        $this->add_action( 'init','EmedController@init');

        $this->add_action( 'rest_api_init','VeaMaternoController@rest_api_init');
        $this->add_action( 'init','VeaMaternoController@init');
        $this->add_action( 'rest_api_init','AdminController@rest_api_init');
        $this->add_action( 'init','AdminController@init');
        $this->add_action( 'rest_api_init','CancerController@rest_api_init');
        $this->add_action( 'init','CancerController@init');
        $this->add_action( 'rest_api_init','DirectoryController@rest_api_init');
        $this->add_action( 'init','DirectoryController@init');
        $this->add_action( 'rest_api_init','SivicoController@rest_api_init');
        $this->add_action( 'init','SivicoController@init');
        $this->add_action( 'rest_api_init','PregnantController@rest_api_init');
        $this->add_action( 'init','PregnantController@init');
        $this->add_action( 'rest_api_init','GeoController@rest_api_init');
        $this->add_action( 'init','GeoController@init');
        $this->add_action( 'rest_api_init','PollAntaminaController@rest_api_init');
        $this->add_action( 'init','PollAntaminaController@init');
        $this->add_action( 'rest_api_init','PollController@rest_api_init');
        $this->add_action( 'init','PollController@init');
        $this->add_action( 'rest_api_init','UserController@rest_api_init');
        $this->add_action( 'init','UserController@init');
        $this->add_action( 'personal_options_update', 'UserController@edit_user_profile_update' );
        $this->add_action( 'edit_user_profile_update', 'UserController@edit_user_profile_update' );
        $this->add_action( 'show_user_profile', 'UserController@edit_user_profile' );
        $this->add_action( 'edit_user_profile', 'UserController@edit_user_profile' );

        $this->add_filter( 'jwt_auth_valid_credential_response', 'UserController@modify_jwt_auth_response', 10, 2);
    }

    public function on_admin() {
        $this->add_action('admin_menu', 'AdminController@init');
    }


}