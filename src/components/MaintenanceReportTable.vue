<template>
  <div class="table-container">
    <table class="neo-table">
      <thead>
        <tr>
          <th>Code</th>
          <th v-if="isAdmin">Student</th>
          <th>Location</th>
          <th>Issue Details</th>
          <th>Category</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Assigned Staff</th>
          <th>Timeline</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="report in reports" :key="report.id">
          <!-- Code -->
          <td class="bold">{{ report.report_code || 'N/A' }}</td>

          <!-- Student Name (Admin only) -->
          <td v-if="isAdmin">{{ report.studentName }}</td>

          <!-- Location -->
          <td>
            <div class="location-details">
              <strong>Room: {{ report.room_number || report.room }}</strong>
              <div class="block-sub" v-if="report.hostel_block">Block: {{ report.hostel_block }}</div>
            </div>
          </td>

          <!-- Issue Details -->
          <td>
            <div class="details-cell">
              <strong class="title-text">{{ report.title }}</strong>
              <p class="description-text">{{ report.description }}</p>
              <div v-if="report.student_remarks" class="remarks-sub student">
                <strong>Student Remarks:</strong> {{ report.student_remarks }}
              </div>
              <div v-if="report.staff_remarks" class="remarks-sub staff">
                <strong>Staff Remarks:</strong> {{ report.staff_remarks }}
              </div>
            </div>
          </td>

          <!-- Category -->
          <td>{{ report.category }}</td>

          <!-- Priority -->
          <td>
            <span class="priority-label" :class="report.priority ? report.priority.toLowerCase() : 'medium'">
              {{ report.priority || 'Medium' }}
            </span>
          </td>

          <!-- Status -->
          <td>
            <MaintenanceStatusBadge :status="report.status" />
          </td>

          <!-- Assigned Staff -->
          <td>
            <span :class="{ 'unassigned-text': !report.assigned_staff_id }">
              {{ report.assignedStaff || 'Unassigned' }}
            </span>
            <div v-if="report.assigned_at" class="timestamp-sub">
              Assigned: {{ formatDate(report.assigned_at) }}
            </div>
            <div v-if="report.resolved_at" class="timestamp-sub">
              Resolved: {{ formatDate(report.resolved_at) }}
            </div>
          </td>

          <!-- Timeline -->
          <td>
            <button @click="$emit('view-timeline', report)" class="mini-btn pink">
              Track
            </button>
          </td>

          <!-- Actions -->
          <td>
            <div class="actions-cell">
              <!-- Staff/Admin actions -->
              <template v-if="isAdmin">
                <button @click="$emit('assign-staff', report)" class="mini-btn yellow mr-2">
                  Assign
                </button>
                <button @click="$emit('update-status', report)" class="mini-btn white mr-2">
                  Update
                </button>
                <button @click="handleDelete(report.id)" class="mini-btn red">
                  Delete
                </button>
              </template>
              
              <!-- Student actions -->
              <template v-else>
                <span class="no-actions-text" v-if="report.status === 'Resolved'">Resolved</span>
                <span class="no-actions-text" v-else-if="report.status === 'Rejected'">Rejected</span>
                <span class="no-actions-text" v-else>In Progress</span>
              </template>
            </div>
          </td>
        </tr>

        <!-- Empty State -->
        <tr v-if="reports.length === 0">
          <td :colspan="isAdmin ? 10 : 9" class="empty-row text-center">
            No maintenance reports match your query.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import MaintenanceStatusBadge from './MaintenanceStatusBadge.vue'

defineProps({
  reports: {
    type: Array,
    required: true
  },
  isAdmin: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['assign-staff', 'update-status', 'delete-report', 'view-timeline'])

const handleDelete = (id) => {
  if (confirm('Are you sure you want to delete this maintenance report? This action is permanent.')) {
    emit('delete-report', id)
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  try {
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return dateStr
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (e) {
    return dateStr
  }
}
</script>

<style scoped>
.table-container {
  width: 100%;
  overflow-x: auto;
  border: 4px solid #000000;
  box-shadow: 4px 4px 0px #000000;
  margin-top: 16px;
  background-color: #FFFFFF;
}

.neo-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

.neo-table th,
.neo-table td {
  border-bottom: 3px solid #000000;
  border-right: 3px solid #000000;
  padding: 12px;
  text-align: left;
  background-color: #FFFFFF;
  font-size: 15px;
}

.neo-table th:last-child,
.neo-table td:last-child {
  border-right: none;
}

.neo-table tr:last-child td {
  border-bottom: none;
}

.neo-table th {
  background-color: var(--primary-yellow);
  font-weight: 700;
  text-transform: uppercase;
  font-size: 14px;
}

.bold {
  font-weight: 700;
}

.block-sub {
  font-size: 12px;
  color: #666666;
  margin-top: 2px;
}

.details-cell {
  max-width: 320px;
}

.title-text {
  display: block;
  font-size: 16px;
  margin-bottom: 4px;
}

.description-text {
  font-size: 14px;
  color: #4a5568;
  line-height: 1.4;
  white-space: pre-wrap;
}

.remarks-sub {
  margin-top: 8px;
  padding: 6px 10px;
  font-size: 13px;
  border-left: 3px solid #000000;
  border-radius: 4px;
}

.remarks-sub.student {
  background-color: #FFFDF0;
}

.remarks-sub.staff {
  background-color: #F0FDFA;
}

.timestamp-sub {
  font-size: 11px;
  color: #666666;
  margin-top: 4px;
}

.unassigned-text {
  color: #888888;
  font-style: italic;
}

.priority-label {
  display: inline-block;
  padding: 4px 8px;
  border: 2px solid #000000;
  border-radius: 4px;
  font-weight: 700;
  font-size: 12px;
  text-transform: uppercase;
}

.priority-label.low {
  background-color: #A7F3D0;
}

.priority-label.medium {
  background-color: #FDE68A;
}

.priority-label.high {
  background-color: #FBCFE8;
}

.priority-label.critical {
  background-color: #FECACA;
  color: #DC2626;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.actions-cell {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.mini-btn {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  border: 2px solid #000000;
  border-radius: 6px;
  padding: 6px 10px;
  cursor: pointer;
  box-shadow: 2px 2px 0 #000000;
  font-size: 13px;
  transition: all 0.1s ease;
}

.mini-btn:hover {
  transform: translate(1px, 1px);
  box-shadow: 1px 1px 0 #000000;
}

.mini-btn:active {
  transform: translate(2px, 2px);
  box-shadow: 0px 0px 0 #000000;
}

.mini-btn.yellow {
  background-color: var(--primary-yellow);
}

.mini-btn.pink {
  background-color: var(--primary-pink);
}

.mini-btn.white {
  background-color: #FFFFFF;
}

.mini-btn.red {
  background-color: #F43F5E;
  color: #FFFFFF;
}

.no-actions-text {
  font-size: 13px;
  color: #666666;
  font-style: italic;
}

.empty-row {
  padding: 40px;
  font-size: 16px;
  color: #888888;
  font-style: italic;
}

.text-center {
  text-align: center;
}

.mr-2 {
  margin-right: 4px;
}
</style>
