import api from '@/axios'

/**
 * Download a CSV from an authenticated API endpoint.
 * Uses axios so the Bearer token is sent automatically.
 *
 * @param {string} endpoint  - e.g. '/export/results'
 * @param {object} params    - query params
 * @param {string} filename  - suggested filename
 */
export async function downloadCsv(endpoint, params = {}, filename = 'export.csv') {
  try {
    const response = await api.get(endpoint, {
      params,
      responseType: 'blob',
    })

    // Try to get filename from Content-Disposition header
    const disposition = response.headers['content-disposition']
    if (disposition) {
      const match = disposition.match(/filename="?([^";\n]+)"?/i)
      if (match) filename = match[1]
    }

    const url  = window.URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8;' }))
    const link = document.createElement('a')
    link.href        = url
    link.download    = filename
    link.style.display = 'none'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Export failed:', e)
    alert('Export failed. Please try again.')
  }
}
