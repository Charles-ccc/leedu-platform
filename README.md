<h1 align="center">Leedu Platform</h1>

<h4 align="center">
  面向商用落地的网校系统二次开发版本（Fork 自上游开源项目）
</h4>

<p align="center">⚡ 基于 PHP+Laravel 开发的在线网校解决方案 🔍</p>

**Leedu** 是一款基于 PHP7.4 + Laravel8 + MySQL + Redis 的前后端分离网校系统，支持 PC / H5 / Admin 多端。

## 项目声明

- 本仓库是基于上游开源项目 Fork 后的业务化改造版本。
- 本仓库中的 `Leedu` 为本项目内部命名，不代表对上游商标、品牌或商业权益的继承。
- 如需对外商用，请先由法务确认上游许可证、附加条款、商标与二开分发义务。

## 仓库地址

```bash
https://github.com/Charles-ccc/leedu-platform
```

## 🚀 快速上手

拉取代码：

```
git clone --branch main https://github.com/Charles-ccc/leedu-platform.git leedu-platform
```

运行(分 3 步):

**① 进入目录并复制环境配置**

```
cd leedu-platform
cp .env.example .env          # Windows: 改为 copy .env.example .env
```

**② 编辑 `.env`,把 `APP_KEY=` 和 `JWT_SECRET=` 两行都填上随机密钥**

> `APP_KEY` 是 Laravel 全应用对称加密密钥(Cookie/Session/加密字段等);`JWT_SECRET` 是 JWT 签名密钥。两者**都必须自行生成且保密**,留空或使用公开示例值会导致 Cookie 可被解密、Token 可被伪造,出现未授权访问风险。

**生成 `APP_KEY`**(任选其一,必须是 `base64:<32 字节 base64>` 格式):

```
# macOS / Linux
echo "base64:$(openssl rand -base64 32)"

# Windows PowerShell
$b=New-Object byte[] 32;[Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($b);"base64:"+[Convert]::ToBase64String($b)
```

**生成 `JWT_SECRET`**(任选其一):

```
# macOS / Linux
openssl rand -base64 48

# Windows PowerShell
$b=New-Object byte[] 48;[Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($b);[Convert]::ToBase64String($b)
```

将输出分别粘贴到 `.env` 中对应行后面(等号后无空格),例如:

```
APP_KEY=base64:7tQp...(你生成的字符串)
JWT_SECRET=hVZ8b2pK...(你生成的字符串)
```

**③ 启动容器**

```
docker-compose up -d
```

等待 `30s` 左右。现在打开您的浏览器，输入 `http://localhost:8300` 即可访问后台管理界面。

- PC 端口 `http://localhost:8100`
- H5 端口 `http://localhost:8200`
- API 端口 `http://localhost:8000`

## 🔰️ 软件安全

安全问题请通过私有渠道报告给项目维护者。建议使用专用安全邮箱（例如：`security@your-domain.com`）并在 24 小时内响应。

## 📃 使用许可

- 本项目沿用上游许可证分发，请以仓库内 `LICENSE` 与 [附件条款和条件](ADDITIONAL_TERMS.md) 为准。
