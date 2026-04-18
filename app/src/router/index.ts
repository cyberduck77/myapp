import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/',
      redirect: '/dashboard',
    },
  ],
})

router.beforeEach((to, _from, next) => {
  const accessToken = localStorage.getItem('at')
  const isAuth = !!accessToken
  if (to.meta.requiresAuth && !isAuth) {
    next({ name: 'login' })
    return
  }
  if (to.meta.guest && isAuth) {
    next({ name: 'dashboard' })
    return
  }
  next()
})

export default router
