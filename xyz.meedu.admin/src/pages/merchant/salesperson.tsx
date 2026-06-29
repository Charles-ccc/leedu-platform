import { useState, useEffect } from "react";
import {
  Table,
  Modal,
  message,
  Space,
  Button,
  Form,
  Input,
  Select,
  Tag,
} from "antd";
import type { ColumnsType } from "antd/es/table";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";

interface DataType {
  id: number;
  name: string;
  mobile: string;
  invite_code: string;
  alipay_account: string;
  balance: number;
  status: number;
}

const SalespersonPage = () => {
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const [bindForm] = Form.useForm();

  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const [list, setList] = useState<DataType[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [refresh, setRefresh] = useState(false);

  const [showWin, setShowWin] = useState(false);
  const [editId, setEditId] = useState(0);
  const [showBind, setShowBind] = useState(false);
  const [bindSp, setBindSp] = useState<DataType | null>(null);
  const [showReassign, setShowReassign] = useState(false);
  const [reassignSp, setReassignSp] = useState<DataType | null>(null);
  const [reassignTarget, setReassignTarget] = useState<number | undefined>();

  useEffect(() => {
    document.title = "业务员管理";
    dispatch(titleAction("业务员管理"));
  }, []);

  useEffect(() => {
    getData();
  }, [page, size, refresh]);

  const getData = () => {
    if (loading) return;
    setLoading(true);
    merchant
      .salespersonList({ page, size })
      .then((res: any) => {
        setList(res.data.data);
        setTotal(res.data.total);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };

  const openCreate = () => {
    setEditId(0);
    form.resetFields();
    setShowWin(true);
  };

  const openEdit = (r: DataType) => {
    setEditId(r.id);
    form.setFieldsValue({
      name: r.name,
      mobile: r.mobile,
      alipay_account: r.alipay_account,
      status: r.status,
    });
    setShowWin(true);
  };

  const submit = () => {
    form.validateFields().then((values: any) => {
      const req =
        editId > 0
          ? merchant.salespersonUpdate(editId, values)
          : merchant.salespersonStore(values);
      req.then(() => {
        message.success("保存成功");
        setShowWin(false);
        setRefresh(!refresh);
      });
    });
  };

  const openBind = (r: DataType) => {
    setBindSp(r);
    bindForm.resetFields();
    setShowBind(true);
  };

  const submitBind = () => {
    if (!bindSp) return;
    bindForm.validateFields().then((values: any) => {
      merchant.salespersonBind(bindSp.id, values).then(() => {
        message.success("绑定成功");
        setShowBind(false);
      });
    });
  };

  const openReassign = (r: DataType) => {
    setReassignSp(r);
    setReassignTarget(undefined);
    setShowReassign(true);
  };

  const submitReassign = () => {
    if (!reassignSp || !reassignTarget) {
      message.warning("请选择接收的业务员");
      return;
    }
    merchant
      .salespersonReassign({
        from_salesperson_id: reassignSp.id,
        to_salesperson_id: reassignTarget,
      })
      .then((res: any) => {
        message.success(`已改派 ${res.data.reassigned} 个客户`);
        setShowReassign(false);
      });
  };

  const columns: ColumnsType<DataType> = [
    { title: "ID", dataIndex: "id", width: 70 },
    { title: "姓名", dataIndex: "name" },
    { title: "手机号", dataIndex: "mobile" },
    { title: "推广码", dataIndex: "invite_code" },
    { title: "收款支付宝", dataIndex: "alipay_account" },
    {
      title: "可提现(元)",
      dataIndex: "balance",
      render: (v: number) => (v / 100).toFixed(2),
    },
    {
      title: "状态",
      dataIndex: "status",
      width: 80,
      render: (v: number) =>
        v === 1 ? <Tag color="green">在职</Tag> : <Tag>离职</Tag>,
    },
    {
      title: "操作",
      key: "action",
      width: 160,
      render: (_: any, r: DataType) => (
        <Space>
          <Button type="link" size="small" onClick={() => openEdit(r)}>
            编辑
          </Button>
          <Button type="link" size="small" onClick={() => openBind(r)}>
            绑定客户
          </Button>
          <Button type="link" size="small" onClick={() => openReassign(r)}>
            改派
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <div style={{ marginBottom: 16, textAlign: "right" }}>
        <Button type="primary" onClick={openCreate}>
          新建业务员
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
        title={editId > 0 ? "编辑业务员" : "新建业务员"}
        open={showWin}
        onOk={submit}
        onCancel={() => setShowWin(false)}
        destroyOnClose
      >
        <Form form={form} labelCol={{ span: 6 }} wrapperCol={{ span: 16 }}>
          <Form.Item
            label="姓名"
            name="name"
            rules={[{ required: true, message: "请输入姓名" }]}
          >
            <Input placeholder="业务员姓名" />
          </Form.Item>
          <Form.Item label="手机号" name="mobile">
            <Input placeholder="手机号(可选)" />
          </Form.Item>
          <Form.Item label="收款支付宝" name="alipay_account">
            <Input placeholder="收提成的支付宝账号(可选)" />
          </Form.Item>
          {editId > 0 && (
            <Form.Item label="状态" name="status">
              <Select
                options={[
                  { label: "在职", value: 1 },
                  { label: "离职", value: 2 },
                ]}
              />
            </Form.Item>
          )}
        </Form>
      </Modal>

      <Modal
        title={`绑定客户 - ${bindSp?.name ?? ""}`}
        open={showBind}
        onOk={submitBind}
        onCancel={() => setShowBind(false)}
        destroyOnClose
      >
        <Form form={bindForm} labelCol={{ span: 6 }} wrapperCol={{ span: 16 }}>
          <Form.Item
            label="客户用户ID"
            name="user_id"
            rules={[{ required: true, message: "请输入客户的用户ID" }]}
            extra="把该学员绑定到此业务员名下(永久,后续该客户下单计提归此业务员)"
          >
            <Input type="number" placeholder="学员 user_id" />
          </Form.Item>
        </Form>
      </Modal>

      <Modal
        title={`改派客户 - ${reassignSp?.name ?? ""}`}
        open={showReassign}
        onOk={submitReassign}
        onCancel={() => setShowReassign(false)}
        destroyOnClose
      >
        <div style={{ marginBottom: 12, color: "#888" }}>
          把「{reassignSp?.name}」名下所有有效客户改派给下面选中的业务员。仅影响之后的新订单，历史提成不变。
        </div>
        <Select
          style={{ width: "100%" }}
          placeholder="选择接收客户的业务员"
          value={reassignTarget}
          onChange={(v) => setReassignTarget(v)}
          showSearch
          optionFilterProp="label"
          options={list
            .filter((s) => s.id !== reassignSp?.id)
            .map((s) => ({ label: s.name, value: s.id }))}
        />
      </Modal>
    </div>
  );
};

export default SalespersonPage;
