<template>
  <div class="maintenance-card neo-card">
    <!-- Card Header: Title and Status Badge -->
    <div class="card-header">
      <h3 class="issue-title">{{ report.title }}</h3>
      <StatusBadge :status="report.status" />
    </div>

    <!-- Description -->
    <p class="description">{{ report.description }}</p>

    <!-- Details Grid -->
    <div class="details-grid">
      <div class="detail-item">
        <span class="detail-label">Room:</span>
        <span class="detail-val font-bold">{{ report.room }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Submitted By:</span>
        <span class="detail-val">{{ report.studentName }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Submitted On:</span>
        <span class="detail-val">{{ formatDate(report.dateSubmitted) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Assigned Staff:</span>
        <span class="detail-val highlight-pink">{{ report.assignedStaff || 'Unassigned' }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Deadline:</span>
        <span class="detail-val text-red font-bold">{{ formatDate(report.deadline) || 'Not Set' }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div v-if="showActions && report.status !== 'Completed'" class="card-actions">
      <button
        v-if="report.status === 'Pending'"
        @click="$emit('update-status', report.id, 'In Progress')"
        class="neo-btn neo-btn-pink btn-sm"
      >
        Start Progress
      </button>
      <button
        v-if="report.status === 'In Progress'"
        @click="$emit('update-status', report.id, 'Completed')"
        class="neo-btn neo-btn-yellow btn-sm"
      >
        Mark Completed
      </button>
    </div>
  </div>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue'

defineProps({
  report: {
    type: Object,
    required: true
  },
  showActions: {
    type: Boolean,
    default: true
  }
})

defineEmits(['update-status'])

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
.maintenance-card {
  display: flex;
  flex-direction: column;
  gap: 16px;
  background-color: #FFFFFF;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  border-bottom: 2px solid #000000;
  padding-bottom: 12px;
}

.issue-title {
  font-size: 20px;
  line-height: 1.2;
}

.description {
  font-size: 15px;
  color: #333333;
  line-height: 1.4;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px 16px;
  background: var(--bg-color);
  padding: 12px;
  border: 2px solid #000000;
  border-radius: 8px;
  box-shadow: 2px 2px 0px #000000;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.detail-label {
  font-size: 12px;
  font-weight: 700;
  color: #555555;
  text-transform: uppercase;
}

.detail-val {
  font-size: 14px;
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

.text-red {
  color: #FF006E;
}

.card-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

@media (max-width: 500px) {
  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>
