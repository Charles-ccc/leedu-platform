# MeEdu 平台化改造设计文档

> 把单商户网校（MeEdu 开源版）改造为**多机构入驻平台（淘宝模式）+ 平台级业务员拓客返点**体系。
> 版本：v1.4 ｜ 日期：2026-06-28

---

## 实施进度（截至 2026-06-28）

| 里程碑 | 状态 | 说明 |
|--------|------|------|
| M0 开发环境 | ✅ 完成 | 见 `DEV.md` |
| M1 多租户地基 | ✅ 完成 | merchant_id + 全局作用域 + 上下文中间件 |
| M2 机构后台/课程审核/入驻 | ✅ 完成 | 机构管理、机构管理员、课程审核、入驻申请审核 |
| M3 支付宝收单（每机构） | ✅ 代码完成 | 收单/回调按机构路由；真实凭证联调见 `ALIPAY_INTEGRATION.md` |
| M4 平台分成分账 | ✅ 代码完成 | 逐期计提平台分成；真实分账 API 待联调 |
| M5 芝麻先享 | ✅ 代码完成 | 分期配置/合同存证/签约两层存证/合同勾选页(pc+h5)；核身/签约/扣款待联调 |
| M6 业务员返点 | ✅ 完成 | 计提/机构付/退款冲正/改派/提现 全闭环 |

**质量门**：三端前端 `tsc && vite build` 生产构建全部通过；安全审查（`/security-review`）未发现高/中危可利用漏洞。
**部署**：见 `DEPLOY.md`（用仓库 Dockerfile 构建自定义镜像，不是官方预构建镜像）。
**联调**：见 `ALIPAY_INTEGRATION.md`（所有 `TODO(联调)` 点 + 所需支付宝接口）。

---

## 0. 关键架构决策（已拍板）

| # | 决策 | 选择 |
|---|------|------|
| 1 | 多商户数据隔离 | **共享库 + `merchant_id` 行级隔离** |
| 2 | 支付通道 | **仅支付宝**，支持「一次性付款」+「芝麻先享（分期多次扣款）」；芝麻先享下单前须**实名核身 + 代扣签约**（签约归属 用户×机构） |
| 3 | 收单主体 | **机构各自签约支付宝商户号收单**，钱先进机构账户 |
| 4 | 平台分成 | 走**支付宝原生分账**，**按每期实付逐期分账**给平台；比例**每机构单独约定** |
| 5 | 业务员提成 | **下单即计提**（按订单成交额×比例），由**订单所属机构直接支付**给业务员；比例**每机构一个**；退款则冲正（追回+下期扣抵） |
| 6 | 业务员归属 | **平台级业务员**；客户绑定**永久**，业务员离职后后台可**改派客户**给其他业务员 |
| 7 | 课程上下架 | 机构提交上架 → **平台审核**通过才在客户端展示；下架仅停展示，**已购学员可继续学习** |
| 8 | 第一阶段交付 | **先出设计文档**（即本文件），评审通过再动代码 |

---

## 1. 目标与范围

**做什么**
- 多个**机构**入驻平台，各自上架课程、管理学员、用自己的支付宝商户号收款。
- 平台方有**总后台**：审核机构、审核课程、配各机构分成/提成比例、管业务员、对账、看全局数据。
- 机构方有**机构后台**：只看/管自己机构的课程、订单、学员（数据隔离）；设置课程分期与价格；支付业务员提成。
- **平台级业务员**：拉客户下单，成单后由订单所属机构按比例直接付提成。
- 收款后**平台分成**经支付宝逐期分账自动到账。

**不做什么（本期）**
- 不做平台统一收银台、不做跨机构购物车（决策 3：机构各自收单）。
- 不动直播 / 考试 / 电子书等商业版功能（开源版没有）。

---

## 2. MeEdu 现状评估（基于源码勘察）

