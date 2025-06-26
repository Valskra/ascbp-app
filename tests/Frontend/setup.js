// tests/Frontend/setup.js - VERSION FINALE CORRIGÉE
import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// Mock des fonctions globales Inertia
global.route = vi.fn((name, params) => {
    return `/mock-route/${name}${params ? `/${Object.values(params).join('/')}` : ''}`
})

// Mock de ziggy
vi.mock('ziggy-js', () => ({
    default: global.route,
    route: global.route,
}))

// Mock de l'objet Inertia page
global.$page = {
    props: {
        auth: {
            user: {
                id: 1,
                firstname: 'Test',
                lastname: 'User',
                email: 'test@example.com',
                is_admin: false,
                is_animator: false,
                membership_status: 1,
                membership_time_left: 30,
                first_membership_date: '2023-01-01',
                email_verified_at: '2023-01-01'
            }
        },
        flash: {},
        errors: {}
    },
    url: '/',
    component: 'TestComponent',
    version: '1'
}

// Mock des composants Inertia avec useForm corrigé
vi.mock('@inertiajs/vue3', () => ({
    router: {
        visit: vi.fn(),
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
    },
    usePage: () => ({
        props: global.$page.props,
        url: global.$page.url,
        component: global.$page.component,
        version: global.$page.version,
    }),
    useForm: (data = {}) => ({
        ...data,
        processing: false,
        errors: {},
        recentlySuccessful: false,
        reset: vi.fn(() => {
            Object.keys(data).forEach(key => {
                data[key] = ''
            })
        }),
        patch: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }),
    Head: {
        name: 'Head',
        template: '<head><slot /></head>',
    },
    Link: {
        name: 'Link',
        props: ['href', 'method', 'data', 'as'],
        template: '<a :href="href"><slot /></a>',
    },
}))

// Mock de @vueuse/core
vi.mock('@vueuse/core', () => ({
    useDateFormat: vi.fn((date, format) => ({
        value: new Date(date).toLocaleDateString('fr-FR')
    }))
}))

// Configuration globale pour Vue Test Utils
config.global.mocks = {
    $page: global.$page,
    route: global.route
}

config.global.provide = {
    route: global.route
}

// Stubs pour les composants manquants
config.global.stubs = {
    Teleport: true,
    Transition: false,
    TransitionGroup: false
}

// Mock du ResizeObserver
global.ResizeObserver = class ResizeObserver {
    constructor(callback) {
        this.callback = callback
    }
    observe() { }
    unobserve() { }
    disconnect() { }
}

// Mock de l'API Clipboard
Object.assign(navigator, {
    clipboard: {
        writeText: vi.fn(() => Promise.resolve()),
        readText: vi.fn(() => Promise.resolve(''))
    }
})

// Mock du fetch global
global.fetch = vi.fn(() =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({}),
        text: () => Promise.resolve(''),
    })
)

// Mock de requestAnimationFrame
global.requestAnimationFrame = vi.fn((cb) => setTimeout(cb, 16))
global.cancelAnimationFrame = vi.fn()

// Mock de window.Stripe pour les tests de paiement
global.Stripe = vi.fn(() => ({
    createToken: vi.fn(() => Promise.resolve({
        token: { id: 'tok_test_123' },
        error: null
    }))
}))

// Mock de l'objet window pour les tests
Object.defineProperty(window, 'location', {
    value: {
        href: 'http://localhost:3000',
        reload: vi.fn()
    }
})

// Mock pour les événements DOM
Object.defineProperty(window, 'addEventListener', {
    value: vi.fn()
})

Object.defineProperty(window, 'removeEventListener', {
    value: vi.fn()
})

// Configuration des erreurs de console
const originalError = console.error
console.error = (...args) => {
    if (
        args[0]?.includes?.('Vue received a Component') ||
        args[0]?.includes?.('[Vue warn]') ||
        args[0]?.includes?.('Unknown custom element')
    ) {
        return
    }
    originalError.call(console, ...args)
}

// Mock pour les métadonnées CSRF
Object.defineProperty(document, 'querySelector', {
    value: vi.fn((selector) => {
        if (selector === 'meta[name="csrf-token"]') {
            return {
                content: 'mock-csrf-token'
            }
        }
        return null
    })
})

// Reset de l'état global avant chaque test
beforeEach(() => {
    // Reset des mocks
    vi.clearAllMocks()

    // Reset de l'utilisateur par défaut
    global.$page.props.auth.user = {
        id: 1,
        firstname: 'Test',
        lastname: 'User',
        email: 'test@example.com',
        is_admin: false,
        is_animator: false,
        membership_status: 1,
        membership_time_left: 30,
        first_membership_date: '2023-01-01',
        email_verified_at: '2023-01-01'
    }

    // Reset des props flash
    global.$page.props.flash = {}
    global.$page.props.errors = {}

    // Reset du body style
    document.body.style.overflow = ''
})