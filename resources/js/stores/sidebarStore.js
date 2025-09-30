// resources/js/stores/sidebarStore.js
document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        isMini: localStorage.getItem('sidebarMini') === 'true',
        isMobileOpen: false,
        menuHTML: '',

        init() {
            this.loadMenuData();
            this.setupEventListeners();
        },

        async loadMenuData() {
            try {
                const response = await fetch('/api/sidebar/menu');
                const data = await response.json();
                this.menuHTML = this.generateMenuHTML(data);
            } catch (error) {
                console.error('Ошибка загрузки меню:', error);
                this.menuHTML = this.getFallbackMenu();
            }
        },

        generateMenuHTML(menuData) {
            // Генерация HTML на основе роли пользователя
            return `
                <div class="section-header">
                    <i class="bi bi-menu-button-wide"></i>
                    <h4>Основное меню</h4>
                </div>
                <ul class="nav-menu">
                    ${menuData.items.map(item => `
                        <li class="nav-item">
                            <a class="nav-link ${item.isActive ? 'active' : ''}"
                               href="${item.url}"
                               data-tooltip="${item.tooltip}">
                                <i class="nav-icon ${item.icon}"></i>
                                <span class="nav-text">${item.name}</span>
                                ${item.badge ? `<span class="badge ${item.badge.class}">${item.badge.text}</span>` : ''}
                            </a>
                        </li>
                    `).join('')}
                </ul>
            `;
        },

        toggleMini() {
            this.isMini = !this.isMini;
            localStorage.setItem('sidebarMini', this.isMini);
            document.dispatchEvent(new CustomEvent('sidebarToggle', { detail: { isMini: this.isMini } }));
        },

        toggleMobile() {
            this.isMobileOpen = !this.isMobileOpen;
        }
    });

    Alpine.store('user', {
        name: '{{ Auth::user()->name }}',
        avatar: '{{ Auth::user()->profile_photo_url }}',
        roleBadge: `{!! $userRoleBadge !!}` // Генерируется на сервере
    });
});