| 维度 | 现状 | 改造需求 |
|------|------|---------|
| 课程归属 | `courses.user_id`（讲师），无机构维度；有 `is_show`、`published_at` | 加 `merchant_id`、**审核状态**、分期配置 |
| 订单 | `orders(user_id, goods_id, goods_type, charge, status)` | 加 `merchant_id`、支付宝商户路由、**分期扣款**、平台分账记录 |
| 管理后台 | 单一 `administrators` + 角色权限体系 | 扩成「平台管理员 / 机构管理员」两层 |
| 学员 | `users` 平台级；有 `user_course` 已购关系 | **保持平台级**；下架不影响 `user_course` 访问 |
| 返佣 | 已有邀请返佣雏形：`invite_user_id`、`invite_balance`、`user_invite_balance_records`、`promo_codes` | 业务员提成复用「绑定关系 + 流水」思路扩建 |
| 支付 | 单一平台支付配置 | 改为**每机构独立支付宝配置 + 平台分账** |

---

## 3. 角色与整体架构

```
┌─────────────────────────────────────────────────────────┐
│                        平台 (Platform)                    │
│  平台管理员 ── 总后台 ── 审核机构 / 审核课程 / 配比例       │
│                          / 业务员管理 / 对账               │
│  业务员    ── 拉客户下单 ── 成单后由机构直接付提成          │
│  平台支付宝账户 ◀── 逐期分账(平台分成)                     │
└───────────────┬───────────────────────┬─────────────────┘
                │ merchant_id 隔离        │
        ┌───────▼───────┐        ┌───────▼───────┐
        │   机构 A       │        │   机构 B       │
        │ 机构后台/管理员 │        │ 机构后台/管理员 │
        │ 自有支付宝商户号│        │ 自有支付宝商户号│  ← 收单主体
        │ 课程(待审核/上架)│       │ 课程(待审核/上架)│
        └───────┬───────┘        └───────┬───────┘
                │ 每期扣款成功 → 支付宝分账平台分成
                │ 成单 → 直接支付业务员提成
        ┌───────▼────────────────────────▼───────┐
        │           学员 (平台级 users)            │
        │  PC / H5 浏览(仅审核通过课程)、下单、学习  │
        └─────────────────────────────────────────┘
```

---

## 4. 多租户隔离设计（核心）

### 4.1 隔离机制
- 核心业务表新增 `merchant_id`（`0` = 平台自营 / 全局）。
- Laravel **Global Scope**（`MerchantScope`）：机构管理员请求自动按当前机构过滤；平台管理员可跨机构。
- 请求级 `MerchantContext` 单例：从登录态（机构管理员 token 携带 merchant_id）解析当前机构。

### 4.2 两层管理员
**复用现有 `administrators` 表，加 `merchant_id`**（`0`=平台管理员，>0=机构管理员），角色/权限/菜单沿用；机构管理员只拿机构级权限子集 + 数据作用域过滤，接口与菜单双重校验，绝不触达平台级。

### 4.3 需要加 `merchant_id` 的表（初步清单）
- 课程域：`courses`、`course_categories`、`course_chapter`、`course_comments`、`course_attach`、`user_course`、`user_video`、`course_user_records`
- 交易域：`orders`、`order_goods`、`order_paid_records`、`order_refund`、`promo_codes`
- 记录域：`user_watch_stat`、`administrator_logs`、`admin_logs`
- **不加**：`users`、`user_profiles`（平台级）。

---

## 5. 数据模型变更

### 5.1 新增表

**`merchants`（机构）**
```
id, name, slug, logo, intro,
contact_name, contact_mobile,
status                       -- 0待审核 1正常 2禁用 3已驳回
audit_remark,
owner_admin_id               -- 机构主账号(administrators.id)
platform_share_rate          -- 平台分成比例(每机构单独约定, 逐期分账用)
salesperson_commission_rate  -- 业务员提成比例(每机构一个, 成单计提用)
referrer_salesperson_id      -- 拉它入驻的业务员(可选)
created_at, updated_at, deleted_at
```

