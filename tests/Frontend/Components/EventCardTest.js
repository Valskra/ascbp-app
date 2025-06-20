// tests/Frontend/Components/EventCardTest.js
import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import EventCard from '@/Components/EventCard.vue'

describe('EventCard Component', () => {
    let eventData

    beforeEach(() => {
        eventData = {
            id: 1,
            title: 'Tournoi de Tennis',
            description: 'Un super tournoi de tennis',
            start_date: '2024-12-25T10:00:00.000Z',
            end_date: '2024-12-25T18:00:00.000Z',
            price: '25 euros',
            max_participants: 20,
            participants_count: 5,
            category: 'Sport',
            illustration: {
                url: '/images/tennis.jpg'
            },
            address: {
                city: 'Paris',
                street_name: 'Rue du Sport'
            },
            organizer: {
                firstname: 'Jean',
                lastname: 'Dupont'
            },
            registration_status: {
                can_register: true,
                reason: null
            },
            is_registered: false,
            status: 'upcoming'
        }
    })

    it('renders event information correctly', () => {
        const wrapper = mount(EventCard, {
            props: { event: eventData }
        })

        expect(wrapper.text()).toContain('Tournoi de Tennis')
        expect(wrapper.text()).toContain('Un super tournoi de tennis')
        expect(wrapper.text()).toContain('25 euros')
        expect(wrapper.text()).toContain('Sport')
        expect(wrapper.text()).toContain('Paris')
        expect(wrapper.text()).toContain('Jean Dupont')
    })

    it('displays correct participant count', () => {
        const wrapper = mount(EventCard, {
            props: { event: eventData }
        })

        expect(wrapper.text()).toContain('5/20')
    })

    it('shows registration button when user can register', () => {
        const wrapper = mount(EventCard, {
            props: { event: eventData }
        })

        const registerButton = wrapper.find('[data-testid="register-button"]')
        await registerButton.trigger('click')

        expect(wrapper.emitted('register')).toBeTruthy()
        expect(wrapper.emitted('register')[0]).toEqual([eventData])
    })

    it('shows different styles for different event statuses', () => {
        // Test événement en cours
        eventData.status = 'ongoing'
        const ongoingWrapper = mount(EventCard, {
            props: { event: eventData }
        })
        expect(ongoingWrapper.classes()).toContain('event-ongoing')

        // Test événement passé
        eventData.status = 'past'
        const pastWrapper = mount(EventCard, {
            props: { event: eventData }
        })
        expect(pastWrapper.classes()).toContain('event-past')
    })

    it('calculates progress percentage correctly', () => {
        const wrapper = mount(EventCard, {
            props: { event: eventData }
        })

        // 5 participants sur 20 = 25%
        const progressBar = wrapper.find('[data-testid="progress-bar"]')
        expect(progressBar.attributes('style')).toContain('25%')
    })

    it('handles unlimited participants correctly', () => {
        eventData.max_participants = null

        const wrapper = mount(EventCard, {
            props: { event: eventData }
        })

        expect(wrapper.text()).toContain('5 participant')
        expect(wrapper.text()).not.toContain('/')
    })
})
expect(registerButton.exists()).toBe(true)
expect(registerButton.text()).toContain('S\'inscrire')
  })

it('shows "Inscrit" when user is already registered', () => {
    eventData.is_registered = true

    const wrapper = mount(EventCard, {
        props: { event: eventData }
    })

    const status = wrapper.find('[data-testid="registration-status"]')
    expect(status.text()).toContain('Inscrit')
})

it('shows "Complet" when event is full', () => {
    eventData.registration_status.can_register = false
    eventData.registration_status.reason = 'event_full'

    const wrapper = mount(EventCard, {
        props: { event: eventData }
    })

    const status = wrapper.find('[data-testid="registration-status"]')
    expect(status.text()).toContain('Complet')
})

it('formats date correctly', () => {
    const wrapper = mount(EventCard, {
        props: { event: eventData }
    })

    // La date devrait être formatée en français
    expect(wrapper.text()).toContain('25 décembre')
})

it('handles missing illustration gracefully', () => {
    eventData.illustration = null

    const wrapper = mount(EventCard, {
        props: { event: eventData }
    })

    const img = wrapper.find('img')
    expect(img.exists()).toBe(true)
    // Devrait avoir une image par défaut ou placeholder
})