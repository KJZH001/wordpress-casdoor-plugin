<?php

// ABSPATH prevent public user to directly access your .php files through URL.
defined('ABSPATH') or die('No script kiddies please!');

function defaults()
{
    return [
        'client_id'             => '',
        'client_secret'         => '',
        'backend'               => '',
        'redirect_to_dashboard' => 0,
        'login_only'            => 0,
    ];
}
function casdoor_get_options_internal()
{
    $options = get_option(casdoor_admin::OPTIONS_NAME, []);
    if (!is_array($options)) {
        $options = defaults();
    }
    $options = array_merge(defaults(), $options);
    return $options;
}

/**
 * get option value
 *
 * @param string $option_name
 *
 * @return void|string
 */
function casdoor_get_option(string $option_name)
{
    $options = casdoor_get_options_internal();
    if (!empty($v = $options[$option_name])) {
        return $v;
    }
}
function casdoor_set_options(string $key, $value)
{
    $options = casdoor_get_options_internal();
    $options[$key] = $value;
    update_option(casdoor_admin::OPTIONS_NAME, $options);
}

// OIDC 空梦统一通行证
// 用于生成随机的 state。默认 16 字节随机数，即 128 bit。
function generateRandomState($length = 16)
{
    return bin2hex(random_bytes($length));
}

/**
 * Validate a post-login redirect.
 *
 * The dashboard option keeps its original priority. Otherwise only URLs
 * accepted by WordPress are allowed, preventing an open redirect.
 */
function casdoor_validate_user_redirect(string $redirect = ''): string
{
    $fallback = casdoor_get_user_redirect_url();

    if (absint(casdoor_get_option('redirect_to_dashboard')) === 1) {
        return $fallback;
    }

    if ($redirect === '') {
        return $fallback;
    }

    return wp_validate_redirect(esc_url_raw($redirect), $fallback);
}

/**
 * Build a URL for the current WordPress request.
 *
 * The result is validated before it is ever used as a redirect target.
 */
function casdoor_get_current_request_url(): string
{
    if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
        return home_url();
    }

    $scheme = is_ssl() ? 'https' : 'http';
    $host   = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
    $uri    = wp_unslash($_SERVER['REQUEST_URI']);

    return esc_url_raw($scheme . '://' . $host . $uri);
}

/**
 * Store OAuth state server-side and bind it to the browser that started login.
 *
 * A transient stores the redirect target. A short-lived HttpOnly, host-only
 * cookie containing an HMAC binds that state to this browser. The redirect URL
 * itself is therefore never overloaded into the OAuth state parameter.
 */
function casdoor_store_oauth_state(string $state, string $redirect): void
{
    $ttl            = 10 * MINUTE_IN_SECONDS;
    $transient_key  = 'casdoor_oauth_state_' . $state;
    $cookie_name    = 'casdoor_oauth_state_' . $state;
    $cookie_value   = hash_hmac('sha256', $state, wp_salt('auth'));
    $cookie_path    = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';

    set_transient($transient_key, [
        'redirect' => casdoor_validate_user_redirect($redirect),
    ], $ttl);

    setcookie($cookie_name, $cookie_value, [
        'expires'  => time() + $ttl,
        'path'     => $cookie_path,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Keep the current PHP request consistent with the Set-Cookie header.
    $_COOKIE[$cookie_name] = $cookie_value;
}

/**
 * Verify and consume OAuth state exactly once.
 *
 * @return string|WP_Error Redirect URL on success, WP_Error on failure.
 */
function casdoor_consume_oauth_state(string $state)
{
    if (!preg_match('/^[a-f0-9]{32}$/D', $state)) {
        return new WP_Error('casdoor_invalid_state', 'Invalid OAuth state.');
    }

    $transient_key = 'casdoor_oauth_state_' . $state;
    $cookie_name   = 'casdoor_oauth_state_' . $state;
    $expected      = hash_hmac('sha256', $state, wp_salt('auth'));
    $received      = isset($_COOKIE[$cookie_name])
        ? sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]))
        : '';

    if ($received === '' || !hash_equals($expected, $received)) {
        return new WP_Error('casdoor_state_mismatch', 'OAuth state validation failed. Please start the login again.');
    }

    $state_data = get_transient($transient_key);
    if (!is_array($state_data) || empty($state_data['redirect'])) {
        return new WP_Error('casdoor_state_expired', 'OAuth state has expired or was already used. Please start the login again.');
    }

    // Consume the state before exchanging the code, so the callback cannot be replayed.
    delete_transient($transient_key);

    $cookie_path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    setcookie($cookie_name, '', [
        'expires'  => time() - HOUR_IN_SECONDS,
        'path'     => $cookie_path,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[$cookie_name]);

    return casdoor_validate_user_redirect((string) $state_data['redirect']);
}

