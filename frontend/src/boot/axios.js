import { defineBoot } from '#q-app/wrappers'
import axios from 'axios'

const configuredBaseURL =
  import.meta.env.VITE_API_BASE_URL ||
  process.env.API_BASE_URL ||
  'http://localhost:8000/api'

const probeClient = axios.create({
  timeout: 1200,
  headers: {
    Accept: 'application/json',
  },
})

let resolvedBaseURL = null
let resolvingPromise = null

function normalizeBaseURL(url) {
  return (url || '').replace(/\/+$/, '')
}

function isLikelyLocalBaseURL(url) {
  return /^https?:\/\/(localhost|127\.0\.0\.1):\d+\/api$/i.test(url)
}

function shouldProbeLocalFallbackPorts() {
  if (typeof window === 'undefined') {
    return false
  }

  const currentHost = window.location.hostname
  return currentHost === 'localhost' || currentHost === '127.0.0.1'
}

function buildCandidateBaseURLs(baseURL) {
  const normalized = normalizeBaseURL(baseURL)
  const candidates = [normalized]

  if (isLikelyLocalBaseURL(normalized) && shouldProbeLocalFallbackPorts()) {
    const base = new URL(normalized)
    const currentPort = Number(base.port || 80)

    for (let port = 8000; port <= 8005; port += 1) {
      if (port === currentPort) {
        continue
      }

      candidates.push(`${base.protocol}//${base.hostname}:${port}/api`)
    }
  }

  return [...new Set(candidates)]
}

function isRouteNotFoundPayload(error) {
  const message = error?.response?.data?.message || ''
  return typeof message === 'string' && message.toLowerCase().includes('route') && message.toLowerCase().includes('could not be found')
}

async function hasExpectedApiRoute(baseURL, routePath) {
  try {
    const response = await probeClient.get(`${baseURL}${routePath}`, {
      validateStatus: () => true,
    })

    if ([200, 201, 204, 401, 403, 405, 422].includes(response.status)) {
      return true
    }

    if (response.status === 404) {
      return false
    }

    const message = response?.data?.message || ''
    if (typeof message === 'string' && message.toLowerCase().includes('route') && message.toLowerCase().includes('could not be found')) {
      return false
    }

    return response.status < 500
  } catch {
    return false
  }
}

async function canReachQueueApi(baseURL) {
  const [canReachPublicOffices, canReachLogin] = await Promise.all([
    hasExpectedApiRoute(baseURL, '/offices/public'),
    hasExpectedApiRoute(baseURL, '/auth/login'),
  ])

  return canReachPublicOffices && canReachLogin
}

async function resolveApiBaseURL() {
  if (resolvedBaseURL) {
    return resolvedBaseURL
  }

  if (resolvingPromise) {
    return resolvingPromise
  }

  resolvingPromise = (async () => {
    const candidates = buildCandidateBaseURLs(configuredBaseURL)

    for (const candidate of candidates) {
      if (await canReachQueueApi(candidate)) {
        resolvedBaseURL = candidate
        break
      }
    }

    // Keep probing in future requests until we find a reachable queue API.
    return resolvedBaseURL || normalizeBaseURL(configuredBaseURL)
  })()

  try {
    return await resolvingPromise
  } finally {
    resolvingPromise = null
  }
}

const api = axios.create({
  baseURL: normalizeBaseURL(configuredBaseURL),
  timeout: 10000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use(async (config) => {
  const activeBaseURL = await resolveApiBaseURL()
  config.baseURL = activeBaseURL

  const token = localStorage.getItem('qserve_token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const alreadyRetried = Boolean(error?.config?.__qserveBaseUrlRetried)

    if (isRouteNotFoundPayload(error) && !alreadyRetried) {
      resolvedBaseURL = null

      return api.request({
        ...error.config,
        __qserveBaseUrlRetried: true,
      })
    }

    if (error?.response?.status === 401) {
      localStorage.removeItem('qserve_token')
    }

    return Promise.reject(error)
  },
)

export default defineBoot(({ app }) => {
  app.config.globalProperties.$axios = axios
  app.config.globalProperties.$api = api
})

export { api }
