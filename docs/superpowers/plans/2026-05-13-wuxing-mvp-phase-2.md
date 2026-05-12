# 五行穿衣 MVP 第二阶段作战计划

## 目标

围绕 `/wuxing-chuanyi/` 先补齐四条闭环：

- 访问稳定：统一公网、Cloudflare、bt4 proxy_protocol 的验收口径，避免把直连源站误判为前台故障。
- 移动端体验：手机首屏优先展示日期、大吉色和查询动作，避免按钮纵向拖得过长。
- SEO：工具页输出 title、description、canonical、Open Graph、JSON-LD；日期参数页 noindex/follow，canonical 回工具页。
- 日更文章：用 WP-CLI 生成或更新每日五行穿衣文章，文章与工具页互相导流。

## 任务拆解

1. 访问稳定
   - 新增 `scripts/check-access-bt4.sh` 只读巡检脚本。
   - 巡检 WordPress runtime、Nginx vhost、源站证书、frp proxy protocol、公共 DNS、首页/工具页/REST HTTPS。
   - 在运维文档中记录 Cloudflare Origin CA 与直连源站反例。

2. 移动端体验
   - 保留桌面双列 hero。
   - 手机端将日期表单、前一天/后一天、回到今天按紧凑网格排列。
   - 手机端事实卡改为两列，小屏再退回单列。
   - 非法日期前台显示轻量提示，不再静默回退。

3. SEO
   - 新增 `YiToolsCore\Seo\WuxingSeo`。
   - 工具页动态 title 与 meta description 读取当天颜色结果。
   - 默认工具页 canonical 指向 `/wuxing-chuanyi/`。
   - 非今天的 `?date=` 查询页设置 `noindex,follow`，降低重复内容风险。

4. 日更文章闭环
   - 新增 WP-CLI 命令 `wp yi-tools publish-daily-wuxing`。
   - 支持 `--date=today|tomorrow|yesterday|YYYY-MM-DD` 与 `--dry-run`。
   - 按日期 slug 幂等创建或更新文章。
   - 自动设置 `五行穿衣` 分类、关键词标签和 `_yi_wuxing_date` 元数据。
   - 主题首页显示最新日更入口，单篇五行穿衣文章文末输出工具页 CTA。

## 验收

- 本地 `php -l` 覆盖插件和主题 PHP 文件。
- 本地五行核心烟测通过。
- 线上部署前执行文件与数据库备份。
- 线上 WP-CLI dry-run 与真实日更命令可执行。
- 线上 `/wuxing-chuanyi/`、非法日期、REST、日更文章返回正常。
- Codex 内置浏览器验证桌面和移动端页面无明显重叠。
