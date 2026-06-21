import { createRouter, createWebHistory } from 'vue-router'

import LoginView from '../views/auth/LoginView.vue'

import DashboardView from '../views/DashboardView.vue'
import MaintenanceReports from '../pages/MaintenanceReports.vue'
import FacilitiesBookingView from '../views/FacilitiesBookingView.vue'
import NewBookingView from '../views/NewBookingView.vue'
import BookingHistoryView from '../views/BookingHistoryView.vue'
import ProfileView from '../views/ProfileView.vue'
import AnalyticsView from '../views/AnalyticsView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordPage.vue'

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
    component: MaintenanceReports,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/maintenance/new',
    redirect: '/maintenance'
  },
  {
    path: '/maintenance/tracking',
    redirect: '/maintenance'
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
    path: '/analytics',
    name: 'analytics',
    component: AnalyticsView,
    meta: {
      requiresAuth: true,
      role: 'staff/admin'
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
    path: '/reset-password',
    name: 'reset-password',
    component: ResetPasswordView,
    meta: { hideHeader: true, requiresGuest: true }
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
  const user = JSON.parse(localStorage.getItem('user') || 'null')

  const requiresAuth = to.matched.some(r => r.meta.requiresAuth)
  const requiresGuest = to.matched.some(r => r.meta.requiresGuest)

  if (requiresAuth) {
    if (!token || !user) {
      next('/login')
    } else if (to.meta.roles && !to.meta.roles.includes(user.role)) {
      next('/dashboard')
    } else {
      next()
    }
  }

  else if (requiresGuest) {
    if (token && user) {
      next('/dashboard')
    } else {
      next()
    }
  }

  else {
    next()
  }
})

export default router
