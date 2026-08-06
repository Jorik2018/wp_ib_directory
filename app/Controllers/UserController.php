<?php

namespace IB\directory\Controllers;

use WPMVC\MVC\Controller;
use function IB\directory\Util\remove;
use SimpleJWTLogin\Services\AuthenticateService;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;

class UserController extends Controller
{

    const API_USER = 'api/user';

    public function init()
    {
        add_role(
            'supervisor',
            'Supervisor',
            array(
                'supervise'         => true
            )
        );
    }

    public function rest_api_init()
    {
        register_rest_route("api", 'user', array(
            'methods' => 'GET',
            'callback' => array($this, 'get')
        ));
        register_rest_route(self::API_USER, 'me', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_user_profile_get')
        ));
        register_rest_route(self::API_USER, 'profile', array(
            'methods' => 'PUT',
            'callback' => array($this, 'profile_put')
        ));
        register_rest_route(self::API_USER, 'profile', array(
            'methods' => 'POST',
            'callback' => array($this, 'profile_put')
        ));
        register_rest_route(self::API_USER, '(?P<id>\d+)/profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_user_profile_get')
        ));
        register_rest_route('api/oauth', 'token', [
            'methods'  => 'POST',
            'callback' => [$this, 'api_oauth_token_post'],
        ]);
        register_rest_route('api/oauth', '/(?P<provider>[a-zA-Z0-9_-]+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_oauth_authorize_get'],
        ]);
    }
    public function api_oauth_authorize_get(\WP_REST_Request $request)
    {
        $provider = $request->get_param('provider');

          if (!class_exists(\SimpleJWTLogin\Services\AuthenticateService::class)) {
        return [
            'error' => 'Simple JWT Login no está cargado'
        ];
    }

        switch ($provider) {

            case 'miniorange':

                $authorizeUrl =
                    'https://dbasure.com/wp-json/moserver/authorize'
                    . '?client_id=' . urlencode(get_option('oauth_client_id'))
                    . '&response_type=code'
                    . '&scope=' . urlencode('openid profile email')
                    . '&redirect_uri=' . urlencode('https://dbasure.com/erp')
                    . '&state=' . urlencode(wp_create_nonce($provider));

                break;

            default:

                return new WP_Error(
                    'invalid_provider',
                    'Provider not supported',
                    ['status' => 400]
                );
        }

        return [
            'url' => $authorizeUrl
        ];
    }
    public function api_oauth_token_post($request)
    {
        $provider = $request->get_param('provider');
        $code = $request->get_param('code');

        switch ($provider) {

            case 'miniorange':

                $tokenEndpoint =
                    home_url('/wp-json/moserver/token');

                $clientId = 
                    get_option('oauth_client_id');

                $clientSecret =
                    get_option('oauth_client_secret');

                break;

            default:

                return new \WP_Error(
                    'provider',
                    'Unsupported provider',
                    ['status'=>400]
                );
        }

        $response = wp_remote_post(
            $tokenEndpoint,
            [
                'headers'=>[
                    'Content-Type'=>'application/x-www-form-urlencoded'
                ],
                'body'=>[
                    'grant_type'=>'authorization_code',
                    'client_id'=>$clientId,
                    'client_secret'=>$clientSecret,
                    'code'=>$code,
                    'redirect_uri'=>'https://dbasure.com/erp'
                ]
            ]
        );

        if(is_wp_error($response)){
            return $response;
        }

        $oauth = json_decode(
            wp_remote_retrieve_body($response),
            true
        );

        $user = $this->get_user_from_oauth(
            $provider,
            $oauth
        );
        // TODO:
        // obtener usuario
        // generar Simple JWT

        return $user;
    }

    private function get_user_from_oauth($provider, $oauth)
    {
        switch($provider){
            case 'miniorange':

                $decoded = JWT::extractDataFromJwt(
                    $oauth['id_token']
                );

                $email = $decoded['payload']['email'];

                $user = get_user_by(
                    'email',
                    $email
                );

                if (!$user) {
                    return new \WP_Error(
                        'user_not_found',
                        'User not found',
                        ['status'=>404]
                    );
                }

                return $user;
        }
    }

    function get()
    {
        $u = (array)wp_get_current_user();
        $u['id'] = remove($u, 'ID');
        return $u;
            /*return array('user'=>$u,
        'first_name'=>get_user_meta( $u->ID, 'first_name', true ),
        'last_name'=>get_user_meta( $u->ID, 'last_name', true ),
        'meta'=>get_user_meta( $u->ID ))*/;
    }

    function api_user_profile_get()
    {
        $u = (array)wp_get_current_user();
        $uid = $u['ID'];
        $u['people'] = get_userdata($uid);
        $u['names'] = get_user_meta($uid, 'names', true);
        $u['firstSurname'] = get_user_meta($uid, 'first_surname', true);
        $u['lastSurname'] = get_user_meta($uid, 'last_surname', true);
        $u['sex'] = get_user_meta($uid, 'sex', true);
        $u['id'] = $u['ID'];
        $data = $u['data'];
        if ($data) {
            $u['mail'] = $data->user_email;
        }
        return $u;
    }

    function profile_put($request)
    {
        $o = get_param($request);
        $u = (array)wp_get_current_user();
        $uid = $u['ID'];
        update_user_meta($uid, 'names', $o['names']);
        update_user_meta($uid, 'first_surname', $o['firstSurname']);
        update_user_meta($uid, 'last_surname', $o['lastSurname']);
        update_user_meta($uid, 'sex', $o['sex']);
        $args = array(
            'ID'         => $uid,
            'user_email' => esc_attr($o['mail'])
        );
        wp_update_user($args);
        return true;
    }

    function api_supervisor_func()
    {
        $results = $GLOBALS['wpdb']->get_results("SELECT  um.user_id,u.display_name,meta_value as supervisor
        FROM `wpsy_usermeta` um
        INNER JOIN wpsy_users u ON u.ID=um.user_id
        WHERE `meta_key`='supervisor'", OBJECT);
        $current_user = wp_get_current_user();
        return  array($results, $current_user);
    }

    function edit_user_profile($user)
    {
        $results = $GLOBALS['wpdb']->get_results("SELECT  um.user_id,u.display_name
        FROM `wpsy_usermeta` um
        INNER JOIN wpsy_users u ON u.ID=um.user_id
        WHERE `meta_key`='wpsy_capabilities' and `meta_value` like '%supervisor%'", OBJECT);

        $user_id = get_the_author_meta('supervisor', $user->ID);
?>
        <table class="form-table">
            <tr>
                <th><label for="postalcode"><?php _e("Supervisor"); ?></label></th>
                <td>
                    <select name="supervisor">
                        <option value="" <?= !$user_id ? 'selected="selected"' : '' ?>>--Select Option--</option>
                        <?
                        foreach ($results as $r) {
                        ?>

                            <option <?= $r->user_id == $user_id ? 'selected="selected"' : '' ?> value="<?= $r->user_id ?>"><?= $r->display_name ?></option>

                        <? }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
<?php
    }

    function edit_user_profile_update($user_id)
    {
        update_user_meta($user_id, 'supervisor', $_POST['supervisor']);
    }

    function modify_jwt_auth_response($response, $user)
    {
        // Modify the response data as needed
        //$u=$user;
        //$u['id']=remove($u,'ID');
        $response['perms'] = $user->allcaps;
        //$response['user_nicename'] = $user->data['user_nicename'];
        // Example: Add user meta data to the response
        //$user_meta = get_user_meta($user->ID);
        $token = $response['data']['token'];
        if(!$token)$token = $response['data']['jwt'];
        $nicename = $response['data']['nicename'];
        unset($response['data']);
        $response['token'] = $token;
        $response['user_nicename'] = $nicename?$nicename:$user->data->user_nicename;
        return $response;
    }
}
