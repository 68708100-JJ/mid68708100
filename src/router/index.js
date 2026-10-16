import { createRouter, createWebHistory } from 'vue-router'
import StudentManagement from '../views/student.vue'

const routes = [
  {
    path: '/',
    name: 'students',
    component: StudentManagement
  },
  {
    path: '/ACC',
    redirect: '/'
  },
  {
    path: '/add',
    redirect: '/'
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

export default router
