# WordPress 架构决策

日期：2026-05-12

## 决策

`yi.wuaishare.cn` 首期采用 WordPress-first 架构。

具体形态：

```text
WordPress CMS + 自研工具插件 + 自研展示主题 + 后期可拆出的算法服务
```

不采用首期纯静态站、纯 Next.js 独立应用或一开始 Headless WordPress 的路线。

## 背景

项目首期要做五行穿衣查询工具，同时承接“今日五行穿衣”“明日五行穿衣”“每日穿衣颜色”等长尾 SEO。后续还要融合 `/Users/jingchen/Github/跟着 AI 学易经与术数` 的学习路线，并扩展到黄历、八字、周易、六爻、梅花易数、紫微斗数、风水和姓名学。

这些需求同时需要：

- 内容发布和后台管理。
- 分类、标签、文章、专题、媒体库。
- SEO 标题、摘要、内链和日更内容。
- 可交互工具页。
- 后期可扩展的算法能力。

WordPress 能直接解决内容和运营底座，自研插件解决工具算法，自研主题解决体验。

## 当前运行时事实

已通过 bt4 只读 WP-CLI 验证：

```text
远端根目录：/www/wwwroot/yi.wuaishare.cn
WordPress 版本：6.9.4
站点名称：吾爱易学
管理员邮箱：wuaishare@gmail.com
数据库：sql_yi_wuaishare
```

补充事实：

- 远端 Nginx vhost 引用 `enable-php-84.conf`。
- WP-CLI 当前使用 PHP 8.3.30 CLI。
- 远端存在 PHP 8.4 与 PHP 8.3 运行目录。
- bt4 上管理员凭据文件权限为 `600 root root`。
- 访问 `http://192.168.31.24:13305/wp/local` 会进入宝塔面板安全入口校验页，不是 WordPress 前台。
- 当前 Nginx vhost 使用 `proxy_protocol`，不要用 `127.0.0.1` 直连失败判断 WordPress 前台异常。

## 推荐架构

### WordPress

职责：

- 页面、文章、分类、标签、媒体库。
- 后台管理。
- SEO 基础设施。
- 日更内容发布。
- 学习内容承载。

### `yi-tools-core` 插件

职责：

- 五行穿衣计算。
- 干支、农历、日五行、颜色推荐。
- REST API。
- 短代码或区块。
- 工具数据结构。
- 单元测试和样例数据。

插件内部建议拆分：

```text
wp-content/plugins/yi-tools-core/
  yi-tools-core.php
  includes/
    Calendar/
    Wuxing/
    Rest/
    Shortcodes/
    Blocks/
    Seo/
  config/
    wuxing-colors.php
    disclaimer.php
  tests/
```

### `yi-theme` 主题

职责：

- 首页、工具页、文章页、学习页展示。
- 移动端布局。
- 色卡、查询表单、Tab、结果卡片。
- 视觉风格。

主题不负责：

- 算法。
- 五行口径。
- SEO 标题规则。
- 工具分类真相。

### 学习内容迁移

`/Users/jingchen/Github/跟着 AI 学易经与术数` 不做一次性硬迁移。先按 `/learn/` 路线导入：

```text
/learn/
/learn/start-here/
/learn/beginner/
/learn/foundation/
/learn/advanced/
/learn/practice/
```

第一阶段只迁移学习路线、核心导览、底线规则和少量高价值教程。源仓库继续作为内容生产和复核来源。

## 信息架构

建议首期路径：

```text
/                         首页
/wuxing-chuanyi/          五行穿衣查询工具
/wuxing-chuanyi/YYYY-MM-DD/ 日期页，后期实现
/daily/                   日更文章集合
/learn/                   跟着 AI 学易经与术数
/knowledge/               术语与知识库
/tools/                   工具大全，后期扩展
```

栏目优先级：

1. 五行穿衣工具。
2. 日更文章与工具页互导。
3. 学习路线入口。
4. 黄历/今日宜忌。
5. 周易、八字、六爻、梅花、紫微等复杂工具。

## 为什么不用纯独立应用作为首期

纯独立应用优势：

- 前端交互自由。
- 工具逻辑可完全工程化。
- 可部署为轻量服务。

但首期代价更高：

- 内容后台要重建。
- SEO 发布流要重建。
- 媒体、分类、标签、权限都要重建。
- 与既有 WordPress / Actions Bridge / bt4 运维经验复用少。

因此不作为首期路线。

## 为什么不首期 Headless

Headless WordPress 长期可行，但首期会引入双系统复杂度：

- WordPress 后台。
- 独立前端构建。
- API 缓存。
- 预渲染/动态路由。
- 双端部署和调试。

在五行穿衣 MVP 尚未验证前，不值得先引入。

## 风险与约束

### 算法口径风险

五行穿衣、干支、农历、节气存在不同流派和口径。解决方式：

- 首期固定一种口径。
- 页面公开说明“按常见万年历干支口径”。
- 测试样例写入插件。
- 后续允许配置化口径，但不在首期做多流派。

### WordPress 主题膨胀风险

解决方式：

- 算法只放插件。
- 主题只调用插件 API。
- 主题模板不得复制业务规则。

### 日更低质重复风险

解决方式：

- 工具页作为长期主资产。
- 日更文章只做搜索入口和解释补充。
- 同一天避免“今日/明日”两篇高度重复文章。
- 日期页后期用结构化模板和 canonical 策略控制。

### 运行时入口风险

当前 `192.168.31.24:13305` 是宝塔面板入口，不是 WordPress 前台。真实前台应通过 `https://yi.wuaishare.cn/` 或正确代理链验收。

bt4 vhost 使用 `proxy_protocol`，所以本机 `curl --resolve yi.wuaishare.cn:80:127.0.0.1` 可能返回空响应，不能作为前台故障判断。

### 凭据风险

管理员密码只保存在：

```text
/root/codex-wp-create-20260512/yi.wuaishare.cn.credentials.txt
```

禁止复制到仓库。

## 首期非目标

首期不做：

- 完整八字排盘。
- 完整紫微斗数排盘。
- 六爻复杂断卦系统。
- 会员系统。
- 付费课程。
- Headless 前端。
- 多流派算法切换。
- 大规模内容自动发布。

## 下一步

1. 把当前目录初始化为源码/文档工作区，后续如需版本管理再单独建 Git。
2. 创建 `yi-tools-core` 插件骨架。
3. 创建 `yi-theme` 或选择合适的子主题策略。
4. 建立本地/远端同步脚本，参考 `ai.wuaishare.cn` 的 manifest-driven 工作流。
5. 先做 `/wuxing-chuanyi/` 工具页。
6. 用 Codex 内置浏览器验收真实前台。
