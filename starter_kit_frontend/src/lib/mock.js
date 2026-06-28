// Static mock data mirroring the Starter Kit API response shapes.
// Used purely to drive the static design — no real requests are made.

export const currentAdmin = {
  id: 1,
  name: 'Default Admin',
  email: 'admin@example.com',
  user_type: 1,
  user_type_label: 'Admin',
  status: 1,
  status_label: 'Active',
  roles: [{ name: 'Administrator', status_label: 'Active' }],
  permissions: [
    'roles.view', 'roles.create', 'roles.update', 'roles.delete',
    'admins.view', 'admins.create', 'admins.update', 'admins.delete',
    'users.view', 'users.update', 'locations.view', 'audit.view',
  ],
}

// Permissions grouped exactly like GET /admin/permissions
export const permissionGroups = [
  {
    group: 'Roles',
    permissions: [
      { id: 1, name: 'roles.view' },
      { id: 2, name: 'roles.create' },
      { id: 3, name: 'roles.update' },
      { id: 4, name: 'roles.delete' },
    ],
  },
  {
    group: 'Admins',
    permissions: [
      { id: 5, name: 'admins.view' },
      { id: 6, name: 'admins.create' },
      { id: 7, name: 'admins.update' },
      { id: 8, name: 'admins.delete' },
    ],
  },
  {
    group: 'Users',
    permissions: [
      { id: 9, name: 'users.view' },
      { id: 10, name: 'users.update' },
    ],
  },
  {
    group: 'Locations',
    permissions: [{ id: 11, name: 'locations.view' }],
  },
  {
    group: 'Audit',
    permissions: [{ id: 12, name: 'audit.view' }],
  },
]

export const roles = [
  {
    id: 1,
    name: 'Administrator',
    status: 1,
    status_label: 'Active',
    admins_count: 1,
    permissions: permissionGroups.flatMap((g) => g.permissions),
    created_at: '12/01/2026 09:14 AM',
  },
  {
    id: 2,
    name: 'Manager',
    status: 1,
    status_label: 'Active',
    admins_count: 3,
    permissions: [
      { id: 1, name: 'roles.view' },
      { id: 5, name: 'admins.view' },
      { id: 11, name: 'locations.view' },
    ],
    created_at: '03/02/2026 02:40 PM',
  },
  {
    id: 3,
    name: 'Support Agent',
    status: 1,
    status_label: 'Active',
    admins_count: 5,
    permissions: [
      { id: 5, name: 'admins.view' },
      { id: 9, name: 'users.view' },
    ],
    created_at: '21/03/2026 11:02 AM',
  },
  {
    id: 4,
    name: 'Auditor',
    status: 0,
    status_label: 'Inactive',
    admins_count: 0,
    permissions: [{ id: 12, name: 'audit.view' }],
    created_at: '08/04/2026 04:25 PM',
  },
]

export const activeRoleOptions = roles
  .filter((r) => r.status === 1)
  .map((r) => ({ id: r.id, name: r.name }))

export const adminUsers = [
  {
    id: 1, name: 'Default Admin', email: 'admin@example.com',
    user_type: 1, status: 1, status_label: 'Active',
    roles: [{ name: 'Administrator', status_label: 'Active' }],
    role_id: 1, permissions: ['roles.view', 'admins.view', 'locations.view'],
    created_at: '12/01/2026 09:14 AM',
  },
  {
    id: 2, name: 'Jane Manager', email: 'jane@example.com',
    user_type: 1, status: 1, status_label: 'Active',
    roles: [{ name: 'Manager', status_label: 'Active' }],
    role_id: 2, permissions: ['roles.view', 'admins.view'],
    created_at: '14/02/2026 10:30 AM',
  },
  {
    id: 3, name: 'Carlos Reyes', email: 'carlos@example.com',
    user_type: 1, status: 1, status_label: 'Active',
    roles: [{ name: 'Support Agent', status_label: 'Active' }],
    role_id: 3, permissions: ['admins.view', 'users.view'],
    created_at: '02/03/2026 08:12 AM',
  },
  {
    id: 4, name: 'Mei Lin', email: 'mei@example.com',
    user_type: 1, status: 0, status_label: 'Inactive',
    roles: [{ name: 'Support Agent', status_label: 'Active' }],
    role_id: 3, permissions: ['admins.view'],
    created_at: '19/03/2026 01:55 PM',
  },
  {
    id: 5, name: 'Tomás Novak', email: 'tomas@example.com',
    user_type: 1, status: 1, status_label: 'Active',
    roles: [{ name: 'Manager', status_label: 'Active' }],
    role_id: 2, permissions: ['roles.view', 'admins.view'],
    created_at: '30/03/2026 06:48 PM',
  },
]

