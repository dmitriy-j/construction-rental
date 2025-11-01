<template>
  <div class="professional-pagination" v-if="shouldShowPagination">
    <nav aria-label="Навигация по страницам">
      <ul class="pagination justify-content-center mb-0">
        <!-- Кнопка "Назад" -->
        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
          <button
            class="page-link"
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            aria-label="Предыдущая страница"
          >
            <i class="fas fa-chevron-left"></i>
          </button>
        </li>

        <!-- Первая страница -->
        <li class="page-item" v-if="showFirstPage">
          <button class="page-link" @click="goToPage(1)">1</button>
        </li>

        <!-- Многоточие после первой страницы -->
        <li class="page-item disabled" v-if="showLeftEllipsis">
          <span class="page-link">...</span>
        </li>

        <!-- Основные страницы -->
        <li
          v-for="page in visiblePages"
          :key="page"
          class="page-item"
          :class="{ 'active': page === currentPage }"
        >
          <button
            class="page-link"
            @click="goToPage(page)"
            :aria-current="page === currentPage ? 'page' : null"
          >
            {{ page }}
          </button>
        </li>

        <!-- Многоточие перед последней страницей -->
        <li class="page-item disabled" v-if="showRightEllipsis">
          <span class="page-link">...</span>
        </li>

        <!-- Последняя страница -->
        <li class="page-item" v-if="showLastPage">
          <button class="page-link" @click="goToPage(totalPages)">{{ totalPages }}</button>
        </li>

        <!-- Кнопка "Вперед" -->
        <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
          <button
            class="page-link"
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            aria-label="Следующая страница"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
        </li>
      </ul>
    </nav>

    <!-- Информация о странице -->
    <div class="pagination-info text-center mt-2">
      <small class="text-muted">
        Показано {{ showingStart }}-{{ showingEnd }} из {{ totalItems }} заявок
      </small>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProfessionalPagination',
  props: {
    currentPage: {
      type: Number,
      required: true,
      default: 1
    },
    totalItems: {
      type: Number,
      required: true,
      default: 0
    },
    perPage: {
      type: Number,
      default: 10
    },
    maxVisiblePages: {
      type: Number,
      default: 5
    }
  },
  computed: {
    totalPages() {
      return Math.ceil(this.totalItems / this.perPage);
    },

    shouldShowPagination() {
      return this.totalPages > 1;
    },

    showingStart() {
      return ((this.currentPage - 1) * this.perPage) + 1;
    },

    showingEnd() {
      const end = this.currentPage * this.perPage;
      return end > this.totalItems ? this.totalItems : end;
    },

    visiblePages() {
      const pages = [];
      const half = Math.floor(this.maxVisiblePages / 2);
      let start = Math.max(1, this.currentPage - half);
      let end = Math.min(this.totalPages, start + this.maxVisiblePages - 1);

      // Adjust start if we're near the end
      if (end - start + 1 < this.maxVisiblePages) {
        start = Math.max(1, end - this.maxVisiblePages + 1);
      }

      for (let i = start; i <= end; i++) {
        pages.push(i);
      }

      return pages;
    },

    showFirstPage() {
      return this.visiblePages[0] > 1;
    },

    showLastPage() {
      return this.visiblePages[this.visiblePages.length - 1] < this.totalPages;
    },

    showLeftEllipsis() {
      return this.visiblePages[0] > 2;
    },

    showRightEllipsis() {
      return this.visiblePages[this.visiblePages.length - 1] < this.totalPages - 1;
    }
  },
  methods: {
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages && page !== this.currentPage) {
        this.$emit('page-changed', page);
      }
    }
  }
}
</script>

<style scoped>
.professional-pagination {
  margin: 2rem 0 1rem;
}

.pagination {
  gap: 0.25rem;
}

.page-item .page-link {
  border: 1px solid #e9ecef;
  border-radius: 0.375rem;
  color: #6c757d;
  font-weight: 500;
  min-width: 42px;
  text-align: center;
  transition: all 0.2s ease;
  background: white;
}

.page-item .page-link:hover {
  background-color: #f8f9fa;
  border-color: #dee2e6;
  color: #495057;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.page-item.active .page-link {
  background: linear-gradient(135deg, #0d6efd, #0a58ca);
  border-color: #0d6efd;
  color: white;
  box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
}

.page-item.disabled .page-link {
  color: #6c757d;
  background-color: #f8f9fa;
  border-color: #dee2e6;
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.page-item:not(.disabled):not(.active) .page-link:focus {
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
  border-color: #86b7fe;
}

.pagination-info {
  font-size: 0.875rem;
}

/* Анимация перехода между страницами */
.page-link {
  position: relative;
  overflow: hidden;
}

.page-link::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  background: rgba(13, 110, 253, 0.1);
  border-radius: 50%;
  transition: all 0.3s ease;
  transform: translate(-50%, -50%);
}

.page-link:active::after {
  width: 100px;
  height: 100px;
}

/* Responsive */
@media (max-width: 768px) {
  .pagination {
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
  }

  .page-item .page-link {
    min-width: 38px;
    padding: 0.375rem 0.5rem;
    font-size: 0.875rem;
  }

  .pagination-info {
    font-size: 0.8rem;
  }
}

@media (max-width: 576px) {
  .professional-pagination {
    margin: 1.5rem 0 0.5rem;
  }

  .page-item .page-link {
    min-width: 36px;
    padding: 0.25rem 0.4rem;
    font-size: 0.8rem;
  }

  .pagination-info small {
    font-size: 0.75rem;
  }
}

/* Темная тема поддержка */
@media (prefers-color-scheme: dark) {
  .page-item .page-link {
    background: #2d3748;
    border-color: #4a5568;
    color: #e2e8f0;
  }

  .page-item .page-link:hover {
    background: #4a5568;
    border-color: #718096;
    color: #f7fafc;
  }

  .page-item.disabled .page-link {
    background: #1a202c;
    border-color: #2d3748;
    color: #718096;
  }
}
</style>
