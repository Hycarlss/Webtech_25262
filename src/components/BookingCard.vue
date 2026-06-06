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
        <div v-if="booking.studentName" class="detail-item">
          <span class="label">Booked By</span>
          <span class="value highlight-pink">{{ booking.studentName }}</span>
        </div>
      </div>
      <div v-if="booking.purpose" class="purpose-section">
        <span class="label">Purpose</span>
        <p class="purpose-text">{{ booking.purpose }}</p>
      </div>
    </div>

    <!-- Actions for Staff/Admin to Approve or Reject -->
    <div v-if="user.role === 'staff/admin' && booking.status === 'Pending'" class="card-actions">
      <button @click="$emit('update-status', booking.id, 'Approved')" class="neo-btn neo-btn-yellow btn-sm">
        Approve
      </button>
      <button @click="$emit('update-status', booking.id, 'Rejected')" class="neo-btn neo-btn-pink btn-sm">
        Reject
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import StatusBadge from './StatusBadge.vue'

defineProps({
  booking: {
    type: Object,
    required: true
  }
})

defineEmits(['update-status'])

const user = ref({})

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
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

.highlight-pink {
  background-color: var(--primary-pink);
  padding: 1px 6px;
  border: 1px solid #000000;
  border-radius: 4px;
  display: inline-block;
  width: fit-content;
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

.card-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
  border-top: 2px solid #000000;
  padding-top: 16px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

@media (max-width: 500px) {
  .details-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }
}
</style>
