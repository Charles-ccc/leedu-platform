import { useState, useEffect } from "react";
import { Table, message, Space, Button, Modal, Input, Image } from "antd";
import type { ColumnsType } from "antd/es/table";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";
import { dateWholeFormat } from "../../utils/index";

interface DataType {
  id: number;
  merchant_id: number;
  merchant_name: string;
  title: string;
  thumb: string;
  charge: number;
  submitted_at: string;
}

const CourseAuditPage = () => {
  const dispatch = useDispatch();

  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const [list, setList] = useState<DataType[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [refresh, setRefresh] = useState(false);

  useEffect(() => {
    document.title = "课程审核";
    dispatch(titleAction("课程审核"));
  }, []);

  useEffect(() => {
    getData();
  }, [page, size, refresh]);

  const getData = () => {
    if (loading) {
      return;
    }
    setLoading(true);
    merchant
      .courseAuditPending({ page, size })
      .then((res: any) => {
        setList(res.data.data);
        setTotal(res.data.total);
        setLoading(false);
      })
      .catch(() => setLoading(false));
  };

  const pass = (record: DataType) => {
    Modal.confirm({
      title: "审核通过",
      content: `确认通过「${record.title}」？通过后课程将自动上架展示。`,
      onOk: () => {
        return merchant.courseAudit(record.id, { action: "pass" }).then(() => {
          message.success("已通过");
          setRefresh(!refresh);
        });
      },
    });
  };

  const reject = (record: DataType) => {
    let remark = "";
    Modal.confirm({
      title: "驳回课程",
      content: (
        <div style={{ marginTop: 12 }}>
          <div style={{ marginBottom: 8 }}>驳回原因（机构可见）：</div>
          <Input.TextArea
            rows={3}
            onChange={(e) => (remark = e.target.value)}
            placeholder="请填写驳回原因"
          />
        </div>
      ),
      onOk: () => {
        return merchant
          .courseAudit(record.id, { action: "reject", remark })
          .then(() => {
            message.success("已驳回");
            setRefresh(!refresh);
          });
      },
    });
  };

  const columns: ColumnsType<DataType> = [
    { title: "ID", dataIndex: "id", width: 70 },
    {
      title: "封面",
      dataIndex: "thumb",
      width: 90,
      render: (v: string) =>
        v ? <Image src={v} width={60} height={40} /> : "-",
    },
    { title: "课程标题", dataIndex: "title" },
    { title: "所属机构", dataIndex: "merchant_name" },
    {
      title: "价格(分)",
      dataIndex: "charge",
      width: 90,
    },
    {
      title: "提交时间",
      dataIndex: "submitted_at",
      width: 170,
      render: (v: string) => (v ? dateWholeFormat(v) : "-"),
    },
    {
      title: "操作",
      key: "action",
      width: 140,
      fixed: "right",
      render: (_: any, record: DataType) => (
        <Space>
          <Button type="link" size="small" onClick={() => pass(record)}>
            通过
          </Button>
          <Button type="link" size="small" danger onClick={() => reject(record)}>
            驳回
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Table
        loading={loading}
        rowKey="id"
        columns={columns}
        dataSource={list}
        scroll={{ x: 900 }}
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

export default CourseAuditPage;
