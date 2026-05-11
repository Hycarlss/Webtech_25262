import { createRouter, createWebHistory } from 'vue-router'

import AddPersonView from '../views/AddPersonView.vue'
import LastPersonView from '../views/LastPersonView.vue'
import PersonListView from '../views/PersonListView.vue'

// Define routes in array
const routes = [
  {
    path: '/',
    name: 'add-person',
    component: AddPersonView
  },
  {
    path: '/last',
    name: 'last-person',
    component: LastPersonView
  },
  {
    path: '/list',
    name: 'person-list',
    component: PersonListView
  }
]

//backward & foward in browser
const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router