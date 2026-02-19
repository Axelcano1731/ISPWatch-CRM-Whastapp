<script setup>
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    activeChat: {
        type: Array,
        default: () => [],
    },
    activePhone: {
        type: String,
        default: null,
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const showNewChatModal = ref(false);
const messagesContainer = ref(null);
const search = ref('');

const form = useForm({
    phone: '',
    message: '',
});

const newChatForm = useForm({
    phone: '',
    message: '',
});

// Initialize form phone
onMounted(() => {
    if (props.activePhone) {
        form.phone = props.activePhone;
        scrollToBottom();
    }
});

// Update form phone when active chat changes
watch(() => props.activePhone, (newVal) => {
    form.phone = newVal || '';
    scrollToBottom();
});

// Scroll to bottom when messages change
watch(() => props.activeChat, () => {
    scrollToBottom();
}, { deep: true });

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

const submit = () => {
    if (!form.message.trim()) return;
    
    form.post(route('messages.store'), {
        onSuccess: () => {
            form.reset('message');
            scrollToBottom();
        },
        preserveScroll: true,
    });
};

const startNewChat = () => {
    if (!newChatForm.phone || !newChatForm.message) return;

    newChatForm.post(route('messages.store'), {
        onSuccess: () => {
            newChatForm.reset();
            showNewChatModal.value = false;
        },
    });
};

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const isToday = date.toDateString() === now.toDateString();
    
    return new Intl.DateTimeFormat('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        day: isToday ? undefined : 'numeric',
        month: isToday ? undefined : 'short',
    }).format(date);
}

// Polling for new messages every 3 seconds
let pollingInterval = null;

