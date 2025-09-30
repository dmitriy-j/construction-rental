import { createRouter, createWebHistory } from 'vue-router'

// Импортируйте ваши компоненты
// import RentalRequestList from './components/RentalRequest/RentalRequestList.vue'
// import RentalRequestShow from './components/RentalRequest/RentalRequestShow.vue'

const routes = [
  // Определите ваши маршруты здесь
  // { path: '/lessee/rental-requests', component: RentalRequestList, name: 'rental-requests.index' },
  // { path: '/lessee/rental-requests/:id', component: RentalRequestShow, name: 'rental-requests.show' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
