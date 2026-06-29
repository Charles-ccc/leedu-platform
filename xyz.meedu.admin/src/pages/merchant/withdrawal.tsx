import { useState, useEffect } from "react";
import { Table, Tag, Space, Button, Modal, message, Form, InputNumber, Select } from "antd";
import type { ColumnsType } from "antd/es/table";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";
import { dateWholeFormat } from "../../utils/index";

interface DataType {
  id: number;
  salesperson_id: number;
  salesperson_name: string;
  amount: number;
  status: number;
  alipay_account: string;
  created_at: string;
}

const fen = (v: number) => "¥" + (v / 100).toFixed(2);
const STATUS: { [k: number]: { t: string; c: string } } = {
  0: { t: "待打款", c: "orange" },
  1: { t: "通过", c: "blue" },
  2: { t: "已拒绝", c: "red" },
  3: { t: "已打款", c: "green" },
};

const WithdrawalPage = () => {
  const dispatch = useDispatch();
  const [form] = Form.useForm();
  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const [list, setList] = useState<DataType[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [refresh, setRefresh] = useState(false);
  const [showWin, setShowWin] = useState(false);
  const [salespeople, setSalespeople] = useState<any[]>([]);

  useEffect(() => {
    document.title = "业务员提现";
    dispatch(titleAction("业务员提现"));
  }, []);

  useEffect(() => {
    getData();
  }, [page, size, refresh]);

  const getData = () => {
    if (loading) return;
    setLoading(true);
    merchant
      .withdrawalList({ page, size })
      .then((res: any) => {
        setList(res.data.data);
        setTotal(res.data.total);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };

  const openCreate = () => {
    form.resetFields();
    merchant.salespersonList({ page: 1, size: 1000 }).then((res: any) => {
      setSalespeople(
        res.data.data.map((s: any) => ({
          label: `${s.name}(余额${(s.balance / 100).toFixed(2)})`,
          value: s.id,
        }))
      );
    });
    setShowWin(true);
  };

  const submit = () => {
    form.validateFields().then((values: any) => {
      merchant
        .withdrawalCreate({ salesperson_id: values.salesperson_id, amount: Math.round(values.amount * 100) })
        .then(() => {
          message.success("提现已发起");
          setShowWin(false);
          setRefresh(!refresh);
        });
    });
  };

  const process = (r: DataType, action: "pay" | "reject") => {
    Modal.confirm({
      title: action === "pay" ? "标记已打款" : "拒绝提现",
      content:
        action === "pay"
          ? `确认已向「${r.salesperson_name}」打款 ${fen(r.amount)}？`
          : `拒绝后金额 ${fen(r.amount)} 退回业务员余额。`,
      onOk: () =>
        merchant.withdrawalProcess(r.id, { action }).then(() => {
          message.success("已处理");
          setRefresh(!refresh);
        }),
    });
  };

  const columns: ColumnsType<DataType> = [
    { title: "ID", dataIndex: "id", width: 70 },
    { title: "业务员", dataIndex: "salesperson_name" },
    { title: "金额", dataIndex: "amount", render: (v) => <b>{fen(v)}</b> },
    { title: "收款支付宝", dataIndex: "alipay_account" },
    {
      title: "状态",
      dataIndex: "status",
      width: 100,
      render: (v: number) => {
        const s = STATUS[v] || STATUS[0];
        return <Tag color={s.c}>{s.t}</Tag>;
      },
    },
    {
      title: "时间",
      dataIndex: "created_at",
      width: 165,
      render: (v: string) => (v ? dateWholeFormat(v) : "-"),
    },
    {
      title: "操作",
      key: "action",
      width: 150,
      render: (_: any, r: DataType) =>
        r.status === 0 ? (
          <Space>
            <Button type="link" size="small" onClick={() => process(r, "pay")}>
              已打款
            </Button>
            <Button type="link" size="small" danger onClick={() => process(r, "reject")}>
              拒绝
            </Button>
          </Space>
        ) : (
          "-"
        ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <div style={{ marginBottom: 16, textAlign: "right" }}>
        <Button type="primary" onClick={openCreate}>
          发起提现
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
      <Modal title="发起提现" open={showWin} onOk={submit} onCancel={() => setShowWin(false)} destroyOnClose>
        <Form form={form} labelCol={{ span: 6 }} wrapperCol={{ span: 16 }}>
          <Form.Item
            label="业务员"
            name="salesperson_id"
            rules={[{ required: true, message: "请选择业务员" }]}
          >
            <Select options={salespeople} placeholder="选择业务员" showSearch optionFilterProp="label" />
          </Form.Item>
          <Form.Item
            label="提现金额(元)"
            name="amount"
            rules={[{ required: true, message: "请输入金额" }]}
          >
            <InputNumber min={0.01} precision={2} style={{ width: "100%" }} />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
};

export default WithdrawalPage;
