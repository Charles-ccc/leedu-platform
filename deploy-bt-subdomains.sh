#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

NGINX_BIN="${NGINX_BIN:-/www/server/nginx/sbin/nginx}"
NGINX_CONF="${NGINX_CONF:-/www/server/nginx/conf/nginx.conf}"
API_DOMAIN="${API_DOMAIN:-leedu.happymaa.cn}"
API_UPSTREAM="${API_UPSTREAM:-http://127.0.0.1:8000}"
CERT_DIR="${CERT_DIR:-/www/server/panel/vhost/cert/happymaa.cn}"
VHOST_DIR="${VHOST_DIR:-/www/server/panel/vhost/nginx}"

COMPOSE="docker compose"

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "缺少命令: $1"
    exit 1
  fi
}

require_cmd docker
require_cmd pnpm
require_cmd grep
require_cmd sed
require_cmd perl

if [ ! -x "$NGINX_BIN" ]; then
  echo "找不到宝塔 Nginx 可执行文件: $NGINX_BIN"
  exit 1
fi

if [ ! -f .env ]; then
  echo "缺少根目录 .env，请先配置后端环境变量。"
  exit 1
fi

if ! grep -qE '^APP_KEY=base64:.+' .env; then
  echo "根目录 .env 中 APP_KEY 不合法。"
  exit 1
fi

if ! grep -qE '^JWT_SECRET=.+' .env; then
  echo "根目录 .env 中 JWT_SECRET 为空。"
  exit 1
fi

if ! grep -qE '^APP_URL=https://.+' .env; then
  echo "根目录 .env 中 APP_URL 缺失或不是 https 地址。"
  exit 1
fi

ensure_api_vhost() {
  local vhost_file="$VHOST_DIR/$API_DOMAIN.conf"

  if [ ! -f "$vhost_file" ]; then
    cat > "$vhost_file" <<EOF
server {
  listen 80;
  listen [::]:80;
  listen 443 ssl;
  listen [::]:443 ssl;
  http2 on;
  server_name $API_DOMAIN;

  ssl_certificate $CERT_DIR/fullchain.pem;
  ssl_certificate_key $CERT_DIR/privkey.pem;
  ssl_protocols TLSv1.1 TLSv1.2 TLSv1.3;
  ssl_ciphers EECDH+CHACHA20:EECDH+CHACHA20-draft:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:EECDH+3DES:RSA+3DES:!MD5;
  ssl_prefer_server_ciphers on;
  ssl_session_cache shared:SSL:10m;
  ssl_session_timeout 10m;
  add_header Strict-Transport-Security "max-age=31536000";
  error_page 497 https://\$host\$request_uri;

  location / {
    proxy_pass $API_UPSTREAM;
    proxy_http_version 1.1;
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
    proxy_read_timeout 60s;
  }

  access_log /www/wwwlogs/$API_DOMAIN.log;
  error_log /www/wwwlogs/$API_DOMAIN.error.log;
}
EOF
  fi
}

ensure_spa_fallback() {
  local vhost_file="$1"

  if grep -Fq 'try_files $uri $uri/ /index.html;' "$vhost_file"; then
    return
  fi

  if grep -qE 'location /[[:space:]]*\{' "$vhost_file"; then
    perl -0pi -e 's#location /\s*\{.*?\n\s*\}#location / {\n        try_files \$uri \$uri/ /index.html;\n    }#s' "$vhost_file"
    return
  fi

  perl -0pi -e 's#\n\s*access_log\s+/www/wwwlogs/#\n\n    location / {\n        try_files \$uri \$uri/ /index.html;\n    }\n\n    access_log /www/wwwlogs/#' "$vhost_file"
}

build_frontend() {
  local app_dir="$1"
  local domain="$2"
  local vhost_file="$VHOST_DIR/$domain.conf"
  local env_file="$app_dir/.env.production"
  local release_dir="$app_dir/dist-release"

  if [ ! -f "$env_file" ]; then
    echo "缺少 $env_file"
    exit 1
  fi

  if ! grep -q '^VITE_APP_URL=https://leedu.happymaa.cn' "$env_file"; then
    echo "$env_file 未指向 https://leedu.happymaa.cn"
    exit 1
  fi

  echo "==> 构建 $app_dir"
  (cd "$app_dir" && pnpm install --frozen-lockfile && rm -rf dist-release && pnpm exec tsc && pnpm exec vite build --outDir dist-release)

  if ! grep -R -q 'https://leedu.happymaa.cn' "$release_dir"; then
    echo "$release_dir 中未找到目标 API 地址，构建结果异常。"
    exit 1
  fi

  if [ ! -f "$vhost_file" ]; then
    echo "缺少站点配置: $vhost_file"
    exit 1
  fi

  perl -0pi -e "s#root\s+${app_dir//\//\/}/dist(?:-release)?;#root ${app_dir}/dist-release;#g" "$vhost_file"
  ensure_spa_fallback "$vhost_file"
}

echo "==> 1/5 启动 leedu 后端"
$COMPOSE up -d

echo "==> 2/5 检查本机 API"
if ! curl -fsS http://127.0.0.1:8000 >/dev/null; then
  echo "本机 127.0.0.1:8000 未响应。"
  exit 1
fi

echo "==> 3/5 确保 API 域名反代配置"
ensure_api_vhost

echo "==> 4/5 构建前端并切到 dist-release"
build_frontend "$PWD/xyz.leedu.admin" admin.happymaa.cn
build_frontend "$PWD/xyz.leedu.pc" pc.happymaa.cn
build_frontend "$PWD/xyz.leedu.h5" h5.happymaa.cn

echo "==> 5/5 重载宝塔 Nginx 并校验"
"$NGINX_BIN" -t -c "$NGINX_CONF"
"$NGINX_BIN" -s reload -c "$NGINX_CONF"

for url in "https://$API_DOMAIN" "https://admin.happymaa.cn" "https://pc.happymaa.cn" "https://h5.happymaa.cn"; do
  echo "-- 检查 $url"
  curl -k -I -s "$url" | sed -n '1,8p'
  echo
done

echo "发布完成。"