<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    messages: {
        type: Array,
        default: () => [],
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    phone: '',
    message: '',
});

const submit = () => {
    form.post(route('messages.store'), {
        onSuccess: () => form.reset('message'),
    });
};

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-ES', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(date);
}
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <h1 class="text-xl font-bold text-gray-800">WhatsApp CRM</h1>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Success Message -->
                <div v-if="flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ flash.success }}</span>
                </div>
                <div v-if="$page.props.errors.message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ $page.props.errors.message }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Send Message Form -->
                    <div class="md:col-span-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Enviar Mensaje</h2>
                            <form @submit.prevent="submit" class="space-y-4">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                                    <input 
                                        id="phone" 
                                        v-model="form.phone" 
                                        type="text" 
                                        placeholder="573001234567"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border"
                                        required
                                    />
                                    <p class="text-xs text-gray-500 mt-1">Incluye el código de país.</p>
                                </div>

                                <div>
                                    <label for="message" class="block text-sm font-medium text-gray-700">Mensaje</label>
                                    <textarea 
                                        id="message" 
                                        v-model="form.message" 
                                        rows="4" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border"
                                        required
                                    ></textarea>
                                </div>

                                <div>
                                    <button 
                                        type="submit" 
                                        :disabled="form.processing"
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                    >
                                        {{ form.processing ? 'Enviando...' : 'Enviar' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Message History -->
                    <div class="md:col-span-2">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 bg-white border-b border-gray-200">
                                <h2 class="text-lg font-medium text-gray-900 mb-4">Historial de Mensajes</h2>
                                
                                <div v-if="messages.length === 0" class="text-center text-gray-500 py-8">
                                    No hay mensajes enviados aún.
                                </div>

                                <div v-else class="flow-root">
                                    <ul role="list" class="-my-5 divide-y divide-gray-200">
                                        <li v-for="msg in messages" :key="msg.id" class="py-4">
                                            <div class="flex items-center space-x-4">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate">
                                                        Para: {{ msg.phone }}
                                                    </p>
                                                    <p class="text-sm text-gray-500 truncate">
                                                        {{ msg.message }}
                                                    </p>
                                                </div>
                                                <div class="inline-flex items-center text-base font-semibold text-gray-900">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        {{ msg.status }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ formatDate(msg.created_at) }}
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
