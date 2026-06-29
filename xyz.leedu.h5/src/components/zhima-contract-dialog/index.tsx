import { useEffect, useState } from "react";
import { CenterPopup, Collapse, Checkbox, Button, Toast } from "antd-mobile";
import { zhima } from "../../api/index";

interface PropsInterface {
  open: boolean;
  courseId: number;
  onAgree: () => void;
  onCancel: () => void;
}

// 平台化 M5：H5 芝麻先享下单前 - 展示两份合同 + 勾选同意
const ZhimaContractDialog = (props: PropsInterface) => {
  const [agreed, setAgreed] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [contracts, setContracts] = useState<any>({});

  useEffect(() => {
    if (props.open && props.courseId) {
      setAgreed(false);
      zhima
        .zhimaContracts(props.courseId)
        .then((res: any) => setContracts(res.data || {}))
        .catch(() => {});
    }
  }, [props.open, props.courseId]);

  const confirm = () => {
    if (!agreed) {
      Toast.show("请先阅读并勾选同意两份合同");
      return;
    }
    const types: string[] = [];
    if (contracts.course_sale) types.push("course_sale");
    if (contracts.zhima_withhold) types.push("zhima_withhold");
    if (types.length === 0) {
      Toast.show("该课程暂未配置先学后付合同");
      return;
    }
    setSubmitting(true);
    zhima
      .zhimaConsent({ course_id: props.courseId, types })
      .then(() => {
        setSubmitting(false);
        props.onAgree();
      })
      .catch(() => setSubmitting(false));
  };

  return (
    <CenterPopup
      visible={props.open}
      onMaskClick={props.onCancel}
      style={{ "--max-width": "90vw" } as any}
    >
      <div style={{ padding: 16, width: "86vw", maxHeight: "70vh", overflow: "auto" }}>
        <div style={{ fontWeight: 600, fontSize: 16, marginBottom: 12 }}>
          先学后付 · 合同确认
        </div>
        <Collapse>
          {contracts.course_sale && (
            <Collapse.Panel key="cs" title={contracts.course_sale.title || "网课买卖合同"}>
              <div style={{ maxHeight: 180, overflow: "auto", whiteSpace: "pre-wrap", fontSize: 13 }}>
                {contracts.course_sale.content}
              </div>
            </Collapse.Panel>
          )}
          {contracts.zhima_withhold && (
            <Collapse.Panel key="zw" title={contracts.zhima_withhold.title || "芝麻先享代扣合同"}>
              <div style={{ maxHeight: 180, overflow: "auto", whiteSpace: "pre-wrap", fontSize: 13 }}>
                {contracts.zhima_withhold.content}
              </div>
            </Collapse.Panel>
          )}
        </Collapse>
        <div style={{ margin: "16px 0" }}>
          <Checkbox checked={agreed} onChange={(v) => setAgreed(v)}>
            <span style={{ fontSize: 13 }}>
              我已阅读并同意《网课买卖合同》与《芝麻先享代扣合同》
            </span>
          </Checkbox>
        </div>
        <div style={{ display: "flex", gap: 12 }}>
          <Button block onClick={props.onCancel}>
            取消
          </Button>
          <Button block color="primary" loading={submitting} onClick={confirm}>
            同意并继续
          </Button>
        </div>
      </div>
    </CenterPopup>
  );
};

export default ZhimaContractDialog;
