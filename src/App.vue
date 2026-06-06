<template>
  <AppHeader v-if="!$route.meta.hideHeader" />

  <div :class="{ 'page-container': !$route.meta.hideHeader }">
    <router-view />
  </div>
</template>

<script setup>
import AppHeader from '@/components/AppHeader.vue'
</script>

<style>
/* Global CSS transitions or customizations if needed */
</style>