<script setup>
defineProps({
  title: {
    type: String,
    default: 'HOSTEL MANAGEMENT'
  },
  subtitle: {
    type: String,
    default: 'Sign in to your account'
  }
})
</script>

<template>
  <div class="login-header">

    <div class="logo-section">
      <div class="logo-icon">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="40"
          height="40"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            d="M5 4h14v16H5z"
            stroke="white"
            stroke-width="2.5"
            stroke-linejoin="round"
          />
          <path
            d="M10 12h4v6h-4z"
            stroke="white"
            stroke-width="2.5"
            stroke-linejoin="round"
          />
        </svg>
      </div>
    </div>

    <h1>{{ title }}</h1>
    <p class="subtitle">{{ subtitle }}</p>

  </div>
</template>

<style scoped>
.login-header {
  display: flex;
  flex-direction: column;
  align-items: center;   
  text-align: center;
  margin-bottom: 30px;
}

.logo-section {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 16px;
}

.logo-icon {
  width: 80px;
  height: 80px;
  background: #4F39F6;
  border: 3px solid #000000;
  border-radius: 10px;

  display: flex;
  align-items: center;
  justify-content: center; 

  box-shadow: 2px 2px 0px #000000;
}

h1 {
  margin-top: 15px;
  margin-bottom: 10px;
}

.subtitle {
  color: #666;
  margin: 0;
}
</style>