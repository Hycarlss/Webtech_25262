<template>
  <section class="welcome-section neo-card">
    <h1 class="welcome-title">WELCOME BACK, <span class="text-purple">{{ name }}</span>!</h1>
    <p class="welcome-subtitle">Your hostel dashboard overview and recent activities.</p>
  </section>
</template>

<script setup>
defineProps({
  name: {
    type: String,
    default: 'Student'
  }
})
</script>

<style scoped>
.welcome-section {
  background-color: var(--primary-pink);
  margin-bottom: 24px;
}

.welcome-title {
  font-size: 32px;
  text-transform: uppercase;
  letter-spacing: -0.5px;
  margin-bottom: 8px;
}

.welcome-subtitle {
  font-size: 18px;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.8);
}
</style>