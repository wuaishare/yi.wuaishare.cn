# 运维说明

## 运行时真实来源

bt4 上的远端 WordPress 目录是当前线上运行时：

```text
/www/wwwroot/yi.wuaishare.cn
```

当前 Codex 工作区是规划、源码和文档工作区：

```text
/Users/jingchen/Github/yi.wuaishare.cn
```

后续开发默认在 Codex 工作区进行，再同步到 bt4。不要直接把远端运行目录当作主要开发工作区。

## 当前站点信息

已验证：

```text
站点域名：yi.wuaishare.cn
站点名称：吾爱易学
WordPress：6.9.4
数据库：sql_yi_wuaishare
管理员邮箱：wuaishare@gmail.com
远端根目录：/www/wwwroot/yi.wuaishare.cn
```

管理员凭据：

```text
/root/codex-wp-create-20260512/yi.wuaishare.cn.credentials.txt
```

该文件是 bt4 上的 root-only 凭据文件，不得复制到仓库、提交信息、日志或公开文档。

## 只读验证命令

站点基础信息：

```bash
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn core version"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn option get blogname"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn option get home"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn option get siteurl"
```

主题和插件：

```bash
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn theme list"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn plugin list"
```

用户列表只允许做必要的只读核验，不输出密码：

```bash
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn user list --fields=ID,user_login,user_email,roles"
```

## PHP 与 WP-CLI 注意事项

当前远端 Nginx vhost 引用：

```text
enable-php-84.conf
```

当前 WP-CLI `--info` 显示 PHP binary 为：

```text
/www/server/php/83/bin/php
```

这表示 Web PHP-FPM 与 WP-CLI CLI PHP 可能不是同一个版本。调试 PHP 版本相关问题时，必须区分：

- Web 请求使用的 PHP-FPM。
- `wp` 命令使用的 PHP CLI。

## Nginx 与前台入口

当前 `yi.wuaishare.cn` vhost 使用：

```nginx
listen 80 proxy_protocol;
listen 443 ssl http2 proxy_protocol;
real_ip_header proxy_protocol;
set_real_ip_from 127.0.0.1;
```

因此不要用以下方式误判前台：

```bash
curl --resolve yi.wuaishare.cn:80:127.0.0.1 http://yi.wuaishare.cn/
curl -H 'Host: yi.wuaishare.cn' http://127.0.0.1/
```

这类直连可能因为缺少 proxy protocol 而返回空响应或连接重置。

正确验收优先使用：

- Codex 内置浏览器打开真实前台 URL。
- 经过 Cloudflare / 正确代理链的 `https://yi.wuaishare.cn/`。
- 明确配置好的本地 ServBay 镜像入口。
- WP-CLI 只读命令辅助确认 WordPress 状态。

`http://192.168.31.24:13305/wp/local` 当前会进入宝塔面板安全入口校验页，不是 WordPress 前台验收地址。

Nginx 配置测试：

```bash
ssh bt4 "/www/server/nginx/sbin/nginx -t -c /www/server/nginx/conf/nginx.conf"
```

只有在确认配置正确、已备份、且需要应用变更时，才 reload：

```bash
ssh bt4 "/www/server/nginx/sbin/nginx -s reload -c /www/server/nginx/conf/nginx.conf"
```

## 备份要求

任何写操作前，至少做文件备份；涉及数据库、插件激活、主题切换、批量内容、search-replace 时必须做数据库备份。

建议备份目录：

```text
/root/backups
```

临时手动备份示例：

```bash
ssh bt4 "mkdir -p /root/backups && tar -czf /root/backups/yi.wuaishare.cn-files-$(date +%Y-%m-%d-%H%M%S).tgz -C /www/wwwroot yi.wuaishare.cn"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn db export /root/backups/yi.wuaishare.cn-db-$(date +%Y-%m-%d-%H%M%S).sql"
```

## 维护路径建议

首期建议只把自研路径纳入源码维护：

```text
wp-content/plugins/yi-tools-core/
wp-content/themes/yi-theme/
```

不要纳入：

- WordPress core。
- `wp-config.php`。
- uploads。
- cache。
- backup。
- 第三方官方主题/插件。
- 数据库 dump。
- 凭据文件。

后续可以参考 `/Users/jingchen/Github/ai.wuaishare.cn` 的 `config/managed-paths.txt`、`scripts/setup-local-runtime.sh`、`scripts/deploy-bt4.sh` 模式，为本项目建立 manifest-driven 同步脚本。

## 上线前验收清单

首期五行穿衣上线前至少检查：

- 真实前台 URL 可访问。
- `/wuxing-chuanyi/` 默认显示当天结果。
- 日期选择、前一天、后一天、回到今天可用。
- 非法日期有清楚错误状态。
- 公历、农历、星期、干支、日五行显示正确。
- 大吉色、次吉色、平平色、慎用色、不宜色按同一规则生成。
- 页面移动端不重叠。
- SEO title、description、canonical 符合策略。
- 免责声明可见但不喧宾夺主。
- 日更文章能导流到工具页。
