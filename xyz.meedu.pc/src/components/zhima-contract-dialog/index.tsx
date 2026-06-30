import { useEffect, useState } from "react";
import { Modal, Checkbox, Spin, message, Collapse } from "antd";
import { zhima } from "../../api/index";

interface PropsInterface {
  open: boolean;
  courseId: number;
  onAgree: () => void;
  onCancel: () => void;
}

// 平台化 M5：芝麻先享下单前 - 展示网课买卖合同 + 芝麻代扣合同，阅读勾选后下一步
export const ZhimaContractDialog = (props: PropsInterface) => {
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [agreed, setAgreed] = useState(false);
  const [contracts, setContracts] = useState<any>({});

  useEffect(() => {
    if (props.open && props.courseId) {
      setAgreed(false);
      setLoading(true);
      zhima
        .zhimaContracts(props.courseId)
        .then((res: any) => {
          setContracts(res.data || {});
          setLoading(false);
        })
        .catch(() => setLoading(false));
    }
  }, [props.open, props.courseId]);

  const confirm = () => {
    if (!agreed) {
      message.warning("请先阅读并勾选同意两份合同");
      return;
    }
    const types: string[] = [];
    if (contracts.course_sale) types.push("course_sale");
    if (contracts.zhima_withhold) types.push("zhima_withhold");
    if (types.length === 0) {
      message.error("该课程暂未配置先学后付合同");
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

  const items: any[] = [];
  if (contracts.course_sale) {
    items.push({
      key: "course_sale",
      label: contracts.course_sale.title || "网课买卖合同",
      children: (
        <div style={{ maxHeight: 200, overflow: "auto", whiteSpace: "pre-wrap" }}>
          {contracts.course_sale.content}
        </div>
      ),
    });
  }
  if (contracts.zhima_withhold) {
    items.push({
      key: "zhima_withhold",
      label: contracts.zhima_withhold.title || "芝麻先享代扣合同",
      children: (
        <div style={{ maxHeight: 200, overflow: "auto", whiteSpace: "pre-wrap" }}>
          {contracts.zhima_withhold.content}
        </div>
      ),
    });
  }

  return (
    <Modal
      title="先学后付 - 合同确认"
      open={props.open}
      onOk={confirm}
      onCancel={props.onCancel}
      okText="同意并继续"
      confirmLoading={submitting}
      width={640}
      destroyOnClose
    >
      <Spin spinning={loading}>
        {items.length > 0 ? (
          <Collapse items={items} defaultActiveKey={items.map((i) => i.key)} />
        ) : (
          <div style={{ padding: 20, textAlign: "center", color: "#999" }}>
            该课程暂未配置先学后付合同
          </div>
        )}
        <div style={{ marginTop: 16 }}>
          <Checkbox checked={agreed} onChange={(e) => setAgreed(e.target.checked)}>
            我已阅读并同意上述《网课买卖合同》与《芝麻先享代扣合同》
          </Checkbox>
        </div>
      </Spin>
    </Modal>
  );
};
