<?php

// ABSPATH prevent public user to directly access your .php files through URL.
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class casdoor_admin
 */
class casdoor_admin
{
    const OPTIONS_NAME = 'casdoor_options';

    public static function init()
    {
        // add_action adds a callback function to an action hook.
        // admin_init fires as an admin screen or script is being initialized.
        add_action('admin_init', [new self, 'admin_init']);
        // admin_menu fires before the administration menu loads in the admin.
        // This action is used to add extra submenus and menu options to the admin panel’s menu structure. It runs after the basic admin panel menu structure is in place.
        add_action('admin_menu', [new self, 'add_page']);
    }

    /**
     * [admin_init description]
     *
     * @return [type] [description]
     */
    public function admin_init()
    {
        // A callback function that sanitizes the option's value
        register_setting('casdoor_options', self::OPTIONS_NAME, [$this, 'validate']);
    }

    /**
     * Add casdoor submenu page to the settings main menu
     */
    public function add_page()
    {
        add_options_page('Casdoor SSO', 'Casdoor SSO', 'manage_options', 'casdoor_settings', [$this, 'options_do_page']);
    }

    /**
     * [options_do_page description]
     *
     * @return [type] [description]
     */
    public function options_do_page()
    {
        ?>
        <div class="wrap">
            <h2>Casdoor 插件设置</h2>
            <p>该插件需要与 <a href="https://casdoor.org/">casdoor</a> 配合一起使用</p>
            <p>
                激活后，该插件将把所有登录请求重定向到您的 casdoor 页面。
                <br/>
                <strong>NOTE:</strong> 如果您想在主题中的任何地方添加自定义链接，
                只需在用户未登录时链接到 <strong><?= site_url('?auth=casdoor'); ?></strong> 即可。
            </p>
            <p><strong>汉化 By 晓空 | <a href="https://blog.moeworld.tech/">https://blog.moeworld.tech/ </a></strong> </p>
            <div id="accordion">
                <h4>Step 1: Setup</h4>
                <div>
                    <strong>配置 Casdoor</strong>
                    <ol>
                        <li>安装并运行 casdoor (<a
                                    href="https://github.com/casbin/casdoor" target="_blank">GitHub</a>)
                        </li>
                        <li>创建新应用程序，并在回调 URL 中添加以下URI:
                            <strong class="code"><?= site_url('?auth=casdoor'); ?></strong></li>
                        <li>复制 应用ID 和 应用秘钥 备用，我们将会在第二步中用到</li>
                    </ol>
                </div>
                <h4 id="sso-configuration">Step 2: Configuration</h4>
                <div>
                    <form method="post" action="options.php">
                        <?php settings_fields('casdoor_options'); ?>
                        <table class="form-table">
                        <tr valign="top">
                                <th scope="row">Activate Casdoor</th>
                                <td>
                                    <input type="checkbox"
                                        name="<?= self::OPTIONS_NAME ?>[active]"
                                        value="1" <?= casdoor_get_option('active') == 1 ? 'checked="checked"' : ''; ?> />
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">Client ID</th>
                                <td>
                                    <input type="text" name="<?= self::OPTIONS_NAME ?>[client_id]" min="10"
                                           value="<?= casdoor_get_option('client_id') ?>"/>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">Client Secret</th>
                                <td>
                                    <input type="text" name="<?= self::OPTIONS_NAME ?>[client_secret]" min="10"
                                           value="<?= casdoor_get_option('client_secret'); ?>"/>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">Backend URL</th>
                                <td>
                                    <input type="text" name="<?= self::OPTIONS_NAME ?>[backend]" min="10"
                                           value="<?= casdoor_get_option('backend'); ?>"/>
                                    <p class="description">Example: https://your-casdoor-backend.com</p>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">Organization</th>
                                <td>
                                    <input type="text" name="<?= self::OPTIONS_NAME ?>[organization]" 
                                           value="<?= casdoor_get_option('organization'); ?>"/>
                                    <p class="description">Example/Default: built-in</p>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">登录后自动重定向到Wordpress仪表盘</th>
                                <td>
                                    <input type="checkbox"
                                           name="<?= self::OPTIONS_NAME ?>[redirect_to_dashboard]"
                                           value="1" <?= casdoor_get_option('redirect_to_dashboard') == 1 ? 'checked="checked"' : ''; ?> />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">关闭新用户登录（仅允许已有账户通过单点登录）</th>
                                <td>
                                    <input type="checkbox"
                                           name="<?= self::OPTIONS_NAME ?>[login_only]"
                                           value="1" <?= casdoor_get_option('login_only') == 1 ? 'checked="checked"' : ''; ?> />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">如果未登录则自动执行单点登录</th>
                                <td>
                                    <input type="checkbox"
                                           name="<?= self::OPTIONS_NAME ?>[auto_sso]"
                                           value="1" <?= casdoor_get_option('auto_sso') == 1 ? 'checked="checked"' : ''; ?> />
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
                        </p>
                </div>

                </form>
            </div>
        </div>
        <div style="clear:both;"></div>
        </div>
        <!-- 额外的css by 晓空 -->
        <style>
            /* 修复 感谢使用 WordPress 进行创作 错位的问题 */
            #wpfooter {
                position: static !important;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 10px 20px;
                color: #50575e;
            }
        </style>
        <!-- 额外的css结束 by 晓空 -->
        <?php
    }

    /**
     * Settings Validation
     *
     * @param array $input option array
     *
     * @return array
     */
    public function validate(array $input): array
    {
        $input['redirect_to_dashboard'] = isset($input['redirect_to_dashboard']) ? $input['redirect_to_dashboard'] : 0;
        $input['login_only']            = isset($input['login_only']) ? $input['login_only'] : 0;
        $input['organization']          = isset($input['organization']) ? $input['organization'] : 'built-in';

        return $input;
    }
}

casdoor_admin::init();
