import { createRouter, createWebHistory } from 'vue-router'

import DashboardView from '../views/DashboardView.vue'
import MaintenanceRequestsView from '../views/MaintenanceRequestsView.vue'
import NewMaintenanceRequestView from '../views/NewMaintenanceRequestView.vue'
import MaintenanceTrackingView from '../views/MaintenanceTrackingView.vue'
import FacilitiesBookingView from '../views/FacilitiesBookingView.vue'
import NewBookingView from '../views/NewBookingView.vue'
import BookingHistoryView from '../views/BookingHistoryView.vue'
import ProfileView from '../views/ProfileView.vue'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router