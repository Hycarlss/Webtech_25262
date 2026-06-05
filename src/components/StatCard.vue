<template>
  <div class="stat-card neo-card" :class="themeClass">
    <div class="card-header">
      <h3 class="stat-title">{{ title }}</h3>
    </div>
    <div class="stat-body">
      <span class="stat-value">{{ value }}</span>
      <p class="stat-desc">{{ description }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    default: 0
  },
  description: {
    type: String,
    default: ''
  },
  variant: {
    type: String,
    default: 'yellow' // 'yellow', 'pink', 'white'
  }
})

const themeClass = computed(() => {
  return {
    'bg-yellow': props.variant === 'yellow',
    'bg-pink': props.variant === 'pink',
    'bg-white': props.variant === 'white'
  }
})
</script>

<style scoped>
.stat-card {
  margin-bottom: 0; /* Let grid control layout margin */
}

.bg-yellow {
  background-color: var(--primary-yellow);
}

.bg-pink {
  background-color: var(--primary-pink);
}

.bg-white {
  background-color: #FFFFFF;
}

.stat-title {
  font-size: 18px;
  text-transform: uppercase;
  letter-spacing: -0.2px;
  margin-bottom: 12px;
  border-bottom: 2px solid #000000;
  padding-bottom: 8px;
}

.stat-value {
  display: block;
  font-size: 48px;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 8px;
}

.stat-desc {
  font-size: 15px;
  font-weight: 500;
  color: #000000;
}
</style>