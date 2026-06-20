<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const router = useRouter()

const password = ref('')
const confirmPassword = ref('')
const loading = ref(false)
const error = ref('')
const message = ref('')

const route = useRoute()
const token = computed(() => route.query.token)

const resetPassword = async () => {
  loading.value = true
  error.value = ''
  message.value = ''

  try {
    if (!password.value || !confirmPassword.value) {
      throw new Error('Please fill in all fields')
    }

    if (password.value !== confirmPassword.value) {
      throw new Error('Passwords do not match')
    }

    if (!token.value) {
      throw new Error('Invalid reset link')
    }

    const res = await fetch('http://localhost:8000/reset-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        token: token.value,
        password: password.value
      })
    })

    const data = await res.json()

    if (!data.success) {
      throw new Error(data.message || 'Reset failed')
    }

    message.value = 'Password updated successfully! Redirecting...'

    setTimeout(() => {
      router.push('/login')
    }, 1500)

  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="card">

      <!-- BACK -->
      <router-link to="/login" class="back">
        <span class="arrow">←</span>
        Back to login
      </router-link>

      <!-- ICON -->
      <div class="icon-box">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" fill="none">
          <path
            d="M12 17v-4"
            stroke="black"
            stroke-width="2"
            stroke-linecap="round"
          />
          <circle cx="12" cy="10" r="7" stroke="black" stroke-width="2"/>
        </svg>
      </div>

      <!-- HEADER -->
      <h1>Reset Password</h1>
      <p class="subtitle">Enter your new password</p>

      <!-- FORM -->
      <form @submit.prevent="resetPassword" class="form">

        <label>New Password</label>
        <input
          v-model="password"
          type="password"
          placeholder="Enter new password"
        />

        <label>Confirm Password</label>
        <input
          v-model="confirmPassword"
          type="password"
          placeholder="Confirm new password"
        />

        <button type="submit" :disabled="loading">
          {{ loading ? 'Updating...' : 'Reset Password' }}
        </button>

        <p v-if="error" class="error">{{ error }}</p>
        <p v-if="message" class="success">{{ message }}</p>

      </form>

    </div>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #FFC900;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 16px;
}

.card {
  width: 448px;
  background: white;
  border: 4px solid black;
  box-shadow: 6px 6px 0 black;
  padding: 36px;
  position: relative;
}

/* BACK */
.back {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  font-size: 14px;
  color: #4A5565;
  text-decoration: none;
  margin-bottom: 24px;
}

.back:hover {
  text-decoration: underline;
}

/* ICON */
.icon-box {
  width: 64px;
  height: 64px;
  background: #4ECDC4;
  border: 2px solid black;
  box-shadow: 2px 2px 0 black;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

/* TEXT */
h1 {
  text-align: center;
  font-size: 28px;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.subtitle {
  text-align: center;
  color: #4A5565;
  margin-bottom: 24px;
}

/* FORM */
.form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

label {
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
}

input {
  height: 56px;
  border: 4px solid black;
  box-shadow: 4px 4px 0 black;
  padding: 0 16px;
  font-size: 16px;
}

button {
  height: 56px;
  background: #FF90E8;
  border: 4px solid black;
  box-shadow: 4px 4px 0 black;
  font-weight: 700;
  text-transform: uppercase;
  cursor: pointer;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* MESSAGES */
.error {
  color: red;
  font-size: 14px;
}

.success {
  color: green;
  font-size: 14px;
}
</style>