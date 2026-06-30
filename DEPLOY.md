# 生产部署手册（Leedu 平台版）

> 把含平台化改造（多机构 + 业务员返点 + 支付分账 + 芝麻先享）的代码部署到服务器。
> 关键：**必须用本仓库 Dockerfile 重新构建镜像**——官方 `compose.yml` 拉的预构建镜像 `leedu/light` 不含你的改动。

相关文档：架构 [PLATFORM_DESIGN.md](PLATFORM_DESIGN.md) ｜ 本地开发 [DEV.md](DEV.md) ｜ 支付联调 [ALIPAY_INTEGRATION.md](ALIPAY_INTEGRATION.md)

---

## 1. 服务器前置

- Linux x86_64 服务器，已装 **Docker** + **Docker Compose v2**
- 开放/反代端口（建议前置 Nginx + HTTPS）
- 域名（支付宝回调需公网可达，建议 HTTPS）
- 资源建议：2C4G 起步（构建镜像时前端打包较吃内存，建议 ≥4G 或加 swap）

---

## 2. 获取代码

把部署包（见"打包"产物）上传到服务器并解压，或：

```bash
git clone <你的仓库> leedu && cd leedu
```

> 部署包已剔除 `node_modules`、`vendor`、`dist`、`.git`、开发用 `.env`/`compose.dev.yml` 等；镜像构建时会自动重新拉取依赖并打包。

---

## 3. 配置环境变量

```bash
cp .env.production.example .env
# 生成密钥（务必新生成，勿用任何示例/开发值）
echo "APP_KEY=base64:$(openssl rand -base64 32)"
echo "JWT_SECRET=$(openssl rand -base64 48)"
# 把上面两行输出填入 .env，并设置强 DB_PASSWORD / REDIS_PASSWORD
vi .env
```

必填：`APP_KEY`、`JWT_SECRET`、`DB_PASSWORD`、`REDIS_PASSWORD`。

---

## 4. 构建并启动

一键脚本：

```bash
chmod +x deploy.sh
./deploy.sh
```

或手动：

```bash
# 跨架构(如本地 arm64 构建给 x86_64 服务器)可加 DOCKER_DEFAULT_PLATFORM=linux/amd64
docker compose -f compose.prod.yml build
docker compose -f compose.prod.yml up -d
```

容器 **首次启动会自动执行 `leedu:upgrade`（=数据库迁移 + 同步配置）**，无需手动 migrate。

确认状态：

```bash
docker compose -f compose.prod.yml ps
docker compose -f compose.prod.yml logs -f leedu
```

---

## 5. 初始化超级管理员（仅首次）

```bash
docker compose -f compose.prod.yml exec leedu php artisan install role
docker compose -f compose.prod.yml exec leedu php artisan install administrator
# 按提示设邮箱/密码；或静默(默认 leedu@leedu.leedu / leedu123，务必随后改密)
# docker compose -f compose.prod.yml exec leedu php artisan install administrator --q
```

---

## 6. 访问与验证

| 端口 | 用途      |
| ---- | --------- |
| 8000 | API       |
| 8100 | PC 学员端 |
| 8200 | H5 移动端 |
| 8300 | 运营后台  |

- 登录后台（8300）→ 平台菜单应可见：机构管理 / 课程审核 / 平台分账 / 业务员管理 / 提成记录 / 业务员提现 / 合同模板。
- 走一遍：机构入驻申请 → 平台审核 → 机构上架课程 → 平台审核 → 学员端可见。
- 生产**前置 Nginx**：把 80/443 反代到 8300（后台）、8100（PC）、8200（H5）、并把 `/api` → 8000；或按你的域名规划分配子域名。各端"访问地址"在后台系统设置里配成线上域名。

---

## 7. 支付宝 / 芝麻先享 联调

收单/分账/芝麻先享在填入**真实支付宝商户凭证**前不可用（其余功能正常）。
机构登录后在「机构后台 → 支付宝配置」填写本机构 app_id/私钥/证书。
完整待联调点与所需接口见 [ALIPAY_INTEGRATION.md](ALIPAY_INTEGRATION.md)。

---

## 8. 升级 / 回滚

**升级**（拉新代码后）：

```bash
git pull   # 或上传新部署包覆盖
docker compose -f compose.prod.yml build
docker compose -f compose.prod.yml up -d   # 启动会自动迁移
```

**回滚**：

- 代码：`git checkout <上个版本>` 后重新 build + up。
- 数据库：**升级前先备份**（见下）。迁移含 `down()`，但生产回滚优先用备份恢复。

