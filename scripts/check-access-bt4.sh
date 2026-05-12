#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${DOMAIN:-yi.wuaishare.cn}"
BT4_HOST="${BT4_HOST:-bt4}"
REMOTE_ROOT="${REMOTE_ROOT:-/www/wwwroot/yi.wuaishare.cn}"
REMOTE_VHOST="${REMOTE_VHOST:-/www/server/panel/vhost/nginx/yi.wuaishare.cn.conf}"
REMOTE_CERT="${REMOTE_CERT:-/www/server/panel/vhost/cert/ai.wuaishare.cn/fullchain.pem}"
TIMEOUT="${TIMEOUT:-20}"

section() {
  printf '\n== %s ==\n' "$1"
}

section "WordPress runtime"
ssh "$BT4_HOST" "wp --allow-root --path='$REMOTE_ROOT' core version && wp --allow-root --path='$REMOTE_ROOT' option get blogname && wp --allow-root --path='$REMOTE_ROOT' option get home && wp --allow-root --path='$REMOTE_ROOT' option get siteurl"

section "Nginx vhost and certificate"
ssh "$BT4_HOST" "/www/server/nginx/sbin/nginx -t -c /www/server/nginx/conf/nginx.conf"
ssh "$BT4_HOST" "grep -nE 'listen |server_name|ssl_certificate|proxy_protocol|real_ip_header|set_real_ip_from' '$REMOTE_VHOST'"
ssh "$BT4_HOST" "openssl x509 -in '$REMOTE_CERT' -noout -issuer -subject -dates -ext subjectAltName"

section "FRP proxy protocol hints"
ssh "$BT4_HOST" "grep -nEi 'customDomains|proxyProtocolVersion|localPort|serverAddr' /frp-hk/frpc.toml /frp-cn-ali/frpc.toml 2>/dev/null || true"

section "Public DNS"
if command -v dig >/dev/null 2>&1; then
  dig +short @1.1.1.1 "$DOMAIN" A
  dig +short @8.8.8.8 "$DOMAIN" A
else
  echo "dig not found; skip public DNS check"
fi

section "Public HTTPS"
curl -sS -D - -o /dev/null --max-time "$TIMEOUT" "https://$DOMAIN/" | sed -n '1,24p'
curl -sS -L -o /dev/null -w 'home status=%{http_code} effective=%{url_effective} remote_ip=%{remote_ip} ssl_verify=%{ssl_verify_result} total=%{time_total}\n' --max-time "$TIMEOUT" "https://$DOMAIN/"
curl -sS -L -o /dev/null -w 'tool status=%{http_code} effective=%{url_effective} remote_ip=%{remote_ip} ssl_verify=%{ssl_verify_result} total=%{time_total}\n' --max-time "$TIMEOUT" "https://$DOMAIN/wuxing-chuanyi/"
curl -sS -L -o /dev/null -w 'rest status=%{http_code} effective=%{url_effective} remote_ip=%{remote_ip} ssl_verify=%{ssl_verify_result} total=%{time_total}\n' --max-time "$TIMEOUT" "https://$DOMAIN/wp-json/yi-tools/v1/wuxing-clothing"

section "Expected direct-origin failures"
ssh "$BT4_HOST" "curl -I --max-time 8 -H 'Host: $DOMAIN' http://127.0.0.1/ || true"
ssh "$BT4_HOST" "curl -I --max-time 8 https://$DOMAIN/ || true"
