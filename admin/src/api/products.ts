import { apiRequest, apiUpload } from '@/api/client';

export type ProductImage = {
  id: number;
  product_variant_id?: number | null;
  media_file_id?: number | null;
  role: string;
  url: string;
  original_name: string;
  mime: string;
  size: number;
  alt: string | null;
  sort_order: number;
};

export type ProductCategorySummary = {
  id: number;
  name: string;
  slug: string;
};

export type ProductListItem = {
  id: number;
  name: string;
  slug: string;
  category_id: number | null;
  category: ProductCategorySummary | null;
  sku: string | null;
  short_description: string | null;
  price: string | number;
  compare_at_price: string | number | null;
  weight_grams: number;
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
  image: ProductImage | null;
  option_values: ProductVariantOptionValue[];
};

export type ProductPersonalizationField = {
  id?: number | null;
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
  personalization_override: boolean;
  personalization_default: {
    enabled: boolean;
    label: string | null;
    description: string | null;
    required: boolean;
    max_length: number;
    fields: ProductPersonalizationField[];
  } | null;
  gallery_images: ProductImage[];
  parameters: ProductParameter[];
  options: ProductOption[];
  variants: ProductVariant[];
  personalization_fields: ProductPersonalizationField[];
};

export type ProductListFilters = {
  q?: string;
  status?: string;
  category?: string;
  page?: number;
  per_page?: number;
};

export type ProductAiSuggestion = {
  name: string | null;
  sku: string | null;
  short_description: string | null;
  description: string | null;
  category_id: number | null;
  price: number | null;
  seo_title: string | null;
  seo_description: string | null;
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

export function createProduct(token: string, body: Record<string, unknown>) {
  return apiRequest<{ product: AdminProduct }>('/admin/products', { method: 'POST', token, body });
}

export function generateProductWithAi(token: string, files: File[]) {
  const form = new FormData();
  files.forEach((file) => form.append('images[]', file));

  return apiUpload<{ suggestion: ProductAiSuggestion }>('/admin/products/ai-generate', {
    token,
    form,
  });
}

export function getProduct(token: string, id: number) {
  return apiRequest<{ product: AdminProduct }>(`/admin/products/${id}`, { token });
}

export function updateProduct(token: string, id: number, body: Record<string, unknown>) {
  return apiRequest<{ product: AdminProduct }>(`/admin/products/${id}`, { method: 'PATCH', token, body });
}

export function deleteProduct(token: string, id: number) {
  return apiRequest<Record<string, never>>(`/admin/products/${id}`, { method: 'DELETE', token });
}

export function restoreProduct(token: string, id: number) {
  return apiRequest<{ product: AdminProduct }>(`/admin/products/${id}/restore`, { method: 'POST', token });
}

export function forceDeleteProduct(token: string, id: number) {
  return apiRequest<Record<string, never>>(`/admin/products/${id}/force`, { method: 'DELETE', token });
}

export function shareProductPersonalization(token: string, id: number, body: Record<string, unknown>) {
  return apiRequest<{ product: AdminProduct; updated_count: number }>(`/admin/products/${id}/personalization/share`, {
    method: 'POST',
    token,
    body,
  });
}

export function uploadProductFrontImage(
  token: string,
  productId: number,
  file: File,
  options: { signal?: AbortSignal; onProgress?: (percent: number) => void } = {}
) {
  const form = new FormData();
  form.append('image', file);

  return apiUpload<{ image: ProductImage }>(`/admin/products/${productId}/images/front`, {
    token,
    form,
    signal: options.signal,
    onProgress: options.onProgress,
  });
}

export function uploadProductGalleryImage(
  token: string,
  productId: number,
  file: File,
  options: { signal?: AbortSignal; onProgress?: (percent: number) => void } = {}
) {
  const form = new FormData();
  form.append('image', file);

  return apiUpload<{ images: ProductImage[] }>(`/admin/products/${productId}/images`, {
    token,
    form,
    signal: options.signal,
    onProgress: options.onProgress,
  });
}

export function updateProductImage(
  token: string,
  productId: number,
  imageId: number,
  body: { alt?: string | null; sort_order?: number }
) {
  return apiRequest<{ image: ProductImage }>(`/admin/products/${productId}/images/${imageId}`, {
    method: 'PATCH',
    token,
    body,
  });
}

export function makeProductImageFront(token: string, productId: number, imageId: number) {
  return apiRequest<{ image: ProductImage }>(`/admin/products/${productId}/images/${imageId}/front`, {
    method: 'POST',
    token,
  });
}

export function deleteProductImage(token: string, productId: number, imageId: number) {
  return apiRequest<Record<string, never>>(`/admin/products/${productId}/images/${imageId}`, {
    method: 'DELETE',
    token,
  });
}

export function attachProductFrontImage(token: string, productId: number, mediaId: number) {
  return apiRequest<{ image: ProductImage }>(`/admin/products/${productId}/images/front`, {
    method: 'POST',
    token,
    body: { media_id: mediaId },
  });
}

export function attachProductGalleryImages(token: string, productId: number, mediaIds: number[]) {
  return apiRequest<{ images: ProductImage[] }>(`/admin/products/${productId}/images`, {
    method: 'POST',
    token,
    body: { media_ids: mediaIds },
  });
}

export function attachVariantImage(token: string, productId: number, variantId: number, mediaId: number) {
  return apiRequest<{ image: ProductImage }>(`/admin/products/${productId}/variants/${variantId}/image`, {
    method: 'POST',
    token,
    body: { media_id: mediaId },
  });
}

export function uploadVariantImage(
  token: string,
  productId: number,
  variantId: number,
  file: File,
  options: { signal?: AbortSignal; onProgress?: (percent: number) => void } = {}
) {
  const form = new FormData();
  form.append('image', file);

  return apiUpload<{ image: ProductImage }>(`/admin/products/${productId}/variants/${variantId}/image`, {
    token,
    form,
    signal: options.signal,
    onProgress: options.onProgress,
  });
}

export function deleteVariantImage(token: string, productId: number, variantId: number) {
  return apiRequest<Record<string, never>>(`/admin/products/${productId}/variants/${variantId}/image`, {
    method: 'DELETE',
    token,
  });
}