**`merchant_alipay_configs`（机构支付宝收单+分账配置）**
```
id, merchant_id,
app_id, alipay_public_key, app_private_key,   -- 机构支付宝应用密钥(加密存储)
seller_id / pid,                              -- 机构商户号
zhima_enabled,                                -- 是否开通芝麻先享
is_platform_settle_bound                      -- 与平台的分账关系是否已绑定
```

**`salespeople`（业务员，平台级）**
```
id, name, mobile, account, password,
invite_code,                 -- 专属推广码/链接
alipay_account,              -- 收提成的支付宝
status,                      -- 1在职 2离职(离职后其客户可被改派)
created_at, updated_at, deleted_at
```

**`salesperson_user_relations`（业务员↔客户绑定，永久，可改派）**
```
id, salesperson_id, user_id,
bound_at,
is_active,                   -- 改派时旧关系置失效
reassigned_from,             -- 改派来源业务员(留痕)
created_at, updated_at
```

**`commission_records`（业务员提成流水，下单即计提，机构直付）**
```
id, salesperson_id, merchant_id, order_id,
base_amount,                 -- 计提基数(订单成交额)
rate, amount,                -- 比例 / 提成金额(冲正为负数)
record_type,                 -- 1正向计提 2退款冲正(负向)
ref_record_id,               -- 冲正时指向原计提记录
pay_status,                  -- 0待机构支付 1机构已支付
paid_at,
clawback_status,             -- 冲正记录用: 0待处理 1已追回 2已下期扣抵 3部分
remark, created_at
```

**`agreement_templates`（协议模板，按类型+版本管理）** — 沿用 meedu `AgreementService` 思路
```
id, type,                        -- course_sale(网课买卖合同) / zhima_withhold(芝麻先享代扣合同)
merchant_id,                     -- 0=平台通用模板; >0=机构自定义(网课买卖合同常按机构)
version,                         -- 协议版本号
title, content,                  -- 协议文本
content_hash,                    -- 文本 MD5/Hash(与签约/同意存证比对)
is_active, effective_at, created_at
```

**`user_contract_consents`（先学后付下单时合同阅读勾选存证，append-only）**
```
id, user_id, order_id, merchant_id,
agreement_type,                  -- course_sale / zhima_withhold
agreement_version, agreement_hash,
consented_at,                    -- 勾选同意时间
consent_ip, consent_ua,          -- 设备信息
created_at
```
> 先学后付下单展示的两份合同，各写一条同意记录，只增不改。与下文签约存证 `zhima_sign_events` 串成完整证据链。

**`user_zhima_signings`（芝麻先享 实名核身 + 代扣签约：当前有效态）** — 归属 用户×机构
```
id, user_id, merchant_id,        -- 因机构各自收单, 签约绑定到该机构的支付宝应用
alipay_open_id,                  -- 支付宝 open_id
real_name, cert_no_enc,          -- 实名信息(加密存储)
verify_status,                   -- 核身: 0未核 1通过 2失败
verify_channel,                  -- 核身方式(支付宝刷脸/身份核验)
verified_at,
agreement_no,                    -- 当前有效的支付宝代扣签约协议号
sign_status,                     -- 签约: 0未签 1已签 2已解约 3已失效
signed_at, expired_at,
created_at, updated_at
```
> 同一用户在不同机构下芝麻先享单，需各自完成核身+签约（协议属于各机构的支付宝应用）。

**`zhima_sign_events`（签约成功回调存证，append-only 不覆盖）** — 合规举证用
```
id, user_id, alipay_open_id,
merchant_id,
agreement_no,                    -- 本次签约协议号
agreement_version,               -- 协议版本号
agreement_hash,                  -- 协议文本 MD5/Hash(锁定当时签的是哪版文本)
signed_at,                       -- 签约时间戳(支付宝回调时间)
sign_ip, sign_ua,                -- 设备信息: IP、User-Agent
raw_callback,                    -- 支付宝原始回调报文(留存)
created_at
```
> 每次签约成功回调写一条，**只增不改**。`user_zhima_signings` 记当前有效态，`zhima_sign_events` 记历史留痕，解约/重签都各留一条。

