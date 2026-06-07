import { createRouter, createWebHistory } from 'vue-router'
import { isTokenExpired } from '../utils/auth'

import LoginView from '../views/auth/LoginView.vue'

import DashboardView from '../views/DashboardView.vue'
import MaintenanceRequestsView from '../views/MaintenanceRequestsView.vue'
import NewMaintenanceRequestView from '../views/NewMaintenanceRequestView.vue'
import MaintenanceTrackingView from '../views/MaintenanceTrackingView.vue'
import FacilitiesBookingView from '../views/FacilitiesBookingView.vue'
import NewBookingView from '../views/NewBookingView.vue'
import BookingHistoryView from '../views/BookingHistoryView.vue'
import ProfileView from '../views/ProfileView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: {
      hideHeader: true,
      requiresGuest: true
    }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/maintenance',
    name: 'maintenance',
    component: MaintenanceRequestsView,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/maintenance/new',
    name: 'new-maintenance',
    component: NewMaintenanceRequestView,
    meta: {
      requiresAuth: true,
      role: 'student'
    }
  },
  {
    path: '/maintenance/tracking',
    name: 'maintenance-tracking',
    component: MaintenanceTrackingView,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/facilities',
    name: 'facilities',
    component: FacilitiesBookingView,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/facilities/book',
    name: 'new-booking',
    component: NewBookingView,
    meta: {
      requiresAuth: true,
      role: 'student'
    }
  },
  {
    path: '/bookings',
    name: 'bookings',
    component: BookingHistoryView,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: ForgotPasswordView,
    meta: {
      hideHeader: true,
      requiresGuest: true
    }
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: {
      hideHeader: true,
      requiresGuest: true
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const userStr = localStorage.getItem('user')
  const user = userStr ? JSON.parse(userStr) : null

  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)

  if (requiresAuth) {
    if (!token || isTokenExpired(token) || !user) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      next('/login')
    } else if (to.meta.role && user.role !== to.meta.role) {
      next('/dashboard')
    } else {
      next()
    }
  } else if (requiresGuest) {
    if (token && !isTokenExpired(token) && user) {
      next('/dashboard')
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router