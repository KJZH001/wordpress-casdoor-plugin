# wordpress-casdoor-plugin
这个插件是为 [casdoor](https://github.com/casbin/casdoor) 设计和开发的。激活插件后，它将用 casdoor 支持的登录表单替换标准的 WordPress 登录表单。

该插件需要与 casdoor 配合一起使用

激活后，该插件将把所有登录请求重定向到您的 casdoor 页面。<br>
NOTE: 如果您想在主题中的任何地方添加自定义链接， 只需在用户未登录时链接到 https://blog.moeworld.tech/?auth=casdoor 即可。

汉化 By 晓空 | [https://blog.moeworld.tech/](https://blog.moeworld.tech/)

Github发布页 [https://github.com/KJZH001/wordpress-casdoor-plugin](https://github.com/KJZH001/wordpress-casdoor-plugin)

* **免责声明：这是一个由晓空修改的Casdoor插件，请自行考虑安全性以决定是否使用， 我们不会为此插件承担任何责任**

## 新特性
- 修复了原版插件向所有界面无差别注入会报错的js代码的问题
- 汉化了绝大多数的地方
- 延长了cookie有效期（从48小时延长到了14天）
- 修复了后台的 `感谢使用 WordPress 进行创作。X.X.X 版本` 的页脚错位

## 安装
该插件尚未发布到 WordPress 插件商店，因此需要手动下载并将其移动到 `wp-content/plugins` 目录。

## 开始使用
首先，作为管理员激活该插件，这将在设置页面添加一个关于 casdoor 的新页面。

因为这个插件是 casdoor 的客户端，所以需要运行 casdoor 程序，创建一个应用并将 `http://your-wordpress-domain/?auth=casdoor` 添加到 casdoor 应用的 `Redirect URLs` 列表中。

然后点击新部分并设置你的 casdoor 插件，主要涉及以下设置：

- 启用 Casdoor：如果选中该单选框，默认登录表单将被替换。
- 客户端 ID：你的 casdoor 应用的客户端 ID。
- 客户端密钥：你的 casdoor 应用的客户端密钥。
- 后端 URL：运行 casdoor 程序的计算机地址：后端端口。
- 组织：这是 `casdoor-php-sdk` 的设置，目前可以忽略。
- 登录后重定向到仪表板：如果选中该单选框，登录后用户将被重定向到仪表板页面。
- 限制仅登录：如果选中该单选框，casdoor 将不会将用户信息插入 WordPress 的 wp_users 表。换句话说，不存在于 WordPress 的 casdoor 用户将无法登录。
- 为未登录用户自动 SSO：如果选中该单选框，即使用户访问的页面不需要登录，用户也会被重定向到登录页面。

成功设置该插件后，所有发送到 login.php 的登录请求将被重定向到 casdoor 应用。

## 工作流程
在 casdoor 验证了你输入的用户名/邮箱和密码后，可能会有两种情况。casdoor 会尝试查找对应的用户，如果用户在 WordPress 中存在，casdoor 将以该用户身份登录，否则它将用户信息插入 WordPress 的 wp_users 表，然后以该用户身份登录。

## 待办事项
- 集成 `php-casdoor-sdk`
- 将该插件发布到 WordPress
- 显示警告和错误消息
