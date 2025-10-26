class SidebarComponent extends HTMLElement {
    constructor() {
        super();
        this.isMini = localStorage.getItem('sidebarMini') === 'true';
    }

    connectedCallback() {
        this.render();
        this.initEventListeners();
    }

    render() {
        this.innerHTML = `
            <aside class="sidebar-container ${this.isMini ? 'sidebar-mini' : ''}"
                    x-data="sidebarData()"
                    @resize.window="handleResize">
                <!-- Динамическое содержимое через Alpine.js -->
                <div class="user-profile-card">
                    <div class="avatar-container">
                        <div class="avatar">
                            <img x-bind:src="$store.user.avatar" x-bind:alt="$store.user.name" class="profile-avatar"
                                 x-show="$store.user.avatar">
                            <i class="bi bi-person-circle" x-show="!$store.user.avatar"></i>
                        </div>
                        <div class="user-info" x-show="!isMini">
                            <div class="user-name" x-text="$store.user.name"></div>
                            <div class="user-role" x-html="$store.user.roleBadge"></div>
                        </div>
                    </div>
                </div>

                <div class="sidebar-controls">
                    <button class="sidebar-collapse-btn ripple d-lg-none"
                            @click="$store.sidebar.toggleMobile()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <button class="sidebar-minify-btn ripple d-none d-lg-block"
                            @click="$store.sidebar.toggleMini()">
                        <i class="bi" x-bind:class="isMini ? 'bi-chevron-right' : 'bi-chevron-left'"></i>
                    </button>
                </div>

                <nav class="sidebar-navigation">
                    <!-- Меню будет рендериться через Alpine.js -->
                    <div x-html="$store.sidebar.menuHTML"></div>
                </nav>

                <div class="sidebar-footer" x-show="!isMini">
                    <div class="app-version">v1.2.5</div>
                    <div class="session-time">
                        <i class="bi bi-clock-history"></i>
                        <span x-text="new Date().toLocaleString('ru-RU')"></span>
                    </div>
                </div>
            </aside>
        `;
    }

    initEventListeners() {
        // Все взаимодействия через Alpine.js
    }
}

customElements.define('app-sidebar', SidebarComponent);
