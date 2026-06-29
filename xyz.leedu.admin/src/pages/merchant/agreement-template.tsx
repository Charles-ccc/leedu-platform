import { useState, useEffect } from "react";
import { Table, Modal, message, Button, Form, Input, Select, Tag } from "antd";
import type { ColumnsType } from "antd/es/table";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";
import { dateWholeFormat } from "../../utils/index";

interface DataType {
  id: number;
  type: string;
  version: string;
  title: string;
  content_hash: string;
  is_active: number;
  created_at: string;
}

const TYPE_LABEL: { [k: string]: string } = {
  course_sale: "网课买卖合同",
  zhima_withhold: "芝麻代扣合同",
};

const AgreementTemplatePage = () => {
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const [list, setList] = useState<DataType[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [refresh, setRefresh] = useState(false);
  const [showWin, setShowWin] = useState(false);

  useEffect(() => {
    document.title = "合同模板";
    dispatch(titleAction("合同模板"));
  }, []);

  useEffect(() => {
    getData();
  }, [page, size, refresh]);

  const getData = () => {
    if (loading) return;
    setLoading(true);
    merchant
      .agreementTemplateList({ page, size })
      .then((res: any) => {
        setList(res.data.data);
        setTotal(res.data.total);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };

  const openCreate = () => {
    form.resetFields();
    form.setFieldsValue({ type: "zhima_withhold" });
    setShowWin(true);
  };

  const submit = () => {
    form.validateFields().then((values: any) => {
      merchant.agreementTemplateStore(values).then(() => {
        message.success("已发布新版本");
        setShowWin(false);
        setRefresh(!refresh);
      });
    });
  };

  const columns: ColumnsType<DataType> = [
    { title: "ID", dataIndex: "id", width: 70 },
    {
      title: "类型",
      dataIndex: "type",
      render: (v: string) => TYPE_LABEL[v] || v,
    },
    { title: "版本", dataIndex: "version" },
    { title: "标题", dataIndex: "title" },
    {
      title: "文本Hash",
      dataIndex: "content_hash",
      render: (v: string) => <span style={{ fontSize: 12 }}>{v?.slice(0, 12)}…</span>,
    },
    {
      title: "状态",
      dataIndex: "is_active",
      width: 90,
      render: (v: number) =>
        v === 1 ? <Tag color="green">生效中</Tag> : <Tag>历史</Tag>,
    },
    {
      title: "时间",
      dataIndex: "created_at",
      width: 170,
      render: (v: string) => (v ? dateWholeFormat(v) : "-"),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <div style={{ marginBottom: 16, textAlign: "right" }}>
        <Button type="primary" onClick={openCreate}>
          发布新版本
        </Button>
      </div>
      <Table
        loading={loading}
        rowKey="id"
        columns={columns}
        dataSource={list}
        pagination={{
          current: page,
          pageSize: size,
          total,
          showSizeChanger: true,
          onChange: (p, s) => {
            setPage(p);
            setSize(s);
          },
        }}
      />
      <Modal
        title="发布合同新版本"
        open={showWin}
        onOk={submit}
        onCancel={() => setShowWin(false)}
        destroyOnClose
        width={640}
      >
        <Form form={form} labelCol={{ span: 5 }} wrapperCol={{ span: 18 }}>
          <Form.Item
            label="合同类型"
            name="type"
            rules={[{ required: true }]}
          >
            <Select
              options={[
                { label: "网课买卖合同", value: "course_sale" },
                { label: "芝麻代扣合同", value: "zhima_withhold" },
              ]}
            />
          </Form.Item>
          <Form.Item
            label="版本号"
            name="version"
            rules={[{ required: true, message: "请输入版本号,如 v1.0" }]}
          >
            <Input placeholder="如 v1.0" />
          </Form.Item>
          <Form.Item label="标题" name="title">
            <Input placeholder="合同标题(可选)" />
          </Form.Item>
          <Form.Item
            label="合同正文"
            name="content"
            rules={[{ required: true, message: "请输入合同正文" }]}
            extra="发布后系统会记录文本Hash用于存证;同类型旧版本自动下线"
          >
            <Input.TextArea rows={8} placeholder="合同正文内容" />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default AgreementTemplatePage;
