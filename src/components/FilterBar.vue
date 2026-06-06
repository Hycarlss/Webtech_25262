<template>
  <div class="filter-bar">
    <button
      v-for="option in options"
      :key="option"
      type="button"
      class="filter-btn"
      :class="{ active: modelValue === option }"
      @click="$emit('update:modelValue', option)"
    >
      {{ option }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    required: true
  },
  options: {
    type: Array,
    default: () => ['All', 'Pending', 'In Progress', 'Completed']
  }
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 24px;
}

.filter-btn {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: 14px;
  color: #000000;
  background-color: #FFFFFF;
  border: 2px solid #000000;
  border-radius: 8px;
  padding: 8px 16px;
  cursor: pointer;
  box-shadow: 2px 2px 0px #000000;
  transition: all 0.1s ease;
}

.filter-btn:hover {
  background-color: var(--bg-color);
  transform: translate(1px, 1px);
  box-shadow: 1px 1px 0px #000000;
}

.filter-btn.active {
  background-color: var(--primary-yellow);
  border: 3px solid #000000;
  box-shadow: 3px 3px 0px #000000;
  transform: translate(-1px, -1px);
}

.filter-btn.active:active {
  transform: translate(2px, 2px);
  box-shadow: 0px 0px 0px #000000;
}
</style>
