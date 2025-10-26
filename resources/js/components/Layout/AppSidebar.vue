<template>
  <aside class="app-sidebar" :class="{ 'app-sidebar--collapsed': isCollapsed }">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
      <div class="user-profile" v-if="!isCollapsed">
        <div class="user-avatar">
          <div class="avatar-placeholder">
            {{ user.initials }}
          </div>
        </div>
        <div class="user-info">
          <div class="user-name">{{ user.name }}</div>
          <div class="user-role">
            <i :class="roleIcon" class="role-icon"></i>
            {{ user.role }}
          </div>
        </div>
      </div>

      <button
        class="sidebar-trigger"
        @click="toggleCollapse"
        :title="isCollapsed ? 'Развернуть меню' : 'Свернуть меню'"
      >
        <i class="bi" :class="isCollapsed ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
      </button>
    </div>

    <!-- Navigation Content -->
    <nav class="sidebar-nav">
      <div class="sidebar-section">
        <div v-if="!isCollapsed" class="section-label">
          Основное меню
        </div>
        <ul class="nav-list">
          <li class="nav-item" v-for="item in navigation.main" :key="item.id">
            <a
              :href="item.route"
              class="nav-link"
              :class="{ 'active': isActiveRoute(item) }"
            >
              <i :class="item.icon" class="nav-icon"></i>
              <span class="nav-text">{{ item.label }}</span>
              <span v-if="item.badge && !isCollapsed" class="nav-badge">
                {{ item.badge }}
              </span>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-section">
        <div v-if="!isCollapsed" class="section-label">
          Аккаунт
        </div>
        <ul class="nav-list">
          <li class="nav-item" v-for="item in navigation.account" :key="item.id">
            <a
              :href="item.route"
              class="nav-link"
              :class="{ 'active': isActiveRoute(item) }"
            >
              <i :class="item.icon" class="nav-icon"></i>
              <span class="nav-text">{{ item.label }}</span>
              <span v-if="item.badge && !isCollapsed" class="nav-badge">
                {{ item.badge }}
              </span>
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
      <div class="footer-content">
        <div class="app-version" v-if="!isCollapsed">
          v{{ version }}
        </div>
        <button
          @click="toggleTheme"
          class="theme-btn"
          :title="currentTheme === 'dark' ? 'Переключить на светлую тему' : 'Переключить на темную тему'"
        >
          <i class="bi" :class="currentTheme === 'dark' ? 'bi-sun' : 'bi-moon'"></i>
        </button>
      </div>
    </div>
  </aside>
</template>

<script>
export default {
  name: 'AppSidebar',
  data() {
    return {
      isCollapsed: localStorage.getItem('sidebar-collapsed') === 'true',
      currentTheme: localStorage.getItem('theme') || 'light',

      user: {
        name: 'Дмитрий Иванович',
        initials: 'ДИ',
        role: 'Арендатор'
      },

      version: '1.3.0',

      navigation: {
        main: [
          {
            id: 'dashboard',
            label: 'Главная',
            icon: 'bi bi-speedometer2',
            route: '/lessee/dashboard',
            badge: null
          },
          {
            id: 'rental-requests',
            label: 'Мои заявки',
            icon: 'bi bi-clipboard-plus',
            route: '/lessee/rental-requests',
            badge: 5
          },
          {
            id: 'catalog',
            label: 'Каталог техники',
            icon: 'bi bi-search',
            route: '/catalog',
            badge: null
          },
          {
            id: 'cart',
            label: 'Корзина',
            icon: 'bi bi-cart',
            route: '/lessee/cart',
            badge: 3
          },
          {
            id: 'orders',
            label: 'Мои заказы',
            icon: 'bi bi-list-task',
            route: '/lessee/orders',
            badge: null
          }
        ],
        account: [
          {
            id: 'profile',
            label: 'Профиль',
            icon: 'bi bi-person',
            route: '/profile',
            badge: null
          },
          {
            id: 'notifications',
            label: 'Уведомления',
            icon: 'bi bi-bell',
            route: '/notifications',
            badge: 12
          }
        ]
      }
    }
  },

  computed: {
    roleIcon() {
      const icons = {
        'Арендатор': 'bi bi-truck',
        'Арендодатель': 'bi bi-building',
        'Администратор': 'bi bi-shield-check'
      }
      return icons[this.user.role] || 'bi bi-person'
    }
  },

  methods: {
    toggleCollapse() {
      this.isCollapsed = !this.isCollapsed
      localStorage.setItem('sidebar-collapsed', this.isCollapsed.toString())
      this.updateBodyClasses()
    },

    toggleTheme() {
      this.currentTheme = this.currentTheme === 'dark' ? 'light' : 'dark'
      document.documentElement.setAttribute('data-theme', this.currentTheme)
      localStorage.setItem('theme', this.currentTheme)
    },

    isActiveRoute(item) {
      const currentPath = window.location.pathname
      const itemPath = item.route.replace(/\/$/, '')
      return currentPath.startsWith(itemPath)
    },

    updateBodyClasses() {
      if (this.isCollapsed) {
        document.body.classList.add('sidebar-collapsed')
        document.body.classList.remove('sidebar-expanded')
      } else {
        document.body.classList.add('sidebar-expanded')
        document.body.classList.remove('sidebar-collapsed')
      }
    }
  },

  mounted() {
    console.log('✅ AppSidebar mounted successfully')

    this.updateBodyClasses()
    document.documentElement.setAttribute('data-theme', this.currentTheme)
    document.body.classList.add('has-vue-sidebar')
  },

  beforeUnmount() {
    document.body.classList.remove('has-vue-sidebar', 'sidebar-collapsed', 'sidebar-expanded')
  }
}
</script>

