<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { getEventBadgeClass, getEventLabel } from '@/utils/eventHelpers';
import DashboardBarChart from '@/Components/Charts/DashboardBarChart.vue';
import DashboardGroupedBarChart from '@/Components/Charts/DashboardGroupedBarChart.vue';
import DashboardLineChart from '@/Components/Charts/DashboardLineChart.vue';
import DashboardDoughnutChart from '@/Components/Charts/DashboardDoughnutChart.vue';
import DashboardPolarChart from '@/Components/Charts/DashboardPolarChart.vue';
import DashboardComboChart from '@/Components/Charts/DashboardComboChart.vue';
import DashboardRadarChart from '@/Components/Charts/DashboardRadarChart.vue';

const page = usePage();
const accessibleModules = computed(() => page.props.auth?.accessible_modules || []);
const canAccessStudents = computed(() => accessibleModules.value.includes('students'));
const canAccessDiscipline = computed(() => accessibleModules.value.includes('discipline'));
const canAccessGuidance = computed(() => accessibleModules.value.includes('guidance'));
const canAccessOrganizations = computed(() => accessibleModules.value.includes('organizations'));

const props = defineProps({
    upcomingEvents: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    academicTermLabel: {
        type: String,
        default: null,
    },
    comparison: {
        type: Object,
        default: null,
    },
    chartTermSummary: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartComparison: {
        type: Object,
        default: null,
    },
    chartEventsByMonth: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartDisciplineByType: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartComplaintsByCategory: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartDisciplineByCourse: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartGuidanceByCourse: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartDisciplineBySeverity: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartEnrollmentByYearLevel: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartEventsByOrganization: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartViolationsPerMonth: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    riskSummary: {
        type: Object,
        default: () => ({ high: 0, moderate: 0, low: 0, not_computed: 0, total: 0 }),
    },
    chartRiskLevelDistribution: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    chartRiskByCourse: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
    topAtRiskStudents: {
        type: Array,
        default: () => [],
    },
});

const hasRiskData = () => (props.riskSummary?.high ?? 0) + (props.riskSummary?.moderate ?? 0) + (props.riskSummary?.low ?? 0) > 0;
const hasRiskByCourseData = () => props.chartRiskByCourse?.values?.length > 0;

const hasTermSummaryData = () =>
    props.chartTermSummary?.values?.some((v) => v > 0) ?? false;
const hasEventsByMonthData = () =>
    props.chartEventsByMonth?.values?.some((v) => v > 0) ?? false;
const hasDisciplineByTypeData = () =>
    props.chartDisciplineByType?.values?.length > 0 && props.chartDisciplineByType?.values?.some((v) => v > 0);
const hasComplaintsByCategoryData = () =>
    props.chartComplaintsByCategory?.values?.length > 0 && props.chartComplaintsByCategory?.values?.some((v) => v > 0);
const hasDisciplineByCourseData = () =>
    props.chartDisciplineByCourse?.values?.length > 0 && props.chartDisciplineByCourse?.values?.some((v) => v > 0);
const hasGuidanceByCourseData = () =>
    props.chartGuidanceByCourse?.values?.length > 0 && props.chartGuidanceByCourse?.values?.some((v) => v > 0);
const hasDisciplineBySeverityData = () =>
    props.chartDisciplineBySeverity?.values?.length > 0 && props.chartDisciplineBySeverity?.values?.some((v) => v > 0);
const hasEnrollmentByYearLevelData = () =>
    props.chartEnrollmentByYearLevel?.values?.length > 0 && props.chartEnrollmentByYearLevel?.values?.some((v) => v > 0);
const hasEventsByOrganizationData = () =>
    props.chartEventsByOrganization?.values?.length > 0 && props.chartEventsByOrganization?.values?.some((v) => v > 0);
const hasViolationsPerMonthData = () =>
    props.chartViolationsPerMonth?.values?.length > 0 && props.chartViolationsPerMonth?.values?.some((v) => v > 0);
const hasEnrollmentPerSemesterData = () =>
    props.chartEnrollmentPerSemester?.values?.length > 0 && props.chartEnrollmentPerSemester?.values?.some((v) => v > 0);
</script>

