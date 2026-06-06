<template>
  <header class="app-header">
    <!-- Logo -->
    <div class="logo-section">
      <div class="logo-icon">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
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
      <h1 class="logo-title">Hostel Management</h1>
    </div>

    <!-- Navigation -->
    <nav class="nav-menu">
      <RouterLink
        to="/dashboard"
        class="nav-item"
        :class="{ active: isTabActive('/dashboard') }"
      >
        Dashboard
      </RouterLink>

      <RouterLink
        to="/maintenance"
        class="nav-item"
        :class="{ active: isTabActive('/maintenance') }"
      >
        Maintenance
      </RouterLink>

      <RouterLink
        to="/facilities"
        class="nav-item"
        :class="{ active: isTabActive('/facilities') }"
      >
        Facilities
      </RouterLink>

      <RouterLink
        to="/profile"
        class="nav-item"
        :class="{ active: isTabActive('/profile') }"
      >
        Profile
      </RouterLink>

      <button @click="handleLogout" class="logout-btn">
        Logout
      </button>
    </nav>
  </header>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const isTabActive = (path) => {
  if (path === '/dashboard') {
    return route.path === '/dashboard' || route.path === '/'
  }
  return route.path.startsWith(path)
}

const handleLogout = () => {
  if (confirm("Are you sure you want to logout?")) {
    localStorage.removeItem('user')
    router.push('/login')
  }
}
</script>

<style scoped>
.app-header {
  height: 72px;
  background: #FFFFFF;
  border-bottom: 4px solid #000000;
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 4px 0px rgba(0, 0, 0, 0.05);
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-icon {
  width: 40px;
  height: 40px;
  background: #4F39F6;
  border: 3px solid #000000;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 2px 2px 0px #000000;
}

.logo-title {
  font-size: 20px;
  font-weight: 700;
  color: #000000;
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 16px;
}

.nav-item {
  height: 40px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  border-radius: 8px;
  text-decoration: none;
  color: #000000;
  font-size: 16px;
  font-weight: 700;
  border: 2px solid transparent;
  transition: all 0.1s ease;
}

.nav-item:hover {
  background: #F0E6D2;
  border: 2px solid #000000;
}

.nav-item.active {
  background: #FFC900;
  border: 3px solid #000000;
  box-shadow: 4px 4px 0px #000000;
}

.logout-btn {
  background: #FF90E8;
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: 16px;
  color: #000000;
  border: 2px solid #000000;
  border-radius: 8px;
  height: 40px;
  padding: 0 16px;
  cursor: pointer;
  box-shadow: 2px 2px 0px #000000;
  transition: all 0.1s ease;
}

.logout-btn:hover {
  transform: translate(1px, 1px);
  box-shadow: 1px 1px 0px #000000;
}

.logout-btn:active {
  transform: translate(2px, 2px);
  box-shadow: 0px 0px 0px #000000;
}

@media (max-width: 768px) {
  .app-header {
    height: auto;
    padding: 16px;
    flex-direction: column;
    gap: 12px;
  }
  .nav-menu {
    width: 100%;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
  }
}
</style>