<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    user: {
        type: Object
    },
    labelWidth: {
        type: String,
        default: '30%'
    },
});

const form = useForm({

    address: `${props.user.home_address?.house_number ?? ''} ${props.user.home_address?.street_name ?? ''}`,
    city: props.user.home_address?.city ?? '',
    postal_code: props.user.home_address?.postal_code ?? '',
    country: props.user.home_address?.country ?? '',
});

</script>

<style scoped>
.label {
    width: v-bind(labelWidth);
}

.value {
    width: calc(100% - v-bind(labelWidth));
}
</style>


<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Modifier l'adresse de domicile
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Mettez à jour votre adresse principale.
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.updateAddress'))" class="mt-6 space-y-3">

            <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
                <table class="w-full border-collapse">
                    <tbody>
                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <td
                                class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                                Adresse
                            </td>
                            <td class="text-gray-700 dark:text-gray-300 value">
                                <input id="address" type="text" v-model="form.address"
                                    class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                            </td>
                            <InputError class="mt-2" :message="form.errors.address" />
                        </tr>

                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <td
                                class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                                Ville
                            </td>
                            <td class="text-gray-700 dark:text-gray-300 value">
                                <input id="city" type="text" v-model="form.city"
                                    class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                            </td>
                            <InputError class="mt-2" :message="form.errors.city" />
                        </tr>
                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <td
                                class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                                Code Postal
                            </td>
                            <td class="text-gray-700 dark:text-gray-300 value">
                                <input id="postal_code" type="text" v-model="form.postal_code"
                                    class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                            </td>
                            <InputError class="mt-2" :message="form.errors.postal_code" />
                        </tr>
                        <tr class="border-b border-gray-300 dark:border-gray-600">
                            <td
                                class="px-4 py-3 font-semibold bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white label">
                                Pays
                            </td>
                            <td class="text-gray-700 dark:text-gray-300 value">
                                <input id="country" type="text" v-model="form.country"
                                    class="w-full border-0 bg-transparent py-2 px-3 text-gray-700 dark:text-gray-200 focus:ring-0" />
                            </td>
                            <InputError class="mt-2" :message="form.errors.country" />
                        </tr>
                    </tbody>
                </table>
            </div>


            <div class="flex justify-end">
                <PrimaryButton @click="form.patch(route('profile.updateAddress'))" :disabled="form.processing"
                    v-if="!form.recentlySuccessful">
                    Enregistrer
                </PrimaryButton>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="ml-4 text-sm text-gray-600 dark:text-gray-400">
                        Enregistré.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
