# 支付宝 / 芝麻信用 联调清单

> 平台化改造（M3/M4/M5）中所有"需真实凭证才能跑通"的点，按区域列出：代码位置、需调用的支付宝接口、入参、联调后要补的逻辑。
> 代码框架、数据模型、存证、分账/提成计算都已就位，联调 = 把各处 `TODO(联调)` 换成真实接口调用。
> 配套设计见 [PLATFORM_DESIGN.md](PLATFORM_DESIGN.md)。

---

## 0. 前置：需要准备的凭证 / 资质

### 每个入驻机构（在「机构后台 → 支付宝配置」填写，已加密存储）
- 支付宝开放平台**应用 APPID**
- **应用私钥**（`app_private_key`）
- **支付宝公钥** / **应用公钥证书** / **支付宝根证书**（证书模式）
- **商户号 PID**（`seller_id`）
- 开通产品权限：
  - **电脑网站支付 / 手机网站支付**（收单）
  - **分账（分润）**（平台分成、业务员提成分账用）
  - **芝麻先享 / 信用购**（先学后付）
  - **周期扣款 / 代扣协议**（分期扣款用）

### 平台方
- 作为**分账接收方**的支付宝账户（收平台分成）
- 与各机构建立**分账关系绑定**（`alipay.trade.royalty.relation.bind`）
- 关注**分账方比例 / 数量上限**合规（影响"业务员提成是否能直接分账到个人"）

---

## 1. M3 收单：下单走机构支付宝

| 项 | 内容 |
|----|------|
| 现状 | 配置解析、按订单机构路由、回调按机构验签**已接好**；H5(wap)支付已走 resolver |
| 代码 | `app/Meedu/Merchant/AlipayClientResolver.php`（构建 yansongda 配置）<br>`app/Bus/UniPayBus.php::createAlipayH5OrderWithCache`（`Pay::alipay($config)->wap(...)`）<br>`app/Http/Controllers/Frontend/PaymentController.php::index/callback` |
| 联调要点 | 1. 机构填真实凭证后，下单即用其商户号收款（钱进机构账户）。<br>2. **PC 电脑网站支付**：目前只接了 `wap`，如需 PC 端跳转支付宝收银台，补 `Pay::alipay($config)->web([...])`（同样用 resolver 取配置）。<br>3. 回调验签已按订单 `merchant_id` 取配置，真实 notify 即可生效。 |
| 验证 | 用机构真实凭证下一单 → 跳支付宝 → 支付 → 回调 → 订单完成 + 生成 `order_installments`(1期已扣) |

---

## 2. M3 退款：按机构配置退款（待补）

| 项 | 内容 |
|----|------|
| 现状 | meedu 原退款 `app/Meedu/Payment/Alipay/AlipayRefund.php` 仍用**平台配置**，未按机构 |
| 联调要点 | 把退款也改用 `AlipayClientResolver::configForMerchant($order['merchant_id'])` 取配置再退款（参考 PaymentController 的改法）。 |
| 支付宝接口 | `alipay.trade.refund`（yansongda `->refund()`） |

---

## 3. M4 平台分成分账

| 项 | 内容 |
|----|------|
| 现状 | 每期扣款成功 → 已按机构 `platform_share_rate` 生成 `platform_share_records`(status=待分账)；**真实分账调用留 TODO** |
| 代码 | `app/Meedu/Merchant/ProfitShareService.php::settleInstallment`（`// TODO(联调): 调用支付宝分账API`） |
| 支付宝接口 | **分账**：`alipay.trade.order.settle`（对已收单交易发起分账，把平台分成分到平台账户）<br>前置：`alipay.trade.royalty.relation.bind`（机构与平台分账关系绑定） |
| 入参 | `out_request_no`、`trade_no`(该期扣款的支付宝交易号 `order_installments.alipay_trade_no`)、`royalty_parameters`[{trans_in=平台账户, amount=`platform_share_records.amount`/100}] |
| 联调后补 | 调用成功 → `$record->update(['status'=>SUCCESS,'alipay_settle_no'=>$明细号])`；失败置 FAILED 重试 |
| 退款回退 | 退款时对已分账做**分账回退** `alipay.trade.order.settle`(reverse) / `alipay.trade.refund` 带 `refund_royalty_parameters`，并把 `platform_share_records` 置「已回退」 |

---

## 4. M5 芝麻先享（先学后付）

### 4.1 课程配置（已就位）
机构后台课程表单可设「先学后付开关 / 分期期数 / 每期周期」→ `courses.installment_enabled/periods/cycle_days`。

### 4.2 合同勾选存证（已就位，不依赖凭证）
学员端 pc/h5「先学后付」→ 展示网课买卖+芝麻代扣合同 → 勾选 → `POST /api/v3/zhima/consent` 存证。
- 代码：`app/Http/Controllers/Api/V3/ZhimaController.php`、`ZhimaService::recordConsent`

