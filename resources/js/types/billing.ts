export interface BillingStats {
    monthly_revenue: number;
    outstanding_balance: number;
    collected_payments: number;
    overdue_count: number;
    late_fees_collected: number;
    paid_invoices_count: number;
}

export interface RevenueTrendPoint {
    month: string;
    revenue: number;
}

export interface InvoiceItem {
    id: string;
    description: string;
    quantity: number;
    unit_price: number;
    total_price: number;
    type: string | null;
}

export interface InvoicePaymentRow {
    id: string;
    payment_number: string;
    amount: number;
    payment_method: string;
    method_label: string;
    reference_number: string | null;
    transaction_id: string | null;
    payment_date: string;
    notes: string | null;
    status: string;
    status_label: string;
    proof_of_payment_url: string | null;
    verified_at: string | null;
    created_by_name: string | null;
}

export interface LateFeeRow {
    id: string;
    type: string;
    type_label: string;
    rate: number;
    amount: number;
    days_overdue: number;
    applied_at: string;
}

export interface InvoiceTenantRef {
    id: string;
    full_name: string;
    email: string | null;
    phone: string | null;
    tenant_code: string;
}

export interface InvoiceLeaseRef {
    id: string;
    lease_number: string;
    rent_amount: number;
    billing_day: number;
}

export interface Invoice {
    id: string;
    invoice_number: string;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    issue_date: string | null;
    billing_period_start: string | null;
    billing_period_end: string | null;
    due_date: string;
    subtotal: number;
    tax_amount: number;
    late_fee_amount: number;
    discount_amount: number;
    total_amount: number;
    paid_amount: number;
    balance_due: number;
    notes: string | null;
    sent_at: string | null;
    paid_at: string | null;
    voided_at: string | null;
    tenant_name: string;
    tenant_id: string | null;
    property_name: string;
    property_id: string | null;
    unit_number: string;
    building_name: string | null;
    tenant?: InvoiceTenantRef | null;
    lease?: InvoiceLeaseRef | null;
    items?: InvoiceItem[];
    payments?: InvoicePaymentRow[];
    late_fees?: LateFeeRow[];
}

export interface Payment {
    id: string;
    payment_number: string;
    invoice_id: string | null;
    invoice_number: string | null;
    tenant_name: string;
    tenant_id: string | null;
    amount: number;
    payment_method: string;
    method_label: string;
    reference_number: string | null;
    transaction_id: string | null;
    payment_date: string;
    notes: string | null;
    status: string;
    status_label: string;
    proof_of_payment_url: string | null;
    verified_at: string | null;
    verified_by_name: string | null;
    rejection_reason: string | null;
}

export interface LateFee {
    id: string;
    invoice_number: string | null;
    invoice_id: string;
    tenant_name: string;
    property_name: string;
    type: string;
    type_label: string;
    rate: number;
    amount: number;
    days_overdue: number;
    applied_at: string;
}

export interface BillingSettings {
    currency: string;
    timezone: string;
    invoice_prefix: string;
    invoice_number_format: string;
    grace_period_days: number;
    auto_generate_invoices: boolean;
    auto_send_reminders: boolean;
    late_fee_enabled: boolean;
    late_fee_type: string;
    late_fee_amount: number;
    late_fee_percentage: number;
    apply_late_fee_after_days: number;
    compound_monthly: boolean;
    reminder_days_before: number[];
    reminder_channels: string[];
}

export interface SelectOption {
    value: string;
    label: string;
}

export interface PaginatedInvoices {
    data: Invoice[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface PaginatedPayments {
    data: Payment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface PaginatedLateFees {
    data: LateFee[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface InvoiceFilters {
    search?: string;
    status?: string;
    property_id?: string;
    date_from?: string;
    date_to?: string;
}

export interface PaymentFilters {
    search?: string;
    method?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
}

export interface LeaseOption {
    value: string;
    label: string;
    rent_amount: number;
    billing_day: number;
}

export interface DashboardRecentInvoice {
    id: string;
    invoice_number: string;
    tenant_name: string;
    property_name: string;
    total_amount: number;
    balance_due: number;
    due_date: string;
    status: string;
    status_label: string;
}

export interface DashboardRecentPayment {
    id: string;
    payment_number: string;
    invoice_number: string | null;
    tenant_name: string;
    amount: number;
    payment_method: string;
    method_label: string;
    payment_date: string;
    status: string;
    status_label: string;
}
