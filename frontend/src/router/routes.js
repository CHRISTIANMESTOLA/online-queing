const routes = [
  {
    path: '/',
    component: () => import('layouts/AppLayout.vue'),
    children: [
      { path: '', redirect: '/queue' },
      {
        path: 'login',
        name: 'login',
        component: () => import('pages/LoginPage.vue'),
        meta: { guestOnly: true },
      },
      {
        path: 'queue',
        name: 'queue-generate',
        component: () => import('pages/QueueGeneratePage.vue'),
      },
      {
        path: 'monitor',
        name: 'queue-monitor',
        component: () => import('pages/MonitorPage.vue'),
      },
      {
        path: 'staff/queue',
        name: 'staff-queue',
        component: () => import('pages/StaffQueuePage.vue'),
        meta: { requiresAuth: true, roles: ['admin', 'staff'] },
      },
      {
        path: 'admin/offices',
        name: 'admin-offices',
        component: () => import('pages/AdminOfficesPage.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
      },
      {
        path: 'admin/staff',
        name: 'admin-staff',
        component: () => import('pages/AdminStaffPage.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
      },
    ],
  },

  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
