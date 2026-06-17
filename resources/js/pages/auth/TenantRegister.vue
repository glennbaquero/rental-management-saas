<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
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
import { store } from '@/routes/register';

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
}>();

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
</script>

<template>
    <Head title="Create account" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <!-- Hidden inputs to bridge reactive state into the form submission -->
        <input type="hidden" name="subdomain" :value="subdomain" />
        <input type="hidden" name="timezone" :value="selectedTimezone" />
        <input type="hidden" name="currency" :value="selectedCurrency" />

        <!-- Section A: Company Information -->
        <div class="grid gap-4">
            <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                Company Information
            </p>

            <div class="grid gap-2">
                <Label for="company_name">Company Name</Label>
                <Input
                    id="company_name"
                    name="company_name"
                    type="text"
                    v-model="companyName"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="organization"
                    placeholder="Acme Property Management"
                />
                <InputError :message="errors.company_name" />
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
                <InputError :message="errors.subdomain" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-2">
                    <Label>Timezone</Label>
                    <Select v-model="selectedTimezone">
                        <SelectTrigger :tabindex="3">
                            <SelectValue placeholder="Select timezone" />
                        </SelectTrigger>
                        <SelectContent class="max-h-60">
                            <SelectItem
                                v-for="tz in timezones"
                                :key="tz"
                                :value="tz"
                            >
                                {{ tz }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.timezone" />
                </div>

                <div class="grid gap-2">
                    <Label>Currency</Label>
                    <Select v-model="selectedCurrency">
                        <SelectTrigger :tabindex="4">
                            <SelectValue placeholder="Currency" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="c in currencies"
                                :key="c"
                                :value="c"
                            >
                                {{ c }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.currency" />
                </div>
            </div>
        </div>

        <Separator />

        <!-- Section B: Your Account -->
        <div class="grid gap-4">
            <p class="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                Your Account
            </p>

            <div class="grid gap-2">
                <Label for="name">Full Name</Label>
                <Input
                    id="name"
                    name="name"
                    type="text"
                    required
                    :tabindex="5"
                    autocomplete="name"
                    placeholder="Jane Smith"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email Address</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    required
                    :tabindex="6"
                    autocomplete="email"
                    placeholder="jane@acme.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="7"
                    autocomplete="new-password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm Password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    :tabindex="8"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>
        </div>

        <Button
            type="submit"
            class="w-full"
            :tabindex="9"
            :disabled="processing"
            data-test="create-account-button"
        >
            <Spinner v-if="processing" />
            Create account &amp; start free trial
        </Button>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink :href="login()" :tabindex="10">Log in</TextLink>
        </div>
    </Form>
</template>
