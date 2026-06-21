<template>
  <div class="profile-card neo-card">
    <div class="card-header">
      <div class="avatar-title">
        <div class="avatar">
          {{ getInitials(user.name) }}
        </div>
        <div>
          <h2 class="profile-name">{{ user.name }}</h2>
          <span v-if="user.role !== 'staff/admin'" class="matrix-badge neo-badge">Student</span>
          <span v-else class="matrix-badge neo-badge" style="background-color: var(--primary-yellow)">Staff / Admin</span>
        </div>
      </div>
      <button
        v-if="!isEditing"
        @click="startEditing"
        class="neo-btn neo-btn-pink btn-sm"
      >
        Edit Profile
      </button>
    </div>

    <!-- Success Message Alert -->
    <div v-if="successMsg" class="alert-banner alert-success">
      {{ successMsg }}
    </div>

    <!-- View Mode -->
    <div v-if="!isEditing" class="profile-details">
      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Full Name</span>
          <p class="info-value">{{ user.name }}</p>
        </div>
        <div v-if="user.role !== 'staff/admin'" class="info-item">
          <span class="info-label">Matrix Number</span>
          <p class="info-value font-mono">{{ user.matrixNumber }}</p>
        </div>
        <div class="info-item">
          <span class="info-label">Email Address</span>
          <p class="info-value">{{ user.email }}</p>
        </div>
        <div class="info-item">
          <span class="info-label">Phone Number</span>
          <p class="info-value">{{ user.phone }}</p>
        </div>
        <div v-if="user.role !== 'staff/admin'" class="info-item">
          <span class="info-label">Hostel Block</span>
          <p class="info-value">{{ user.hostelBlock }}</p>
        </div>
        <div v-if="user.role !== 'staff/admin'" class="info-item">
          <span class="info-label">Room Number</span>
          <p class="info-value highlight-yellow">{{ user.roomNumber }}</p>
        </div>
      </div>
    </div>

    <!-- Edit Mode Form -->
    <form v-else @submit.prevent="handleSave" class="edit-form">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label" for="edit-name">Full Name</label>
          <input
            type="text"
            id="edit-name"
            v-model="editForm.name"
            class="form-input"
            :class="{ 'input-error': errors.name }"
          />
          <span v-if="errors.name" class="form-error">{{ errors.name }}</span>
        </div>

        <div v-if="user.role !== 'staff/admin'" class="form-group">
          <label class="form-label" for="edit-matrix">Matrix Number</label>
          <input
            type="text"
            id="edit-matrix"
            v-model="editForm.matrixNumber"
            class="form-input"
            :class="{ 'input-error': errors.matrixNumber }"
          />
          <span v-if="errors.matrixNumber" class="form-error">{{ errors.matrixNumber }}</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit-email">Email Address</label>
          <input
            type="email"
            id="edit-email"
            v-model="editForm.email"
            class="form-input"
            :class="{ 'input-error': errors.email }"
          />
          <span v-if="errors.email" class="form-error">{{ errors.email }}</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit-phone">Phone Number</label>
          <input
            type="text"
            id="edit-phone"
            v-model="editForm.phone"
            class="form-input"
            :class="{ 'input-error': errors.phone }"
          />
          <span v-if="errors.phone" class="form-error">{{ errors.phone }}</span>
        </div>

        <div v-if="user.role !== 'staff/admin'" class="form-group">
          <label class="form-label" for="edit-block">Hostel Block</label>
          <input
            type="text"
            id="edit-block"
            v-model="editForm.hostelBlock"
            class="form-input"
            :class="{ 'input-error': errors.hostelBlock }"
          />
          <span v-if="errors.hostelBlock" class="form-error">{{ errors.hostelBlock }}</span>
        </div>

        <div v-if="user.role !== 'staff/admin'" class="form-group">
          <label class="form-label" for="edit-room">Room Number</label>
          <input
            type="text"
            id="edit-room"
            v-model="editForm.roomNumber"
            class="form-input"
            :class="{ 'input-error': errors.roomNumber }"
          />
          <span v-if="errors.roomNumber" class="form-error">{{ errors.roomNumber }}</span>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" :disabled="saving" class="neo-btn neo-btn-yellow">
          {{ saving ? 'Saving...' : 'Save Changes' }}
        </button>
        <button type="button" :disabled="saving" @click="cancelEditing" class="neo-btn neo-btn-white">
          Cancel
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  saving: {
    type: Boolean,
    default: false
  },
  successMsg: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['save'])

const isEditing = ref(false)

