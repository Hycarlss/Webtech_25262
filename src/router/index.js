import { createRouter, createWebHistory } from 'vue-router'

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
      hideHeader: true
    }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView
  },
  {
    path: '/maintenance',
    name: 'maintenance',
    component: MaintenanceRequestsView
  },
  {
    path: '/maintenance/new',
    name: 'new-maintenance',
    component: NewMaintenanceRequestView
  },
  {
    path: '/maintenance/tracking',
    name: 'maintenance-tracking',
    component: MaintenanceTrackingView
  },
  {
    path: '/facilities',
    name: 'facilities',
    component: FacilitiesBookingView
  },
  {
    path: '/facilities/book',
    name: 'new-booking',
    component: NewBookingView
  },
  {
    path: '/bookings',
    name: 'bookings',
    component: BookingHistoryView
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: ForgotPasswordView,
    meta: {
      hideHeader: true
    }
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: {
      hideHeader: true
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const user = localStorage.getItem('user')

  const publicPages = ['/login', '/forgot-password', '/register']
  const isPublic = publicPages.includes(to.path)

  if (!isPublic && !user) {
    next('/login')
  } else {
    next()
  }
})

export default router