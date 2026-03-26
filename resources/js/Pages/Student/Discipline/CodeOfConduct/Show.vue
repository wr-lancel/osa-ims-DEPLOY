<script setup>
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    slug: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    severity: {
        type: String,
        default: 'info',
    },
    content: {
        type: String,
        default: '',
    },
});

const severityConfig = computed(() => {
    const configs = {
        major: {
            label: 'Major Offense',
            bg: 'bg-red-100 dark:bg-red-900/30',
            text: 'text-red-800 dark:text-red-300',
            border: 'border-red-200 dark:border-red-800',
            icon: '!',
        },
        minor: {
            label: 'Minor Offense',
            bg: 'bg-yellow-100 dark:bg-yellow-900/30',
            text: 'text-yellow-800 dark:text-yellow-300',
            border: 'border-yellow-200 dark:border-yellow-800',
            icon: '!',
        },
        mixed: {
            label: 'Minor / Major Offense',
            bg: 'bg-orange-100 dark:bg-orange-900/30',
            text: 'text-orange-800 dark:text-orange-300',
            border: 'border-orange-200 dark:border-orange-800',
            icon: '!',
        },
        info: {
            label: 'Information',
            bg: 'bg-blue-100 dark:bg-blue-900/30',
            text: 'text-blue-800 dark:text-blue-300',
            border: 'border-blue-200 dark:border-blue-800',
            icon: 'i',
        },
    };
    return configs[props.severity] || configs.info;
});

const formattedParagraphs = computed(() => {
    return props.content.split('\n\n').map(paragraph => {
        const lines = paragraph.split('\n');
        const items = [];
        let currentGroup = { type: 'text', lines: [] };

        lines.forEach(line => {
            const trimmed = line.trim();
            if (trimmed.startsWith('•')) {
                if (currentGroup.type !== 'list') {
                    if (currentGroup.lines.length) items.push({ ...currentGroup });
                    currentGroup = { type: 'list', lines: [] };
                }
                currentGroup.lines.push(trimmed.substring(1).trim());
            } else {
                if (currentGroup.type !== 'text') {
                    if (currentGroup.lines.length) items.push({ ...currentGroup });
                    currentGroup = { type: 'text', lines: [] };
                }
                if (trimmed) currentGroup.lines.push(trimmed);
            }
        });

        if (currentGroup.lines.length) items.push({ ...currentGroup });
        return items;
    });
});
</script>

<template>
    <Head :title="title" />

    <StudentLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ title }}
                </h2>
                <Link
                    :href="route('student.discipline.code-of-conduct.index')"
                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 text-sm self-start sm:self-auto"
                >
                    &larr; Code of Conduct
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Severity Badge -->
            <div
                :class="[severityConfig.bg, severityConfig.border]"
                class="rounded-lg border px-4 py-3 flex items-center gap-3"
            >
                <span
                    :class="[severityConfig.text]"
                    class="inline-flex items-center justify-center w-6 h-6 rounded-full border-2 border-current text-xs font-bold flex-shrink-0"
                >
                    {{ severityConfig.icon }}
                </span>
                <span :class="[severityConfig.text]" class="text-sm font-semibold">
                    Classification: {{ severityConfig.label }}
                </span>
            </div>

            <!-- Content -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                <div class="space-y-4">
                    <div v-for="(paragraph, pIdx) in formattedParagraphs" :key="pIdx">
                        <template v-for="(group, gIdx) in paragraph" :key="gIdx">
                            <!-- Regular text lines -->
                            <p
                                v-if="group.type === 'text'"
                                v-for="(line, lIdx) in group.lines"
                                :key="'t-' + lIdx"
                                class="text-sm text-gray-700 dark:text-gray-200"
                                :class="{
                                    'font-semibold text-gray-900 dark:text-white': line.endsWith(':'),
                                    'mt-1': lIdx > 0,
                                }"
                            >
                                {{ line }}
                            </p>

                            <!-- Bullet list -->
                            <ul
                                v-if="group.type === 'list'"
                                class="list-disc list-inside space-y-1 ml-2 mt-1"
                            >
                                <li
                                    v-for="(item, iIdx) in group.lines"
                                    :key="'l-' + iIdx"
                                    class="text-sm text-gray-700 dark:text-gray-200"
                                >
                                    {{ item }}
                                </li>
                            </ul>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Reference -->
            <p class="text-xs text-gray-400 dark:text-gray-500 italic">
                Reference: Student Handbook 2024
            </p>
        </div>
    </StudentLayout>
</template>