const editForm = reactive({
  name: '',
  matrixNumber: '',
  email: '',
  phone: '',
  hostelBlock: '',
  roomNumber: ''
})

const errors = reactive({
  name: '',
  matrixNumber: '',
  email: '',
  phone: '',
  hostelBlock: '',
  roomNumber: ''
})

// Initialize editForm values when editing starts
const startEditing = () => {
  editForm.name = props.user.name || ''
  editForm.matrixNumber = props.user.matrixNumber || ''
  editForm.email = props.user.email || ''
  editForm.phone = props.user.phone || ''
  editForm.hostelBlock = props.user.hostelBlock || ''
  editForm.roomNumber = props.user.roomNumber || ''
  
  // Clear any existing errors
  Object.keys(errors).forEach(key => errors[key] = '')
  
  isEditing.value = true
}

const cancelEditing = () => {
  isEditing.value = false
}

// Watch saving prop - if it changes to false and there is a success message, close edit form
watch(() => props.saving, (newVal) => {
  if (!newVal && props.successMsg && isEditing.value) {
    isEditing.value = false
  }
})

const validateForm = () => {
  let isValid = true
  
  // Name
  if (!editForm.name.trim()) {
    errors.name = 'Name is required'
    isValid = false
  } else {
    errors.name = ''
  }

  // Matrix Number (only check for student)
  if (props.user.role !== 'staff/admin') {
    if (!editForm.matrixNumber.trim()) {
      errors.matrixNumber = 'Matrix number is required'
      isValid = false
    } else {
      errors.matrixNumber = ''
    }
  } else {
    errors.matrixNumber = ''
  }

  // Email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!editForm.email.trim()) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!emailRegex.test(editForm.email.trim())) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  } else {
    errors.email = ''
  }

  // Phone
  if (!editForm.phone.trim()) {
    errors.phone = 'Phone number is required'
    isValid = false
  } else {
    errors.phone = ''
  }

  // Block (only check for student)
  if (props.user.role !== 'staff/admin') {
    if (!editForm.hostelBlock.trim()) {
      errors.hostelBlock = 'Block is required'
      isValid = false
    } else {
      errors.hostelBlock = ''
    }
  } else {
    errors.hostelBlock = ''
  }

  // Room Number (only check for student)
  if (props.user.role !== 'staff/admin') {
    if (!editForm.roomNumber.trim()) {
      errors.roomNumber = 'Room number is required'
      isValid = false
    } else {
      errors.roomNumber = ''
    }
  } else {
    errors.roomNumber = ''
  }

  return isValid
}

const handleSave = () => {
  if (validateForm()) {
    emit('save', { ...editForm })
  }
}

const getInitials = (name) => {
  if (!name) return 'S'
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
}
</script>

<style scoped>
.profile-card {
  background-color: #FFFFFF;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 3px solid #000000;
  padding-bottom: 20px;
  margin-bottom: 24px;
}

.avatar-title {
  display: flex;
  align-items: center;
  gap: 16px;
}

.avatar {
  width: 60px;
  height: 60px;
  background-color: var(--logo-purple);
  border: 3px solid #000000;
  border-radius: 50%;
  color: #FFFFFF;
  font-weight: 700;
  font-size: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 3px 3px 0px #000000;
}

.profile-name {
  font-size: 24px;
  margin-bottom: 4px;
}

.matrix-badge {
  background-color: var(--primary-pink);
  font-size: 13px;
  font-family: monospace;
}

.btn-sm {
  padding: 8px 16px;
  font-size: 14px;
}

.alert-banner {
  border: 2px solid #000000;
  border-radius: 8px;
  padding: 12px 16px;
  font-weight: 700;
  margin-bottom: 24px;
  box-shadow: 2px 2px 0px #000000;
}

.alert-success {
  background-color: var(--status-completed);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px 32px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.info-label {
  font-size: 14px;
  font-weight: 700;
  color: #555555;
  text-transform: uppercase;
}

.info-value {
  font-size: 18px;
  font-weight: 500;
}

.font-mono {
  font-family: monospace;
}

.highlight-yellow {
  background-color: var(--primary-yellow);
  padding: 4px 10px;
  border: 2px solid #000000;
  border-radius: 6px;
  box-shadow: 2px 2px 0px #000000;
  width: fit-content;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px 24px;
  margin-bottom: 24px;
}

.input-error {
  border-color: var(--status-rejected);
  background-color: #FFF5F5;
}

.form-actions {
  display: flex;
  gap: 16px;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 600px) {
  .info-grid, .form-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
</style>
