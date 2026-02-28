<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const accessibleModules = computed(() => page.props.auth?.accessible_modules || []);

// Define all navigation items with their required modules
const allNavItems = [
  {
    href: route('admin.dashboard'),
    label: 'Dashboard',
    routeName: 'admin.dashboard',
    module: 'dashboard'
  },
  {
    href: route('admin.students.index'),
    label: 'Student Records',
    routeName: 'admin.students.index',
    module: 'students'
  },
  {
    href: route('admin.staff.index'),
    label: 'Manage Staff',
    routeName: 'admin.staff.index',
    module: 'staff'
  },
  {
    href: route('admin.discipline.index'),
    label: 'Discipline Unit',
    routeName: 'admin.discipline.index',
    module: 'discipline'
  },
  {
    href: route('admin.organizations.index'),
    label: 'Organization Unit',
    routeName: 'admin.organizations.index',
    module: 'organizations'
  },
  {
    href: route('admin.sports.index'),
    label: 'Sports Unit',
    routeName: 'admin.sports.index',
    module: 'sports'
  },
  {
    href: route('admin.guidance.index'),
    label: 'Guidance Unit',
    routeName: 'admin.guidance.index',
    module: 'guidance'
  },
  {
    href: route('admin.settings'),
    label: 'Settings',
    routeName: 'admin.settings',
    module: 'settings'
  },
];

// Filter navigation items based on accessible modules
const navItems = computed(() => {
  return allNavItems.filter(item => accessibleModules.value.includes(item.module));
});

const isActive = (routeName) => route().current(routeName);
</script>

<template>
  <aside class="w-72">
    <!-- Desktop Sidebar -->
    <div class="hidden md:block">
      <div class="w-full sticky top-0 bg-white border-r border-slate-200/70">
        <!-- Brand -->
        <div class="px-5 pt-6 pb-4">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center"
              aria-hidden="true">
              <div class="h-4 w-4 rounded bg-slate-900"></div>
            </div>

            <div class="leading-tight">
              <div class="text-sm font-semibold text-slate-900">Admin Portal</div>
              <div class="text-xs text-slate-500">OSA-IMS</div>
            </div>
          </div>
        </div>

        <!-- Nav -->
        <nav class="px-3 pb-6">
          <div class="text-[11px] font-medium tracking-wide text-slate-400 px-3 mb-2">
            NAVIGATION
          </div>

          <div class="space-y-1">
            <Link v-for="item in navItems" :key="item.routeName" :href="item.href"
              class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition" :class="isActive(item.routeName)
                ? 'bg-slate-900 text-white shadow-sm'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'">
              <!-- Minimal icon placeholder (can be replaced with real icons later) -->
              <span class="h-8 w-8 rounded-lg flex items-center justify-center border transition" :class="isActive(item.routeName)
                ? 'border-white/10 bg-white/10'
                : 'border-slate-200 bg-white group-hover:border-slate-300'" aria-hidden="true">
                <span class="h-2 w-2 rounded-full transition"
                  :class="isActive(item.routeName) ? 'bg-white' : 'bg-slate-400 group-hover:bg-slate-700'" />
              </span>

              <span class="font-medium">
                {{ item.label }}
              </span>

              <!-- Active indicator -->
              <span v-if="isActive(item.routeName)" class="absolute right-3 h-1.5 w-1.5 rounded-full bg-white/80"
                aria-hidden="true" />
            </Link>
          </div>

          <!-- Bottom subtle footer -->
          <div class="mt-6 px-3 pt-4 border-t border-slate-200/70">
            <div class="text-xs text-slate-500">
              Minimal • Clean • Fast
            </div>
          </div>
        </nav>
      </div>
    </div>

    <!-- Mobile Sidebar (Top nav list) -->
    <div class="md:hidden border-b border-slate-200 bg-white">
      <div class="px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center"
            aria-hidden="true">
            <div class="h-3.5 w-3.5 rounded bg-slate-900"></div>
          </div>
          <div class="leading-tight">
            <div class="text-sm font-semibold text-slate-900">Admin Portal</div>
            <div class="text-xs text-slate-500">OSA-IMS</div>
          </div>
        </div>
      </div>

      <div class="px-2 pb-3">
        <div class="grid grid-cols-2 gap-2">
          <Link v-for="item in navItems" :key="item.routeName" :href="item.href"
            class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition border" :class="isActive(item.routeName)
              ? 'bg-slate-900 text-white border-slate-900'
              : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'">
            <span class="h-2 w-2 rounded-full" :class="isActive(item.routeName) ? 'bg-white' : 'bg-slate-400'"
              aria-hidden="true" />
            <span class="truncate">{{ item.label }}</span>
          </Link>
        </div>
      </div>
    </div>
  </aside>
</template>