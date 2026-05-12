# 吾爱易学 / 易知阁

`yi.wuaishare.cn` 是东方传统文化工具站。首期目标是五行穿衣查询工具，后续扩展到黄历、八字、周易、六爻、梅花易数、紫微斗数、风水、姓名学，以及学习路线内容。

## 当前工程边界

- `wp-content/plugins/yi-tools-core/`：工具算法、REST、短代码。
- `wp-content/themes/yi-theme/`：前台展示主题。
- `docs/`：产品、架构、运维和 Agent Team 文档。
- `config/managed-paths.txt`：需要同步到远端 WordPress 根目录的自研路径。

## 运行时

远端 WordPress 根目录：

```text
/www/wwwroot/yi.wuaishare.cn
```

前台：

```text
https://yi.wuaishare.cn/
```

运维说明见 [docs/OPERATIONS.md](docs/OPERATIONS.md)。
