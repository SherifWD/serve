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
import ERP from '../pages/ERP.vue'
import MES from '../pages/MES.vue'
import PLM from '../pages/PLM.vue'
import SCM from '../pages/SCM.vue'
import WMS from '../pages/WMS.vue'
import QMS from '../pages/QMS.vue'
import HRMS from '../pages/HRMS.vue'
import DMS from '../pages/DMS.vue'
import Visitor from '../pages/Visitor.vue'
import IoT from '../pages/IoT.vue'
import Procurement from '../pages/Procurement.vue'
import Commerce from '../pages/Commerce.vue'
import CMMS from '../pages/CMMS.vue'
import Finance from '../pages/Finance.vue'
import CRM from '../pages/CRM.vue'
import BI from '../pages/BI.vue'
import HSE from '../pages/HSE.vue'
import Budgeting from '../pages/Budgeting.vue'
import Projects from '../pages/Projects.vue'
import Communication from '../pages/Communication.vue'
import Platform from '../pages/Platform.vue'
import BudgetingReports from '../pages/BudgetingReports.vue'
import ProjectsReports from '../pages/ProjectsReports.vue'
import CommunicationReports from '../pages/CommunicationReports.vue'

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/login', component: Login },
  { path: '/dashboard', component: ERP, meta: { requiresAuth: true } },
  { path: '/erp', redirect: '/dashboard' },
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
  {path: '/pos', component: Dashboard, meta: { requiresAuth: true }},
  {path: '/mes', component: MES, meta: { requiresAuth: true }},
  {path: '/plm', component: PLM, meta: { requiresAuth: true }},
  {path: '/scm', component: SCM, meta: { requiresAuth: true }},
  {path: '/wms', component: WMS, meta: { requiresAuth: true }},
  {path: '/dms', component: DMS, meta: { requiresAuth: true }},
  {path: '/iot', component: IoT, meta: { requiresAuth: true }},
  {path: '/cmms', component: CMMS, meta: { requiresAuth: true }},
  {path: '/qms', component: QMS, meta: { requiresAuth: true }},
  {path: '/hrms', component: HRMS, meta: { requiresAuth: true }},
  {path: '/visitor', component: Visitor, meta: { requiresAuth: true }},
  {path: '/procurement', component: Procurement, meta: { requiresAuth: true }},
  {path: '/commerce', component: Commerce, meta: { requiresAuth: true }},
  {path: '/crm', component: CRM, meta: { requiresAuth: true }},
  {path: '/finance', component: Finance, meta: { requiresAuth: true }},
  {path: '/bi', component: BI, meta: { requiresAuth: true }},
  {path: '/hse', component: HSE, meta: { requiresAuth: true }},
  {path: '/budgeting', component: Budgeting, meta: { requiresAuth: true }},
  {path: '/budgeting/reports', component: BudgetingReports, meta: { requiresAuth: true }},
  {path: '/projects', component: Projects, meta: { requiresAuth: true }},
  {path: '/projects/reports', component: ProjectsReports, meta: { requiresAuth: true }},
  {path: '/communication', component: Communication, meta: { requiresAuth: true }},
  {path: '/communication/reports', component: CommunicationReports, meta: { requiresAuth: true }},
  {path: '/platform', component: Platform, meta: { requiresAuth: true }},
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
