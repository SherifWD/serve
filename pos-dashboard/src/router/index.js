import { createRouter, createWebHashHistory } from 'vue-router'
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
import Settings from '../pages/Settings.vue'

const authenticatedMeta = { requiresAuth: true }

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', name: 'login', component: Login, meta: { guestOnly: true, title: 'Login' } },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: Dashboard,
    meta: { ...authenticatedMeta, title: 'Dashboard' },
  },
  {
    path: '/branches',
    name: 'branches',
    component: Branches,
    meta: { ...authenticatedMeta, title: 'Branches' },
  },
  {
    path: '/users',
    name: 'users',
    component: Users,
    meta: { ...authenticatedMeta, title: 'Users' },
  },
  {
    path: '/products',
    name: 'products',
    component: Products,
    meta: { ...authenticatedMeta, title: 'Products' },
  },
  {
    path: '/orders',
    name: 'orders',
    component: Orders,
    meta: { ...authenticatedMeta, title: 'Orders' },
  },
  {
    path: '/tables',
    name: 'tables',
    component: Tables,
    meta: { ...authenticatedMeta, title: 'Tables' },
  },
  {
    path: '/employees',
    name: 'employees',
    component: Employees,
    meta: { ...authenticatedMeta, title: 'Employees' },
  },
  {
    path: '/categories',
    name: 'categories',
    component: Categories,
    meta: { ...authenticatedMeta, title: 'Categories' },
  },
  {
    path: '/menus',
    name: 'menus',
    component: Menus,
    meta: { ...authenticatedMeta, title: 'Menus' },
  },
  {
    path: '/inventory',
    name: 'inventory',
    component: Inventory,
    meta: { ...authenticatedMeta, title: 'Inventory' },
  },
  {
    path: '/suppliers',
    name: 'suppliers',
    component: Suppliers,
    meta: { ...authenticatedMeta, title: 'Suppliers' },
  },
  {
    path: '/ingredients',
    name: 'ingredients',
    component: Ingredients,
    meta: { ...authenticatedMeta, title: 'Ingredients' },
  },
  {
    path: '/recipe',
    name: 'recipe',
    component: Recipe,
    meta: { ...authenticatedMeta, title: 'Recipes' },
  },
  {
    path: '/settings',
    name: 'settings',
    component: Settings,
    meta: { ...authenticatedMeta, title: 'Settings' },
  },
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
  history: createWebHashHistory(import.meta.env.BASE_URL),
  routes,
})

// Navigation guard for auth
router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (to.meta?.title) {
    document.title = `Restaurant Suite | ${to.meta.title}`
  } else {
    document.title = 'Restaurant Suite Dashboard'
  }

  if (to.meta.requiresAuth && !auth.token) {
    next('/login')
    return
  }

  if (to.meta.guestOnly && auth.token) {
    next('/dashboard')
    return
  }

  next()
})

export default router
