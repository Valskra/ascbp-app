// tests/Frontend/setup.js
import { vi } from 'vitest'

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
            user: null
        },
        flash: {},
        errors: {}
    },
    url: '/',
    component: 'TestComponent',
    version: '1'
}

// Mock des composants Inertia
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