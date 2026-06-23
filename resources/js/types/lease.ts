export type LeaseStatus =
    | 'draft'
    | 'pending'
    | 'active'
    | 'expiring_soon'
    | 'expired'
    | 'renewed'
    | 'terminated'
    | 'cancelled';

export type LeaseType = 'monthly' | 'yearly' | 'fixed_term' | 'commercial';
export type LeaseDepositType = 'security' | 'advance' | 'utility';
export type LeaseDepositStatus = 'pending' | 'paid' | 'partially_paid' | 'refunded';
export type LeaseRenewalStatus = 'pending' | 'approved' | 'rejected' | 'completed';
export type LeaseTerminationReason =
    | 'tenant_request'
    | 'non_payment'
    | 'violation_of_agreement'
    | 'property_sold'
    | 'other';

export type LeaseTenant = {
    id: string;
    full_name: string;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    profile_photo_url: string | null;
};

export type LeaseUnit = {
    id: string;
    unit_number: string;
    property_id: string | null;
    property: { id: string; name: string } | null;
    building: { id: string; name: string } | null;
};

export type LeaseDocument = {
    id: string;
    name: string;
    type: string;
    file_path: string;
    file_url: string;
    file_size: number | null;
    mime_type: string | null;
    uploaded_at: string | null;
};

export type LeaseDeposit = {
    id: string;
    type: LeaseDepositType;
    type_label: string;
    amount: number;
    payment_date: string | null;
    status: LeaseDepositStatus;
    status_label: string;
    refund_amount: number | null;
    deduction_amount: number | null;
    refund_date: string | null;
    notes: string | null;
};

export type LeaseRenewal = {
    id: string;
    previous_start_date: string | null;
    previous_end_date: string;
    new_end_date: string;
    new_rent_amount: number;
    renewal_date: string;
    renewal_status: LeaseRenewalStatus;
    renewal_status_label: string;
    reason: string | null;
    notes: string | null;
};

export type LeaseHistory = {
    id: string;
    event_type: string;
    description: string;
    metadata: Record<string, unknown> | null;
    occurred_at: string;
};

export type LeaseInvoiceSummary = {
    id: string;
    invoice_number: string;
    status: string;
    status_label: string;
    total_amount: number;
    due_date: string | null;
};

export type Lease = {
    id: string;
    lease_number: string;
    lease_type: LeaseType | null;
    lease_type_label: string | null;
    status: LeaseStatus | null;
    status_label: string | null;
    start_date: string | null;
    end_date: string | null;
    move_in_date: string | null;
    move_out_date: string | null;
    rent_amount: number;
    deposit_amount: number;
    advance_payment: number;
    utility_deposit: number;
    parking_fee: number;
    other_charges: number;
    currency: string;
    billing_day: number;
    billing_cycle: string | null;
    generate_days_before: number | null;
    issue_date_offset: number | null;
    days_remaining: number;
    months_remaining: number;
    progress_percentage: number;
    total_monthly_charges: number;
    termination_date: string | null;
    termination_reason: string | null;
    notes: string | null;
    unit_id: string | null;
    building_id: string | null;
    rental_tenant_id: string | null;
    tenant: LeaseTenant | null;
    unit: LeaseUnit | null;
    building: { id: string; name: string } | null;
    property_name: string | null;
    building_name: string | null;
    unit_number: string | null;
    documents_count: number;
    deposits_count: number;
    renewals_count: number;
    invoices_count: number;
    deposits?: LeaseDeposit[];
    renewals?: LeaseRenewal[];
    documents?: LeaseDocument[];
    histories?: LeaseHistory[];
    invoices?: LeaseInvoiceSummary[];
};

export type PaginatedLeases = {
    data: Lease[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type LeaseFilters = {
    search?: string;
    status?: string;
    lease_type?: string;
    property_id?: string;
    expiring_before?: string;
};
