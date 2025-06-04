<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    notification: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['mark-as-read', 'dismiss']);

const notificationIcon = computed(() => {
    switch (props.notification.type) {
        case 'event_updated':
            return {
                icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                bgColor: 'bg-blue-100 dark:bg-blue-900',
                iconColor: 'text-blue-600 dark:text-blue-400'
            };
        case 'document_expiring':
            return {
                icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                bgColor: 'bg-orange-100 dark:bg-orange-900',
                iconColor: 'text-orange-600 dark:text-orange-400'
            };
        case 'membership_expiring':
            return {
                icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                bgColor: 'bg-purple-100 dark:bg-purple-900',
                iconColor: 'text-purple-600 dark:text-purple-400'
            };
        case 'event_reminder':
            return {
                icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                bgColor: 'bg-green-100 dark:bg-green-900',
                iconColor: 'text-green-600 dark:text-green-400'
            };
        default:
            return {
                icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                bgColor: 'bg-gray-100 dark:bg-gray-900',
                iconColor: 'text-gray-600 dark:text-gray-400'
            };
    }
});

const timeAgo = computed(() => {
    const now = new Date();
    const notificationDate = new Date(props.notification.created_at);
    const diffMs = now - notificationDate;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) {
        return `Il y a ${diffMins}min`;
    } else if (diffHours < 24) {
        return `Il y a ${diffHours}h`;
    } else {
        return `Il y a ${diffDays}j`;
    }
});

function handleClick() {
    if (!props.notification.read_at) {
        emit('mark-as-read', props.notification.id);
    }
}

function handleDismiss(e) {
    e.stopPropagation();
    emit('dismiss', props.notification.id);
}
</script>

<template>
    <div @click="handleClick"
        class="group relative flex items-start space-x-3 p-4 border rounded-lg cursor-pointer transition-all hover:shadow-sm"
        :class="notification.read_at
            ? 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700'
            : 'bg-white dark:bg-gray-900 border-blue-200 dark:border-blue-800 shadow-sm'">

        <!-- Icône -->
        <div class="flex-shrink-0">
            <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="notificationIcon.bgColor">
                <svg class="w-5 h-5" :class="notificationIcon.iconColor" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="notificationIcon.icon" />
                </svg>
            </div>
        </div>

        <!-- Contenu -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium"
                        :class="notification.read_at ? 'text-gray-700 dark:text-gray-300' : 'text-gray-900 dark:text-white'">
                        {{ notification.title }}
                    </p>
                    <p class="text-sm mt-1"
                        :class="notification.read_at ? 'text-gray-500 dark:text-gray-400' : 'text-gray-600 dark:text-gray-300'">
                        {{ notification.message }}
                    </p>
                    <p class="text-xs mt-2 text-gray-400 dark:text-gray-500">
                        {{ timeAgo }}
                    </p>
                </div>

                <!-- Bouton de suppression -->
                <button @click="handleDismiss"
                    class="opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Indicateur non lu -->
        <div v-if="!notification.read_at" class="absolute top-4 right-4 w-2 h-2 bg-blue-600 rounded-full"></div>
    </div>
</template>