**`order_installments`（订单分期/扣款计划）** — 一次性=1期，芝麻先享=多期
```
id, order_id, merchant_id,
period_no, amount,
plan_charge_at,              -- 计划扣款时间
status,                      -- 0待扣 1已扣 2扣款失败(催扣) 3已退款
retry_count,                 -- 催扣次数
alipay_trade_no, charged_at, created_at
```

**`platform_share_records`（平台分成分账记录，逐期）**
```
id, order_id, order_installment_id, merchant_id,
rate, amount,                -- 该期平台分成
alipay_settle_no,            -- 支付宝分账明细号
status,                      -- 0待分账 1成功 2失败 3已回退(退款)
created_at
```

### 5.2 改动表
- `administrators`：+ `merchant_id`（0=平台）。
- §4.3 所有核心表：+ `merchant_id`（带索引）。
- `courses`：+ `merchant_id`、+ `audit_status`（0待审核 1通过 2驳回）、+ `audit_remark`、+ `installment_enabled`（是否支持芝麻先享分期）、+ `installment_periods`（期数）、+ `installment_cycle_days`（每期周期）。
  - 客户端展示条件：`audit_status=1 且 is_show=显示`。下架=`is_show` 改隐藏，**不影响 `user_course` 已购学员访问**。
- `orders`：+ `merchant_id`、+ `pay_type`（一次性/芝麻先享）、+ `total_periods`、+ `bound_salesperson_id`（下单时绑定的业务员，决定提成归属与承担机构）、+ `zhima_agreement_no`（芝麻先享订单关联的代扣签约协议号，逐期扣款时据此代扣）。
- `users`：复用 `invite_user_id`。

> 全部用**增量 migration** 新增，不改历史 migration，保证可回滚、不破坏 `meedu:upgrade`。

---

## 6. 核心流程

### 6.1 机构入驻
1. 机构提交申请 + 资质 → 平台审核 → 通过建 `merchants` + 机构主账号 + 配 `platform_share_rate` / `salesperson_commission_rate`。
2. 机构录入自有支付宝密钥/商户号（`merchant_alipay_configs`）→ 与平台完成分账关系绑定。

### 6.2 课程上架审核
1. 机构后台创建/编辑课程，设置价格、是否支持芝麻先享及期数/周期 → 提交上架（`audit_status=待审核`）。
2. 平台审核：通过 `audit_status=通过`（满足 `is_show` 即客户端展示）；驳回 `audit_status=驳回` + `audit_remark`。
3. 下架：机构/平台将 `is_show` 置隐藏，客户端不再展示；**已购学员经 `user_course` 仍可学习**。

### 6.3 下单与扣款
- 学员下单 → 订单带 `merchant_id`、`bound_salesperson_id`（若该客户有绑定业务员）→ **下单即计提业务员提成**（见 §7.2）。
- 一次性付款：`order_installments` 1 期，付成功即扣。
- 芝麻先享：下单前先过 §6.4 **实名核身 + 代扣签约**门槛；信用达标先开课，`order_installments` 多期，按 `plan_charge_at` 用 `zhima_agreement_no` 代扣逐期扣款；**扣款失败进催扣**（重试 + 通知，超限策略见 §11）。

### 6.4 芝麻先享：合同确认 + 实名核身 + 代扣签约（前置门槛）
1. **合同展示与勾选**：用户选「芝麻先享（先学后付）」下单 → 展示两份合同：**网课买卖合同**（`course_sale`，取该机构当前有效版本）+ **芝麻先享代扣合同**（`zhima_withhold`）。用户**阅读并勾选同意**后才能「下一步」。
   - 勾选同意 → 各写一条 `user_contract_consents`（用户、订单、合同类型/版本/`agreement_hash`、时间、IP/UA），未勾选不可继续。
