<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('chat')"/>

        <div class="chat-wrapper">
            <div class="chat-contacts">
                <div class="search-contact">
                    <div class="search-input-group">
                        <app-icon name="search" class="size-17 search-icon"/>
                        <input type="text"
                               v-model="searchContact"
                               class="form-control search-contact-input"
                               placeholder="Search contact">
                    </div>
                    <button class="btn btn-primary btn-sm ml-2" @click="openGroupModal" title="Crear Grupo">
                        <app-icon name="plus" class="size-15"/>
                    </button>
                </div>

                <div class="contact-list custom-scrollbar">
                    
                    <div class="contact-category-header" @click="isGroupsOpen = !isGroupsOpen">
                        <span class="text-muted font-weight-bold text-uppercase text-size-12">
                            Grupos ({{ filteredGroups.length }})
                        </span>
                        <app-icon :name="isGroupsOpen ? 'chevron-down' : 'chevron-right'" class="size-14 text-muted"/>
                    </div>

                    <div v-show="isGroupsOpen">
                        <template v-for="contact in filteredGroups">
                            <a class="contact"
                               :class="{'active': userInfo.id === contact.id}"
                               @click.prevent="changeActive(contact)">
                                <div class="contact-icon">
                                    <div class="chat-avatar-group">
                                        <div class="avatars-group-container d-flex">
                                            <template v-for="(avatar, i) in findUser(contact.groupMembers)">
                                                <app-avatar
                                                    v-if="i < 2"
                                                    :key="`group-avatar-${avatar.id}`"
                                                    :img="avatar.profile_picture ? urlGenerator(avatar.profile_picture.path) : avatar.profile_picture"
                                                    class="mr-1"
                                                    style="width: 25px; height: 25px;"
                                                /> 
                                            </template>
                                            <span v-if="contact.groupMembers.length > 2" class="badge badge-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 25px; height: 25px;">
                                                +{{ contact.groupMembers.length - 2 }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="contact-info">
                                    <p class="mb-0">{{ contact.full_name }}</p>
                                </div>
                            </a>
                        </template>
                        <div v-if="filteredGroups.length === 0" class="p-2 text-center text-muted text-size-12">
                            No hay grupos
                        </div>
                    </div>

                    <div class="contact-category-header mt-2" @click="isDirectOpen = !isDirectOpen">
                        <span class="text-muted font-weight-bold text-uppercase text-size-12">
                            Mensajes Directos ({{ filteredUsers.length }})
                        </span>
                        <app-icon :name="isDirectOpen ? 'chevron-down' : 'chevron-right'" class="size-14 text-muted"/>
                    </div>

                    <div v-show="isDirectOpen">
                        <template v-for="contact in filteredUsers">
                            <a class="contact"
                               :class="{'active': userInfo.id === contact.id}"
                               @click.prevent="changeActive(contact)">
                                <div class="contact-icon">
                                    <div class="avatars-w-40">
                                        <app-avatar :alt-text="contact.full_name"
                                                    :img="contact.profile_picture ?
                                                        urlGenerator(contact.profile_picture.path) :
                                                        contact.profile_picture"
                                                    :shadow="true"
                                                    :title="contact.full_name"/>
                                    </div>
                                </div>
                                <div class="contact-info">
                                    <p class="mb-0">{{ contact.full_name }}</p>
                                </div>
                            </a>
                        </template>
                         <div v-if="filteredUsers.length === 0" class="p-2 text-center text-muted text-size-12">
                            No hay usuarios
                        </div>
                    </div>

                </div>
            </div>

            <div class="chat-messages">
                <div class="h-100 d-flex flex-column" v-if="userInfo && userInfo.id">
                    
                    <div class="message-header">
                        <div class="contact-title">
                            <h4 class="mb-0">{{ userInfo.full_name }}</h4>
                        </div>
                        <div class="contact-user">
                            <div v-if="userInfo.type === 'group'" class="d-flex align-items-center">
                                    <template v-for="(avatar, i) in findUser(userInfo.groupMembers)">
                                    <app-avatar v-if="i < 3"
                                                :key="`header-group-avatar-${avatar.id}`"
                                                :img="avatar.profile_picture ? urlGenerator(avatar.profile_picture.path) : avatar.profile_picture"
                                                class="ml-n2 border border-white rounded-circle"
                                                :title="avatar.full_name"/> 
                                </template>
                            </div>
                            <div class="avatars-w-40" v-else>
                                <app-avatar :alt-text="userInfo.full_name"
                                            :img="userInfo.profile_picture ? urlGenerator(userInfo.profile_picture.path) : userInfo.profile_picture"
                                            :shadow="true"
                                            :title="userInfo.full_name"/>
                            </div>
                        </div>
                    </div>

                    <div class="message-body" v-chat-scroll>
                        <div v-if="loadingMessages" class="d-flex h-100 justify-content-center align-items-center flex-column">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>

                        <template v-else>
                            <div v-if="userMessageLists.length === 0" class="d-flex h-100 justify-content-center align-items-center">
                                <p class="text-muted">No hay mensajes aún.</p>
                            </div>
                            <template v-for="userMessage in userMessageLists">
                                <div class="message" :class="{'reply-message': userInfo.id === userMessage.user.id}"> 
                                    <div class="avatars-w-40" v-if="userMessage.user">
                                        <app-avatar :alt-text="userMessage.user.full_name"
                                                    :img="userMessage.user.profile_picture ? urlGenerator(userMessage.user.profile_picture.path) : userMessage.user.profile_picture"
                                                    :shadow="true"
                                                    :title="userMessage.user.full_name"/>
                                    </div>
                                    <template v-if="userMessage.attachments.length">
                                        <img class="chat-message-image img-thumbnail"
                                                v-for="attachment in userMessage.attachments"
                                                :src="`${urlGenerator(attachment.path)}`" alt="Not found"/>
                                    </template>
                                    <div class="text" v-if="userMessage.message">
                                        <span v-html="userMessage.message"></span>
                                    </div>
                                    <div class="chat-date">
                                        <span class="text-muted">{{ moment(userMessage.created_at).format(momentFormattingString) }}</span>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>

                    <div class="message-editor">
                        <div class="attached-file-options">
                            <input type="file" class="image-input cursor-pointer" @change="sendImage($event)" :id="`chat-image-upload-${userInfo.id}`"/>
                            <span class="option p-1 text-primary"><app-icon name="paperclip" class="size-18"/></span>
                        </div>
                        <div class="message-input">
                            <div class="message-input-group">
                                <input type="text" class="form-control" v-model="messageText" data-emojiable="true" @keyup.enter="sendMessage()" placeholder="Type something here...">
                                <div class="message-input-append">
                                    <div class="btn-group dropdown-emoji">
                                        <button type="button" class="btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <app-icon name="smile" class="size-15"/>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="#" v-for="emoji in emojiList" class="emoji" @click.prevent="selectEmoji($event, emoji)" v-html="emoji.code"></a>
                                        </div>
                                    </div>
                                    <a href="#" :class="{'disabled': !messageText, 'text-primary': messageText}" class="btn btn-send" @click.prevent="sendMessage()">
                                        <app-icon name="send" class="size-15"/>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="h-100 d-flex justify-content-center align-items-center">
                    <p class="text-muted">Selecciona un chat para comenzar</p>
                </div>
            </div>
            
            <div class="chat-contact-details" v-if="userInfo && userInfo.id">
                <div class="d-flex flex-column align-items-center">
                    <div v-if="userInfo.type === 'group'" class="chat-avatar-group mb-2">
                            <div class="d-flex justify-content-center">
                                <template v-for="(avatar, i) in findUser(userInfo.groupMembers)">
                                    <app-avatar v-if="i < 4" :key="`detail-group-avatar-${avatar.id}`"
                                            :img="avatar.profile_picture ? urlGenerator(avatar.profile_picture.path) : avatar.profile_picture"
                                            class="mr-1" :title="avatar.full_name"/> 
                                </template>
                            </div>
                    </div>
                    <div class="avatars-w-40" v-else>
                        <app-avatar :alt-text="userInfo.full_name"
                                    :img="userInfo.profile_picture ? urlGenerator(userInfo.profile_picture.path) : userInfo.profile_picture"
                                    :shadow="true" :title="userInfo.full_name"/>
                    </div>
                    <p class="mt-2 mb-0 font-weight-bold">{{ userInfo.full_name }}</p>
                    <small v-if="userInfo.type === 'group'" class="text-muted">{{ userInfo.groupMembers.length }} Miembros</small>
                </div>
            </div>
        </div>

        <div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Nuevo Grupo</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre del Grupo</label>
                            <input type="text" class="form-control" v-model="newGroup.name" placeholder="Ej: Equipo de Ventas">
                        </div>
                        <div class="form-group">
                            <label>Seleccionar Miembros</label>
                            <div class="users-list-scroll" style="max-height: 200px; overflow-y: auto;">
                                <div v-for="user in contactList" :key="`select-user-${user.id}`" v-if="user.type !== 'group'" class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" :id="`user-check-${user.id}`" :value="user.id" v-model="newGroup.members">
                                    <label class="custom-control-label d-flex align-items-center" :for="`user-check-${user.id}`"><span class="mr-2">{{ user.full_name }}</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" @click="createGroup" :disabled="!newGroup.name || newGroup.members.length === 0">Crear Grupo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import {urlGenerator} from "../../../../../Helpers/AxiosHelper";
import {FormMixin} from "../../../../../../core/mixins/form/FormMixin";
import moment from "moment";

export default {
    name: 'Chat',
    mixins: [FormMixin],
    data() {
        return {
            momentFormattingString: 'hh:mm A DD-MM-YY',
            searchContact: '',
            moment,
            messageText: '',
            activeEmoji: '&#128077;',
            contactList: [],
            userMessageLists: [],
            loadingMessages: false,
            userInfo: {}, // OBJETO ACTIVO
            
            // NUEVOS DATOS PARA COLAPSAR
            isGroupsOpen: true,
            isDirectOpen: true,

            file_upload: '',
            fileUploadUrl: '',
            urlGenerator,
            newGroup: { name: '', members: [] },
            emojiList: [
                {id: 1, code: '&#9994;'}, {id: 2, code: '&#9995;'}, {id: 3, code: '&#9996;'}, {id: 4, code: '&#128074;'},
                {id: 5, code: '&#128076;'}, {id: 6, code: '&#128077;'}, {id: 7, code: '&#128078;'}, {id: 8, code: '&#128079;'},
                {id: 9, code: '&#128148;'}, {id: 10, code: '&#128149;'}, {id: 11, code: '&#128150;'}, {id: 12, code: '&#128153;'},
            ],
        }
    },
    computed: {
        // Filtro general
        allVisibleContacts() {
            return this.contactList.filter(item => 
                item.full_name.toLowerCase().includes(this.searchContact.toLowerCase())
            );
        },
        // Filtro solo grupos
        filteredGroups() {
            return this.allVisibleContacts.filter(c => c.type === 'group');
        },
        // Filtro solo usuarios
        filteredUsers() {
            return this.allVisibleContacts.filter(c => c.type !== 'group');
        }
    },
    watch: {
        'contactList.length': {
            handler: function (length) {
                // Al cargar la lista, seleccionamos el primero si existe
                if (this.contactList.length > 0 && !this.userInfo.id) {
                    this.changeActive(this.contactList[0]);
                }
            },
            immediate: true
        }
    },
    methods: {
        findUser(userIds) {
            if (!userIds || !Array.isArray(userIds)) return [];
            return this.contactList.filter(user => userIds.includes(user.id));
        },
        openGroupModal() {
            this.newGroup.name = '';
            this.newGroup.members = [];
            $('#createGroupModal').modal('show');
        },
        async createGroup() {
            let formData = { name: this.newGroup.name, members: this.newGroup.members };
            try {
                const response = await axios.post('chat/groups', formData);
                $('#createGroupModal').modal('hide');
                this.$toastr.s("Grupo creado exitosamente");
                this.newGroup.name = '';
                this.newGroup.members = [];
                await this.getAllUser(); 
            } catch (error) {
                this.$toastr.e(error.response?.data?.message || "Error al crear grupo");
            }
        },
        
        // --- CAMBIO DE CHAT ---
        changeActive(user) {
            // Si ya estoy en este chat, no hago nada (evita recargas innecesarias)
            if (this.userInfo.id === user.id) return;

            this.userInfo = user;
            this.getUserMessages(user.id); 
        },

        setEmoji(code) {
            this.messageText += code; // Corregido para agregar al texto existente
        },
        sendMessage() {
            if (this.messageText.length) {
                let formData = {
                    message: this.messageText,
                    receiver_id: this.userInfo.id
                };
                if(this.userInfo.type === 'group') formData.is_group = true;
                
                this.submitFromFixin('post', `messages`, formData)
            }
        },
        sendActiveEmoji() {
            let formData = {
                message: this.activeEmoji,
                receiver_id: this.userInfo.id
            };
            if(this.userInfo.type === 'group') formData.is_group = true;
            this.submitFromFixin('post', `messages`, formData)
        },
        sendImage(event) {
            const file = event.target.files[0]
            this.file_upload = file
            let formData = new FormData();
            formData.append('receiver_id', this.userInfo.id);
            formData.append('file_upload', this.file_upload);
            if(this.userInfo.type === 'group') formData.append('is_group', true);
            
            this.submitFromFixin('post', `messages`, formData)
        },
        afterSuccess(response) {
            this.messageText = '';
            this.file_upload = '';
            let receiverId = response.data.message ? response.data.message.receiver_id : this.userInfo.id;
            if (response.data.message && response.data.message.chat_group_id) {
                receiverId = response.data.message.chat_group_id;
            }
            this.getUserMessages(receiverId);
        },
        selectEmoji(event, emoji) {
             this.messageText += emoji.code; // Corregido
        },
        async getAllUser() {
            try {
                const response = await axios.get('chat/users');
                this.contactList = response.data;
            } catch (error) {
                console.error("Error cargando usuarios/grupos", error);
            }
        },

        // --- SOLUCIÓN AL "MEZCLADO" DE CHATS (RACE CONDITION FIX) ---
        async getUserMessages(id) {
            // 1. Limpiar visualmente
            this.userMessageLists = []; 
            this.loadingMessages = true;

            // 2. Guardar el ID que estamos pidiendo AHORA MISMO
            const requestedId = id;

            let isGroup = this.userInfo && this.userInfo.type === 'group';
            
            try {
                const response = await axios.get(`user-messages/${id}?is_group=${isGroup}`);
                
                // 3. VALIDACIÓN CRÍTICA:
                // Solo asignamos los mensajes si el usuario TODAVÍA tiene seleccionado
                // el mismo chat que solicitamos. Si cambió a otro, descartamos esto.
                if (this.userInfo.id === requestedId) {
                    this.userMessageLists = response.data;
                } else {
                    console.log("Respuesta descartada: El usuario cambió de chat.");
                }

            } catch (error) {
                console.error("Error cargando mensajes", error);
            } finally {
                // Solo quitamos el loader si seguimos en el mismo chat
                if (this.userInfo.id === requestedId) {
                    this.loadingMessages = false;
                }
            }
        }
    },
    mounted() {
        if (typeof user !== 'undefined') {
            Echo.private(`chat.${user.id}`)
                .listen('ChatEvent', (e) => {
                    let isCurrentChat = false;
                    // Lógica para detectar si el mensaje entrante pertenece al chat abierto
                    if (e.message.chat_group_id) {
                         if (this.userInfo.type === 'group' && this.userInfo.id === e.message.chat_group_id) {
                             isCurrentChat = true;
                         }
                    } else {
                        if (this.userInfo && (this.userInfo.id === e.message.sender_id || this.userInfo.id === e.message.receiver_id)) {
                             isCurrentChat = true;
                        }
                    }

                    if (isCurrentChat) {
                       this.getUserMessages(this.userInfo.id);
                    }
                });
        }
    },
    created() {
        this.getAllUser();
    }
}
</script>

<style lang="scss">
/* Estilos para las cabeceras colapsables */
.contact-category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    cursor: pointer;
    background-color: #f8f9fa; /* Gris muy suave */
    border-radius: 4px;
    margin-bottom: 5px;
    transition: background-color 0.2s;
    user-select: none;

    &:hover {
        background-color: #e9ecef;
    }
}

.custom-scrollbar {
    overflow-y: auto;
    max-height: calc(100vh - 200px); /* Ajusta esto según tu header */
}

.attached-file-options {
    display: flex;
    align-items: center;
    .image-input {
        opacity: 0;
        position: absolute;
        width: 25px;
    }
}
.chat-message-image {
    max-height: 200px;
    max-width: 150px;
    margin-right: 10px;
}
.chat-date {
    position: absolute;
    font-size: 0.5rem;
    top: 4.5rem;
    right: 65px;
}
.chat-avatar-group {
    position: relative;
    .avatars-group-container {}
}
</style>