onMounted(() => {
    pollingInterval = setInterval(() => {
        router.reload({
            only: ['conversations', 'activeChat'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 3000);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});
function formatPhone(phone) {
    if (!phone) return '';
    // If phone starts with 57 and has 12 digits (57 + 10 digits), remove 57
    if (phone.startsWith('57') && phone.length === 12) {
        return phone.substring(2);
    }
    return phone;
}
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100 overflow-hidden">
        <!-- Navbar -->
        <nav class="bg-[#00a884] shadow-sm shrink-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-14">
                    <div class="flex">
                        <div class="shrink-0 flex items-center text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h1 class="text-lg font-bold">WhatsApp CRM</h1>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <button @click="showNewChatModal = true" class="text-white hover:text-gray-200 p-2 rounded-full hover:bg-white/10 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Layout -->
        <div class="flex-1 flex overflow-hidden max-w-7xl mx-auto w-full bg-white shadow-xl my-4 rounded-lg">
            
            <!-- Sidebar (Conversations List) -->
            <div class="w-1/3 border-r border-gray-200 flex flex-col bg-white">
                <!-- Search Header -->
                <div class="p-3 bg-gray-50 border-b border-gray-100">
                    <div class="relative">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Buscar o empezar un nuevo chat" 
                            class="w-full pl-10 pr-4 py-2 border-none rounded-lg bg-white shadow-sm focus:ring-1 focus:ring-[#00a884] text-sm"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Conversation List -->
                <div class="flex-1 overflow-y-auto">
                    <template v-if="conversations.length > 0">
                        <Link 
                            v-for="conv in conversations.filter(c => c.phone.includes(search))" 
                            :key="conv.phone" 
                            :href="route('dashboard', { phone: conv.phone })"
                            class="flex items-center p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition duration-150 ease-in-out"
                            :class="{'bg-gray-100 border-l-4 border-l-[#00a884]': activePhone === conv.phone}"
                        >
                            <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold text-lg shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-4 flex-1 min-w-0">
                                <div class="flex justify-between items-baseline mb-1">
                                    <h3 class="text-sm font-medium text-gray-900 truncate">{{ formatPhone(conv.phone) }}</h3>
                                    <span class="text-xs text-gray-500">{{ formatDate(conv.last_message?.created_at) }}</span>
                                </div>
                                <p class="text-sm text-gray-500 truncate">
                                    <span v-if="conv.last_message?.status === 'sent'" class="mr-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    {{ conv.last_message?.message }}
                                </p>
                            </div>
                        </Link>
                    </template>
                    <div v-else class="p-4 text-center text-gray-500 text-sm">
                        No hay conversaciones iniciadas.
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 flex flex-col bg-[#efeae2] relative">
                <!-- Chat Background Pattern -->
                <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');"></div>

                <template v-if="activePhone">
                    <!-- Chat Header -->
                    <div class="bg-white p-3 border-b border-gray-200 flex items-center shadow-sm z-10 shrink-0">
                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center text-white shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-gray-900 font-medium">{{ formatPhone(activePhone) }}</h2>
                            <p class="text-xs text-green-600">En línea</p>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-2 z-0 relative">
                        <div v-for="msg in activeChat" :key="msg.id" class="flex" :class="{'justify-end': msg.status === 'sent'}">
                            <div 
                                class="max-w-[70%] rounded-lg px-3 py-1 shadow-sm relative text-sm"
                                :class="{
                                    'bg-[#d9fdd3] rounded-tr-none': msg.status === 'sent', 
                                    'bg-white rounded-tl-none': msg.status === 'received'
                                }"
                            >
                                <!-- Tail -->
                                <span v-if="msg.status === 'sent'" class="absolute top-0 -right-2 w-0 h-0 border-[8px] border-t-[#d9fdd3] border-r-transparent border-b-transparent border-l-[#d9fdd3]"></span>
                                <span v-else class="absolute top-0 -left-2 w-0 h-0 border-[8px] border-t-white border-l-transparent border-b-transparent border-r-white"></span>
                                
                                <p class="text-gray-900 whitespace-pre-wrap break-words pb-4">{{ msg.message }}</p>
                                <div class="absolute bottom-1 right-2 flex items-center space-x-1">
                                    <span class="text-[10px] text-gray-500">{{ new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                    <span v-if="msg.status === 'sent'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="bg-[#f0f2f5] p-3 z-10 shrink-0">
                        <form @submit.prevent="submit" class="flex items-end space-x-2">
                            <div class="flex-1 bg-white rounded-lg flex items-center border border-white focus-within:ring-1 focus-within:ring-[#00a884] shadow-sm">
                                <textarea 
                                    v-model="form.message"
                                    rows="1"
                                    placeholder="Escribe un mensaje" 
                                    class="w-full border-none focus:ring-0 rounded-lg resize-none py-3 px-4 text-sm max-h-32 bg-transparent"
                                    @keydown.enter.exact.prevent="submit"
                                ></textarea>
                            </div>
                            <button 
                                type="submit" 
                                :disabled="form.processing || !form.message"
                                class="p-3 bg-[#00a884] text-white rounded-full hover:bg-[#008f6f] disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm flex items-center justify-center h-11 w-11"
                            >
                                <svg v-if="!form.processing" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-90" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                </svg>
                                <svg v-else class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </template>
                
                <div v-else class="flex-1 flex flex-col items-center justify-center text-center p-8 bg-[#f0f2f5] border-b-[6px] border-[#4adf83]">
                    <div class="bg-white rounded-full p-4 mb-4 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="text-3xl font-light text-gray-600 mb-2">WhatsApp Web CRM</h2>
                    <p class="text-gray-500 max-w-md text-sm">Envía y recibe mensajes sin mantener tu teléfono conectado. Selecciona un chat para comenzar.</p>
                </div>
            </div>
        </div>

        <!-- New Chat Modal -->
        <div v-if="showNewChatModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showNewChatModal = false"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-50 relative">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Nuevo Mensaje
                                </h3>
                                <div class="mt-2 space-y-3">
                                    <div>
                                        <label for="new-phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                                        <input 
                                            id="new-phone" 
                                            v-model="newChatForm.phone" 
                                            type="text" 
                                            placeholder="57300..."
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border"
                                            :class="{'border-red-500': newChatForm.errors.phone}"
                                        >
                                        <p v-if="newChatForm.errors.phone" class="text-red-500 text-xs mt-1">{{ newChatForm.errors.phone }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Incluye el código de país (ej: 57 para Colombia).</p>
                                    </div>
                                    <div>
                                        <label for="new-message" class="block text-sm font-medium text-gray-700">Mensaje</label>
                                        <textarea 
                                            id="new-message" 
                                            v-model="newChatForm.message" 
                                            rows="3" 
                                            placeholder="Hola..."
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2 border"
                                            :class="{'border-red-500': newChatForm.errors.message}"
                                        ></textarea>
                                        <p v-if="newChatForm.errors.message" class="text-red-500 text-xs mt-1">{{ newChatForm.errors.message }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                            :disabled="newChatForm.processing"
                            @click="startNewChat"
                        >
                            <span v-if="newChatForm.processing">Enviando...</span>
                            <span v-else>Enviar</span>
                        </button>
                        <button 
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="showNewChatModal = false"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

