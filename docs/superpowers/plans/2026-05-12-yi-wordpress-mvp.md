# Yi WordPress MVP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first usable `yi.wuaishare.cn` WordPress MVP with a governed plugin/theme boundary and a `/wuxing-chuanyi/` five-elements clothing color tool.

**Architecture:** WordPress remains the CMS and SEO base. `yi-tools-core` owns calculation logic, REST/shortcode/block integration, configuration, and tests. `yi-theme` owns visual presentation and calls plugin APIs rather than duplicating rules.

**Tech Stack:** WordPress 6.9.4, PHP 8.4 for web runtime, WP-CLI on bt4, custom WordPress plugin, custom theme or child theme, Codex in-app browser for frontend verification.

---

## File Structure

Planned source files:

```text
AGENTS.md
docs/OPERATIONS.md
docs/agent-team/yi-agent-team.md
docs/strategy/wordpress-architecture-decision.md
docs/superpowers/specs/2026-05-12-yi-wordpress-agent-team-design.md
docs/superpowers/plans/2026-05-12-yi-wordpress-mvp.md
wp-content/plugins/yi-tools-core/
wp-content/themes/yi-theme/
config/managed-paths.txt
scripts/
```

Responsibility split:

- `AGENTS.md`: durable project rules for future Codex work.
- `docs/OPERATIONS.md`: bt4/WordPress runtime facts and safe operations.
- `docs/agent-team/yi-agent-team.md`: multi Agent team model.
- `docs/strategy/wordpress-architecture-decision.md`: WordPress-first architecture decision.
- `yi-tools-core`: calculation and tool contract.
- `yi-theme`: UI only.
- `config/managed-paths.txt`: paths owned by this repo.
- `scripts/`: future setup/sync/deploy helpers.

## Task 1: Confirm Runtime Entry And Local Worktree Strategy

**Files:**
- Read: `docs/OPERATIONS.md`
- Modify only if facts change: `docs/OPERATIONS.md`

- [ ] **Step 1: Verify remote WordPress facts**

Run:

```bash
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn core version"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn option get blogname"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn option get home"
```

Expected:

```text
6.9.4
吾爱易学
https://yi.wuaishare.cn
```

- [ ] **Step 2: Verify frontend through the right route**

Use Codex in-app browser to open:

```text
https://yi.wuaishare.cn/
```

Expected:

```text
The WordPress frontend renders. It must not show the bt4 panel login page.
```

- [ ] **Step 3: If browser cannot reach the frontend, inspect logs without changing config**

Run:

```bash
ssh bt4 "tail -n 40 /www/wwwlogs/yi.wuaishare.cn.error.log 2>/dev/null || true"
ssh bt4 "tail -n 20 /www/wwwlogs/yi.wuaishare.cn.log 2>/dev/null || true"
ssh bt4 "/www/server/nginx/sbin/nginx -t -c /www/server/nginx/conf/nginx.conf"
```

Expected:

```text
No syntax failure. Access log should show whether requests are reaching WordPress.
```

- [ ] **Step 4: Record any changed runtime truth**

If the URL, PHP runtime, Nginx behavior, or database changes, update `docs/OPERATIONS.md` before implementation continues.

## Task 2: Initialize Managed Source Paths

**Files:**
- Create: `config/managed-paths.txt`
- Create later: `wp-content/plugins/yi-tools-core/`
- Create later: `wp-content/themes/yi-theme/`

- [ ] **Step 1: Create managed path manifest**

Create `config/managed-paths.txt`:

```text
# Managed source paths for yi.wuaishare.cn.
# Paths are relative to the WordPress root.

wp-content/plugins/yi-tools-core/
wp-content/themes/yi-theme/
```

- [ ] **Step 2: Verify manifest contains only owned paths**

Run:

```bash
sed -n '1,80p' config/managed-paths.txt
```

Expected:

```text
Only yi-tools-core and yi-theme are listed.
```

## Task 3: Scaffold `yi-tools-core`

**Files:**
- Create: `wp-content/plugins/yi-tools-core/yi-tools-core.php`
- Create: `wp-content/plugins/yi-tools-core/includes/Wuxing/ElementRules.php`
- Create: `wp-content/plugins/yi-tools-core/includes/Wuxing/ClothingColors.php`
- Create: `wp-content/plugins/yi-tools-core/includes/Rest/WuxingClothingController.php`
- Create: `wp-content/plugins/yi-tools-core/includes/Shortcodes/WuxingClothingShortcode.php`

- [ ] **Step 1: Implement element rules**

`ElementRules.php` must expose:

```php
<?php
namespace YiToolsCore\Wuxing;

final class ElementRules {
	public const ELEMENTS = array( '木', '火', '土', '金', '水' );

	public const GENERATES = array(
		'木' => '火',
		'火' => '土',
		'土' => '金',
		'金' => '水',
		'水' => '木',
	);

	public const CONTROLS = array(
		'木' => '土',
		'土' => '水',
		'水' => '火',
		'火' => '金',
		'金' => '木',
	);
}
```

- [ ] **Step 2: Implement color mapping and result builder**

`ClothingColors.php` must expose a function or class method that receives `day_element` and returns:

```php
array(
	'lucky' => array(),
	'secondary' => array(),
	'neutral' => array(),
	'caution' => array(),
	'avoid' => array(),
)
```

