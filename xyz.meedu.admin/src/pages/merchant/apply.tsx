import { useState } from "react";
import { Form, Input, Button, message, Result } from "antd";
import { merchant } from "../../api/index";

const MerchantApplyPage = () => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  const submit = () => {
    form.validateFields().then((values: any) => {
      setLoading(true);
      merchant
        .merchantApply(values)
        .then(() => {
          setLoading(false);
          setDone(true);
        })
        .catch(() => setLoading(false));
    });
  };

  return (
    <div
      style={{
        minHeight: "100vh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        background: "#f0f2f5",
        padding: 24,
      }}
    >
      <div
        style={{
          width: 480,
          background: "#fff",
          borderRadius: 8,
          padding: 32,
          boxShadow: "0 2px 12px rgba(0,0,0,0.08)",
        }}
      >
        {done ? (
          <Result
            status="success"
            title="入驻申请已提交"
            subTitle="平台审核通过后，您即可用申请时填写的邮箱和密码登录机构后台。"
          />
        ) : (
          <>
            <h2 style={{ textAlign: "center", marginBottom: 24 }}>机构入驻申请</h2>
            <Form form={form} layout="vertical">
              <Form.Item
                label="机构名称"
                name="name"
                rules={[{ required: true, message: "请输入机构名称" }]}
              >
                <Input placeholder="请输入机构名称" />
              </Form.Item>
              <Form.Item
                label="联系人"
                name="contact_name"
                rules={[{ required: true, message: "请输入联系人" }]}
              >
                <Input placeholder="联系人姓名" />
              </Form.Item>
              <Form.Item
                label="联系电话"
                name="contact_mobile"
                rules={[{ required: true, message: "请输入联系电话" }]}
              >
                <Input placeholder="联系电话" />
              </Form.Item>
              <Form.Item
                label="登录邮箱"
                name="email"
                rules={[
                  { required: true, type: "email", message: "请输入有效邮箱" },
                ]}
                extra="审核通过后用此邮箱登录机构后台"
              >
                <Input placeholder="登录邮箱" />
              </Form.Item>
              <Form.Item
                label="登录密码"
                name="password"
                rules={[{ required: true, min: 6, message: "密码至少6位" }]}
              >
                <Input.Password placeholder="登录密码(至少6位)" />
              </Form.Item>
              <Form.Item label="机构简介" name="intro">
                <Input.TextArea rows={3} placeholder="简单介绍一下机构(可选)" />
              </Form.Item>
              <Form.Item>
                <Button
                  type="primary"
                  block
                  loading={loading}
                  onClick={submit}
                >
                  提交申请
                </Button>
              </Form.Item>
            </Form>
          </>
        )}
      </div>
    </div>
  );
};

export default MerchantApplyPage;
