# 吾爱易学 WordPress 与 Agent Team 设计

日期：2026-05-12

## 目标

为 `yi.wuaishare.cn` 建立首期可执行方向：采用 WordPress-first 架构，配置适用于 Codex 的多 Agent 一人公司团队，并把五行穿衣工具作为 MVP 第一阶段。

## 已验证事实

- 当前 Codex 工作区：`/Users/jingchen/Github/yi.wuaishare.cn`
- 当前目录不是 Git 仓库。
- 远端 bt4 站点根目录存在：`/www/wwwroot/yi.wuaishare.cn`
- WordPress 版本：`6.9.4`
- 站点名称：`吾爱易学`
- 管理员邮箱：`wuaishare@gmail.com`
- 数据库：`sql_yi_wuaishare`
- 凭据文件：`/root/codex-wp-create-20260512/yi.wuaishare.cn.credentials.txt`
- 凭据文件权限：`600 root root`
- Nginx vhost 使用 `proxy_protocol`
- 远端 Web vhost 引用 PHP 8.4，WP-CLI 当前使用 PHP 8.3 CLI

## 架构选择

首期采用：

```text
WordPress CMS + yi-tools-core 插件 + yi-theme 主题
```

WordPress 负责内容与运营底座；插件负责工具算法、REST API、短代码/区块和测试；主题负责展示与交互。

不在首期采用纯独立应用或 Headless WordPress，因为当前最需要的是快速形成工具页 + SEO 内容 + 后台发布闭环。

## 站点信息架构

首期路径：

```text
/                         首页
/wuxing-chuanyi/          五行穿衣查询
/daily/                   日更文章集合
/learn/                   跟着 AI 学易经与术数
/knowledge/               术语与知识库
/tools/                   工具大全
```

后续路径：

```text
/huangli/
/jinri-yiji/
/bazi/
/zhouyi/
/meihua-yishu/
/liuyao/
/ziwei/
/fengshui/
/xingming/
```

## 五行穿衣 MVP

用户打开 `/wuxing-chuanyi/` 后：

1. 默认显示当天结果。
2. 可切换前一天、后一天、回到今天。
3. 可选择任意日期。
4. 显示公历、农历、星期、年干支、月干支、日干支、日五行。
5. 显示大吉色、次吉色、平平色、慎用色、不宜色。
6. 显示穿搭建议。
7. 显示口径说明和免责声明。

首期固定一种“常见万年历干支口径”。如果与其他黄历站存在差异，页面只说明口径，不做多口径切换。

## 学习内容融合

`/Users/jingchen/Github/跟着 AI 学易经与术数` 先作为 `/learn/` 学习内容来源，不做一次性全量迁移。

迁移顺序：

1. 学习路线首页。
2. 开始这里。
3. 零基础阶段。
4. 阴阳五行八卦基础。
5. 伦理边界与免责声明。

数字能量学、姓名学、手相、面相等内容放在现代流行/旁支比较位置，不提升为主干。

## Agent Team

团队采用：

```text
Supervisor + CEO + PM + CTO + 易学内容总编 + 算法与历法工程师 + Content SEO + Frontend/UX + Codex + QA + Ops + Growth
```

Supervisor 按任务挑选必要角色，不允许全员无差别参与。每次作战模式必须输出：

- 参与角色与原因。
- 统一结论。
- 任务拆解。
- 风险。
- 验收标准。
- 下一步。

## 运行时约束

- 所有 bt4 写操作前备份。
- 管理员密码不进入仓库。
- 前台验收优先 Codex 内置浏览器。
- 不用 `127.0.0.1` 直连 proxy_protocol vhost 的失败结果判断前台异常。
- 远端 WP-CLI 写操作必须确认目标路径和数据库。

## 首期非目标

首期不做：

- 完整八字排盘。
- 完整紫微斗数排盘。
- 六爻断卦。
- 会员/付费。
- Headless 架构。
- 多流派算法配置。
- 自动批量发布。

## 验收标准

本阶段文档验收：

- `AGENTS.md` 存在并包含浏览器优先、WordPress-first、运行时安全边界。
- `docs/agent-team/yi-agent-team.md` 存在并定义团队角色和调度模式。
- `docs/strategy/wordpress-architecture-decision.md` 存在并说明 WordPress 选型。
- `docs/OPERATIONS.md` 存在并记录 bt4 运行时事实和验收注意事项。
- `docs/superpowers/plans/2026-05-12-yi-wordpress-mvp.md` 存在并能指导后续开发。