<style scoped>
.app-sidebar {
  position: fixed;
  top: var(--navbar-height, 80px);
  left: 0;
  width: 280px;
  height: calc(100vh - var(--navbar-height, 80px));
  background: var(--bg-surface, #ffffff);
  border-right: 1px solid var(--divider, #dee2e6);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.app-sidebar--collapsed {
  width: 80px;
}

.sidebar-header {
  padding: 1rem;
  border-bottom: 1px solid var(--divider, #dee2e6);
  background: var(--bg-surface, #ffffff);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-shrink: 0;
  min-height: 100px;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color, #0b5ed7), var(--primary-dark, #0a58ca));
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-weight: 700;
  color: var(--text-primary, #1a1d21);
  margin-bottom: 0.25rem;
  font-size: 1rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-role {
  font-size: 0.8rem;
  color: var(--text-secondary, #6c757d);
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.role-icon {
  font-size: 0.7rem;
}

.sidebar-trigger {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid var(--divider, #dee2e6);
  background: var(--bg-surface, #ffffff);
  color: var(--text-primary, #1a1d21);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
  margin-left: 0.5rem;
}

.sidebar-trigger:hover {
  background: var(--primary-color, #0b5ed7);
  color: white;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0.5rem;
  overflow-y: auto;
}

.sidebar-section {
  margin-bottom: 1.5rem;
}

.section-label {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary, #6c757d);
  padding: 0.5rem 1rem;
  margin-bottom: 0.5rem;
}

.nav-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.nav-item {
  margin-bottom: 0.25rem;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  color: var(--text-primary, #1a1d21);
  text-decoration: none;
  transition: all 0.2s ease;
  position: relative;
}

.nav-link:hover {
  background: var(--primary-light, #cfe2ff);
  color: var(--primary-color, #0b5ed7);
}

.nav-link.active {
  background: var(--primary-color, #0b5ed7);
  color: white;
  font-weight: 600;
}

.nav-icon {
  width: 20px;
  height: 20px;
  margin-right: 0.75rem;
  font-size: 1rem;
  flex-shrink: 0;
  transition: inherit;
}

.nav-text {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: opacity 0.2s ease;
}

.nav-badge {
  background: var(--primary-color, #0b5ed7);
  color: white;
  border-radius: 12px;
  padding: 0.2rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 600;
  min-width: 20px;
  text-align: center;
}

.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid var(--divider, #dee2e6);
  background: var(--bg-surface, #ffffff);
  flex-shrink: 0;
}

.footer-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.app-version {
  font-size: 0.8rem;
  color: var(--text-secondary, #6c757d);
  background: var(--bg-secondary, #f8f9fa);
  padding: 0.3rem 0.6rem;
  border-radius: 6px;
  font-weight: 600;
}

.theme-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid var(--divider, #dee2e6);
  background: var(--bg-surface, #ffffff);
  color: var(--text-primary, #1a1d21);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.theme-btn:hover {
  background: var(--primary-color, #0b5ed7);
  color: white;
}

/* Collapsed state */
.app-sidebar--collapsed .user-info,
.app-sidebar--collapsed .nav-text,
.app-sidebar--collapsed .section-label,
.app-sidebar--collapsed .app-version {
  opacity: 0;
  visibility: hidden;
}

.app-sidebar--collapsed .nav-link {
  justify-content: center;
  padding: 0.75rem;
}

.app-sidebar--collapsed .nav-icon {
  margin-right: 0;
  font-size: 1.2rem;
}

.app-sidebar--collapsed .nav-badge {
  position: absolute;
  top: 4px;
  right: 4px;
  transform: scale(0.8);
}

/* Mobile Responsive */
@media (max-width: 991.98px) {
  .app-sidebar {
    transform: translateX(-100%);
  }

  .app-sidebar--mobile-open {
    transform: translateX(0);
  }
}

/* Content spacing when sidebar is present */
body.has-vue-sidebar .content-area {
  margin-left: 280px;
  transition: margin-left 0.3s ease;
}

body.sidebar-collapsed .content-area {
  margin-left: 80px;
}

@media (max-width: 991.98px) {
  body.has-vue-sidebar .content-area {
    margin-left: 0 !important;
  }
}
</style>
