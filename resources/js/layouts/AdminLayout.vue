<template>
    <div class="flex h-dvh overflow-hidden bg-(--bg-base) text-(--text-primary)">
        <Sidebar
            role="admin"
            :collapsed="sidebarCollapsed"
            :mobile-open="mobileSidebarOpen"
            @close="mobileSidebarOpen = false"
        />

        <div class="flex min-h-0 min-w-0 flex-1 flex-col transition-[padding] duration-300" :class="contentOffsetClass">
            <Topbar @toggle-sidebar="toggleSidebar" />
            <main class="min-h-0 flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
                <div class="mx-auto w-full max-w-[1600px] space-y-4 md:space-y-5 fin-section-enter">
                <BreadcrumbNav />
                <RouterView />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { RouterView } from 'vue-router';
import BreadcrumbNav from '../components/layout/BreadcrumbNav.vue';
import Sidebar from '../components/layout/Sidebar.vue';
import Topbar from '../components/layout/Topbar.vue';

const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

const contentOffsetClass = computed(() => {
    return sidebarCollapsed.value ? 'md:pl-20' : 'md:pl-64';
});

function toggleSidebar() {
    if (window.matchMedia('(max-width: 767px)').matches) {
        mobileSidebarOpen.value = !mobileSidebarOpen.value;
        return;
    }

    sidebarCollapsed.value = !sidebarCollapsed.value;
}
</script>
