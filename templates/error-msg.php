<?php

// ABSPATH prevent public user to directly access your .php files through URL.
defined('ABSPATH') or die('No script kiddies please!');

$message = $wp_query->get('message');
$alert_message = '';
if ($message == 'casdoor_login_only') {
    $alert_message = '您的通行证账户在此站点没有绑定的账户，且此站点关闭了新用户登录，请尝试使用其它账户登录';
} elseif ($message == 'casdoor_sso_failed') {
    $alert_message = '通行证单点登录失败！您的用户与现有数据不匹配或冲突，单点登录无法完成。';
} elseif ($message == 'casdoor_id_not_allowed') {
    $alert_message = '出于安全原因，您的通行证账户无法完成单点登录';
}

if (!empty($alert_message)) : ?>
    <div class="error">
        <p class="alertbar"><?= $alert_message . ' <a href="' . site_url('?auth=casdoor') . '">请重试</a>'?></p>
    </div>
<?php endif; ?>
