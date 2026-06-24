export type DashboardRole = 'owner' | 'property_manager' | 'accountant' | 'staff';

export interface TrendPoint {
    month: string;
    label: string;
    value: number;
}

export interface ChartDataPoint {
    label: string;
    value: number;
    color?: string;
    count?: number;
}

export interface PropertyStats {
    total_properties: number;
    total_units: number;
    occupied_units: number;
    vacant_units: number;
    occupancy_rate: number;
}

export interface LeaseStats {
    active: number;
    expiring_soon: number;
    expiring_this_month: number;
    expired: number;
    renewed: number;
    terminated: number;
}

export interface MaintenanceStats {
    open: number;
    assigned: number;
    in_progress: number;
    resolved: number;
    emergency: number;
    avg_resolution_hours: number;
    total_cost_this_month: number;
}

export interface PropertyOverviewItem {
    id: string;
    name: string;
    total_units: number;
    occupied_units: number;
    vacant_units: number;
    occupancy_rate: number;
    monthly_income: number;
}

export interface LeaseOverviewItem {
    id: string;
    lease_number: string;
    tenant_name: string;
    property_name: string;
    unit_number: string;
    end_date: string;
    days_remaining: number;
    status: string;
    status_label: string;
}

export interface MaintenanceOverviewItem {
    id: string;
    ticket_number: string;
    title: string;
    property_name: string;
    unit_number: string;
    tenant_name: string;
    priority: string;
    priority_label: string;
    priority_color: string;
    status: string;
    status_label: string;
    assigned_to: string;
    created_at: string;
}

export interface ActivityItem {
    id: string;
    type: 'lease' | 'payment' | 'maintenance';
    title: string;
    description: string;
    meta: Record<string, string>;
    occurred_at: string;
}

export interface UpcomingEvent {
    type: 'lease_expiry' | 'invoice_overdue' | 'maintenance_emergency';
    title: string;
    description: string;
    due_at: string;
    priority: 'high' | 'medium' | 'emergency';
}

export interface RecentInvoice {
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

export interface UpcomingMoveOut {
    id: string;
    tenant_name: string;
    property_name: string;
    unit_number: string;
    move_out_date: string;
}

// Shared across all roles
export interface DashboardShared {
    role: DashboardRole;
    recent_activities: ActivityItem[];
    upcoming_events: UpcomingEvent[];
}

// Owner-specific
export interface OwnerDashboardProps extends DashboardShared {
    property_stats: PropertyStats;
    monthly_revenue: number;
    monthly_revenue_change: number;
    outstanding_balance: number;
    overdue_count: number;
    rent_collection_rate: number;
    lease_stats: LeaseStats;
    revenue_trend: TrendPoint[];
    occupancy_trend: TrendPoint[];
    property_occupancy: PropertyOverviewItem[];
    expiring_leases: LeaseOverviewItem[];
    maintenance_stats: MaintenanceStats;
    recent_tickets: MaintenanceOverviewItem[];
    revenue_by_property: ChartDataPoint[];
    deposits_held: number;
}

// Property Manager-specific
export interface PropertyManagerDashboardProps extends DashboardShared {
    property_stats: PropertyStats;
    tenant_count: number;
    lease_stats: LeaseStats;
    expiring_leases: LeaseOverviewItem[];
    upcoming_move_outs: UpcomingMoveOut[];
    maintenance_stats: MaintenanceStats;
    recent_tickets: MaintenanceOverviewItem[];
    occupancy_stats: {
        total_units: number;
        occupied_units: number;
        vacant_units: number;
        occupancy_rate: number;
    };
    property_occupancy: PropertyOverviewItem[];
}

// Accountant-specific
export interface AccountantDashboardProps extends DashboardShared {
    monthly_revenue: number;
    monthly_revenue_change: number;
    outstanding_balance: number;
    overdue_count: number;
    deposits_held: number;
    rent_collection_rate: number;
    revenue_trend: TrendPoint[];
    payment_method_breakdown: ChartDataPoint[];
    revenue_by_property: ChartDataPoint[];
    recent_invoices: RecentInvoice[];
}

// Staff-specific
export interface StaffDashboardProps extends DashboardShared {
    maintenance_stats: MaintenanceStats;
    assigned_tickets: MaintenanceOverviewItem[];
    monthly_trend: TrendPoint[];
    priority_distribution: ChartDataPoint[];
}

export type DashboardProps =
    | OwnerDashboardProps
    | PropertyManagerDashboardProps
    | AccountantDashboardProps
    | StaffDashboardProps;
