<template>
  <form @submit.prevent="handleSubmit" class="maintenance-form neo-card">
    <h2 class="form-title">New Request</h2>
    
    <!-- Title Field -->
    <div class="form-group">
      <label for="title" class="form-label">Issue Title</label>
      <input
        type="text"
        id="title"
        v-model="form.title"
        placeholder="e.g. Broken light in room"
        class="form-input"
        :class="{ 'input-error': errors.title }"
      />
      <span v-if="errors.title" class="form-error">{{ errors.title }}</span>
    </div>

    <!-- Location Field -->
    <div class="form-group">
      <label for="location" class="form-label">Location / Room</label>
      <input
        type="text"
        id="location"
        v-model="form.location"
        placeholder="e.g. Room A-205"
        class="form-input"
        :class="{ 'input-error': errors.location }"
      />
      <span v-if="errors.location" class="form-error">{{ errors.location }}</span>
    </div>

    <!-- Description Field -->
    <div class="form-group">
      <label for="description" class="form-label">Description</label>
      <textarea
        id="description"
        v-model="form.description"
        placeholder="Describe the issue in detail (minimum 5 characters)..."
        rows="4"
        class="form-input form-textarea"
        :class="{ 'input-error': errors.description }"
      ></textarea>
      <span v-if="errors.description" class="form-error">{{ errors.description }}</span>
    </div>

    <!-- Buttons -->
    <div class="form-actions">
      <button type="submit" class="neo-btn neo-btn-yellow">
        Submit Request
      </button>
      <button type="button" @click="$emit('cancel')" class="neo-btn neo-btn-white">
        Cancel
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive } from 'vue'

const emit = defineEmits(['submit', 'cancel'])

const props = defineProps({
  defaultLocation: {
    type: String,
    default: ''
  }
})

const form = reactive({
  title: '',
  location: props.defaultLocation || '',
  description: ''
})

const errors = reactive({
  title: '',
  location: '',
  description: ''
})

const validateForm = () => {
  let isValid = true
  
  // Title validation
  if (!form.title.trim()) {
    errors.title = 'Title is required.'
    isValid = false
  } else if (form.title.trim().length < 5) {
    errors.title = 'Title must be at least 5 characters.'
    isValid = false
  } else {
    errors.title = ''
  }

  // Location validation
  if (!form.location.trim()) {
    errors.location = 'Location is required.'
    isValid = false
  } else if (form.location.trim().length < 3) {
    errors.location = 'Location must be at least 3 characters.'
    isValid = false
  } else {
    errors.location = ''
  }

  // Description validation
  if (!form.description.trim()) {
    errors.description = 'Description is required.'
    isValid = false
  } else if (form.description.trim().length < 5) {
    errors.description = 'Description must be at least 5 characters.'
    isValid = false
  } else {
    errors.description = ''
  }

  return isValid
}

const handleSubmit = () => {
  if (validateForm()) {
    emit('submit', { ...form })
  }
}
</script>

<style scoped>
.maintenance-form {
  background-color: #FFFFFF;
  max-width: 600px;
  margin: 0 auto;
}

.form-title {
  font-size: 24px;
  margin-bottom: 20px;
  border-bottom: 3px solid #000000;
  padding-bottom: 12px;
}

.form-textarea {
  resize: vertical;
}

.input-error {
  border-color: var(--status-rejected);
  background-color: #FFF5F5;
}

.form-actions {
  display: flex;
  gap: 16px;
  margin-top: 24px;
}
</style>
