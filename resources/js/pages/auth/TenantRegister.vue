<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { loadStripe } from '@stripe/stripe-js';
import type { Stripe, StripeCardElement } from '@stripe/stripe-js';
import { Check } from '@lucide/vue';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import PlanCard from '@/components/PlanCard.vue';
import TextLink from '@/components/TextLink.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import type { SubscriptionPlan } from '@/types';

defineOptions({
    layout: {
        title: 'Start your free trial',
        description: 'Create your company account and get started in minutes',
    },
});

const props = defineProps<{
    passwordRules: string;
    timezones: string[];
    currencies: string[];
    centralDomain: string;
    subscriptionPlans: SubscriptionPlan[];
    stripeKey: string;
}>();

const currentStep = ref<1 | 2 | 3>(1);

const companyName = ref('');
const subdomain = ref('');
const subdomainTouched = ref(false);
const selectedTimezone = ref('Asia/Manila');
const selectedCurrency = ref('PHP');

function toSubdomain(name: string): string {
    return name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 63);
}

watch(companyName, (val) => {
    if (!subdomainTouched.value) {
        subdomain.value = toSubdomain(val);
    }
});

const billingCycle = ref<'monthly' | 'annual'>('monthly');
const selectedPlanId = ref<string | null>(null);

const filteredPlans = computed(() =>
    props.subscriptionPlans.filter((p) => p.billing_cycle === billingCycle.value),
);

const selectedPlan = computed(() =>
    props.subscriptionPlans.find((p) => p.id === selectedPlanId.value) ?? null,
);

const popularSlugPrefix = 'professional';

const trialEndDate = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 28);
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
});

const stripeInstance = ref<Stripe | null>(null);
const cardElement = ref<StripeCardElement | null>(null);
const cardElementRef = ref<HTMLElement | null>(null);
const stripeError = ref<string | null>(null);

async function mountCardElement(): Promise<void> {
    if (!stripeInstance.value) {
        stripeInstance.value = await loadStripe(props.stripeKey);
    }
    if (!stripeInstance.value) {
        stripeError.value = 'Failed to load payment form. Please refresh the page.';
        return;
    }
    const elements = stripeInstance.value.elements();
    cardElement.value = elements.create('card', {
        style: {
            base: {
                fontFamily: 'inherit',
                fontSize: '14px',
            },
        },
    });
    cardElement.value.mount(cardElementRef.value!);
    cardElement.value.on('change', (e) => {
        stripeError.value = e.error?.message ?? null;
    });
}

onUnmounted(() => cardElement.value?.destroy());

const form = useForm({
    company_name: '',
    subdomain: '',
    timezone: 'Asia/Manila',
    currency: 'PHP',
    company_phone: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    plan_id: '',
    payment_method_id: '',
});

function goToStep2(): void {
    form.company_name = companyName.value;
    form.subdomain = subdomain.value;
    form.timezone = selectedTimezone.value;
    form.currency = selectedCurrency.value;

    if (!form.company_name || !form.subdomain || !form.name || !form.email || !form.password) {
        return;
    }
    currentStep.value = 2;
}

async function goToStep3(): Promise<void> {
    if (!selectedPlanId.value) return;
    form.plan_id = selectedPlanId.value;
    currentStep.value = 3;
    await nextTick();
    await mountCardElement();
}

async function submit(): Promise<void> {
    if (!stripeInstance.value || !cardElement.value) return;

    stripeError.value = null;

    const { paymentMethod, error } = await stripeInstance.value.createPaymentMethod({
        type: 'card',
        card: cardElement.value,
        billing_details: {
            name: form.name,
            email: form.email,
        },
    });

    if (error) {
        stripeError.value = error.message ?? 'Card error. Please try again.';
        return;
    }

    form.payment_method_id = paymentMethod!.id;

    form.company_name = companyName.value;
    form.subdomain = subdomain.value;
    form.timezone = selectedTimezone.value;
    form.currency = selectedCurrency.value;

    form.post('/register', {
        preserveState: true,
        onError: (errors) => {
            stripeError.value = errors.payment_method_id ?? (Object.keys(errors).length ? 'Payment failed. Please check your card details.' : null);
            currentStep.value = 3;
        },
    });
}
</script>

