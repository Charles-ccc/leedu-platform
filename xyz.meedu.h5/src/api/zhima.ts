import client from "./internal/httpClient";

// 平台化 M5：芝麻先享下单合同
export function zhimaContracts(courseId: number) {
  return client.get(`/api/v3/zhima/contracts`, { course_id: courseId });
}

export function zhimaConsent(params: any) {
  return client.post(`/api/v3/zhima/consent`, params);
}
