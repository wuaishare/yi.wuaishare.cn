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

## 访问稳定巡检

第二阶段开始使用只读脚本统一巡检口径：

```bash
scripts/check-access-bt4.sh
```

这个脚本会检查：

- WordPress runtime 基础信息。
- bt4 Nginx vhost 中的 `proxy_protocol`、证书路径和证书 SAN。
- frp 配置中的 `proxyProtocolVersion` 线索。
- 公共 DNS 解析结果。
- 经过 Cloudflare 的首页、工具页、REST API HTTPS 状态。
- bt4 直连反例，用来证明 proxy protocol 行为，不作为前台故障结论。

当前源站证书是 Cloudflare Origin CA，SAN 覆盖 `*.wuaishare.cn` 与 `wuaishare.cn`。这类证书只适合 Cloudflare/代理链路，不适合用浏览器或普通 curl 直接访问源站判断公网证书是否有效。

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

## 五行穿衣日更文章

部署 `yi-tools-core` 后，可以用 WP-CLI 生成或更新每日文章：

```bash
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn yi-tools publish-daily-wuxing --date=today"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn yi-tools publish-daily-wuxing --date=tomorrow --dry-run"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn yi-tools publish-daily-wuxing --date=2026-05-13"
```

命令会按日期 slug 创建或更新文章，例如：

```text
wuxing-chuanyi-2026-05-13
```

文章会自动归入 `五行穿衣` 分类，并写入 `_yi_wuxing_date` 元数据。主题单篇模板会据此在文末输出工具页导流入口。后续配置计划任务时，建议先跑 `--dry-run`，确认标题、slug、摘要正常后再写入。

如果已经有媒体库附件，可以同时绑定精选图。日更文章优先使用真实穿搭、衣物平铺、生活方式摄影或手工设计稿，不使用纯色卡占位图作为正式封面：

```bash
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn yi-tools publish-daily-wuxing --date=2026-05-13 --image-attachment-id=10"
```

本地图片、AI 生成图或公开授权素材统一先进入媒体库，再用 attachment ID 绑定：

```bash
scp tmp/generated-media/wuxing-chuanyi-2026-05-13-cover.webp bt4:/tmp/
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn media import /tmp/wuxing-chuanyi-2026-05-13-cover.webp --post_id=8 --title='2026年5月13日五行穿衣指南配图' --caption='绿青系穿衣参考。' --alt='绿色、青色、浅绿穿搭参考' --featured_image --porcelain"
```

`tmp/` 是本地生成物目录，不纳入 Git。最终图片都应进入 WordPress 媒体库并以 attachment ID 绑定，不要把本地文件路径写入正文。使用公开素材时，caption 或媒体说明中保留来源。

`yi-tools-core` 会在 WordPress 上传和 WP-CLI 媒体导入时自动优化图片：

- PNG、JPEG、WebP 会统一转为 WebP。
- 最大宽高限制为 1600x1200。
- WebP 质量为 82。

因此日更配图可以先用原始大图导入媒体库，再由插件落盘为轻量 WebP 附件和响应式缩略图。