Expected rule:

```text
大吉色：当日五行所生
次吉色：当日五行相同
平平色：颜色五行克当日五行
慎用色：颜色五行生当日五行
不宜色：当日五行克颜色五行
```

- [ ] **Step 3: Add shortcode**

The shortcode should be:

```text
[yi_wuxing_clothing]
```

It should render a minimal query UI and result container. The first implementation can be server-rendered.

- [ ] **Step 4: Add REST endpoint**

Register:

```text
/wp-json/yi-tools/v1/wuxing-clothing?date=YYYY-MM-DD
```

It should return JSON for the requested date. Invalid dates return a `WP_Error` with HTTP 400.

- [ ] **Step 5: Verify PHP syntax**

Run:

```bash
find wp-content/plugins/yi-tools-core -name '*.php' -print0 | xargs -0 -n1 php -l
```

Expected:

```text
No syntax errors detected
```

## Task 4: Build `/wuxing-chuanyi/` Page

**Files:**
- Create or update via WordPress page: `/wuxing-chuanyi/`
- Modify: `wp-content/themes/yi-theme/` if needed

- [ ] **Step 1: Create the WordPress page**

After plugin activation, create a page with slug:

```text
wuxing-chuanyi
```

Content:

```text
[yi_wuxing_clothing]
```

- [ ] **Step 2: Verify page URL**

Open with Codex in-app browser:

```text
https://yi.wuaishare.cn/wuxing-chuanyi/
```

Expected:

```text
The page renders the date selector and color result, not raw shortcode text.
```

- [ ] **Step 3: Check mobile viewport**

Use Codex in-app browser viewport testing for a mobile width around 390px.

Expected:

```text
No overlapping text. Date controls remain usable. Color cards stack cleanly.
```

## Task 5: Add SEO And Disclaimer

**Files:**
- Modify: `wp-content/plugins/yi-tools-core/includes/Shortcodes/WuxingClothingShortcode.php`
- Modify: `wp-content/themes/yi-theme/` only for presentation

- [ ] **Step 1: Add visible disclaimer**

Use this text:

```text
本站内容基于传统文化、民俗资料与生活参考整理，仅供娱乐和文化参考，不构成现实决策依据。
```

- [ ] **Step 2: Add page title strategy**

Tool page title:

```text
五行穿衣指南查询 - 今日大吉色、每日穿衣颜色参考
```

Date-specific title format for later extension:

```text
YYYY年M月D日五行穿衣指南：今日大吉色与穿衣颜色
```

- [ ] **Step 3: Verify frontend**

Open:

```text
https://yi.wuaishare.cn/wuxing-chuanyi/
```

Expected:

```text
The disclaimer appears below useful content. It should not dominate the first screen.
```

## Task 6: Create `/learn/` Integration Entry

**Files:**
- Source reference: `/Users/jingchen/Github/跟着 AI 学易经与术数/README.md`
- WordPress page: `/learn/`

- [ ] **Step 1: Create a learning landing page**

The page should introduce:

```text
跟着 AI 学易经与术数
```

Core sections:

```text
开始这里
零基础阶段
基础阶段
进阶阶段
实战阶段
教学与产品化
```

- [ ] **Step 2: Include boundary copy**

Use this principle:

```text
先学语言，再学判断；先做记录，再谈准不准；AI 是助教，不是神谕机。
```

- [ ] **Step 3: Verify link placement**

The homepage and footer should have a route to `/learn/`.

Expected:

```text
Users can reach learning content without confusing it with the five-elements clothing tool.
```

## Task 7: Remote Deployment And Verification

**Files:**
- Future scripts: `scripts/backup-bt4.sh`
- Future scripts: `scripts/deploy-bt4.sh`

- [ ] **Step 1: Back up remote files and database**

Run before remote mutation:

```bash
ssh bt4 "mkdir -p /root/backups && tar -czf /root/backups/yi.wuaishare.cn-files-$(date +%Y-%m-%d-%H%M%S).tgz -C /www/wwwroot yi.wuaishare.cn"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn db export /root/backups/yi.wuaishare.cn-db-$(date +%Y-%m-%d-%H%M%S).sql"
```

- [ ] **Step 2: Sync only managed paths**

Only sync:

```text
wp-content/plugins/yi-tools-core/
wp-content/themes/yi-theme/
```

Do not sync:

```text
wp-config.php
wp-content/uploads/
WordPress core
credentials
database dumps
```

- [ ] **Step 3: Activate plugin and theme**

Run:

```bash
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn plugin activate yi-tools-core"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn theme activate yi-theme"
```

Expected:

```text
Success messages from WP-CLI.
```

- [ ] **Step 4: Final browser verification**

Use Codex in-app browser:

```text
https://yi.wuaishare.cn/
https://yi.wuaishare.cn/wuxing-chuanyi/
https://yi.wuaishare.cn/learn/
```

Expected:

```text
Homepage, tool page, and learning page render correctly on desktop and mobile.
```

## Self-Review

- Spec coverage: Covers Agent Team, WordPress architecture, runtime facts, five-elements MVP, `/learn/` integration, and deployment safety.
- Placeholder scan: No `TBD`, `TODO`, or unspecified implementation steps remain.
- Type consistency: Planned plugin names, paths, shortcode, and REST route are consistent across tasks.
