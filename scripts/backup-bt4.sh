#!/usr/bin/env bash
set -euo pipefail

REMOTE_ROOT="${REMOTE_ROOT:-/www/wwwroot/yi.wuaishare.cn}"
REMOTE_BACKUP_DIR="${REMOTE_BACKUP_DIR:-/root/backups}"
BT4_HOST="${BT4_HOST:-bt4}"
stamp="$(date +%Y-%m-%d-%H%M%S)"

ssh "$BT4_HOST" "set -euo pipefail
mkdir -p '$REMOTE_BACKUP_DIR'
tar -czf '$REMOTE_BACKUP_DIR/yi.wuaishare.cn-files-$stamp.tgz' -C /www/wwwroot yi.wuaishare.cn
wp --allow-root --path='$REMOTE_ROOT' db export '$REMOTE_BACKUP_DIR/yi.wuaishare.cn-db-$stamp.sql'
gzip -f '$REMOTE_BACKUP_DIR/yi.wuaishare.cn-db-$stamp.sql'
ls -lh '$REMOTE_BACKUP_DIR/yi.wuaishare.cn-files-$stamp.tgz' '$REMOTE_BACKUP_DIR/yi.wuaishare.cn-db-$stamp.sql.gz'
"