<template>
    <Head title="Create account" />

    <div class="flex flex-col gap-6">
        <div class="flex items-center gap-2">
            <div
                v-for="(label, i) in ['Your Details', 'Choose Plan', 'Payment']"
                :key="i"
                class="flex items-center gap-2"
                :class="i < 2 ? 'flex-1' : ''"
            >
                <div class="flex items-center gap-1.5 shrink-0">
                    <div
                        class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold transition-colors"
                        :class="
                            currentStep > i + 1
                                ? 'bg-primary text-primary-foreground'
                                : currentStep === i + 1
                                  ? 'bg-primary text-primary-foreground'
                                  : 'bg-muted text-muted-foreground'
                        "
                    >
                        <Check v-if="currentStep > i + 1" class="h-3.5 w-3.5" />
                        <span v-else>{{ i + 1 }}</span>
                    </div>
                    <span
                        class="text-xs hidden sm:inline"
                        :class="currentStep === i + 1 ? 'text-foreground font-medium' : 'text-muted-foreground'"
                    >
                        {{ label }}
                    </span>
                </div>
                <div v-if="i < 2" class="flex-1 h-px bg-border" />
            </div>
        </div>

        <template v-if="currentStep === 1">
            <div class="grid gap-4">
                <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                    Company Information
                </p>

                <div class="grid gap-2">
                    <Label for="company_name">Company Name</Label>
                    <Input
                        id="company_name"
                        type="text"
                        v-model="companyName"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="organization"
                        placeholder="Acme Property Management"
                    />
                    <InputError :message="form.errors.company_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="subdomain_input">Subdomain</Label>
                    <Input
                        id="subdomain_input"
                        type="text"
                        v-model="subdomain"
                        @input="subdomainTouched = true"
                        :tabindex="2"
                        placeholder="acme"
                        pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]"
                        minlength="3"
                        maxlength="63"
                    />
                    <p class="text-xs text-muted-foreground">
                        Your workspace:
                        <span class="font-medium text-foreground">
                            {{ subdomain || 'yourname' }}.{{ centralDomain }}
                        </span>
                    </p>
                    <InputError :message="form.errors.subdomain" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label>Timezone</Label>
                        <Select v-model="selectedTimezone">
                            <SelectTrigger :tabindex="3">
                                <SelectValue placeholder="Select timezone" />
                            </SelectTrigger>
                            <SelectContent class="max-h-60">
                                <SelectItem v-for="tz in timezones" :key="tz" :value="tz">
                                    {{ tz }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.timezone" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Currency</Label>
                        <Select v-model="selectedCurrency">
                            <SelectTrigger :tabindex="4">
                                <SelectValue placeholder="Currency" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in currencies" :key="c" :value="c">
                                    {{ c }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.currency" />
                    </div>
                </div>
            </div>

            <Separator />

            <div class="grid gap-4">
                <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                    Your Account
                </p>

                <div class="grid gap-2">
                    <Label for="name">Full Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        :tabindex="5"
                        autocomplete="name"
                        placeholder="Jane Smith"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email Address</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        :tabindex="6"
                        autocomplete="email"
                        placeholder="jane@acme.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        v-model="form.password"
                        required
                        :tabindex="7"
                        autocomplete="new-password"
                        placeholder="Password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm Password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        required
                        :tabindex="8"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                        :passwordrules="passwordRules"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>

            <Button type="button" class="w-full" :tabindex="9" @click="goToStep2">
                Continue
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="login()" :tabindex="10">Log in</TextLink>
            </div>
        </template>

        <template v-else-if="currentStep === 2">
            <div class="flex items-center justify-center gap-3">
                <button
                    type="button"
                    class="text-sm transition-colors"
                    :class="billingCycle === 'monthly' ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                    @click="billingCycle = 'monthly'"
                >
                    Monthly
                </button>

                <button
                    type="button"
                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="billingCycle === 'annual' ? 'bg-primary' : 'bg-input'"
                    @click="billingCycle = billingCycle === 'monthly' ? 'annual' : 'monthly'"
                >
                    <span
                        class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                        :class="billingCycle === 'annual' ? 'translate-x-[18px]' : 'translate-x-[3px]'"
                    />
                </button>

                <button
                    type="button"
                    class="flex items-center gap-1.5 text-sm transition-colors"
                    :class="billingCycle === 'annual' ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                    @click="billingCycle = 'annual'"
                >
                    Annual
                    <Badge variant="secondary" class="text-[10px] px-1.5 py-0">Save 17%</Badge>
                </button>
            </div>

            <div class="flex items-start gap-3 rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm">
                <span class="mt-0.5 text-base leading-none">🎉</span>
                <div>
                    <p class="font-medium text-foreground">28-day free trial — no charge today</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">Enter your card to reserve your plan. You won't be billed until your trial ends. Cancel anytime.</p>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <PlanCard
                    v-for="plan in filteredPlans"
                    :key="plan.id"
                    :plan="plan"
                    :selected="selectedPlanId === plan.id"
                    :popular="plan.slug.startsWith(popularSlugPrefix)"
                    @select="selectedPlanId = plan.id"
                />
            </div>

            <InputError :message="form.errors.plan_id" />

            <div class="flex gap-3">
                <Button type="button" variant="outline" class="flex-1" @click="currentStep = 1">
                    Back
                </Button>
                <Button
                    type="button"
                    class="flex-1"
                    :disabled="!selectedPlanId"
                    @click="goToStep3"
                >
                    Continue to Payment
                </Button>
            </div>
        </template>

        <template v-else>
            <div
                v-if="selectedPlan"
                class="rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm space-y-1"
            >
                <p class="font-medium text-foreground">
                    {{ selectedPlan.name }}
                    <span class="text-muted-foreground font-normal">
                        — ${{ selectedPlan.price.toFixed(2) }}/{{
                            selectedPlan.billing_cycle === 'monthly' ? 'month' : 'year'
                        }} after trial
                    </span>
                </p>
                <p class="text-xs text-muted-foreground">
                    Your 28-day free trial starts today. No charge until {{ trialEndDate }}.
                </p>
            </div>

            <div class="grid gap-2">
                <Label>Card Details</Label>
                <div
                    ref="cardElementRef"
                    class="rounded-md border border-input bg-background px-3 py-[9px] min-h-[38px]"
                />
                <InputError :message="stripeError ?? undefined" />
                <InputError :message="form.errors.payment_method_id" />
            </div>

            <div class="flex gap-3">
                <Button
                    type="button"
                    variant="outline"
                    class="flex-1"
                    :disabled="form.processing"
                    @click="currentStep = 2"
                >
                    Back
                </Button>
                <Button
                    type="button"
                    class="flex-1"
                    :disabled="form.processing || !!stripeError"
                    @click="submit"
                >
                    <Spinner v-if="form.processing" />
                    Start Free Trial
                </Button>
            </div>

            <p class="text-center text-xs text-muted-foreground">
                Secured by Stripe. Your card details are never stored on our servers.
            </p>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="login()">Log in</TextLink>
            </div>
        </template>
    </div>
</template>
