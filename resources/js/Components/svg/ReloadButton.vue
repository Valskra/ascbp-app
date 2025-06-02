<script setup>
import { computed } from 'vue'

const props = defineProps({
    loading: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'success', 'warning', 'danger'].includes(value)
    }
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => {
    const baseClasses = 'inline-flex items-center justify-center rounded-lg font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2'

    // Tailles
    const sizeClasses = {
        sm: 'px-3 py-1.5 text-xs gap-1.5',
        md: 'px-4 py-2 text-sm gap-2',
        lg: 'px-6 py-3 text-base gap-2.5'
    }

    // Variantes de couleur
    const variantClasses = {
        primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 disabled:bg-blue-300',
        secondary: 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500 disabled:bg-gray-300',
        success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 disabled:bg-green-300',
        warning: 'bg-orange-600 text-white hover:bg-orange-700 focus:ring-orange-500 disabled:bg-orange-300',
        danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 disabled:bg-red-300'
    }

    // Classes d'état
    const stateClasses = props.disabled || props.loading ? 'cursor-not-allowed opacity-75' : 'cursor-pointer'

    return [
        baseClasses,
        sizeClasses[props.size],
        variantClasses[props.variant],
        stateClasses
    ].join(' ')
})

const iconSize = computed(() => {
    const sizes = {
        sm: 'h-3 w-3',
        md: 'h-4 w-4',
        lg: 'h-5 w-5'
    }
    return sizes[props.size]
})

const handleClick = () => {
    if (!props.disabled && !props.loading) {
        emit('click')
    }
}
</script>

<template>
    <button :class="buttonClasses" :disabled="disabled || loading" @click="handleClick">
        <!-- Icône de chargement (spinning) -->
        <svg v-if="loading" :class="[iconSize, 'animate-spin-reverse']" viewBox="0 0 24 24" fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <title>Chargement...</title>
            <path
                d="M4,13 C4,17.4183 7.58172,21 12,21 C16.4183,21 20,17.4183 20,13 C20,8.58172 16.4183,5 12,5 C10.4407,5 8.98566,5.44609 7.75543,6.21762"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <path d="M9.2384,1.89795 L7.49856,5.83917 C7.27552,6.34441 7.50429,6.9348 8.00954,7.15784 L11.9508,8.89768"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>

        <!-- Icône normale -->
        <svg v-else :class="iconSize" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <title>Recharger</title>
            <path
                d="M4,13 C4,17.4183 7.58172,21 12,21 C16.4183,21 20,17.4183 20,13 C20,8.58172 16.4183,5 12,5 C10.4407,5 8.98566,5.44609 7.75543,6.21762"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <path d="M9.2384,1.89795 L7.49856,5.83917 C7.27552,6.34441 7.50429,6.9348 8.00954,7.15784 L11.9508,8.89768"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
    </button>
</template>

<style scoped>
/* Effet hover amélioré */
button:hover:not(:disabled) svg {
    transform: scale(1.05);
    transition: transform 0.2s ease-in-out;
}

/* États focus pour l'accessibilité */
button:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}
</style>