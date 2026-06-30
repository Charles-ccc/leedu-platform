import { useState, useEffect } from "react";
import { Table, Tag, Select, Space } from "antd";
import type { ColumnsType } from "antd/es/table";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";
import { dateWholeFormat } from "../../utils/index";

interface DataType {
  id: number;
  salesperson_id: number;
  salesperson_name: string;
  merchant_id: number;
  merchant_name: string;
  order_id: number;
  base_amount: number;
  rate: number;
  amount: number;
  record_type: number;
  pay_status: number;
  created_at: string;
}

const fen = (v: number) => "¥" + (v / 100).toFixed(2);

const CommissionPage = () => {
  const dispatch = useDispatch();
  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const [list, setList] = useState<DataType[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [payStatus, setPayStatus] = useState<number | undefined>(undefined);

  useEffect(() => {
    document.title = "提成记录";
    dispatch(titleAction("提成记录"));
  }, []);

  useEffect(() => {
    getData();
  }, [page, size, payStatus]);

  const getData = () => {
    if (loading) return;
    setLoading(true);
    merchant
      .commissionList({ page, size, pay_status: payStatus })
      .then((res: any) => {
        setList(res.data.data);
        setTotal(res.data.total);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };

  const columns: ColumnsType<DataType> = [
    { title: "ID", dataIndex: "id", width: 70 },
    { title: "业务员", dataIndex: "salesperson_name" },
    { title: "承担机构", dataIndex: "merchant_name" },
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
      title: "支付状态",
      dataIndex: "pay_status",
      width: 100,
      render: (v: number) =>
        v === 1 ? (
          <Tag color="green">机构已付</Tag>
        ) : (
          <Tag color="orange">待机构支付</Tag>
        ),
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
      <div style={{ marginBottom: 16 }}>
        <Space>
          <span>支付状态：</span>
          <Select
            allowClear
            style={{ width: 150 }}
            placeholder="全部"
            value={payStatus}
            onChange={(v) => {
              setPage(1);
              setPayStatus(v);
            }}
            options={[
              { label: "待机构支付", value: 0 },
              { label: "机构已付", value: 1 },
            ]}
          />
        </Space>
      </div>
      <Table
        loading={loading}
        rowKey="id"
        columns={columns}
        dataSource={list}
        scroll={{ x: 1100 }}
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

export default CommissionPage;
