<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    contacts: { type: Array, required: true }
});

const form = useForm({
    contacts: props.contacts.length ? [...props.contacts] : [{ firstname: '', lastname: '', phone: '', email: '', relation: '', priority: 1 }]
});

const addContact = () => {
    if (form.contacts.length < 2) {
        form.contacts.push({ firstname: '', lastname: '', phone: '', email: '', relation: '', priority: 1 });
    }
};

const removeContact = (index) => {
    form.contacts.splice(index, 1);
};

const saveContacts = () => {
    form.patch(route('profile.updateContacts'));
};
</script>


<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Contacts d'urgence
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Ajoutez jusqu'à 2 contacts d'urgence à contacter en cas de besoin.
            </p>
        </header>

        <form @submit.prevent="saveContacts" class="mt-6 space-y-6">
            <div v-for="(contact, index) in form.contacts" :key="index"
                class="border p-4 rounded-lg dark:border-gray-500 bg-gray-100 dark:bg-gray-800">
                <div class="flex justify-between ">
                    <h4 class="text-lg font-semibold capitalize text-gray-900 dark:text-gray-100">
                        {{ contact.firstname || 'Nouveau contact' }} {{ contact.lastname || '' }} </h4>
                    <button type="button" @click="removeContact(index)" class="text-red-500 hover:text-red-700"
                        v-if="form.contacts.length > 1">
                        <svg class="w-5 stroke-black dark:stroke-white hover:stroke-red-500 " viewBox="0 0 24 24"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6M18 6V16.2C18 17.8802 18 18.7202 17.673 19.362C17.3854 19.9265 16.9265 20.3854 16.362 20.673C15.7202 21 14.8802 21 13.2 21H10.8C9.11984 21 8.27976 21 7.63803 20.673C7.07354 20.3854 6.6146 19.9265 6.32698 19.362C6 18.7202 6 17.8802 6 16.2V6M14 10V17M10 10V17"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-2">
                    <div>
                        <InputLabel :for="'contact-firstname-' + index" value="Prénom" />
                        <TextInput :id="'contact-firstname-' + index" type="text" class="mt-1 block w-full "
                            v-model="contact.firstname" required />
                    </div>
                    <div>
                        <InputLabel :for="'contact-lastname-' + index" value="Nom" />
                        <TextInput :id="'contact-lastname-' + index" type="text" class="mt-1 block w-full"
                            v-model="contact.lastname" required />
                    </div>
                    <div>
                        <InputLabel :for="'contact-phone-' + index" value="Téléphone" />
                        <TextInput :id="'contact-phone-' + index" type="text" class="mt-1 block w-full"
                            v-model="contact.phone" required />
                    </div>
                    <div>
                        <InputLabel :for="'contact-email-' + index" value="Email" />
                        <TextInput :id="'contact-email-' + index" type="email" class="mt-1 block w-full"
                            v-model="contact.email" />
                    </div>
                    <div>
                        <InputLabel :for="'contact-relation-' + index" value="Relation" />
                        <TextInput :id="'contact-relation-' + index" type="text" class="mt-1 block w-full"
                            v-model="contact.relation" required />
                    </div>
                    <div>
                        <InputLabel :for="'contact-priority-' + index" value="Priorité" />
                        <TextInput :id="'contact-priority-' + index" type="number" min="1" max="5"
                            class="mt-1 block w-full" v-model="contact.priority" required />
                    </div>
                </div>
            </div>

            <button type="button" @click="addContact"
                class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700"
                v-if="form.contacts.length < 2">
                + Ajouter un contact
            </button>

            <div class="flex items-center gap-4 mt-4">
                <PrimaryButton :disabled="form.processing">Enregistrer</PrimaryButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">
                        Enregistré.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
