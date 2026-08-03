<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />
    <div class="flex flex-col flex-1 overflow-hidden">
      <AppNavbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />
      <main class="flex-1 overflow-y-auto p-6">
        <transition name="fade" mode="out-in">
          <router-view :key="$route.fullPath" />
        </transition>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppNavbar  from '@/components/AppNavbar.vue'

const sidebarOpen = ref(true)
</script>
