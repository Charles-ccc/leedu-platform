import client from "./internal/httpClient";

// 平台化 M2：机构(商户)管理接口（仅平台管理员可用）

export function merchantList(params: any) {
  return client.get(`/backend/api/v2/merchant`, params);
}

export function merchantStore(params: any) {
  return client.post(`/backend/api/v2/merchant`, params);
}

export function merchantDetail(id: number) {
  return client.get(`/backend/api/v2/merchant/${id}`, {});
}

export function merchantUpdate(id: number, params: any) {
  return client.put(`/backend/api/v2/merchant/${id}`, params);
}

// 机构管理员账号
export function merchantAdmins(id: number) {
  return client.get(`/backend/api/v2/merchant/${id}/admins`, {});
}

export function merchantStoreAdmin(id: number, params: any) {
  return client.post(`/backend/api/v2/merchant/${id}/admins`, params);
}

// 机构自助:本机构支付宝配置
export function merchantAlipayGet() {
  return client.get(`/backend/api/v2/merchant-alipay`, {});
}

export function merchantAlipaySave(params: any) {
  return client.post(`/backend/api/v2/merchant-alipay`, params);
}

// 入驻申请(公开) + 审核
export function merchantApply(params: any) {
  return client.post(`/backend/api/v2/merchant/apply`, params);
}

export function merchantAudit(id: number, params: any) {
  return client.post(`/backend/api/v2/merchant/${id}/audit`, params);
}

// 平台分成分账记录
export function profitShareList(params: any) {
  return client.get(`/backend/api/v2/profit-share`, params);
}

// 业务员 + 提成
export function salespersonList(params: any) {
  return client.get(`/backend/api/v2/salesperson`, params);
}
export function salespersonStore(params: any) {
  return client.post(`/backend/api/v2/salesperson`, params);
}
export function salespersonUpdate(id: number, params: any) {
  return client.put(`/backend/api/v2/salesperson/${id}`, params);
}
export function salespersonBind(id: number, params: any) {
  return client.post(`/backend/api/v2/salesperson/${id}/bind`, params);
}
export function commissionList(params: any) {
  return client.get(`/backend/api/v2/salesperson/commissions`, params);
}
export function salespersonReassign(params: any) {
  return client.post(`/backend/api/v2/salesperson/reassign`, params);
}
export function withdrawalList(params: any) {
  return client.get(`/backend/api/v2/salesperson/withdrawals`, params);
}
export function withdrawalCreate(params: any) {
  return client.post(`/backend/api/v2/salesperson/withdrawals`, params);
}
export function withdrawalProcess(id: number, params: any) {
  return client.post(`/backend/api/v2/salesperson/withdrawals/${id}/process`, params);
}

// 协议模板(芝麻先享合同)
export function agreementTemplateList(params: any) {
  return client.get(`/backend/api/v2/agreement-template`, params);
}
export function agreementTemplateStore(params: any) {
  return client.post(`/backend/api/v2/agreement-template`, params);
}

// 机构端:本机构待付提成
export function merchantCommissionList(params: any) {
  return client.get(`/backend/api/v2/merchant-commission`, params);
}
export function merchantCommissionPay(id: number) {
  return client.post(`/backend/api/v2/merchant-commission/${id}/pay`, {});
}

// 课程审核
export function courseAuditPending(params: any) {
  return client.get(`/backend/api/v2/course-audit/pending`, params);
}

export function courseAudit(id: number, params: any) {
  return client.post(`/backend/api/v2/course-audit/${id}/audit`, params);
}
