import { apiRequest } from '@/api/client';

export type InvoiceStatus = 'draft' | 'issued' | 'cancelled' | 'credited';
export type InvoiceType = 'invoice' | 'credit_note';
export type Invoice = { id:number; order_id:number; order_number:string; parent_invoice_id:number|null; parent_invoice_number:string|null; type:InvoiceType; number:string|null; status:InvoiceStatus; issue_date:string|null; tax_event_date:string|null; currency:string; seller:Record<string,string|null>; buyer:Record<string,string|null>; items:Array<{name:string;sku:string;qty:number;unit_gross:number;net_total:number;tax_rate:number;tax:number;gross_total:number}>; creditable_items:Array<{index:number;remaining_qty:number}>; shipping_creditable:boolean; subtotal_net:string; discount_net:string; shipping_net:string; tax_amount:string; total_gross:string; reason:string|null; has_pdf:boolean; issued_at:string|null; cancelled_at:string|null; created_at:string|null; credit_notes:Array<{id:number;number:string;status:InvoiceStatus;total_gross:string}> };
export type InvoiceFilters = { q?:string; status?:string; type?:string; date_from?:string; date_to?:string; page?:number; per_page?:number };

export function listInvoices(token:string, query:InvoiceFilters) { return apiRequest<{invoices:Invoice[];pagination:{page:number;per_page:number;total:number;last_page:number}}>('/admin/invoices',{token,query}); }
export function getInvoice(token:string,id:number) { return apiRequest<{invoice:Invoice}>(`/admin/invoices/${id}`,{token}); }
export function issueInvoice(token:string,id:number) { return apiRequest<{invoice:Invoice}>(`/admin/invoices/${id}/issue`,{method:'POST',token}); }
export function createCreditNote(token:string,id:number,reason:string,items:Array<{index:number;qty:number}>,refundShipping:boolean) {
  return apiRequest<{invoice:Invoice}>(`/admin/invoices/${id}/credit-notes`, { method:'POST', token, body:{ reason, items, refund_shipping: refundShipping } });
}
export function cancelInvoice(token:string,id:number,reason:string) { return apiRequest<{invoice:Invoice}>(`/admin/invoices/${id}/cancel`,{method:'POST',token,body:{reason}}); }
export async function downloadInvoice(token:string,id:number,fileName:string) { const response=await fetch(`/admin/invoices/${id}/download`,{headers:{Authorization:`Bearer ${token}`}}); if(!response.ok) throw new Error('Файлът не можа да бъде изтеглен.'); saveBlob(await response.blob(),fileName); }
export async function previewInvoice(token:string,id:number) { const response=await fetch(`/admin/invoices/${id}/preview`,{headers:{Authorization:`Bearer ${token}`}}); if(!response.ok) throw new Error('PDF файлът не можа да бъде отворен.'); return URL.createObjectURL(await response.blob()); }
export async function exportInvoices(token:string,filters:InvoiceFilters) { const url=new URL('/admin/invoices/export',window.location.origin); Object.entries(filters).forEach(([key,value])=>{if(value)url.searchParams.set(key,String(value));}); const response=await fetch(url.pathname+url.search,{headers:{Authorization:`Bearer ${token}`}}); if(!response.ok) throw new Error('Експортът не можа да бъде създаден.'); saveBlob(await response.blob(),`fakturi-${filters.date_from||'nachalo'}-${filters.date_to||'dnes'}.csv`); }
function saveBlob(blob:Blob,name:string){const url=URL.createObjectURL(blob);const link=document.createElement('a');link.href=url;link.download=name;link.click();URL.revokeObjectURL(url);}
