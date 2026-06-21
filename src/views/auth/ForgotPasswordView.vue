<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

const email = ref('')
const loading = ref(false)
const message = ref('')
const error = ref('')
const resetLink = ref('')

const sendReset = async () => {
  loading.value = true
  error.value = ''
  message.value = ''

  try {
    const res = await fetch('http://localhost:8000/forgot-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        email: email.value
      })
    })

    const data = await res.json()

    if (!data.success) throw new Error(data.message)

    resetLink.value = data.resetLink

    message.value = "Reset link generated!"
    console.log("RESET LINK:", data.resetLink)

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

      <!-- BACK BUTTON -->
      <RouterLink to="/login" class="back">
        <span class="arrow">←</span>
        Back to login
      </RouterLink>

      <!-- ICON -->
      <div class="icon-box">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" fill="none">
            <circle cx="8" cy="12" r="3" stroke="black" stroke-width="2"/>
            <path
            d="M11 12h10M18 12v3M15 12v2"
            stroke="black"
            stroke-width="2"
            stroke-linecap="round"
            />
        </svg>
      </div>

      <!-- HEADER -->
      <h1>Reset Password</h1>
      <p class="subtitle">Enter your email to receive a reset link</p>

      <!-- FORM -->
      <form @submit.prevent="sendReset" class="form">

        <label>Email Address</label>

        <input
          v-model="email"
          type="email"
          placeholder="student@hostel.com"
        />

        <button type="submit" :disabled="loading">
          {{ loading ? 'Sending...' : 'Send Reset Link' }}
        </button>

        <p v-if="error" class="error">{{ error }}</p>
        <p v-if="message" class="success">{{ message }}</p>

        <p v-if="resetLink" class="success">
          Reset Link:
          <a :href="resetLink" target="_blank">
            {{ resetLink }}
          </a>
        </p>
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

.icon-box svg {
  width: 32px;
  height: 32px;
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

/* NOTE */
.note {
  margin-top: 24px;
  padding: 16px;
  font-size: 12px;
  color: #4A5565;
}
</style>