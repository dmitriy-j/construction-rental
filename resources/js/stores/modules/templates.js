// resources/js/stores/modules/templates.js
import { ref, readonly } from 'vue'

// Простое реактивное состояние
const templates = ref([])
const templateStats = ref({})
const loading = ref(false)

export const useTemplatesStore = () => {
  const loadTemplates = async (filters = {}) => {
    loading.value = true
    try {
      const response = await axios.get('/api/lessor/proposal-templates', { params: filters })
      templates.value = response.data.data || []
      return response
    } catch (error) {
      console.error('Error loading templates:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  const loadTemplateStats = async () => {
    try {
      const response = await axios.get('/api/lessor/proposal-templates/stats')
      templateStats.value = response.data.data || {}
      return response
    } catch (error) {
      console.error('Error loading template stats:', error)
      throw error
    }
  }

  const createTemplate = async (templateData) => {
    try {
      const response = await axios.post('/api/lessor/proposal-templates', templateData)
      templates.value.push(response.data.data)
      return response
    } catch (error) {
      console.error('Error creating template:', error)
      throw error
    }
  }

  const updateTemplate = async (id, data) => {
    try {
      const response = await axios.put(`/api/lessor/proposal-templates/${id}`, data)
      const index = templates.value.findIndex(t => t.id === id)
      if (index !== -1) {
        templates.value[index] = response.data.data
      }
      return response
    } catch (error) {
      console.error('Error updating template:', error)
      throw error
    }
  }

  const deleteTemplate = async (templateId) => {
    try {
      await axios.delete(`/api/lessor/proposal-templates/${templateId}`)
      templates.value = templates.value.filter(t => t.id !== templateId)
    } catch (error) {
      console.error('Error deleting template:', error)
      throw error
    }
  }

  const bulkAction = async ({ action, template_ids }) => {
    try {
      const response = await axios.post('/api/lessor/proposal-templates/bulk-actions', {
        action,
        template_ids
      })
      return response
    } catch (error) {
      console.error('Error performing bulk action:', error)
      throw error
    }
  }

  return {
    templates: readonly(templates),
    templateStats: readonly(templateStats),
    loading: readonly(loading),
    loadTemplates,
    loadTemplateStats,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    bulkAction
  }
}
