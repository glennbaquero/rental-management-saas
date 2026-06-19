export type PropertyType =
    | 'apartment'
    | 'condominium'
    | 'dormitory'
    | 'house'
    | 'townhouse'
    | 'commercial'
    | 'mixed_use';

export type PropertyStatus = 'active' | 'under_maintenance' | 'inactive';

export type BuildingStatus = 'active' | 'inactive';

export type UnitStatus = 'available' | 'occupied' | 'reserved' | 'maintenance' | 'unavailable';

export type AmenityCategory = 'property' | 'unit' | 'both';

export type SelectOption = {
    value: string;
    label: string;
};

export type Amenity = {
    id: string;
    name: string;
    slug: string | null;
    icon: string | null;
    category: AmenityCategory | null;
    is_system: boolean;
};

export type PropertyImage = {
    id: string;
    url: string;
    caption: string | null;
    sort_order: number;
};

export type PropertyStats = {
    total_buildings: number;
    total_units: number;
    available_units: number;
    occupied_units: number;
    reserved_units: number;
    maintenance_units: number;
    monthly_revenue: number;
};

export type Property = {
    id: string;
    name: string;
    code: string | null;
    type: PropertyType | null;
    type_label: string | null;
    description: string | null;
    address: string;
    city: string;
    province: string | null;
    state: string | null;
    zip: string | null;
    postal_code: string | null;
    country: string;
    latitude: number | null;
    longitude: number | null;
    status: PropertyStatus | null;
    status_label: string | null;
    featured_image_url: string | null;
    total_buildings: number;
    total_units: number;
    available_units: number;
    occupied_units: number;
    reserved_units: number;
    maintenance_units: number;
    monthly_revenue: number;
    amenities: Pick<Amenity, 'id' | 'name' | 'icon' | 'category'>[];
    images: PropertyImage[];
    created_at: string | null;
};

export type Building = {
    id: string;
    name: string;
    code: string | null;
    floors: number;
    description: string | null;
    status: BuildingStatus | null;
    status_label: string | null;
    total_units: number;
    occupied_units: number;
    available_units: number;
};

export type UnitType = {
    id: string;
    name: string;
    description: string | null;
    max_occupants: number | null;
};

export type Unit = {
    id: string;
    unit_number: string;
    floor: string | null;
    area_sqm: number | null;
    bedrooms: number;
    bathrooms: number;
    max_occupants: number;
    rent_amount: number;
    deposit_amount: number;
    status: UnitStatus | null;
    status_label: string | null;
    notes: string | null;
    building: { id: string; name: string } | null;
    unit_type: { id: string; name: string } | null;
};

export type PaginatedProperties = {
    data: Property[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type PaginatedUnits = {
    data: Unit[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type PropertyFilters = {
    search?: string;
    type?: string;
    status?: string;
    city?: string;
};
