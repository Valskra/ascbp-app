<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Contacts d'urgence
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Ajoutez jusqu'à 2 contacts d'urgence pour vous assister en cas de besoin.
            </p>
        </header>

        <form @submit.prevent="saveContacts" class="mt-6 space-y-6">
            <div v-for="(contact, index) in form.contacts" :key="index"
                class="border p-4 rounded-lg dark:border-gray-700">
                <div class="flex justify-between">
                    <h4 class="text-lg font-semibold capitalize">
                        {{ contact.firstname || 'Nouveau contact' }} {{ contact.lastname || '' }}
                    </h4>
                    <button type="button" @click="removeContact(index)" class="text-red-500 hover:text-red-700"
                        v-if="form.contacts.length > 1">🗑️</button>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-2">
                    <div>
                        <InputLabel :for="'contact-firstname-' + index" value="Prénom" />
                        <TextInput :id="'contact-firstname-' + index" type="text" class="mt-1 block w-full"
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
                            v-model="contact.email" required />
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
