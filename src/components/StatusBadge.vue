<template>
  <span class="status-badge neo-badge" :style="badgeStyle">
    {{ status }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true
  }
})

const badgeStyle = computed(() => {
  const normalized = props.status ? props.status.toLowerCase().trim() : ''
  let bgColor = '#FFFFFF'

  if (normalized === 'pending') {
    bgColor = 'var(--status-pending)' // Orange
  } else if (normalized === 'in progress') {
    bgColor = 'var(--status-progress)' // Blue
  } else if (normalized === 'completed' || normalized === 'approved') {
    bgColor = 'var(--status-completed)' // Green
  } else if (normalized === 'rejected' || normalized === 'cancelled' || normalized === 'blocked') {
    bgColor = 'var(--status-rejected)' // Red
  }

  return {
    backgroundColor: bgColor
  }
})
</script>

<style scoped>
.status-badge {
  text-transform: uppercase;
  font-size: 13px;
  letter-spacing: 0.5px;
}
</style>
