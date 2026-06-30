import { useState, useEffect } from "react";
import {
  Table,
  Modal,
  message,
  Space,
  Button,
  Form,
  Input,
  InputNumber,
  Select,
  Tag,
} from "antd";
import type { ColumnsType } from "antd/es/table";
import { merchant } from "../../api/index";
import { useDispatch } from "react-redux";
import { titleAction } from "../../store/user/loginUserSlice";
import { dateWholeFormat } from "../../utils/index";

interface DataType {
  id: number;
  name: string;
  slug: string;
  status: number;
  contact_name: string;
  contact_mobile: string;
  platform_share_rate: number;
  salesperson_commission_rate: number;
  created_at: string;
}

const STATUS_MAP: { [key: number]: { text: string; color: string } } = {
  0: { text: "待审核", color: "orange" },
  1: { text: "正常", color: "green" },
  2: { text: "禁用", color: "red" },
  3: { text: "已驳回", color: "default" },
};

const MerchantPage = () => {
  const dispatch = useDispatch();
  const [form] = Form.useForm();

  const [page, setPage] = useState(1);
  const [size, setSize] = useState(10);
  const [list, setList] = useState<DataType[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [refresh, setRefresh] = useState(false);

  const [showWin, setShowWin] = useState(false);
  const [editId, setEditId] = useState(0);
  const [statusFilter, setStatusFilter] = useState<number | undefined>(undefined);

  // 机构账号管理
  const [adminForm] = Form.useForm();
  const [showAdminWin, setShowAdminWin] = useState(false);
  const [adminMerchant, setAdminMerchant] = useState<DataType | null>(null);
  const [admins, setAdmins] = useState<any[]>([]);
  const [adminLoading, setAdminLoading] = useState(false);

  useEffect(() => {
    document.title = "机构管理";
    dispatch(titleAction("机构管理"));
  }, []);

  useEffect(() => {
    getData();
  }, [page, size, refresh, statusFilter]);

  const getData = () => {
    if (loading) {
      return;
    }
    setLoading(true);
    merchant
      .merchantList({ page, size, status: statusFilter })
      .then((res: any) => {
        setList(res.data.data);
        setTotal(res.data.total);
        setLoading(false);
      })
      .catch(() => {
        setLoading(false);
      });
  };

  const openCreate = () => {
    setEditId(0);
    form.resetFields();
    form.setFieldsValue({
      status: 1,
      platform_share_rate: 0,
      salesperson_commission_rate: 0,
    });
    setShowWin(true);
  };

  const openEdit = (record: DataType) => {
    setEditId(record.id);
    setShowWin(true);
    merchant.merchantDetail(record.id).then((res: any) => {
      form.setFieldsValue(res.data);
    });
  };

  const submit = () => {
    form.validateFields().then((values: any) => {
      const req =
        editId > 0
          ? merchant.merchantUpdate(editId, values)
          : merchant.merchantStore(values);
      req.then(() => {
        message.success("保存成功");
        setShowWin(false);
        setRefresh(!refresh);
      });
    });
  };

  const openAdmins = (record: DataType) => {
    setAdminMerchant(record);
    setShowAdminWin(true);
    adminForm.resetFields();
    loadAdmins(record.id);
  };

  const loadAdmins = (mid: number) => {
    setAdminLoading(true);
    merchant
      .merchantAdmins(mid)
      .then((res: any) => {
        setAdmins(res.data);
        setAdminLoading(false);
      })
      .catch(() => setAdminLoading(false));
  };

  const submitAdmin = () => {
    if (!adminMerchant) {
      return;
    }
    adminForm.validateFields().then((values: any) => {
      merchant.merchantStoreAdmin(adminMerchant.id, values).then(() => {
        message.success("机构管理员创建成功");
        adminForm.resetFields();
        loadAdmins(adminMerchant.id);
      });
    });
  };

  const auditPass = (record: DataType) => {
    Modal.confirm({
      title: "通过入驻审核",
      content: `确认通过「${record.name}」的入驻申请？通过后其主账号即可登录。`,
      onOk: () =>
        merchant.merchantAudit(record.id, { action: "pass" }).then(() => {
          message.success("已通过");
          setRefresh(!refresh);
        }),
    });
  };

  const auditReject = (record: DataType) => {
    let remark = "";
    Modal.confirm({
      title: "驳回入驻申请",
      content: (
        <div style={{ marginTop: 12 }}>
          <div style={{ marginBottom: 8 }}>驳回原因：</div>
          <Input.TextArea
            rows={3}
            onChange={(e) => (remark = e.target.value)}
            placeholder="请填写驳回原因"
          />
        </div>
      ),
      onOk: () =>
        merchant
          .merchantAudit(record.id, { action: "reject", remark })
          .then(() => {
            message.success("已驳回");
            setRefresh(!refresh);
          }),
    });
  };

  const columns: ColumnsType<DataType> = [
    { title: "ID", dataIndex: "id", width: 70 },
    { title: "机构名称", dataIndex: "name" },
    { title: "标识", dataIndex: "slug" },
    {
      title: "状态",
      dataIndex: "status",
      width: 90,
      render: (v: number) => {
        const s = STATUS_MAP[v] || STATUS_MAP[0];
        return <Tag color={s.color}>{s.text}</Tag>;
      },
    },
    { title: "联系人", dataIndex: "contact_name", width: 100 },
    { title: "联系电话", dataIndex: "contact_mobile", width: 130 },
    {
      title: "平台分成(%)",
      dataIndex: "platform_share_rate",
      width: 110,
    },
    {
      title: "业务员提成(%)",
      dataIndex: "salesperson_commission_rate",
      width: 120,
    },
    {
      title: "创建时间",
      dataIndex: "created_at",
      width: 170,
      render: (v: string) => (v ? dateWholeFormat(v) : "-"),
    },
    {
      title: "操作",
      key: "action",
      width: 200,
      fixed: "right",
      render: (_: any, record: DataType) => (
        <Space>
          {record.status === 0 && (
            <>
              <Button
                type="link"
                size="small"
                onClick={() => auditPass(record)}
              >
                通过
              </Button>
              <Button
                type="link"
                size="small"
                danger
                onClick={() => auditReject(record)}
              >
                驳回
              </Button>
            </>
          )}
          <Button type="link" size="small" onClick={() => openEdit(record)}>
            编辑
          </Button>
          <Button type="link" size="small" onClick={() => openAdmins(record)}>
            账号
          </Button>
        </Space>
      ),
    },
  ];

  const adminColumns: ColumnsType<any> = [
    { title: "ID", dataIndex: "id", width: 70 },
    { title: "姓名", dataIndex: "name" },
    { title: "登录邮箱", dataIndex: "email" },
    {
      title: "最近登录",
      dataIndex: "last_login_date",
      render: (v: string) => (v ? dateWholeFormat(v) : "从未登录"),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <div
        style={{
          marginBottom: 16,
          display: "flex",
          justifyContent: "space-between",
          alignItems: "center",
        }}
      >
        <Space>
          <span>状态筛选：</span>
          <Select
            allowClear
            style={{ width: 140 }}
            placeholder="全部"
            value={statusFilter}
            onChange={(v) => {
              setPage(1);
              setStatusFilter(v);
            }}
            options={[
              { label: "待审核", value: 0 },
              { label: "正常", value: 1 },
              { label: "禁用", value: 2 },
              { label: "已驳回", value: 3 },
            ]}
          />
        </Space>
        <Button type="primary" onClick={openCreate}>
          新建机构
        </Button>
      </div>

      <Table
        loading={loading}
        rowKey="id"
        columns={columns}
        dataSource={list}
        scroll={{ x: 1200 }}
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

      <Modal
        title={editId > 0 ? "编辑机构" : "新建机构"}
        open={showWin}
        onOk={submit}
        onCancel={() => setShowWin(false)}
        destroyOnClose
        width={560}
      >
        <Form form={form} labelCol={{ span: 6 }} wrapperCol={{ span: 16 }}>
          <Form.Item
            label="机构名称"
            name="name"
            rules={[{ required: true, message: "请输入机构名称" }]}
          >
            <Input placeholder="请输入机构名称" />
          </Form.Item>
          <Form.Item label="机构标识" name="slug">
            <Input placeholder="字母数字,用于机构主页地址(可选)" />
          </Form.Item>
          <Form.Item label="联系人" name="contact_name">
            <Input placeholder="联系人(可选)" />
          </Form.Item>
          <Form.Item label="联系电话" name="contact_mobile">
            <Input placeholder="联系电话(可选)" />
          </Form.Item>
          <Form.Item label="状态" name="status">
            <Select
              options={[
                { label: "待审核", value: 0 },
                { label: "正常", value: 1 },
                { label: "禁用", value: 2 },
                { label: "已驳回", value: 3 },
              ]}
            />
          </Form.Item>
          <Form.Item label="平台分成(%)" name="platform_share_rate">
            <InputNumber min={0} max={100} precision={2} style={{ width: "100%" }} />
          </Form.Item>
          <Form.Item label="业务员提成(%)" name="salesperson_commission_rate">
            <InputNumber min={0} max={100} precision={2} style={{ width: "100%" }} />
          </Form.Item>
          <Form.Item label="机构简介" name="intro">
            <Input.TextArea rows={3} placeholder="机构简介(可选)" />
          </Form.Item>
        </Form>
      </Modal>

      <Modal
        title={`机构账号 - ${adminMerchant?.name ?? ""}`}
        open={showAdminWin}
        onCancel={() => setShowAdminWin(false)}
        footer={null}
        width={640}
        destroyOnClose
      >
        <Table
          loading={adminLoading}
          rowKey="id"
          size="small"
          columns={adminColumns}
          dataSource={admins}
          pagination={false}
        />
        <div style={{ marginTop: 16, borderTop: "1px solid #f0f0f0", paddingTop: 16 }}>
          <div style={{ marginBottom: 8, fontWeight: 500 }}>新增机构管理员</div>
          <Form form={adminForm} layout="inline">
            <Form.Item
              name="name"
              rules={[{ required: true, message: "请输入姓名" }]}
            >
              <Input placeholder="姓名" />
            </Form.Item>
            <Form.Item
              name="email"
              rules={[{ required: true, type: "email", message: "请输入有效邮箱" }]}
            >
              <Input placeholder="登录邮箱" />
            </Form.Item>
            <Form.Item
              name="password"
              rules={[{ required: true, min: 6, message: "密码至少6位" }]}
            >
              <Input.Password placeholder="登录密码" />
            </Form.Item>
            <Form.Item>
              <Button type="primary" onClick={submitAdmin}>
                创建
              </Button>
            </Form.Item>
          </Form>
        </div>
      </Modal>
    </div>
  );
};

export default MerchantPage;
