// composables/useMasonry.js
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

export function useMasonry(containerRef, itemSelector = '.masonry-item', options = {}) {
    const {
        columnWidth = 320,
        gutter = 24,
        fitWidth = true
    } = options

    const columns = ref([])
    const columnCount = ref(0)
    const isLayouting = ref(false)

    const calculateColumns = () => {
        if (!containerRef.value) return

        const containerWidth = containerRef.value.offsetWidth
        const availableWidth = containerWidth - gutter
        const columnsNeeded = Math.floor(availableWidth / (columnWidth + gutter)) || 1

        columnCount.value = columnsNeeded

        // Initialiser les colonnes
        columns.value = Array.from({ length: columnsNeeded }, () => ({
            height: 0,
            items: []
        }))
    }

    const getShortestColumn = () => {
        return columns.value.reduce((shortest, column, index) => {
            return column.height < columns.value[shortest].height ? index : shortest
        }, 0)
    }

    const positionItems = async () => {
        if (!containerRef.value || isLayouting.value) return

        isLayouting.value = true

        const items = containerRef.value.querySelectorAll(itemSelector)

        // Reset columns
        columns.value.forEach(column => {
            column.height = 0
            column.items = []
        })

        // Position each item
        items.forEach((item, index) => {
            const shortestColumnIndex = getShortestColumn()
            const column = columns.value[shortestColumnIndex]

            // Position de l'item
            const x = shortestColumnIndex * (columnWidth + gutter)
            const y = column.height

            // Appliquer la position
            item.style.position = 'absolute'
            item.style.left = `${x}px`
            item.style.top = `${y}px`
            item.style.width = `${columnWidth}px`
            item.style.transition = 'all 0.3s ease'

            // Mettre à jour la hauteur de la colonne
            // On attend que l'item soit rendu pour obtenir sa vraie hauteur
            requestAnimationFrame(() => {
                const itemHeight = item.offsetHeight
                column.height += itemHeight + gutter
                column.items.push({ element: item, height: itemHeight })

                // Ajuster la hauteur du conteneur
                if (index === items.length - 1) {
                    const maxHeight = Math.max(...columns.value.map(col => col.height))
                    containerRef.value.style.height = `${maxHeight}px`
                    containerRef.value.style.position = 'relative'
                }
            })
        })

        isLayouting.value = false
    }

    const layout = async () => {
        calculateColumns()
        await nextTick()
        await positionItems()
    }

    const resizeObserver = new ResizeObserver(() => {
        layout()
    })

    onMounted(() => {
        if (containerRef.value) {
            resizeObserver.observe(containerRef.value)
            layout()
        }
    })

    onUnmounted(() => {
        resizeObserver.disconnect()
    })

    return {
        layout,
        columnCount,
        isLayouting
    }
}