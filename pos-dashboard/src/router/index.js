import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'
import Login from '../pages/login.vue'
import Dashboard from '../pages/Dashboard.vue'
import Restaurants from '../pages/Restaurants.vue'
import Branches from '../pages/Branches.vue'
import Users from '../pages/Users.vue'
import Roles from '../pages/Roles.vue'
import Products from '../pages/Products.vue'
import Orders from '../pages/Orders.vue'
import Employees from '../pages/Employees.vue'
import Categories from '../pages/Categories.vue'
import Menus from '../pages/Menus.vue'
import Inventory from '../pages/Inventory.vue'
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
    meta: { ...authenticatedMeta, title: 'Dashboard', permission: 'dashboard.view' },
  },
  {
    path: '/restaurants',
    name: 'restaurants',
    component: Restaurants,
    meta: { ...authenticatedMeta, title: 'Restaurants', permission: 'platform.restaurants.manage' },
  },
  {
    path: '/branches',
    name: 'branches',
    component: Branches,
    meta: { ...authenticatedMeta, title: 'Branches', permission: 'branches.view' },
  },
  {
    path: '/users',
    name: 'users',
    component: Users,
    meta: { ...authenticatedMeta, title: 'Users', permission: 'users.view' },
  },
  {
    path: '/roles',
    name: 'roles',
    component: Roles,
    meta: { ...authenticatedMeta, title: 'Roles & Permissions', permission: 'roles.view' },
  },
  {
    path: '/products',
    name: 'products',
    component: Products,
    meta: { ...authenticatedMeta, title: 'Products', permission: 'products.view' },
  },
  {
    path: '/orders',
    name: 'orders',
    component: Orders,
    meta: { ...authenticatedMeta, title: 'Orders', permission: 'orders.view' },
  },
  {
    path: '/tables',
    name: 'tables',
    component: Tables,
    meta: { ...authenticatedMeta, title: 'Tables', permission: 'tables.view' },
  },
  {
    path: '/employees',
    name: 'employees',
    component: Employees,
    meta: { ...authenticatedMeta, title: 'Employees', permission: 'employees.view' },
  },
  {
    path: '/categories',
    name: 'categories',
    component: Categories,
    meta: { ...authenticatedMeta, title: 'Categories', permission: 'categories.view' },
  },
  {
    path: '/menus',
    name: 'menus',
    component: Menus,
    meta: { ...authenticatedMeta, title: 'Menus', permission: 'menu.view' },
  },
  {
    path: '/inventory',
    name: 'inventory',
    component: Inventory,
    meta: { ...authenticatedMeta, title: 'Inventory', permission: 'inventory.view' },
  },
  {
    path: '/suppliers',
    name: 'suppliers',
    component: Suppliers,
    meta: { ...authenticatedMeta, title: 'Suppliers', permission: 'suppliers.view' },
  },
  {
    path: '/ingredients',
    name: 'ingredients',
    component: Ingredients,
    meta: { ...authenticatedMeta, title: 'Ingredients', permission: 'ingredients.view' },
  },
  {
    path: '/recipe',
    name: 'recipe',
    component: Recipe,
    meta: { ...authenticatedMeta, title: 'Recipes', permission: 'recipes.view' },
  },
  {
    path: '/settings',
    name: 'settings',
    component: Settings,
    meta: { ...authenticatedMeta, title: 'Settings', permission: 'settings.view' },
  },
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
  history: createWebHashHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  document.title = to.meta?.title
    ? `Restaurant Suite | ${to.meta.title}`
    : 'Restaurant Suite Dashboard'

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next('/login')
    return
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    next('/dashboard')
    return
  }

  if (to.meta.permission && !auth.can(to.meta.permission)) {
    next('/dashboard')
    return
  }

  next()
})

export default router
