<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/" class="flex items-center gap-3">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
                <span class="max-w-[16rem] text-base font-semibold leading-tight tracking-tight text-gray-800">
                    Office of the Student Affairs
                </span>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
