import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

let stylesInjected = false

export async function showValidationAlert(fields, options = {}) {
  const normalized = normalizeFields(fields)
  if (!normalized.length) return true

  injectValidationStyles()

  await Swal.fire({
    title: options.title || 'Missing information',
    html: validationHtml(normalized),
    icon: 'warning',
    confirmButtonText: options.confirmButtonText || 'Review fields',
    confirmButtonColor: '#2a9d8f',
    focusConfirm: true,
  })

  highlightField(normalized[0])
  return false
}

export function missingField(label, isValid, selector = null) {
  return isValid ? null : { label, selector }
}

function normalizeFields(fields) {
  return (fields || [])
    .filter(Boolean)
    .map((field) => {
      if (typeof field === 'string') {
        return { label: field, selector: null }
      }

      return {
        label: field.label || field.name || 'Required field',
        selector: field.selector || null,
      }
    })
}

function validationHtml(fields) {
  const items = fields
    .map((field) => `<li>${escapeHtml(field.label)}</li>`)
    .join('')

  return `
    <div class="validation-alert-body">
      <p>Please complete these fields before saving:</p>
      <ul>${items}</ul>
    </div>
  `
}

function highlightField(field) {
  const target = findTarget(field)
  if (!target) return

  target.scrollIntoView({ behavior: 'smooth', block: 'center' })
  target.classList.remove('validation-alert-focus')
  void target.offsetWidth
  target.classList.add('validation-alert-focus')

  const input = target.matches('input, textarea, select, button, [tabindex]')
    ? target
    : target.querySelector('input, textarea, select, button, [tabindex]')

  setTimeout(() => {
    input?.focus?.({ preventScroll: true })
  }, 300)
}

function findTarget(field) {
  if (field.selector) {
    const selected = document.querySelector(field.selector)
    if (selected) return selected.closest('.v-input, .v-selection-control, .v-field') || selected
  }

  const label = normalizeLabel(field.label)
  if (!label) return null

  const candidates = [
    ...document.querySelectorAll('.v-navigation-drawer .v-label, .v-navigation-drawer .v-field-label, .v-dialog .v-label, .v-dialog .v-field-label'),
    ...document.querySelectorAll('.v-label, .v-field-label, label, [aria-label]'),
  ]

  const match = candidates.find((element) => {
    if (!isVisible(element)) return false
    const text = normalizeLabel(element.textContent || element.getAttribute('aria-label') || '')
    return text === label || text.includes(label) || label.includes(text)
  })

  return match?.closest('.v-input, .v-selection-control, .v-field') || match || null
}

function isVisible(element) {
  const rect = element.getBoundingClientRect()
  return rect.width > 0 && rect.height > 0
}

function normalizeLabel(value) {
  return String(value || '')
    .replace(/\*/g, '')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase()
}

function escapeHtml(value) {
  const element = document.createElement('div')
  element.textContent = String(value)
  return element.innerHTML
}

function injectValidationStyles() {
  if (stylesInjected) return

  const style = document.createElement('style')
  style.textContent = `
    .validation-alert-body {
      color: #1f2937;
      text-align: left;
    }
    .validation-alert-body p {
      margin: 0 0 10px;
    }
    .validation-alert-body ul {
      margin: 0;
      padding-left: 20px;
    }
    .validation-alert-focus {
      animation: validation-alert-pulse 1.3s ease;
      outline: 2px solid #d32f2f !important;
      outline-offset: 4px;
    }
    @keyframes validation-alert-pulse {
      0%, 100% { box-shadow: 0 0 0 0 rgba(211, 47, 47, 0); }
      35% { box-shadow: 0 0 0 8px rgba(211, 47, 47, 0.18); }
      70% { box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.12); }
    }
  `
  document.head.appendChild(style)
  stylesInjected = true
}
