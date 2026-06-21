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
    bgColor = 'var(--status-pending)'
  } else if (normalized === 'assigned') {
    bgColor = '#FFD166' // Assigned: Warm Orange-Yellow
  } else if (normalized === 'in progress') {
    bgColor = '#06D6A0' // In Progress: Teal
  } else if (normalized === 'resolved' || normalized === 'completed') {
    bgColor = 'var(--status-completed)' // Resolved: Green
  } else if (normalized === 'rejected' || normalized === 'cancelled') {
    bgColor = 'var(--status-rejected)' // Rejected/Cancelled: Pinkish Red
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
