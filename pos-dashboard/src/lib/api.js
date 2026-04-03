const API_TARGETS = {
  prod: 'https://ambernoak.co.uk/serveu/serve/public/api',
  local: 'http://localhost:8000/api',
}

const deployTarget = (import.meta.env.VITE_DEPLOY_TARGET || 'local')
  .toString()
  .trim()
  .toLowerCase()

export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ||
  API_TARGETS[deployTarget] ||
  API_TARGETS.local
