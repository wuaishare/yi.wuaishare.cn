#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BT4_HOST="${BT4_HOST:-bt4}"
REMOTE_ROOT="${REMOTE_ROOT:-/www/wwwroot/yi.wuaishare.cn}"
MANAGED_PATHS_FILE="${MANAGED_PATHS_FILE:-$repo_root/config/managed-paths.txt}"

if [[ ! -f "$MANAGED_PATHS_FILE" ]]; then
  echo "缺少维护路径清单：$MANAGED_PATHS_FILE" >&2
  exit 1
fi

exec 3< "$MANAGED_PATHS_FILE"
while IFS= read -r raw <&3 || [[ -n "$raw" ]]; do
  path="${raw%%#*}"
  path="$(printf '%s' "$path" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
  [[ -z "$path" ]] && continue

  case "$path" in
    /*|../*|*/../*|wp-config.php|wp-admin*|wp-includes*|wp-content/uploads*|wp-content/cache*)
      echo "拒绝同步危险路径：$path" >&2
      exit 1
      ;;
  esac

  source_path="$repo_root/${path%/}/"
  target_path="$REMOTE_ROOT/${path%/}/"

  if [[ ! -d "$source_path" ]]; then
    echo "本地路径不存在：$source_path" >&2
    exit 1
  fi

  echo "同步维护路径：$path"
  ssh -n "$BT4_HOST" "mkdir -p '$(dirname "$target_path")'"
  rsync -az --delete \
    --exclude='node_modules/' \
    --exclude='vendor/' \
    --exclude='.git/' \
    "$source_path" "$BT4_HOST:$target_path"
done
exec 3<&-

ssh -n "$BT4_HOST" "wp --allow-root --path='$REMOTE_ROOT' plugin list --fields=name,status,version --format=table | sed -n '1,20p'"
