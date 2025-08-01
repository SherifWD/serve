import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/login.vue'
import Dashboard from '../pages/Dashboard.vue'
import Branches from '../pages/Branches.vue'
import Users from '../pages/Users.vue'
import Products from '../pages/Products.vue'
import Orders from '../pages/Orders.vue'
import Employees from '../pages/Employees.vue'
import Categories from '../pages/Categories.vue'
import Menus from '../pages/Menus.vue'
import Inventory from '../pages/Inventory.vue'
import { useAuthStore } from '../store/auth'
import Suppliers from '../pages/Suppliers.vue'
import Tables from '../pages/Tables.vue'
import Ingredients from '../pages/Ingredients.vue'
import Recipe from '../pages/Recipe.vue'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', component: Login },
  { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/branches', component: Branches, meta: { requiresAuth: true } },
  { path: '/users', component: Users, meta: { requiresAuth: true } },
  {path: '/products', component: Products},
  {path: '/orders', component: Orders},
  {path: '/tables', component: Tables},
  {path: '/employees', component: Employees},
  {path: '/categories', component: Categories},
  {path: '/menus', component: Menus},
  {path: '/inventory', component: Inventory},
  {path: '/suppliers', component: Suppliers},
  {path: '/ingredients', component: Ingredients},
  {path: '/recipe', component: Recipe},
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard for auth
router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.token) {
    next('/login')
  } else {
    next()
  }
})

export default router
