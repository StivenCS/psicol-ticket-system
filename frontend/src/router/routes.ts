import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    component: () => import('layouts/AuthLayout.vue'),
    meta: { guest: true },
    children: [
      {
        path: '',
        name: 'login',
        component: () => import('pages/auth/LoginPage.vue'),
      },
    ],
  },
  {
    path: '/',
    component: () => import('layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('pages/DashboardPage.vue'),
      },
      {
        path: 'tickets',
        name: 'tickets',
        component: () => import('pages/tickets/TicketListPage.vue'),
      },
      {
        path: 'tickets/create',
        name: 'tickets.create',
        component: () => import('pages/tickets/TicketFormPage.vue'),
      },
      {
        path: 'tickets/:id',
        name: 'tickets.show',
        component: () => import('pages/tickets/TicketDetailPage.vue'),
      },
      {
        path: 'tickets/:id/edit',
        name: 'tickets.edit',
        component: () => import('pages/tickets/TicketFormPage.vue'),
      },
    ],
  },

  // Always leave this as last one
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
];

export default routes;
