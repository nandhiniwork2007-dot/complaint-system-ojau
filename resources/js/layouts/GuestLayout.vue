<template>
    <div class="flex min-h-screen flex-col bg-bg px-4 py-6 sm:px-6 lg:px-8">
        <main class="flex flex-1 items-center justify-center">
            <div class="mx-auto w-full max-w-[420px]">
                <AuthSteps :steps="steps" :current="step" class="pt-2" />

                <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-linear-to-br from-primary to-secondary text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2-2z"
                                ></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold leading-tight text-text-main">Career <span class="text-primary">Next</span></div>
                            <div class="text-[10px] uppercase tracking-wider text-text-muted">Support Portal</div>
                        </div>
                    </div>
                    <ThemeControl />
                </div>

                <Card bordered elevated class="rounded-[2rem] p-8 shadow-lg sm:p-10">
                    <div class="mb-8 text-center">
                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-primary">
                            {{ variant }}
                        </p>
                        <h1 class="text-2xl font-bold leading-tight tracking-tight text-text-main sm:text-3xl">{{ title }}</h1>
                        <p v-if="subtitle" class="mt-2 text-sm text-text-secondary">
                            {{ subtitle }}
                        </p>
                    </div>

                    <slot name="content" />
                </Card>
            </div>
        </main>
        <AppFooter />
    </div>
</template>

<script setup>
import AuthSteps from '../components/AuthSteps.vue';
import Card from '../components/Card.vue';
import AppFooter from '../components/AppFooter.vue';
import ThemeControl from '../components/ThemeControl.vue';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    variant: { type: String, default: 'Career Next' },
    steps: { type: Array, default: () => [] },
    step: { type: Number, default: 1 },
});
</script>
