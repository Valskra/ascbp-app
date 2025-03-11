<script>
export default {
    props: {
        title: {
            type: String,
            required: true
        },
        labels: {
            type: Array,
            required: true
        },
        data: {
            type: Array,
            required: true
        },
        labelWidth: {
            type: String,
            default: '30%'
        },
        defaultContent: {
            type: String,
            default: '-'
        }
    }
};
</script>
<template>
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ title }}</h3>
    <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
        <table class="w-full border-collapse">
            <tbody>
                <tr v-for="(label, index) in labels" :key="index" class="border-b border-gray-300 dark:border-gray-600">
                    <td
                        class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                        {{ label }}
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 value">
                        {{ data[index] || defaultContent }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>


<style scoped>
.label {
    width: v-bind(labelWidth);
}

.value {
    width: calc(100% - v-bind(labelWidth));
}
</style>
