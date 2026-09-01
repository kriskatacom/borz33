import { apiRequest } from '@/api/client';

export type OrderStatus = 'pending' | 'confirmed' | 'paid' | 'processing' | 'shipped' | 'delivered' | 'cancelled';

export type OrderListItem = {
  id: number;
  number: string;
  status: OrderStatus;
  customer_name: string;
  email: string;
  phone: string;
  delivery_method: string;
  payment_method: string;
  currency: string;
  total: string;
  items_count: number;
  created_at: string | null;
  updated_at: string | null;
};

export type OrderItem = {
  id: number;
  product_id: number | null;
  variant_id: number | null;
  name: string;
  sku: string | null;
  options: string | null;
  notes: string | null;
  qty: number;
  unit_price: string;
  total: string;
  product_image_url: string | null;
};

export type AdminOrder = OrderListItem & {
  user_id: number | null;
  first_name: string;
  last_name: string;
  subtotal: string;
  shipping_amount: string;
  vat_enabled: boolean;
  vat_rate: string;
  address_line: string;
  city: string;
  postal_code: string | null;
  country: string;
  econt_office_code: string | null;
  shipping_payer: 'sender' | 'receiver';
  econt_quote_snapshot: { carrier_amount?: number; amount?: number; environment?: string; weight_kg?: number; order_value?: number; cod_amount?: number } | null;
  tracking_number: string | null;
  tracking_url: string | null;
  shipped_at: string | null;
  notes: string | null;
  items: OrderItem[];
  invoice_requested: boolean;
  invoice_company: string | null;
  invoice_eik: string | null;
  invoice_vat_number: string | null;
  invoice_address: string | null;
  invoice_mol: string | null;
  invoices: Array<{id:number;number:string|null;type:string;status:string}>;
};

export type OrderFilters = {
  q?: string;
  status?: string;
  delivery_method?: string;
  payment_method?: string;
  page?: number;
  per_page?: number;
};

export function listOrders(token: string, filters: OrderFilters) {
  return apiRequest<{ orders: OrderListItem[]; pagination: { page: number; per_page: number; total: number; last_page: number } }>('/admin/orders', { token, query: filters });
}

export function getOrder(token: string, id: number) {
  return apiRequest<{ order: AdminOrder }>(`/admin/orders/${id}`, { token });
}

export function updateOrderStatus(token: string, id: number, status: OrderStatus, trackingNumber: string, recordPayment = false) {
  return apiRequest<{ order: AdminOrder; status_changed: boolean; tracking_changed: boolean; email_sent: boolean; payment_recorded: boolean }>(`/admin/orders/${id}`, { method: 'PATCH', token, body: { status, tracking_number: trackingNumber, record_payment: recordPayment } });
}