export const addressLabels = [
  { value: 1, label: 'Home' },
  { value: 2, label: 'Work' },
  { value: 3, label: 'Billing' },
  { value: 4, label: 'Shipping' },
  { value: 99, label: 'Other' },
]

export const userAddresses = [
  {
    id: 10, user_id: 2, admin_area_id: 4,
    label: 1, label_name: 'Home', is_primary: true,
    address_line1: '12 MG Road', address_line2: 'Apt 4B',
    latitude: 18.52, longitude: 73.85, notes: 'Near the park',
    hierarchy: [
      { id: 1, level: 'ZONE', level_label: 'Zone', depth: 1, name: 'Western Zone' },
      { id: 2, level: 'STATE', level_label: 'State', depth: 2, name: 'Maharashtra' },
      { id: 3, level: 'DISTRICT', level_label: 'District', depth: 3, name: 'Pune' },
      { id: 4, level: 'CITY', level_label: 'City', depth: 4, name: 'Pune City' },
    ],
    created_at: '14/02/2026 10:30 AM',
  },
  {
    id: 11, user_id: 2, admin_area_id: 4,
    label: 2, label_name: 'Work', is_primary: false,
    address_line1: 'Tech Park, Tower C', address_line2: null,
    latitude: null, longitude: null, notes: null,
    hierarchy: [
      { id: 1, level: 'ZONE', level_label: 'Zone', depth: 1, name: 'Western Zone' },
      { id: 4, level: 'CITY', level_label: 'City', depth: 4, name: 'Pune City' },
    ],
    created_at: '20/02/2026 04:10 PM',
  },
]

export const countries = [
  { id: 2, iso2: 'BD', iso3: 'BGD', name: 'Bangladesh', isd_prefix: '+880', default_timezone: 'Asia/Dhaka', is_active: true },
  { id: 1, iso2: 'IN', iso3: 'IND', name: 'India', isd_prefix: '+91', default_timezone: 'Asia/Kolkata', is_active: true },
  { id: 4, iso2: 'SG', iso3: 'SGP', name: 'Singapore', isd_prefix: '+65', default_timezone: 'Asia/Singapore', is_active: true },
  { id: 3, iso2: 'US', iso3: 'USA', name: 'United States', isd_prefix: '+1', default_timezone: 'America/New_York', is_active: true },
]

export const countryStructures = {
  1: [
    { depth: 1, level: 'ZONE', label: 'Zone' },
    { depth: 2, level: 'STATE', label: 'State' },
    { depth: 3, level: 'DISTRICT', label: 'District' },
    { depth: 4, level: 'CITY', label: 'City' },
  ],
  2: [
    { depth: 1, level: 'DIVISION', label: 'Division' },
    { depth: 2, level: 'DISTRICT', label: 'District' },
    { depth: 3, level: 'UPAZILA', label: 'Upazila' },
    { depth: 4, level: 'CITY', label: 'City' },
  ],
  3: [
    { depth: 1, level: 'STATE', label: 'State' },
    { depth: 2, level: 'COUNTY', label: 'County' },
    { depth: 3, level: 'CITY', label: 'City' },
  ],
  4: [
    { depth: 1, level: 'REGION', label: 'Region' },
    { depth: 2, level: 'CITY', label: 'City' },
  ],
}

