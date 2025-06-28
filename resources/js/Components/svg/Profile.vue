<script setup>
import { computed } from 'vue'

const props = defineProps({
    userId: {
        type: [Number, String],
        default: 1
    },
    size: {
        type: String,
        default: '150',
        validator: (value) => !isNaN(parseFloat(value))
    }
})

// Générateur de couleur basé sur un algorithme complexe
const backgroundColor = computed(() => {
    const id = typeof props.userId === 'string' ? hashString(props.userId) : Number(props.userId)

    // Utilisation d'un algorithme de mélange complexe pour générer une couleur unique
    const seed = Math.abs(id)

    // Application de plusieurs transformations mathématiques
    const phase1 = (seed * 2654435761) % 4294967296  // Multiplication par un grand nombre premier
    const phase2 = ((phase1 ^ (phase1 >> 16)) * 0x85ebca6b) % 4294967296
    const phase3 = ((phase2 ^ (phase2 >> 13)) * 0xc2b2ae35) % 4294967296
    const finalHash = (phase3 ^ (phase3 >> 16)) >>> 0

    // Extraction des composantes RGB avec distribution équilibrée
    const r = Math.floor((finalHash & 0xFF0000) >> 16)
    const g = Math.floor((finalHash & 0x00FF00) >> 8)
    const b = Math.floor(finalHash & 0x0000FF)

    // Ajustement pour éviter les couleurs trop sombres ou trop claires
    const adjustedR = Math.max(60, Math.min(220, r))
    const adjustedG = Math.max(60, Math.min(220, g))
    const adjustedB = Math.max(60, Math.min(220, b))

    // Application d'une saturation supplémentaire pour des couleurs plus vives
    const saturationBoost = 1.2
    const finalR = Math.min(255, Math.floor(adjustedR * saturationBoost))
    const finalG = Math.min(255, Math.floor(adjustedG * saturationBoost))
    const finalB = Math.min(255, Math.floor(adjustedB * saturationBoost))

    return `rgb(${finalR}, ${finalG}, ${finalB})`
})

// Fonction de hachage pour les chaînes de caractères
const hashString = (str) => {
    let hash = 0
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i)
        hash = ((hash << 5) - hash) + char
        hash = hash & hash // Conversion en 32bit
    }
    return Math.abs(hash)
}

// Calcul de la couleur du texte basée sur la luminosité du fond
const textColor = computed(() => {
    const rgb = backgroundColor.value.match(/\d+/g)
    if (!rgb) return '#fff'

    const [r, g, b] = rgb.map(Number)
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255

    return luminance > 0.6 ? '#000' : '#fff'
})

const viewBox = computed(() => `0 0 ${props.size} ${props.size}`)
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" :viewBox="viewBox" :width="size" :height="size">
        <g id="Calque_2" data-name="Calque 2">
            <rect :width="size" :height="size" :style="{ fill: backgroundColor }" />
        </g>
        <g id="Calque_1" data-name="Calque 1">
            <g id="Iconly_Curved_Profile" data-name="Iconly/Curved/Profile">
                <g id="Profile">
                    <path id="Stroke_1" data-name="Stroke 1"
                        d="M74.9997,127.40369c-21.9861,0-40.76198-3.4214-40.76198-17.12366s18.65677-26.35102,40.76198-26.35102c21.98687,0,40.76258,12.52666,40.76258,26.22893,0,13.6963-18.65659,17.24575-40.76258,17.24575Z"
                        :style="{ fill: 'none', stroke: textColor, strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '7px' }" />
                    <path id="Stroke_3" data-name="Stroke 3"
                        d="M74.96062,70.47292c12.98124,0,23.50212-10.52087,23.50212-23.50169s-10.52087-23.50683-23.50212-23.50683-23.50651,10.52596-23.50651,23.50683c-.04383,12.93699,10.40418,23.45828,23.34095,23.50169h.16556Z"
                        :style="{ fill: 'none', stroke: textColor, strokeLinecap: 'round', strokeLinejoin: 'round', strokeWidth: '7px' }" />
                </g>
            </g>
        </g>
    </svg>
</template>