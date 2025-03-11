<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    labels: {
        type: Array,
        required: true,
    },
    fields: {
        type: Array,
        required: true,
    },
    data: {
        type: Array,
        required: true,
    },
    labelWidth: {
        type: String,
        default: '30%',
    },
    routeName: {
        type: String,
        default: "profile.update",
    },
});

const form = useForm(
    Object.fromEntries(props.labels.map((label, index) => [props.fields[index], props.data[index] || '']))
);

const submit = () => {
    form.patch(route(props.routeName));
};
</script>

<template>
    <section>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ title }}</h3>
        <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
            <table class="w-full border-collapse">
                <tbody>
                    <tr v-for="(label, index) in labels" :key="index"
                        class="border-b border-gray-300 dark:border-gray-600">
                        <td
                            class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                            {{ label }}
                        </td>
                        <td class="text-gray-700 dark:text-gray-300 value">
                            <input type="text" v-model="form[label]" :placeholder="label"
                                class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end">
            <PrimaryButton @click="form.patch(route(routeName))" :disabled="form.processing">
                Enregistrer
            </PrimaryButton>
            <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                <p v-if="form.recentlySuccessful" class="ml-4 text-sm text-gray-600 dark:text-gray-400">
                    Enregistré.
                </p>
            </Transition>
        </div>
    </section>
</template>

<style scoped>
.label {
    width: v-bind(labelWidth);
}

.value {
    width: calc(100% - v-bind(labelWidth));
}
</style>
