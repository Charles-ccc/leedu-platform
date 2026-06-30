import { useEffect, useState } from "react";
import { Form, Input, Button, message, Switch, Card, Tag, Spin } from "antd";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";

const MerchantAlipayConfigPage = () => {
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [flags, setFlags] = useState<any>({});

  useEffect(() => {
    document.title = "支付宝配置";
    dispatch(titleAction("支付宝配置"));
    load();
  }, []);

  const load = () => {
    setLoading(true);
    merchant
      .merchantAlipayGet()
      .then((res: any) => {
        const d = res.data;
        setFlags(d);
        form.setFieldsValue({
          app_id: d.app_id,
          seller_id: d.seller_id,
          zhima_enabled: d.zhima_enabled === 1,
          is_enabled: d.is_enabled === 1,
        });
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };

  const submit = () => {
    form.validateFields().then((values: any) => {
      setSaving(true);
      const params = {
        ...values,
        zhima_enabled: values.zhima_enabled ? 1 : 0,
        is_enabled: values.is_enabled ? 1 : 0,
      };
      merchant
        .merchantAlipaySave(params)
        .then(() => {
          message.success("保存成功");
          setSaving(false);
          // 清空密钥输入框（已保存，避免误以为需重填）
          form.setFieldsValue({
            app_private_key: "",
            alipay_public_key: "",
            app_cert_public_key: "",
            alipay_root_cert: "",
          });
          load();
        })
        .catch(() => setSaving(false));
    });
  };

  const secretLabel = (label: string, has: boolean) => (
    <span>
      {label}{" "}
      {has ? (
        <Tag color="green">已配置</Tag>
      ) : (
        <Tag color="orange">未配置</Tag>
      )}
    </span>
  );

  return (
    <div style={{ padding: 24, maxWidth: 760 }}>
      <Card title="本机构支付宝收单配置">
        <div style={{ marginBottom: 16, color: "#888" }}>
          学员购买本机构课程时，将使用此处配置的支付宝商户号收款。密钥/证书加密存储，保存后不再回显，留空表示不修改。
        </div>
        <Spin spinning={loading}>
          <Form form={form} labelCol={{ span: 6 }} wrapperCol={{ span: 16 }}>
            <Form.Item
              label="应用APPID"
              name="app_id"
              rules={[{ required: true, message: "请输入支付宝应用APPID" }]}
            >
              <Input placeholder="支付宝开放平台应用 APPID" />
            </Form.Item>
            <Form.Item label="商户号/PID" name="seller_id">
              <Input placeholder="支付宝商户号(PID, 可选)" />
            </Form.Item>
            <Form.Item label={secretLabel("应用私钥", flags.has_app_private_key)} name="app_private_key">
              <Input.TextArea rows={3} placeholder="应用私钥(留空不修改)" />
            </Form.Item>
            <Form.Item label={secretLabel("支付宝公钥", flags.has_alipay_public_key)} name="alipay_public_key">
              <Input.TextArea rows={3} placeholder="支付宝公钥(留空不修改)" />
            </Form.Item>
            <Form.Item label={secretLabel("应用公钥证书", flags.has_app_cert_public_key)} name="app_cert_public_key">
              <Input.TextArea rows={3} placeholder="应用公钥证书内容(留空不修改)" />
            </Form.Item>
            <Form.Item label={secretLabel("支付宝根证书", flags.has_alipay_root_cert)} name="alipay_root_cert">
              <Input.TextArea rows={3} placeholder="支付宝根证书内容(留空不修改)" />
            </Form.Item>
            <Form.Item label="开通芝麻先享" name="zhima_enabled" valuePropName="checked">
              <Switch />
            </Form.Item>
            <Form.Item label="启用收单" name="is_enabled" valuePropName="checked">
              <Switch />
            </Form.Item>
            <Form.Item wrapperCol={{ offset: 6 }}>
              <Button type="primary" loading={saving} onClick={submit}>
                保存
              </Button>
            </Form.Item>
          </Form>
        </Spin>
      </Card>
    </div>
  );
};

export default MerchantAlipayConfigPage;
