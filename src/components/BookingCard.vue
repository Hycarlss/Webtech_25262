<template>
  <div class="booking-card neo-card">
    <div class="card-header">
      <h3 class="facility-name">{{ booking.facilityName }}</h3>
      <StatusBadge :status="booking.status" />
    </div>

    <div class="card-body">
      <div class="details-grid">
        <div class="detail-item">
          <span class="label">Date</span>
          <span class="value font-bold">{{ formatDate(booking.date) }}</span>
        </div>
        <div class="detail-item">
          <span class="label">Time Slot</span>
          <span class="value">{{ booking.startTime }} - {{ booking.endTime }}</span>
        </div>
      </div>
      <div v-if="booking.purpose" class="purpose-section">
        <span class="label">Purpose</span>
        <p class="purpose-text">{{ booking.purpose }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue'

defineProps({
  booking: {
    type: Object,
    required: true
  }
})

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  try {
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return dateStr
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch (e) {
    return dateStr
  }
}
</script>

<style scoped>
.booking-card {
  background-color: #FFFFFF;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #000000;
  padding-bottom: 12px;
  margin-bottom: 16px;
}

.facility-name {
  font-size: 20px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 12px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.label {
  font-size: 12px;
  font-weight: 700;
  color: #555555;
  text-transform: uppercase;
}

.value {
  font-size: 16px;
  font-weight: 500;
}

.font-bold {
  font-weight: 700;
}

.purpose-section {
  border-top: 1px dashed #000000;
  padding-top: 12px;
  margin-top: 12px;
}

.purpose-text {
  font-size: 14px;
  color: #333333;
  margin-top: 4px;
  line-height: 1.4;
}

@media (max-width: 500px) {
  .details-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }
}
</style>
