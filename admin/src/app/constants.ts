export const STORAGE_KEYS = {
  token: 'borz33.admin.token',
  user: 'borz33.admin.user',
  deviceUuid: 'borz33.admin.deviceUuid',
} as const;

export const routes = {
  login: '/login',
  forgotPassword: '/forgot-password',
  resetPassword: '/reset-password',
  home: '/',
  orders: '/orders',
  products: '/products',
  users: '/users',
  usersNew: '/users/new',
  usersEdit: '/users/:id',
  customers: '/customers',
  content: '/content',
  campaigns: '/campaigns',
  shipments: '/shipments',
  messages: '/messages',
  reports: '/reports',
  settings: '/settings',
} as const;
