const API_TARGETS = {
  prod: 'https://janovatech.com/pos/serve/api',
  local: 'http://localhost:8000/api',
}

const defaultDeployTarget = import.meta.env.DEV ? 'local' : 'prod'
const deployTarget = (import.meta.env.VITE_DEPLOY_TARGET || defaultDeployTarget)
  .toString()
  .trim()
  .toLowerCase()

export const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ||
  API_TARGETS[deployTarget] ||
  API_TARGETS.local
