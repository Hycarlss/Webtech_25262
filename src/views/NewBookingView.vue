<template>
  <div class="new-booking-view">
    <div class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Opening Facilities booking...</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

onMounted(() => {
  router.replace('/facilities')
})
</script>

<style scoped>
.new-booking-view {
  width: 100%;
}

.loading-state {
  text-align: center;
  padding: 60px 24px;
  background-color: #FFFFFF;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--primary-yellow);
  border-top-color: #000000;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
