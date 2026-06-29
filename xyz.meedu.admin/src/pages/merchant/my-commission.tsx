import { useState, useEffect } from "react";
import { Table, Tag, Select, Space, Button, Modal, message, Card, Statistic } from "antd";
import type { ColumnsType } from "antd/es/table";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";
import { dateWholeFormat } from "../../utils/index";

interface DataType {
  id: number;
  salesperson_name: string;
  order_id: number;
  base_amount: number;
  rate: number;
  amount: number;
  record_type: number;
  pay_status: number;
  created_at: string;
}

const fen = (v: number) => "¥" + (v / 100).toFixed(2);

const MyCommissionPage = () => {
  const dispatch = useDispatch();
  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const [list, setList] = useState<DataType[]>([]);
  const [total, setTotal] = useState(0);
  const [pendingSum, setPendingSum] = useState(0);
  const [loading, setLoading] = useState(false);
  const [refresh, setRefresh] = useState(false);
  const [payStatus, setPayStatus] = useState<number | undefined>(0);

  useEffect(() => {
    document.title = "业务员提成";
    dispatch(titleAction("业务员提成"));
  }, []);

  useEffect(() => {
    getData();
  }, [page, size, payStatus, refresh]);

  const getData = () => {
    if (loading) return;
    setLoading(true);
    merchant
      .merchantCommissionList({ page, size, pay_status: payStatus })
      .then((res: any) => {
        setList(res.data.data);
        setTotal(res.data.total);
        setPendingSum(res.data.pending_sum || 0);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };

  const pay = (r: DataType) => {
    Modal.confirm({
      title: "标记已支付",
      content: `确认已向「${r.salesperson_name}」支付提成 ${fen(r.amount)}？`,
      onOk: () =>
        merchant.merchantCommissionPay(r.id).then(() => {
          message.success("已标记支付");
          setRefresh(!refresh);
        }),
    });
  };

  const columns: ColumnsType<DataType> = [
    { title: "ID", dataIndex: "id", width: 70 },
    { title: "业务员", dataIndex: "salesperson_name" },
    { title: "订单ID", dataIndex: "order_id", width: 90 },
    { title: "成交额", dataIndex: "base_amount", render: (v) => fen(v) },
    { title: "比例", dataIndex: "rate", render: (v) => v + "%" },
    {
      title: "提成",
      dataIndex: "amount",
      render: (v: number) => (
        <b style={{ color: v < 0 ? "#cf1322" : "#3f8600" }}>{fen(v)}</b>
      ),
    },
    {
      title: "类型",
      dataIndex: "record_type",
      width: 90,
      render: (v: number) =>
        v === 2 ? <Tag color="red">退款冲正</Tag> : <Tag color="blue">计提</Tag>,
    },
    {
      title: "状态",
      dataIndex: "pay_status",
      width: 100,
      render: (v: number) =>
        v === 1 ? <Tag color="green">已支付</Tag> : <Tag color="orange">待支付</Tag>,
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
      width: 100,
      render: (_: any, r: DataType) =>
        r.pay_status === 0 && r.record_type === 1 ? (
          <Button type="link" size="small" onClick={() => pay(r)}>
            标记已付
          </Button>
        ) : (
          "-"
        ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Card style={{ marginBottom: 16 }}>
        <Statistic title="待支付提成合计" value={pendingSum / 100} precision={2} prefix="¥" />
      </Card>
      <div style={{ marginBottom: 16 }}>
        <Space>
          <span>状态：</span>
          <Select
            allowClear
            style={{ width: 140 }}
            placeholder="全部"
            value={payStatus}
            onChange={(v) => {
              setPage(1);
              setPayStatus(v);
            }}
            options={[
              { label: "待支付", value: 0 },
              { label: "已支付", value: 1 },
            ]}
          />
        </Space>
      </div>
      <Table
        loading={loading}
        rowKey="id"
        columns={columns}
        dataSource={list}
        scroll={{ x: 1000 }}
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
    </div>
  );
};

export default MyCommissionPage;
