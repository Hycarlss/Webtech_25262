<template>
  <div class="booking-item">
    <h4>{{ facility }}</h4>
    <p>{{ date }}</p>
  </div>
</template>

<script setup>
defineProps({
  facility: {
    type: String,
    default: ''
  },
  date: {
    type: String,
    default: ''
  }
})
</script>