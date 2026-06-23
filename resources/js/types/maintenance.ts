export type MaintenanceStatus =
    | 'open'
    | 'assigned'
    | 'in_progress'
    | 'waiting_for_parts'
    | 'on_hold'
    | 'resolved'
    | 'completed'
    | 'cancelled';

export type MaintenancePriority = 'low' | 'medium' | 'high' | 'urgent' | 'emergency';

export type MaintenanceCategory =
    | 'plumbing'
    | 'electrical'
    | 'air_conditioning'
    | 'appliance_repair'
    | 'internet_wifi'
    | 'water_leak'
    | 'painting'
    | 'pest_control'
    | 'structural_damage'
    | 'cleaning'
    | 'security'
    | 'other';

export type MaintenanceCostType = 'labor' | 'material' | 'contractor_fee' | 'transportation' | 'miscellaneous';
export type MaintenanceCostStatus = 'pending' | 'approved' | 'paid';
export type MaintenanceCommentType = 'internal' | 'tenant' | 'staff';
export type MaintenanceAssigneeType = 'property_manager' | 'maintenance_staff' | 'external_contractor';

export type SelectOption = { value: string; label: string };
export type PriorityOption = SelectOption & { icon: string };

export type MaintenanceAssignment = {
    id: string;
    assignee_type: MaintenanceAssigneeType;
    assignee_type_label: string;
    user: { id: string; name: string; avatar: string | null } | null;
    contractor_name: string | null;
    contractor_contact: string | null;
    assigned_date: string;
    estimated_completion: string | null;
    actual_completion: string | null;
    remarks: string | null;
    is_primary: boolean;
};

export type MaintenanceAttachmentItem = {
    id: string;
    name: string;
    url: string;
    mime_type: string;
    file_size_formatted?: string;
    is_image: boolean;
    is_video: boolean;
    uploaded_by?: string;
    created_at?: string;
};

export type MaintenanceComment = {
    id: string;
    comment_type: MaintenanceCommentType;
    comment_type_label: string;
    body: string;
    is_pinned: boolean;
    user: { id: string; name: string; avatar: string | null } | null;
    attachments: MaintenanceAttachmentItem[];
    created_at: string;
};

export type MaintenanceCost = {
    id: string;
    cost_type: MaintenanceCostType;
    cost_type_label: string;
    description: string;
    amount: number;
    status: MaintenanceCostStatus;
    status_label: string;
    status_color: string;
    added_by: string;
    approved_by: string | null;
    approved_at: string | null;
    created_at: string;
};

export type MaintenanceHistory = {
    id: string;
    event_type: string;
    description: string;
    metadata: Record<string, unknown> | null;
    occurred_at: string;
    created_by: string;
};

export type MaintenanceRating = {
    id: string;
    rating: number;
    feedback: string | null;
    would_recommend: boolean | null;
    rated_at: string;
};

export type MaintenanceTicketRow = {
    id: string;
    ticket_number: string;
    title: string;
    category: MaintenanceCategory;
    category_label: string;
    priority: MaintenancePriority;
    priority_label: string;
    priority_icon: string;
    priority_color: string;
    status: MaintenanceStatus;
    status_label: string;
    status_color: string;
    tenant_name: string;
    property_name: string;
    unit_number: string;
    assigned_to: string;
    created_at: string;
    date_submitted: string;
};

export type MaintenanceTicketDetail = MaintenanceTicketRow & {
    description: string;
    notes: string | null;
    preferred_schedule: string | null;
    resolved_at: string | null;
    completed_at: string | null;
    is_overdue: boolean;
    total_cost: number;
    property: { id: string; name: string } | null;
    building: { id: string; name: string } | null;
    unit: { id: string; unit_number: string } | null;
    rental_tenant: {
        id: string;
        name: string;
        email: string | null;
        phone: string | null;
    } | null;
    assignments: MaintenanceAssignment[];
    comments: MaintenanceComment[];
    attachments: MaintenanceAttachmentItem[];
    costs: MaintenanceCost[];
    histories: MaintenanceHistory[];
    rating: MaintenanceRating | null;
};

export type PaginatedMaintenanceTickets = {
    data: MaintenanceTicketRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type MaintenanceFilters = {
    search?: string;
    status?: string;
    priority?: string;
    property_id?: string;
    category?: string;
    assigned_to?: string;
};

export type MaintenanceDashboardStats = {
    open_count: number;
    in_progress_count: number;
    resolved_this_month: number;
    emergency_count: number;
    avg_resolution_hours: number;
    total_cost_this_month: number;
};

export type MaintenanceMonthlyPoint = {
    month: string;
    count: number;
};

export type MaintenancePriorityDistribution = {
    priority: MaintenancePriority;
    label: string;
    color: string;
    count: number;
};

export type MaintenanceCostByCategory = {
    category: MaintenanceCategory;
    total: number;
};