### 4.3 实名核身（待联调）
| 项 | 内容 |
|----|------|
| 支付宝接口 | 身份核验/刷脸：`alipay.user.certify.open.initialize` → `.certify`(跳转) → `alipay.user.certify.open.query`；或走芝麻认证 |
| 联调后补 | 核身通过 → 写 `user_zhima_signings.verify_status=1` |

### 4.4 代扣签约（框架+存证已就位，签约调用待联调）
| 项 | 内容 |
|----|------|
| 支付宝接口 | **周期扣款/代扣协议签约**：`alipay.user.agreement.page.sign`（跳转签约）→ 回调拿 `agreement_no` |
| 代码 | 回调里调 `ZhimaService::recordSigning([... user_id, merchant_id, alipay_open_id, agreement_no, agreement_version, agreement_hash, ip, ua, raw ...])` —— **两层存证已写好**（`user_zhima_signings` 当前态 + `zhima_sign_events` append-only） |
| 注意 | 签约绑定到**该机构的支付宝应用**；同一用户在不同机构需各自签约（表已按 用户×机构 唯一） |

### 4.5 下单建多期 + 逐期扣款（待联调）
| 项 | 内容 |
|----|------|
| 建多期 | 芝麻先享下单时调 `ZhimaService::createInstallmentsForOrder($orderId,$merchantId,$total,$periods,$cycleDays)`（**已实现**，按课程配置生成 N 期 `order_installments`，首期 now、其后按周期排期）。需在下单流程里：判定 `pay_type=zhima` + 已核身签约(`hasValidSigning`) + 课程 `installment_enabled` 后调用。 |
| 逐期扣款 | **缺定时任务**：扫 `order_installments`(status=待扣 且 plan_charge_at<=now) → 用签约 `agreement_no` 代扣 |
| 支付宝接口 | 代扣：`alipay.trade.pay`（`product_code=GENERAL_WITHHOLDING` + `agreement_params.agreement_no`） |
| 扣款成功后补 | `order_installment` 置已扣 + 记 `alipay_trade_no` + `charged_at`；**触发该期平台分成分账**（调 §3）；首期成功即"成单"已在 §M6 计提提成 |
| 扣款失败 | 置 `status=2失败` + `retry_count++`，催扣（重试间隔/超限策略见 PLATFORM_DESIGN §11） |
| 建议实现 | 新增 `php artisan` 命令 + 计划任务（参考现有 `app/Console/Commands`），如 `meedu:zhima:withhold` 每日跑 |

---

## 5. M6 业务员提成（已完整，仅可选优化）

| 项 | 内容 |
|----|------|
| 现状 | 下单即计提、机构标记已付→计入业务员余额、退款冲正、改派、提现**全部已实现**（DB 层闭环，不依赖凭证） |
| 可选联调 | 若希望"业务员提成直接通过支付宝分账到个人"（而非平台代收/手动打款）：在每期扣款分账(§3)里增加一个分账方=业务员账户。**受分账方比例/数量上限限制**，需评估合规；默认走现有"机构标记已付 + 提现"兜底即可 |

---

## 6. 建议联调顺序

1. **一家机构填真实支付宝凭证** → 跑通 §1 收单（一次性付款）+ §2 退款。
2. **§3 平台分成分账** → 一次性订单扣款后自动分平台分成。
3. **§4 芝麻先享**：核身 → 签约（§4.3/4.4）→ 下单建多期（§4.5）→ 定时代扣 → 逐期分账。
4. **§5 业务员直分**（可选）。

> 每步都已有数据落库与状态机，联调主要是"把真实接口调用接上 + 成功后更新状态"。

---

## 附：相关代码位置速查

| 功能 | 文件 |
|------|------|
| 机构支付宝配置(加密) | `app/Meedu/ServiceV2/Models/MerchantAlipayConfig.php`、`MerchantAlipayController.php` |
| 配置解析器 | `app/Meedu/Merchant/AlipayClientResolver.php` |
| 收单发起 | `app/Bus/UniPayBus.php` |
| 支付回调 | `app/Http/Controllers/Frontend/PaymentController.php` |
| 分期模型 | `app/Meedu/ServiceV2/Models/OrderInstallment.php` |
| 平台分成 | `app/Meedu/Merchant/ProfitShareService.php`、`PlatformShareRecord.php` |
| 芝麻服务 | `app/Meedu/Merchant/ZhimaService.php` |
| 签约存证 | `UserZhimaSigning.php`(当前态)、`ZhimaSignEvent.php`(留痕) |
| 合同同意存证 | `UserContractConsent.php` |
| 提成 | `app/Meedu/Merchant/CommissionService.php`、`CommissionRecord.php` |
| 退款冲正 | `app/Listeners/OrderRefundProcessed/CommissionClawbackListener.php` |
