<template>
    <div class="min-h-screen flex bg-[var(--bg-base)]">
        <Sidebar role="admin" />

        <div class="flex-1 min-w-0 flex flex-col">
            <Topbar />
            <main class="flex-1 p-4 md:p-6 overflow-y-auto">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { RouterView } from 'vue-router';
import Sidebar from '../components/layout/Sidebar.vue';
import Topbar from '../components/layout/Topbar.vue';
</script>