<template>

    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Admin Dashboard
                </h2>
                <div v-if="academicTermLabel" class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-lg">
                        {{ academicTermLabel }}
                    </span>
                    <Link :href="route('admin.reports.term-summary')"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                        Term summary
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-10">

            <!-- ─── OVERVIEW ─── -->
            <section>
                <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4 border-l-2 border-indigo-400 pl-3">
                    Overview
                </h3>
                <div class="space-y-5">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Term summary</h4>
                            <Link :href="route('admin.reports.term-summary')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View report</Link>
                        </div>
                        <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term selected.</div>
                        <div v-else-if="!hasTermSummaryData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No data for this term.</div>
                        <div v-else class="h-[280px]">
                            <DashboardBarChart :labels="chartTermSummary?.labels ?? []" :values="chartTermSummary?.values ?? []" label="This Term" :horizontal="true" :colors="['#6366f1', '#f43f5e', '#f97316', '#8b5cf6', '#0ea5e9', '#10b981', '#eab308']" :max-height="280" />
                        </div>
                    </div>
                    <div v-if="chartComparison" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">
                            Semester comparison
                            <span v-if="chartComparison.previousTermLabel" class="text-sm font-normal text-gray-500 dark:text-gray-400">vs {{ chartComparison.previousTermLabel }}</span>
                        </h4>
                        <div class="h-[280px]">
                            <DashboardGroupedBarChart :labels="chartComparison.labels" :current-values="chartComparison.currentValues" :previous-values="chartComparison.previousValues" current-label="Current term" :previous-label="chartComparison.previousTermLabel || 'Previous term'" current-color="#6366f1" previous-color="#cbd5e1" :max-height="280" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- ─── EVENTS & ORGANIZATIONS ─── -->
            <section>
                <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4 border-l-2 border-indigo-400 pl-3">Events &amp; Organizations</h3>
                <div class="space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Events this term</h4>
                                <Link v-if="canAccessOrganizations" :href="route('admin.organizations.events.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View events</Link>
                            </div>
                            <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                            <div v-else-if="!hasEventsByMonthData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No events for this term.</div>
                            <div v-else class="h-[260px]">
                                <DashboardLineChart :labels="chartEventsByMonth?.labels ?? []" :values="chartEventsByMonth?.values ?? []" label="Events" border-color="#0ea5e9" background-color="rgba(14, 165, 233, 0.12)" :max-height="260" />
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Events by organization</h4>
                                <Link v-if="canAccessOrganizations" :href="route('admin.organizations.events.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View events</Link>
                            </div>
                            <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                            <div v-else-if="!hasEventsByOrganizationData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No events for this term.</div>
                            <div v-else class="h-[260px]">
                                <DashboardBarChart :labels="chartEventsByOrganization?.labels ?? []" :values="chartEventsByOrganization?.values ?? []" label="Events" :horizontal="true" :colors="['#0ea5e9', '#06b6d4', '#14b8a6', '#10b981', '#22c55e', '#84cc16', '#eab308', '#f59e0b', '#f97316', '#ef4444']" :max-height="260" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <div class="flex items-center justify-between px-5 pt-5 pb-3">
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Upcoming Events</h4>
                            <Link v-if="canAccessOrganizations" :href="route('admin.organizations.events.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View all</Link>
                        </div>
                        <div v-if="upcomingEvents && upcomingEvents.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="event in upcomingEvents" :key="event.event_id" class="px-5 py-3 hover:bg-gray-50 dark:bg-gray-900/60 dark:hover:bg-gray-700/40 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ event.event_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ event.organization_name }}</p>
                                        <div class="flex items-center mt-1.5 text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400">
                                            <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            {{ event.event_date_display }}<span v-if="event.start_time" class="ml-2">{{ event.start_time }}</span>
                                        </div>
                                        <div v-if="event.venue" class="flex items-center mt-0.5 text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400">
                                            <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ event.venue }}
                                        </div>
                                    </div>
                                    <span class="ml-2 inline-flex px-2 py-0.5 text-xs font-semibold rounded-full whitespace-nowrap" :class="getEventBadgeClass(event.days_until)">{{ getEventLabel(event.days_until) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-5 py-10 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <p class="mt-2 text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400">No upcoming events</p>
                            <Link v-if="canAccessOrganizations" :href="route('admin.organizations.events.index')" class="mt-2 inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">Create an event<svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ─── STUDENTS ─── -->
            <section>
                <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4 border-l-2 border-indigo-400 pl-3">Students</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Enrollment by year level</h4>
                            <Link v-if="canAccessStudents" :href="route('admin.students.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View student records</Link>
                        </div>
                        <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                        <div v-else-if="!hasEnrollmentByYearLevelData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No enrollment data for this term.</div>
                        <div v-else class="h-[280px]">
                            <DashboardDoughnutChart :labels="chartEnrollmentByYearLevel?.labels ?? []" :values="chartEnrollmentByYearLevel?.values ?? []" :colors="['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e']" :max-height="280" />
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Historical enrollment trend</h4>
                        </div>
                        <div v-if="!hasEnrollmentPerSemesterData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No historical enrollment data.</div>
                        <div v-else class="h-[280px]">
                            <DashboardComboChart :labels="chartEnrollmentPerSemester?.labels ?? []" :values="chartEnrollmentPerSemester?.values ?? []" bar-label="Enrolled" line-label="Avg" :bar-colors="['#8b5cf6', '#d946ef', '#ec4899', '#f43f5e']" :max-height="280" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- ─── DISCIPLINE & COMPLAINTS ─── -->
            <section>
                <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4 border-l-2 border-indigo-400 pl-3">Discipline &amp; Complaints</h3>
                <div class="space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Discipline by type</h4>
                                <Link v-if="canAccessDiscipline" :href="route('admin.discipline.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View discipline</Link>
                            </div>
                            <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                            <div v-else-if="!hasDisciplineByTypeData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No discipline data for this term.</div>
                            <div v-else class="h-[260px]">
                                <DashboardDoughnutChart :labels="chartDisciplineByType?.labels ?? []" :values="chartDisciplineByType?.values ?? []" :colors="['#ec4899', '#6366f1', '#f97316', '#14b8a6', '#8b5cf6', '#eab308', '#06b6d4', '#f43f5e', '#10b981', '#a855f7']" :max-height="260" />
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Violation by severity</h4>
                                <Link v-if="canAccessDiscipline" :href="route('admin.discipline.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View discipline</Link>
                            </div>
                            <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                            <div v-else-if="!hasDisciplineBySeverityData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No discipline data for this term.</div>
                            <div v-else class="h-[260px]">
                                <DashboardBarChart :labels="chartDisciplineBySeverity?.labels ?? []" :values="chartDisciplineBySeverity?.values ?? []" label="Violations" :horizontal="false" :colors="['#22c55e', '#f59e0b', '#f97316', '#ef4444', '#dc2626']" :max-height="260" />
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Complaints by category</h4>
                                <Link v-if="canAccessDiscipline" :href="route('admin.discipline.complaints.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View complaints</Link>
                            </div>
                            <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                            <div v-else-if="!hasComplaintsByCategoryData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No complaints for this term.</div>
                            <div v-else class="h-[260px]">
                                <DashboardPolarChart :labels="chartComplaintsByCategory?.labels ?? []" :values="chartComplaintsByCategory?.values ?? []" :colors="['#8b5cf6', '#06b6d4', '#f97316', '#10b981', '#f43f5e', '#eab308', '#6366f1', '#ec4899']" :max-height="260" />
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Violation by course</h4>
                                <Link v-if="canAccessDiscipline" :href="route('admin.discipline.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View discipline</Link>
                            </div>
                            <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                            <div v-else-if="!hasDisciplineByCourseData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No discipline data for this term.</div>
                            <div v-else class="h-[260px]">
                                <DashboardComboChart :labels="chartDisciplineByCourse?.labels ?? []" :values="chartDisciplineByCourse?.values ?? []" bar-label="Violations" line-label="Average" :max-height="260" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Number of violations this semester</h4>
                            <Link v-if="canAccessDiscipline" :href="route('admin.discipline.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View discipline</Link>
                        </div>
                        <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                        <div v-else-if="!hasViolationsPerMonthData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No violation data for this term.</div>
                        <div v-else class="h-[280px]">
                            <DashboardLineChart :labels="chartViolationsPerMonth?.labels ?? []" :values="chartViolationsPerMonth?.values ?? []" label="Violations" border-color="#f43f5e" background-color="rgba(244, 63, 94, 0.1)" :max-height="280" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- ─── GUIDANCE ─── -->
            <section>
                <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4 border-l-2 border-indigo-400 pl-3">Guidance</h3>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Guidance cases by course</h4>
                        <Link v-if="canAccessGuidance" :href="route('admin.guidance.index')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">View guidance</Link>
                    </div>
                    <div v-if="!academicTermLabel" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No active term.</div>
                    <div v-else-if="!hasGuidanceByCourseData()" class="py-12 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">No guidance cases for this term.</div>
                    <div v-else class="h-[280px]">
                        <DashboardRadarChart :labels="chartGuidanceByCourse?.labels ?? []" :values="chartGuidanceByCourse?.values ?? []" label="Cases" border-color="#8b5cf6" background-color="rgba(139, 92, 246, 0.2)" :max-height="280" />
                    </div>
                </div>
            </section>

            <!-- ─── PREDICTIVE ANALYTICS ─── -->
            <section v-if="canAccessDiscipline">
                <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 border-l-2 border-orange-400 pl-3">
                    Predictive Analytics
                </h3>

                <div v-if="!hasRiskData()" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-8 text-center text-sm text-gray-400 dark:text-gray-500">
                    No risk scores computed yet.
                    <Link :href="route('admin.discipline.index', { tab: 'risk' })" class="ml-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                        Go to Risk Assessment to compute scores.
                    </Link>
                </div>

                <div v-else class="space-y-5">
                    <!-- Summary numbers -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl p-4">
                            <p class="text-xs font-medium text-red-600 dark:text-red-400 opacity-80">High Risk</p>
                            <p class="text-3xl font-bold text-red-700 dark:text-red-300 mt-1">{{ riskSummary.high }}</p>
                            <p class="text-xs text-red-500 dark:text-red-400 mt-1">
                                {{ riskSummary.total > 0 ? Math.round(riskSummary.high / riskSummary.total * 100) : 0 }}% of students
                            </p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
                            <p class="text-xs font-medium text-yellow-600 dark:text-yellow-400 opacity-80">Moderate Risk</p>
                            <p class="text-3xl font-bold text-yellow-700 dark:text-yellow-300 mt-1">{{ riskSummary.moderate }}</p>
                            <p class="text-xs text-yellow-500 dark:text-yellow-400 mt-1">
                                {{ riskSummary.total > 0 ? Math.round(riskSummary.moderate / riskSummary.total * 100) : 0 }}% of students
                            </p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl p-4">
                            <p class="text-xs font-medium text-green-600 dark:text-green-400 opacity-80">Low Risk</p>
                            <p class="text-3xl font-bold text-green-700 dark:text-green-300 mt-1">{{ riskSummary.low }}</p>
                            <p class="text-xs text-green-500 dark:text-green-400 mt-1">
                                {{ riskSummary.total > 0 ? Math.round(riskSummary.low / riskSummary.total * 100) : 0 }}% of students
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-4">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 opacity-80">Not Computed</p>
                            <p class="text-3xl font-bold text-gray-600 dark:text-gray-300 mt-1">{{ riskSummary.not_computed }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">of {{ riskSummary.total }} total students</p>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <!-- Doughnut: distribution -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">Risk level distribution</h4>
                            <div class="h-[260px]">
                                <DashboardDoughnutChart
                                    :labels="chartRiskLevelDistribution.labels"
                                    :values="chartRiskLevelDistribution.values"
                                    :colors="['#ef4444', '#f59e0b', '#22c55e']"
                                    :max-height="260"
                                />
                            </div>
                        </div>

                        <!-- Bar: high-risk by course -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">High-risk students by course</h4>
                                <Link :href="route('admin.discipline.index', { tab: 'risk', risk_level: 'High' })"
                                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    View all
                                </Link>
                            </div>
                            <div v-if="!hasRiskByCourseData()" class="py-12 text-center text-gray-400 dark:text-gray-500 text-sm">
                                No high-risk students recorded.
                            </div>
                            <div v-else class="h-[260px]">
                                <DashboardBarChart
                                    :labels="chartRiskByCourse.labels"
                                    :values="chartRiskByCourse.values"
                                    label="High-risk students"
                                    :horizontal="true"
                                    :colors="['#ef4444', '#f97316', '#f59e0b', '#eab308', '#dc2626', '#b91c1c', '#ea580c', '#d97706', '#ca8a04', '#c2410c']"
                                    :max-height="260"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Top at-risk students -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        <div class="flex items-center justify-between px-5 pt-5 pb-3">
                            <h4 class="text-base font-semibold text-gray-800 dark:text-gray-100">Top at-risk students</h4>
                            <Link :href="route('admin.discipline.index', { tab: 'risk', risk_level: 'High' })"
                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                View all
                            </Link>
                        </div>
                        <div v-if="topAtRiskStudents.length === 0" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                            No high-risk students yet.
                        </div>
                        <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="student in topAtRiskStudents" :key="student.student_number"
                                class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                <div class="flex-1 min-w-0">
                                    <Link
                                        :href="route('admin.students.profile', { student: student.student_number })"
                                        class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 truncate block"
                                    >
                                        {{ student.student_name }}
                                    </Link>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ student.course || '—' }}
                                        <span v-if="student.year_level"> · {{ student.year_level }}</span>
                                    </p>
                                </div>
                                <div class="ml-4 flex items-center gap-3">
                                    <!-- Score bar -->
                                    <div class="hidden sm:flex items-center gap-2 w-28">
                                        <div class="flex-1 h-1.5 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden">
                                            <div class="h-1.5 rounded-full bg-red-500" :style="{ width: Math.min(100, student.risk_score) + '%' }" />
                                        </div>
                                        <span class="text-xs font-mono text-gray-600 dark:text-gray-400 w-8 text-right">
                                            {{ parseFloat(student.risk_score).toFixed(1) }}
                                        </span>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                        High
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </AdminLayout>
</template>
