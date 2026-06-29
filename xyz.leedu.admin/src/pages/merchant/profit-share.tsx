import { useState, useEffect } from "react";
import { Table, Tag, Select, Space, Statistic, Card } from "antd";
import type { ColumnsType } from "antd/es/table";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";
import { dateWholeFormat } from "../../utils/index";

interface DataType {
  id: number;
  order_id: number;
  merchant_id: number;
  merchant_name: string;
  rate: number;
  base_amount: number;
  amount: number;
  status: number;
  created_at: string;
}

const STATUS_MAP: { [k: number]: { t: string; c: string } } = {
  0: { t: "待分账", c: "orange" },
  1: { t: "成功", c: "green" },
  2: { t: "失败", c: "red" },
  3: { t: "已回退", c: "default" },
};

const fen = (v: number) => "¥" + (v / 100).toFixed(2);

const ProfitSharePage = () => {
  const dispatch = useDispatch();
  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const [list, setList] = useState<DataType[]>([]);
  const [total, setTotal] = useState(0);
  const [settled, setSettled] = useState(0);
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState<number | undefined>(undefined);

  useEffect(() => {
    document.title = "平台分账";
    dispatch(titleAction("平台分账"));
  }, []);

  useEffect(() => {
    getData();
  }, [page, size, status]);

  const getData = () => {
    if (loading) {
      return;
    }
    setLoading(true);
    merchant
      .profitShareList({ page, size, status })
      .then((res: any) => {
        setList(res.data.data);
        setTotal(res.data.total);
        setSettled(res.data.settled_amount || 0);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };

  const columns: ColumnsType<DataType> = [
    { title: "ID", dataIndex: "id", width: 70 },
    { title: "订单ID", dataIndex: "order_id", width: 90 },
    { title: "机构", dataIndex: "merchant_name" },
    { title: "计提基数", dataIndex: "base_amount", render: (v) => fen(v) },
    { title: "分成比例", dataIndex: "rate", render: (v) => v + "%" },
    {
      title: "平台分成",
      dataIndex: "amount",
      render: (v) => <b>{fen(v)}</b>,
    },
    {
      title: "状态",
      dataIndex: "status",
      width: 90,
      render: (v: number) => {
        const s = STATUS_MAP[v] || STATUS_MAP[0];
        return <Tag color={s.c}>{s.t}</Tag>;
      },
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
      <Card style={{ marginBottom: 16 }}>
        <Statistic title="已成功分账平台分成合计" value={settled / 100} precision={2} prefix="¥" />
      </Card>
      <div style={{ marginBottom: 16 }}>
        <Space>
          <span>状态：</span>
          <Select
            allowClear
            style={{ width: 140 }}
            placeholder="全部"
            value={status}
            onChange={(v) => {
              setPage(1);
              setStatus(v);
            }}
            options={[
              { label: "待分账", value: 0 },
              { label: "成功", value: 1 },
              { label: "失败", value: 2 },
              { label: "已回退", value: 3 },
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
          total: total,
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

export default ProfitSharePage;