/**
 * Get the login url of casdoor
 *
 * @param string $redirect
 *
 * @return string
 */
function get_casdoor_login_url(string $redirect = ''): string
{
    $state = generateRandomState();
    casdoor_store_oauth_state($state, $redirect);

    $params = [
        'oauth'         => 'authorize',
        'response_type' => 'code',
        'client_id'     => casdoor_get_option('client_id'),
        // client_secret MUST NOT be sent through the browser-facing authorize URL.
        'redirect_uri'  => site_url('?auth=casdoor'),
        'state'         => $state,
    ];
    $params = http_build_query($params);
    return casdoor_get_option('backend') . '/login/oauth/authorize?' . $params;
}
/**
 * Add login button for casdoor on the login form.
 *
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/login_form
 */
function casdoor_login_form_button()
{
    ?>
    <a style="color:#FFF; width:100%; text-align:center; margin-bottom:1em;" class="button button-primary button-large"
       href="<?php echo site_url('?auth=casdoor'); ?>">Casdoor Single Sign On</a>
    <div style="clear:both;"></div>
    <?php
}
// Fires following the ‘Password’ field in the login form.
// It can be used to customize the built-in WordPress login form. Use in conjunction with ‘login_head‘ (for validation).
// add_action('login_form', 'casdoor_login_form_button');
/**
 * Login Button Shortcode
 *
 * @param  [type] $atts [description]
 *
 * @return [type]       [description]
 */
function casdoor_login_button_shortcode($atts)
{
    $a = shortcode_atts([
        'type'   => 'primary',
        'title'  => 'Login using Casdoor',
        'class'  => 'sso-button',
        'target' => '_blank',
        'text'   => 'Casdoor Single Sign On'
    ], $atts);
    $login_url = add_query_arg(
        'redirect_to',
        casdoor_validate_user_redirect(casdoor_get_current_request_url()),
        site_url('?auth=casdoor')
    );

    return '<a class="' . esc_attr($a['class']) . '" href="' . esc_url($login_url) . '" title="' . esc_attr($a['title']) . '" target="' . esc_attr($a['target']) . '">' . esc_html($a['text']) . '</a>';
}
add_shortcode('sso_button', 'casdoor_login_button_shortcode');
/**
 * Get user login redirect.
 * Just in case the user wants to redirect the user to a new url.
 *
 * @return string
 */
function casdoor_get_user_redirect_url(): string
{
    $options           = get_option('casdoor_options');
    // Retrieves the URL to the user’s dashboard.
    $user_redirect_set = $options['redirect_to_dashboard'] == '1' ? get_dashboard_url() : site_url();
    $user_redirect     = apply_filters('casdoor_user_redirect_url', $user_redirect_set);

    return $user_redirect;
}
// 用于获取用户的ip地址信息
// 该函数来自于 kratos-pjax 的 inc/core.php 的 function get_client_ip(){
function casdoor_get_client_ip(){
    if(getenv("HTTP_CLIENT_IP")&&strcasecmp(getenv("HTTP_CLIENT_IP"),"unknown")) $ip = getenv("HTTP_CLIENT_IP");
    elseif(getenv("HTTP_X_FORWARDED_FOR")&&strcasecmp(getenv("HTTP_X_FORWARDED_FOR"),"unknown")) $ip = getenv("HTTP_X_FORWARDED_FOR");
    elseif(getenv("REMOTE_ADDR")&&strcasecmp(getenv("REMOTE_ADDR"),"unknown")) $ip = getenv("REMOTE_ADDR");
    elseif(isset($_SERVER['REMOTE_ADDR'])&&$_SERVER['REMOTE_ADDR']&&strcasecmp($_SERVER['REMOTE_ADDR'],"unknown")) $ip = $_SERVER['REMOTE_ADDR'];
    else $ip = "unknown";
    return ($ip);
}
