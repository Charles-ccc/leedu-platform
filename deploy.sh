#!/usr/bin/env bash
# Leedu 平台版 - 生产部署辅助脚本
# 用法: ./deploy.sh   (在仓库根目录、服务器上执行)
# 详见 DEPLOY.md
set -euo pipefail

cd "$(dirname "$0")"

COMPOSE="docker compose -f compose.prod.yml"

echo "==> 1/5 检查 .env"
if [ ! -f .env ]; then
  echo "缺少 .env，请先: cp .env.production.example .env 并填写真实值"
  exit 1
fi
# 校验关键密钥已填
if ! grep -qE '^APP_KEY=base64:.+' .env; then
  echo "APP_KEY 未生成。可执行: echo \"APP_KEY=base64:\$(openssl rand -base64 32)\""
  exit 1
fi
if ! grep -qE '^JWT_SECRET=.+' .env; then
  echo "JWT_SECRET 未生成。可执行: echo \"JWT_SECRET=\$(openssl rand -base64 48)\""
  exit 1
fi

echo "==> 2/5 构建自定义镜像 (含平台化改动)"
# 跨架构构建可加: DOCKER_DEFAULT_PLATFORM=linux/amd64
$COMPOSE build

echo "==> 3/5 启动依赖 (mysql/redis/meilisearch)"
$COMPOSE up -d mysql redis meilisearch
echo "   等待数据库就绪..."
sleep 15

echo "==> 4/5 启动应用 (启动时会自动执行 leedu:upgrade = 数据库迁移)"
$COMPOSE up -d leedu
sleep 20

echo "==> 5/5 初始化超级管理员 (仅首次需要; 已存在会提示跳过)"
echo "   如需创建超管: $COMPOSE exec leedu php artisan install role"
echo "                  $COMPOSE exec leedu php artisan install administrator"

echo
echo "完成。状态:"
$COMPOSE ps
echo
echo "访问: API :8000 / PC :8100 / H5 :8200 / 后台 :8300 (建议前置 Nginx+HTTPS)"
