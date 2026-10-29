<template>
    <GuestLayout title="Sign in" subtitle="Access your portal to track and manage your complaints." variant="Sign in to your account">
        <template #content>
            <form class="mt-10 grid gap-4" @submit.prevent="submit">
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
                    v-model="form.password"
                    label="Password"
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    :error="error('password')"
                    required
                    autocomplete="current-password"
                />
                <Button type="submit" variant="primary" :loading="loading" class="w-full">
                    {{ loading ? 'Signing in...' : 'Sign in' }}
                </Button>
            </form>

            <div class="mt-10 border-t border-surface-border pt-6">
                <p class="text-sm text-text-secondary text-center">
                    Don't have an account?{{ ' ' }}
                    <RouterLink class="font-medium text-primary hover:text-primary-dark" to="/register">Create one</RouterLink>
                </p>
                <p class="mt-3 text-sm text-text-secondary text-center">
                    <RouterLink class="text-primary hover:text-primary-dark" to="/forgot-password">Forgot your password?</RouterLink>
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

const auth = useAuthStore();
const router = useRouter();
const loading = ref(false);
const errors = ref({});
const form = reactive({ email: '', password: '' });

function error(key) {
    return errors.value[key]?.[0] ?? '';
}

async function submit() {
    loading.value = true;
    errors.value = {};

    try {
        await auth.login(form);
        router.push(auth.user?.is_staff ? '/admin/dashboard' : '/dashboard');
    } catch (error) {
        errors.value = error.response?.data?.errors ?? {};
    } finally {
        loading.value = false;
    }
}
</script>