// India tree used by the Locations explorer
export const countryTree = {
  1: [
    {
      id: 1, level: 'ZONE', level_label: 'Zone', code: 'WEST', name: 'Western Zone', depth: 1,
      children: [
        {
          id: 2, level: 'STATE', level_label: 'State', code: 'MH', name: 'Maharashtra', depth: 2,
          children: [
            {
              id: 3, level: 'DISTRICT', level_label: 'District', code: 'PUNE', name: 'Pune', depth: 3,
              children: [
                { id: 4, level: 'CITY', level_label: 'City', code: 'PUNE-CITY', name: 'Pune City', depth: 4, children: [] },
                { id: 5, level: 'CITY', level_label: 'City', code: 'PIMPRI', name: 'Pimpri-Chinchwad', depth: 4, children: [] },
              ],
            },
            {
              id: 6, level: 'DISTRICT', level_label: 'District', code: 'MUM', name: 'Mumbai', depth: 3,
              children: [
                { id: 7, level: 'CITY', level_label: 'City', code: 'MUM-CITY', name: 'Mumbai City', depth: 4, children: [] },
              ],
            },
          ],
        },
        {
          id: 8, level: 'STATE', level_label: 'State', code: 'GJ', name: 'Gujarat', depth: 2,
          children: [
            {
              id: 9, level: 'DISTRICT', level_label: 'District', code: 'AHM', name: 'Ahmedabad', depth: 3,
              children: [
                { id: 10, level: 'CITY', level_label: 'City', code: 'AHM-CITY', name: 'Ahmedabad City', depth: 4, children: [] },
              ],
            },
          ],
        },
      ],
    },
    {
      id: 11, level: 'ZONE', level_label: 'Zone', code: 'SOUTH', name: 'Southern Zone', depth: 1,
      children: [
        {
          id: 12, level: 'STATE', level_label: 'State', code: 'KA', name: 'Karnataka', depth: 2,
          children: [
            {
              id: 13, level: 'DISTRICT', level_label: 'District', code: 'BLR', name: 'Bengaluru Urban', depth: 3,
              children: [
                { id: 14, level: 'CITY', level_label: 'City', code: 'BLR-CITY', name: 'Bengaluru', depth: 4, children: [] },
              ],
            },
          ],
        },
      ],
    },
  ],
}

export const auditLogs = [
  {
    id: 'a1f3c2', action: 'admin.created',
    actor: { type: 'User', id: '1', name: 'Default Admin' },
    subject: { type: 'User', id: '5', name: 'Tomás Novak' },
    tags: ['admin', 'create'], correlation_id: 'req_9f2a',
    created_at: '30/03/2026 06:48 PM',
  },
  {
    id: 'b7e8d4', action: 'role.updated',
    actor: { type: 'User', id: '1', name: 'Default Admin' },
    subject: { type: 'Role', id: '2', name: 'Manager' },
    tags: ['role', 'update'], correlation_id: 'req_77c1',
    created_at: '28/03/2026 03:22 PM',
  },
  {
    id: 'c2a9f1', action: 'admin.password_reset',
    actor: { type: 'User', id: '2', name: 'Jane Manager' },
    subject: { type: 'User', id: '4', name: 'Mei Lin' },
    tags: ['security', 'password'], correlation_id: 'req_44b0',
    created_at: '24/03/2026 09:05 AM',
  },
  {
    id: 'd5b0e7', action: 'role.deleted',
    actor: { type: 'User', id: '1', name: 'Default Admin' },
    subject: { type: 'Role', id: '9', name: 'Temp Role' },
    tags: ['role', 'delete'], correlation_id: 'req_18aa',
    created_at: '19/03/2026 01:55 PM',
  },
  {
    id: 'e9c4a2', action: 'admin.login',
    actor: { type: 'User', id: '3', name: 'Carlos Reyes' },
    subject: { type: 'User', id: '3', name: 'Carlos Reyes' },
    tags: ['auth'], correlation_id: 'req_0c3d',
    created_at: '18/03/2026 08:40 AM',
  },
]

export const dashboardStats = [
  { key: 'admins', label: 'Admin Users', value: 5, delta: '+2 this month', trend: 'up' },
  { key: 'roles', label: 'Active Roles', value: 3, delta: '4 total', trend: 'flat' },
  { key: 'permissions', label: 'Permissions', value: 12, delta: 'across 5 groups', trend: 'flat' },
  { key: 'countries', label: 'Countries', value: 4, delta: 'location data', trend: 'flat' },
]
