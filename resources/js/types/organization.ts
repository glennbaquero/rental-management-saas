export type RoleName = 'owner' | 'property_manager' | 'staff' | 'accountant';

export type Role = {
    id: string;
    name: RoleName;
    display_name: string;
    permissions?: string[];
    users_count?: number;
};

export type OrgUser = {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    avatar: string | null;
    is_active: boolean;
    last_login_at: string | null;
    role: Pick<Role, 'id' | 'name' | 'display_name'> | null;
    created_at: string;
};

export type Organization = {
    company_name: string;
    company_email: string;
    company_phone: string | null;
    address: string | null;
    logo: string | null;
    timezone: string;
    currency: string;
    tax_id: string | null;
    status: 'trial' | 'active' | 'past_due' | 'canceled' | 'suspended';
};

export type OrgSubscription = {
    plan_name: string;
    billing_cycle: 'monthly' | 'annual';
    status: 'trial' | 'active' | 'past_due' | 'canceled';
    trial_ends_at: string | null;
    period_end: string | null;
    price: number;
};

export type PaginatedUsers = {
    data: OrgUser[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type PermissionCategories = Record<string, string[]>;

export type Permission =
    | 'organization.view_settings'
    | 'organization.manage_settings'
    | 'organization.manage_users'
    | 'organization.manage_billing'
    | 'properties.view'
    | 'properties.create'
    | 'properties.edit'
    | 'properties.delete'
    | 'tenants.view'
    | 'tenants.create'
    | 'tenants.edit'
    | 'tenants.delete'
    | 'leases.view'
    | 'leases.create'
    | 'leases.edit'
    | 'leases.delete'
    | 'invoices.view'
    | 'invoices.create'
    | 'invoices.edit'
    | 'invoices.delete'
    | 'payments.view'
    | 'payments.create'
    | 'payments.edit'
    | 'payments.delete'
    | 'maintenance.view'
    | 'maintenance.create'
    | 'maintenance.edit'
    | 'maintenance.manage'
    | 'reports.view'
    | 'reports.view_financial';
