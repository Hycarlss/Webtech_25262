<template>
  <div class="maintenance-list">
    <div v-if="reports.length === 0" class="empty-state neo-card">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
        class="empty-icon"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
      <h3>No Maintenance Requests</h3>
      <p>There are no maintenance requests matching your criteria.</p>
    </div>
    
    <div v-else class="cards-grid">
      <MaintenanceCard
        v-for="report in reports"
        :key="report.id"
        :report="report"
        :show-actions="showActions"
        @update-status="onUpdateStatus"
      />
    </div>
  </div>
</template>

<script setup>
import MaintenanceCard from './MaintenanceCard.vue'

defineProps({
  reports: {
    type: Array,
    required: true
  },
  showActions: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update-status'])

const onUpdateStatus = (id, newStatus) => {
  emit('update-status', id, newStatus)
}
</script>

<style scoped>
.maintenance-list {
  width: 100%;
}

.cards-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  background-color: #FFFFFF;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.empty-icon {
  width: 48px;
  height: 48px;
  color: #888888;
}

.empty-state h3 {
  font-size: 20px;
}

.empty-state p {
  color: #666666;
  font-size: 15px;
}
</style>