**备份（务必定期）**：

```bash
docker compose -f compose.prod.yml exec mysql sh -c \
  'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" leedu' > backup_$(date +%F).sql
```

---

## 9. 上线检查清单

- [ ] `.env` 用全新 `APP_KEY`/`JWT_SECRET` + 强 DB/Redis 密码
- [ ] 自定义镜像构建成功（`compose.prod.yml build`）
- [ ] 容器启动、迁移自动完成、`ps` 全部 Up（meilisearch 在部分环境可能需调整，仅影响搜索）
- [ ] 超级管理员已创建并改掉默认密码
- [ ] 前置 Nginx + HTTPS、各端访问地址改为线上域名
- [ ] 数据库已配置自动备份
- [ ] 支付上线前完成支付宝联调（见 ALIPAY_INTEGRATION.md）
- [ ] 不要把开发库/测试数据带上生产（示例机构、测试业务员等仅存在于本地 dev 库）

---

## 10. 常见问题

- **改了代码不生效**：生产不挂源码，必须重新 `build` 镜像再 `up`。
- **构建 OOM/卡住**：前端打包吃内存，加 swap 或用 ≥4G 机器；或在本地/CI 构建好镜像推私有 registry，服务器改 `compose.prod.yml` 为 `image:` 拉取。
- **arm 机器构建给 x86 服务器**：构建时设 `DOCKER_DEFAULT_PLATFORM=linux/amd64`。
- **meilisearch 起不来**：老版本在部分虚拟化环境有兼容问题，只影响站内搜索；可换更新版本镜像或暂时禁用搜索。

---

## 11. 宝塔 + 子域部署（当前 happymaa.cn 环境）

当前服务器实际采用的是：

- 宝塔 Nginx 托管三个前端子域名静态站点
- `leedu.happymaa.cn` 反向代理到本机 `127.0.0.1:8000`
- leedu 后端通过根目录 [compose.yml](compose.yml) 启动容器

### 为什么不直接覆盖 `dist`

宝塔会往站点根目录写入 `.user.ini`。如果直接把 Vite 的输出目录设为线上站点根目录，Vite 在清空旧目录时会因为 `.user.ini` 报 `ENOTDIR`。

因此当前推荐做法是：

- 每次构建输出到 `dist-release`
- Nginx 站点根目录指向 `dist-release`
- 不再直接依赖原始 `dist`

### 前端环境变量

三个前端都需要单独的 `.env.production`：

- [xyz.leedu.admin/.env.production](xyz.leedu.admin/.env.production)
- [xyz.leedu.pc/.env.production](xyz.leedu.pc/.env.production)
- [xyz.leedu.h5/.env.production](xyz.leedu.h5/.env.production)

内容统一为：

```bash
VITE_APP_URL=https://leedu.happymaa.cn
```

根目录 `.env` 还需要至少包含：

```bash
APP_URL=https://leedu.happymaa.cn
```

这是后端向腾讯云 VOD 同步事件回调地址时使用的基地址；如果缺失，运行容器会退回到 `http://localhost`。

### 一键发布脚本

服务器执行：

```bash
chmod +x deploy-bt-subdomains.sh
./deploy-bt-subdomains.sh
```

脚本会完成这些事情：

1. 启动 leedu Docker Compose 后端
2. 确保 `leedu.happymaa.cn` 反代配置存在
3. 在 Linux 服务器重新安装三端依赖，避免 `esbuild` 平台不匹配
4. 将 admin / pc / h5 构建到 `dist-release`
5. 把三个宝塔站点根目录切换到 `dist-release`，并确保 `location /` 走 `try_files $uri $uri/ /index.html`
6. 用宝塔 Nginx 二进制重载配置并做 200 状态校验

### 针对当前服务器的关键坑

- **不要上传本机 `node_modules` 到服务器直接复用**：macOS/Windows 上的 `esbuild` 二进制在 Linux 服务器不可用。
- **不要用系统 `nginx -s reload` 替代宝塔 Nginx**：当前环境应使用 `/www/server/nginx/sbin/nginx -s reload -c /www/server/nginx/conf/nginx.conf`。
- **不要让站点根目录长期直接绑定 Vite 默认 `dist`**：宝塔生成的 `.user.ini` 会影响后续构建清理。
- **不要漏掉 SPA 路由回退**：admin / pc / h5 都是前端路由，直开 `/login` 这类路径时，Nginx 需要回退到 `index.html`，否则会直接返回 404。
