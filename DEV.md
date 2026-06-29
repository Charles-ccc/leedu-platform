# 本地开发环境（M0）

> 平台化改造的本地开发环境。后端 API 源码挂载进容器、改 PHP 即时生效；前端用 Vite dev server 热更新。
> 数据库/Redis/MeiliSearch 复用容器，数据持久化在 docker 卷里。
> 整体改造方案见 [PLATFORM_DESIGN.md](PLATFORM_DESIGN.md)。

---

## 一、前置：Docker 运行时（Colima）

本机用 **Colima** 跑 Docker（不是 Docker Desktop）。Colima 不开机自启，**每次重启电脑后**先启动它：

```bash
colima start          # 启动 docker 运行时
colima status         # 确认运行中
```

> docker / docker compose 命令依赖 colima 在运行，否则会报连不上 daemon。

---

## 二、启动开发栈

在项目根目录 `/Users/weihao/meEdu`：

```bash
# 启动全部（API 走源码挂载 + mysql/redis/meilisearch）
docker compose -f compose.yml -f compose.dev.yml up -d

# 查看状态 / 日志
docker compose ps
docker compose logs -f meedu
```

- `compose.dev.yml` 把本地 `./xyz.meedu.api` 挂到容器 `/var/www/api`，并开启 `APP_DEBUG`。
- 改 `xyz.meedu.api` 下的 PHP 代码**立即生效**，无需重启容器、无需重建镜像。
- 改了路由(`routes/*`)或 config 后，若没生效，清一下缓存（见下）。

**访问端口**
| 端口 | 用途 |
|------|------|
| http://localhost:8000 | API |
| http://localhost:8300 | 后台(容器内构建好的静态版, 非热更新) |
| http://localhost:8100 | PC 端(静态版) |
| http://localhost:8200 | H5 端(静态版) |

> 8100/8200/8300 是镜像里**构建好的前端**，改前端源码不会变；前端开发请用下面的 Vite dev server。

---

## 三、后端常用命令

```bash
# 进容器执行 artisan
docker compose exec meedu php artisan <命令>

# 清缓存(改路由/配置/视图后)
docker compose exec meedu php artisan route:clear
docker compose exec meedu php artisan config:clear
docker compose exec meedu php artisan cache:clear

# 跑迁移(新增 migration 后)
docker compose exec meedu php artisan migrate

# 连数据库
docker compose exec mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" meedu'
```

**后台登录**：http://localhost:8300 → 账号 `meedu@meedu.meedu` / 密码 `meedu123`（+ 图形验证码）。
首登要填访问地址：API `http://localhost:8000`、PC `http://localhost:8100`、H5 `http://localhost:8200`（末尾不加斜杠）。

---

## 四、前端 Vite dev server（热更新）

pnpm 通过 npm 全局安装，二进制在 `/Users/weihao/.hermes/node/bin`。把它加进 PATH 再用：

```bash
export PATH="/Users/weihao/.hermes/node/bin:$PATH"
pnpm -v   # 确认可用
```

前端 `.env`（`VITE_APP_URL=http://localhost:8000`，直连 API；API 已开放 CORS）。
Vite 端口避开容器占用的 8100/8200/8300，用 53xx。

> **注意**：用 `node_modules/.bin/vite` 直接启动，**不要用 `pnpm dev`**——本机 pnpm 在运行脚本前会做依赖校验、因"忽略构建脚本"报错而中断。直跑 vite 二进制不受影响（esbuild/swc 的原生二进制已随依赖装好）。

| 前端 | 目录 | 状态 | 启动命令 | 访问 |
|------|------|------|---------|------|
| 后台 admin | `xyz.meedu.admin` | ✅ 已装依赖+已建 .env | `node_modules/.bin/vite --port 5300 --host` | http://localhost:5300 |
| PC | `xyz.meedu.pc` | ✅ 已装依赖+已建 .env | `node_modules/.bin/vite --port 5100 --host` | http://localhost:5100 |
| H5 | `xyz.meedu.h5` | ✅ 已装依赖+已建 .env | `node_modules/.bin/vite --port 5200 --host` | http://localhost:5200 |

**admin（已就绪，直接启动）**
```bash
export PATH="/Users/weihao/.hermes/node/bin:$PATH"
cd xyz.meedu.admin
node_modules/.bin/vite --port 5300 --host
```

**pc / h5（首次需装依赖 + 建 .env）**
```bash
export PATH="/Users/weihao/.hermes/node/bin:$PATH"
cd xyz.meedu.pc      # 或 xyz.meedu.h5
printf 'VITE_APP_URL=http://localhost:8000\n' > .env
pnpm install                                   # 首次安装(较大)
node_modules/.bin/vite --port 5100 --host      # h5 用 5200
```

---

## 五、停止 / 清理

```bash
docker compose down                 # 停止容器(保留数据卷)
docker compose -f compose.yml -f compose.dev.yml down

# 彻底清空(连数据库数据一起删, 慎用)
docker compose down -v
```

---

## 六、环境内部说明（排障用）

- API 配置：`xyz.meedu.api/.env`（git 忽略）。`vendor/` 从镜像拷出（git 忽略），与 `composer.lock`(4.9.32) 完全一致。
- `public/storage` 是软链 → `storage/app/public`；`storage`、`bootstrap/cache` 需可写。
- 容器启动会自动跑 `meedu:upgrade`（迁移+同步配置+清缓存），幂等，不会丢数据。
- **MeiliSearch 容器在 colima 模拟环境下起不来**（`os error 38`），只影响站内搜索，核心功能正常。需要搜索再换镜像或用 Rosetta。
- 数据卷：`meedu_data_mysql` / `meedu_data_redis` / `meedu_data_meilisearch`。
