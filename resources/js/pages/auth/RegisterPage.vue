<template>
    <GuestLayout
        title="Create account"
        subtitle="Start submitting and tracking complaints online."
        variant="Create your account"
        :steps="registrationSteps"
        :step="1"
    >
        <template #content>
            <form class="mt-10 grid gap-4" @submit.prevent="submit">
                <Input
                    v-model="form.name"
                    label="Full name"
                    name="name"
                    placeholder="Alex Rivera"
                    :error="error('name')"
                    required
                    autocomplete="name"
                />
                <Input
                    v-model="form.email"
                    label="Email address"
                    type="email"
                    name="email"
                    placeholder="you@company.com"
                    :error="error('email')"
                    required
                    autocomplete="email"
                />
                <Input
                    v-model="form.phone"
                    label="Phone number"
                    name="phone"
                    placeholder="+1 (555) 000-0000"
                    autocomplete="tel"
                />
                <Input
                    v-model="form.password"
                    label="Password"
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    :error="error('password')"
                    required
                    autocomplete="new-password"
                />
                <Input
                    v-model="form.password_confirmation"
                    label="Confirm password"
                    type="password"
                    name="password_confirmation"
                    placeholder="••••••••"
                    :error="error('password_confirmation')"
                    required
                    autocomplete="new-password"
                />
                <Button type="submit" variant="primary" :loading="loading" class="w-full">
                    {{ loading ? 'Creating account...' : 'Create account' }}
                </Button>
            </form>

            <div class="mt-10 border-t border-surface-border pt-6">
                <p class="text-sm text-text-secondary text-center">
                    Already registered?{{ ' ' }}
                    <RouterLink class="font-medium text-primary hover:text-primary-dark" to="/login">Sign in</RouterLink>
                </p>
            </div>
        </template>
    </GuestLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import GuestLayout from '../../layouts/GuestLayout.vue';
import Input from '../../components/Input.vue';
import Button from '../../components/Button.vue';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toasts';

const auth = useAuthStore();
const router = useRouter();
const toast = useToastStore();
const loading = ref(false);
const errors = ref({});
const form = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' });

const registrationSteps = ['Create account', 'Verify email', 'Get started'];

function error(key) {
    return errors.value[key]?.[0] ?? '';
}

async function submit() {
    loading.value = true;
    errors.value = {};

    try {
        const data = await auth.register(form);

        if (data.mail_warning) {
            const message = data.mail_error ? `${data.mail_warning} ${data.mail_error}` : data.mail_warning;
            toast.error(message);
        } else {
            toast.success('Account created. Check your inbox to verify your email.');
        }

        router.push('/verify-email');
    } catch (error) {
        errors.value = error.response?.data?.errors ?? {};
    } finally {
        loading.value = false;
    }
}
</script>
