<template>
    <div class="min-h-screen flex bg-(--bg-base)">
        <Sidebar
            role="employee"
            :collapsed="sidebarCollapsed"
            :mobile-open="mobileSidebarOpen"
            @close="mobileSidebarOpen = false"
        />

        <div class="flex-1 min-w-0 flex flex-col">
            <Topbar @toggle-sidebar="toggleSidebar" />
            <main class="flex-1 p-4 md:p-6 overflow-y-auto space-y-4">
                <BreadcrumbNav />
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { RouterView } from 'vue-router';
import BreadcrumbNav from '../components/layout/BreadcrumbNav.vue';
import Sidebar from '../components/layout/Sidebar.vue';
import Topbar from '../components/layout/Topbar.vue';

const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

function toggleSidebar() {
    if (window.matchMedia('(max-width: 767px)').matches) {
        mobileSidebarOpen.value = !mobileSidebarOpen.value;
        return;
    }

    sidebarCollapsed.value = !sidebarCollapsed.value;
}
</script>