2. 检查该 用户×机构 是否已有有效签约（`user_zhima_signings.sign_status=已签` 且未过期）。
3. 未签约 → 走支付宝侧 **实名核身**（刷脸/身份核验）→ 通过后发起**代扣协议签约**。
   - **签约成功回调**时，后端固化存证：写一条 `zhima_sign_events`（用户 UID + `alipay_open_id`、签约时间戳、协议版本号 + 协议文本 Hash、`agreement_no`、设备信息 IP/UA、原始回调报文），并更新/落地 `user_zhima_signings` 当前有效态。
4. 核身或签约失败 → 阻断，不允许用芝麻先享下单（可引导改一次性付款）。
5. 已签约 → 直接放行下单，订单记 `zhima_agreement_no`，后续逐期扣款据此代扣。
6. 解约/协议失效 → 该用户在该机构需重新核身签约后才能再用芝麻先享。
> 证据链：`user_contract_consents`（合同同意）→ `zhima_sign_events`（代扣签约）→ `orders`（订单）→ `order_installments`（逐期扣款）。

---

## 7. 资金：分账与提成

### 7.1 平台分成（逐期，支付宝分账）
- **每一期扣款成功**后：平台分成 = 该期实付 × `merchants.platform_share_rate`，调支付宝分账到平台账户，写 `platform_share_records`。

### 7.2 业务员提成（下单即计提，机构直付）
- **下单时**（订单合约成立，不等扣款）一次性计提：提成 = **订单成交额** × `merchants.salesperson_commission_rate`（仅当 `bound_salesperson_id` 非空），写 `commission_records(record_type=正向, pay_status=待机构支付)`。
- 由**订单所属机构**直接支付给业务员；机构在机构后台对待付提成发起支付，平台标记 `pay_status=已支付`，用于对账。
- 提成**不走逐期分账**，与平台分成相互独立。

### 7.3 退款 / 冲正
- 平台分成已分账后退款 → 支付宝**分账回退**，`platform_share_records` 置「已回退」。
- 业务员提成退款 → 生成一条**负向冲正记录**（`record_type=退款冲正`，`amount` 为负，`ref_record_id` 指向原计提）。冲正按**「追回 + 下期扣抵」同时进行**：
  - 平台向业务员发起**追回**该笔已付/应付提成；
  - 同时该负向金额进入业务员**下期提成结算自动扣抵**，扣抵后 `clawback_status` 相应更新；
  - 两条腿并行，谁先到位即结清，避免重复扣。
- 分期：按已扣期数处理退款（提成已按订单成交额全额计提，部分退款按退款比例冲正）。

### 7.4 客户改派（业务员离职）
- 平台后台把离职业务员名下客户 `salesperson_user_relations` 批量改派给新业务员：旧关系 `is_active=0`，新建生效关系并记 `reassigned_from`。
- **改派只影响之后新订单的提成归属；历史已计提的 `commission_records` 不变。**

---

## 8. 前端改造概览

| 前端 | 改造 |
|------|------|
| `admin`（总后台） | 机构审核/管理、**课程审核**、配各机构分成与提成比例、业务员管理、客户改派、提成/分账对账、全局统计 |
| `admin`（机构后台） | 自己机构的课程（含分期/价格设置、提交审核）、订单、学员、支付宝配置；**支付待付业务员提成** |
| `pc` / `h5`（学员端） | 机构主页、按机构浏览（仅展示审核通过课程）；下单走对应机构支付宝；**先学后付下单页：展示网课买卖合同+芝麻代扣合同、阅读勾选后下一步**；业务员推广码落地绑定 |
| `admin`（机构/总后台） | 协议模板管理（`agreement_templates`：网课买卖合同按机构维护、版本化）；合同同意/签约存证查询 |

> 总后台与机构后台先用同一套 admin 代码 + 角色路由控制，跑通后再评估是否拆分。

---

## 9. 分阶段路线图（里程碑）

