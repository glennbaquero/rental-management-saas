import type { SelectOption } from '@/types/property';

export type RentalTenantStatus = 'prospect' | 'active' | 'moved_out' | 'blacklisted';
export type Gender = 'male' | 'female' | 'other';
export type CivilStatus = 'single' | 'married' | 'widowed' | 'divorced' | 'separated';
export type DocumentVerificationStatus = 'pending' | 'verified' | 'rejected';
export type IdDocumentType =
    | 'national_id'
    | 'passport'
    | 'drivers_license'
    | 'sss'
    | 'tin'
    | 'residence_permit'
    | 'other';
export type TenantFileType =
    | 'lease_agreement'
    | 'proof_of_income'
    | 'employment_certificate'
    | 'utility_bill'
    | 'police_clearance'
    | 'other';

export type CurrentLease = {
    id: string;
    unit_number: string | null;
    building_name: string | null;
    property_name: string | null;
    property_id: string | null;
    rent_amount: number;
    start_date: string | null;
    end_date: string | null;
};

export type RentalTenant = {
    id: string;
    tenant_code: string | null;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    full_name: string;
    profile_photo_url: string | null;
    date_of_birth: string | null;
    gender: Gender | null;
    gender_label: string | null;
    civil_status: CivilStatus | null;
    civil_status_label: string | null;
    nationality: string | null;
    email: string | null;
    phone: string | null;
    alternate_phone: string | null;
    current_address: string | null;
    city: string | null;
    province: string | null;
    country: string | null;
    postal_code: string | null;
    occupation: string | null;
    employer: string | null;
    employer_address: string | null;
    monthly_income: number | null;
    status: RentalTenantStatus | null;
    status_label: string | null;
    notes: string | null;
    leases_count: number;
    id_documents_count: number;
    emergency_contacts_count: number;
    current_lease: CurrentLease | null;
    created_at: string | null;
};

export type TenantIdDocument = {
    id: string;
    type: IdDocumentType | null;
    type_label: string | null;
    document_number: string | null;
    issued_by: string | null;
    issued_date: string | null;
    expiry_date: string | null;
    front_image_url: string | null;
    back_image_url: string | null;
    verification_status: DocumentVerificationStatus | null;
    verification_status_label: string | null;
    is_expired: boolean;
};

export type EmergencyContact = {
    id: string;
    name: string;
    relationship: string;
    phone: string;
    alternate_number: string | null;
    email: string | null;
    address: string | null;
    is_primary: boolean;
};

export type TenantFile = {
    id: string;
    name: string;
    type: TenantFileType | null;
    type_label: string | null;
    url: string;
    mime_type: string | null;
    file_size: number | null;
    created_at: string;
};

export type RentalHistoryEntry = {
    id: string;
    property_name: string;
    building_name: string | null;
    unit_number: string;
    start_date: string | null;
    end_date: string | null;
    monthly_rent: number;
    deposit_amount: number;
    status: string | null;
    status_label: string | null;
    move_in_date: string | null;
    move_out_date: string | null;
    remarks: string | null;
};

export type PaginatedTenants = {
    data: RentalTenant[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type TenantFilters = {
    search?: string;
    status?: string;
};

export type { SelectOption };
