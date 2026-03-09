<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
  mobileOpen: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

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

// Close drawer on route change
watch(() => page.url, () => {
  emit('close');
});
</script>

<template>
  <!-- Desktop Sidebar (only in flow on md+) -->
  <aside class="w-72 hidden md:block">
    <div class="w-full h-screen sticky top-0 bg-white border-r border-slate-200/70">
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


      </nav>
    </div>
  </aside>

  <!-- Mobile Drawer Overlay -->
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="mobileOpen" class="fixed inset-0 z-50 md:hidden">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="emit('close')"></div>

        <!-- Drawer Panel -->
        <div class="fixed inset-y-0 left-0 w-72 bg-white shadow-xl flex flex-col">
          <!-- Header -->
          <div class="px-5 pt-6 pb-4 flex items-center justify-between">
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
            <!-- Close button -->
            <button @click="emit('close')"
              class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
              aria-label="Close navigation">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Nav Items -->
          <nav class="flex-1 px-3 pb-6 overflow-y-auto">
            <div class="text-[11px] font-medium tracking-wide text-slate-400 px-3 mb-2">
              NAVIGATION
            </div>
            <div class="space-y-1">
              <Link v-for="item in navItems" :key="item.routeName" :href="item.href"
                class="group relative flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition" :class="isActive(item.routeName)
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'">
                <span class="h-8 w-8 rounded-lg flex items-center justify-center border transition" :class="isActive(item.routeName)
                  ? 'border-white/10 bg-white/10'
                  : 'border-slate-200 bg-white group-hover:border-slate-300'" aria-hidden="true">
                  <span class="h-2 w-2 rounded-full transition"
                    :class="isActive(item.routeName) ? 'bg-white' : 'bg-slate-400 group-hover:bg-slate-700'" />
                </span>
                <span class="font-medium">{{ item.label }}</span>
                <span v-if="isActive(item.routeName)" class="absolute right-3 h-1.5 w-1.5 rounded-full bg-white/80"
                  aria-hidden="true" />
              </Link>
            </div>
          </nav>


        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Drawer transition */
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 0.25s ease;
}

.drawer-enter-active > div:last-child,
.drawer-leave-active > div:last-child {
  transition: transform 0.25s ease;
}

.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}

.drawer-enter-from > div:last-child,
.drawer-leave-to > div:last-child {
  transform: translateX(-100%);
}
</style>