| 阶段 | 内容 | 验收 |
|------|------|------|
| **M0** | 开发环境（源码挂载 + 前端 dev server，复用现有 MySQL/Redis） | 改代码即时生效 |
| **M1** | 多租户地基：`merchants`、`administrators.merchant_id`、`MerchantScope`、核心表加 `merchant_id`、历史数据归入默认机构 | 机构 A 看不到机构 B |
| **M2** | 机构后台 + 课程审核：机构管理员登录、课程隔离管理、提交上架、平台审核、客户端只展示通过课程 | 机构上架→平台审核→学员可见 |
| **M3** | 支付宝收单：每机构 `merchant_alipay_configs` + `AlipayClientResolver` + 一次性付款 + `order_installments` | 下单走机构自己支付宝，钱进机构账户 |
| **M4** | 平台分成逐期分账：`platform_share_records` + 退款分账回退 | 每期扣款自动分平台分成 |
| **M5** | 芝麻先享：协议模板管理 + 下单合同展示勾选存证 + 实名核身+代扣签约门槛 + 课程分期配置 + 多期扣款 + 催扣 + 逐期分账 | 用户阅读勾选合同→核身签约→信用购订单分期扣款并分成 |
| **M6** | 业务员体系：账号、客户绑定/改派、成单提成计提、机构支付提成、对账 | 业务员拉客户成单→机构付提成 |
| **M7**（可选） | 入驻提成、多级提成、数据看板、机构保证金 | 进阶 |

> M3/M4/M5 强依赖支付宝资质与联调。

---

## 10. 风险与注意点

1. **数据隔离漏网**：任一查询漏带 `merchant_id` 即串数据/越权。Global Scope 兜底 + 关键接口测试。
2. **支付宝资质**：每机构需自有支付宝应用、开通分账、芝麻先享需信用产品权限——入驻门槛高，入驻流程中校验。
3. **提成对账可信度**：提成由机构直付、平台仅**记录督促**（本期不做保证金/信用分）。平台后台需提供待付提成清单、督促提醒与对账报表；机构长期拖欠的处置（停业务员合作/降权）留待二期。
4. **分期 + 分账状态机**：每期扣款成功才分平台分成；扣款失败催扣、退款、分账回退需完整状态机。
5. **密钥安全**：机构支付宝私钥加密存储（用 `APP_KEY` 加密字段）；芝麻先享实名信息 `cert_no_enc` 同样加密，遵守个人信息合规。
6. **核身/签约合规与存证**：芝麻先享代扣签约属各机构支付宝应用，签约绑定 用户×机构；需机构开通信用产品权限。解约/失效后须重新核身签约。签约成功回调须**固化存证**（协议版本+文本 Hash + open_id + 时间戳 + IP/UA + 原始报文）并 append-only 保存，用于日后举证；协议文本按版本归档，Hash 必须能复核。
7. **历史数据迁移**：现有课程/订单归入「平台自营默认机构」（固定 merchant_id）；历史课程默认 `audit_status=通过`。
8. **权限边界**：机构管理员绝不能拿到平台级权限。
9. **升级兼容**：全部增量 migration，不破坏 `meedu:upgrade`。

---

## 11. 已确认 / 待确认

**已确认（v1.0 定稿）**
- ✅ 成单时点：**下单即计提**提成。
- ✅ 提成退款冲正：**追回 + 下期扣抵 同时进行**。
- ✅ 提成对账保障：**仅记录督促**（本期不做保证金/信用分）。

**仍待确认（不阻塞 M0~M2，编码到对应阶段前敲定即可）**
- [ ] **芝麻先享催扣**：失败重试次数与间隔？超限后停课 / 转催收 / 违约上报芝麻？（M5 前定）
- [ ] **业务员提成支付通道**：机构线下转账后平台标记，还是机构经平台发起支付宝转账给业务员？（M6 前定）
- [ ] **入驻提成**：业务员拉机构入驻是否给一次性提成、本期是否做？（M6 前定）
- [ ] **机构后台与平台后台**：一期同框架按角色裁剪（推荐）还是拆两套独立站点？（M2 前定）
- [ ] **退款政策**：分期订单中途退课规则、可退期数？（M5 前定）

---

*设计定稿。可从 M0（开发环境）开始落地。*
