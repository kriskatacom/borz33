import { apiRequest } from '@/api/client';

export type ProductImage = {
  id: number;
  role: string;
  url: string;
  original_name: string;
  mime: string;
  size: number;
  alt: string | null;
  sort_order: number;
};

export type ProductListItem = {
  id: number;
  name: string;
  slug: string;
  sku: string | null;
  short_description: string | null;
  price: string | number;
  compare_at_price: string | number | null;
  is_active: boolean;
  personalization_enabled: boolean;
  sort_order: number;
  variants_count: number;
  front_image: ProductImage | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
};

export type ProductParameter = {
  id: number;
  name: string;
  value: string;
  sort_order: number;
};

export type ProductOptionValue = {
  id: number;
  name: string;
  slug: string;
  hex_color: string | null;
  sort_order: number;
};

export type ProductOption = {
  id: number;
  name: string;
  slug: string;
  sort_order: number;
  values: ProductOptionValue[];
};

export type ProductVariantOptionValue = {
  option: string | null;
  option_name: string | null;
  value: string | null;
  value_name: string | null;
  hex_color: string | null;
};

export type ProductVariant = {
  id: number;
  sku: string | null;
  name: string | null;
  price: string | number | null;
  compare_at_price: string | number | null;
  stock: number;
  is_default: boolean;
  is_active: boolean;
  sort_order: number;
  option_values: ProductVariantOptionValue[];
};

export type ProductPersonalizationField = {
  id: number;
  name: string;
  description: string | null;
  field_type: string;
  is_required: boolean;
  max_length: number | null;
  sort_order: number;
};

export type AdminProduct = ProductListItem & {
  description: string | null;
  personalization_label: string | null;
  personalization_description: string | null;
  personalization_required: boolean;
  personalization_max_length: number | null;
  gallery_images: ProductImage[];
  parameters: ProductParameter[];
  options: ProductOption[];
  variants: ProductVariant[];
  personalization_fields: ProductPersonalizationField[];
};

export type ProductListFilters = {
  q?: string;
  status?: string;
  page?: number;
  per_page?: number;
};

export type ProductListData = {
  products: ProductListItem[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export function listProducts(token: string, filters: ProductListFilters) {
  return apiRequest<ProductListData>('/admin/products', { token, query: filters });
}

export function getProduct(token: string, id: number) {
  return apiRequest<{ product: AdminProduct }>(`/admin/products/${id}`, { token });
}

export function updateProduct(token: string, id: number, body: Record<string, unknown>) {
  return apiRequest<{ product: AdminProduct }>(`/admin/products/${id}`, { method: 'PATCH', token, body });
}
