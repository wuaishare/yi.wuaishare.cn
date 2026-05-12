# AGENTS.md

`yi.wuaishare.cn` 项目级协作规则。

## 项目定位

本项目是 `吾爱易学` / `易知阁` 方向的东方传统文化工具站。首期核心是五行穿衣查询工具，后续扩展到黄历、八字、周易、六爻、梅花易数、紫微斗数、风水、姓名学，以及从 `/Users/jingchen/Github/跟着 AI 学易经与术数` 融合而来的学习内容。

默认产品原则：

- 先做可查询、可复访、可 SEO 承接的工具页。
- 再做学习路线、术语库、案例库和课程化内容。
- 不把术数内容包装成医疗、法律、投资或重大现实决策依据。
- 所有术数、命理、民俗类输出必须保留文化参考与不确定性边界。

## 浏览器验收

如需调用浏览器进行调试验证，优先使用 Codex 内置浏览器。

前端、主题、工具页、文章模板、移动端体验、SEO 可见输出发生变化时，默认验收顺序是：

1. 改代码或配置。
2. 优先用 Codex 内置浏览器打开真实页面。
3. 直接检查真实渲染结果。
4. 继续迭代，直到页面结构、中文阅读体验和移动端表现符合要求。

`curl`、WP-CLI、模板源码、HTML 片段只能作为辅助证据，不能替代真实页面验收。

## WordPress 路线

首期采用 WordPress-first 架构：

- WordPress 负责 CMS、后台、文章、分类、标签、媒体、SEO、用户和发布流。
- 自研插件负责工具算法、REST 接口、短代码/区块、数据结构和可测试计算逻辑。
- 主题负责展示和交互，不拥有算法口径和业务规则真相。
- 后期八字、紫微、六爻等复杂能力如果增长到足够规模，再拆为独立算法服务。

禁止把规则长期硬编码在渲染层。以下内容默认必须进入插件配置、taxonomy、term meta、post meta、options、JSON schema 或测试过的算法模块：

- 五行颜色、相生相克、推荐等级、日期口径。
- 工具分类、菜单顺序、专题分组。
- SEO 标题模板、日更文章模板、内链策略。
- 学习内容阶段、门类分类、术语体系。

## 源码与运行时边界

默认把当前目录作为 Codex 工作区和项目源码/文档真相：

```text
/Users/jingchen/Github/yi.wuaishare.cn
```

bt4 远端 WordPress 运行目录是部署结果与运行时真相：

```text
/www/wwwroot/yi.wuaishare.cn
```

运行时信息：

- 站点：`yi.wuaishare.cn`
- 当前站点名：`吾爱易学`
- WordPress：`6.9.4`
- 数据库：`sql_yi_wuaishare`
- bt4 站点根目录：`/www/wwwroot/yi.wuaishare.cn`

管理员凭据不得写入仓库、文档、提交信息或聊天摘要。凭据只记录为 bt4 root-only 文件路径：

```text
/root/codex-wp-create-20260512/yi.wuaishare.cn.credentials.txt
```

## 远端验证规则

涉及 bt4 / WordPress 远端的工作，优先先做只读确认：

```bash
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn core version"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn option get blogname"
ssh bt4 "wp --allow-root --path=/www/wwwroot/yi.wuaishare.cn option get home"
```

高风险写操作前必须备份文件和数据库。禁止在未确认目标站点、路径、数据库和备份状态前执行批量写入、删除、search-replace、插件覆盖、主题覆盖或 Nginx reload。

当前 bt4 Nginx vhost 使用 `proxy_protocol`。不要用 `127.0.0.1` 直连该 vhost 的失败结果判定 WordPress 前台异常；前台验收应使用真实域名、正确代理链，或明确配置的本地 ServBay 镜像入口。

## Agent Team 使用规则

本项目采用“吾爱易学 Agent Team”作为一人公司式协作模型。Supervisor 负责按任务挑选必要角色，不允许每个问题都让全员发言。

常用模式：

- 日常模式：目标明确的小修、小查、小改，由 Codex 直接推进，必要时拉一个 QA 或 WordPress/Ops 视角。
- 作战模式：跨 WordPress、算法、内容、SEO、运维或产品取舍时启用，由 Supervisor 调度 2-6 个角色。
- 收口模式：验收、提交、同步、上线、复盘时启用，必须检查 diff、运行验证、浏览器回看和临时产物。

完整团队设定见：

```text
docs/agent-team/yi-agent-team.md
```

## 内容与伦理边界

所有工具页、学习页、文章页必须遵守：

- 明确这是传统文化、民俗资料、学习和生活参考。
- 不输出“必然发财”“保证转运”“一定应验”等刚性承诺。
- 不替代医疗、法律、投资、心理诊断、婚姻重大决策。
- 出生时间、命盘、案例、个人经历等信息按隐私数据处理。
- 面相、手相、姓名学、数字能量学等争议更高的内容，应放在旁支、比较或文化解读位置，不提升为主干体系。

## 当前首期目标

首期只做一个稳定闭环：

1. 固定 WordPress-first 工程底座。
2. 建立 `yi-tools-core` 插件和 `yi-theme` 主题边界。
3. 做出 `/wuxing-chuanyi/` 五行穿衣查询工具。
4. 建立日更文章与工具页互相导流。
5. 把《跟着 AI 学易经与术数》先以 `/learn/` 学习路线形式融合，而不是一次性全量迁移。
