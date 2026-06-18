export type SubscriptionPlan = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    billing_cycle: 'monthly' | 'annual';
    max_properties: number | null;
    max_units: number | null;
    max_users: number | null;
    features: string[];
    stripe_price_id: string | null;
};

export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};
