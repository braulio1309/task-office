<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('chat')"/>

        <div class="chat-wrapper">
            <!-- ═══════════════════════════ LEFT SIDEBAR ═══════════════════════════ -->
            <div class="chat-contacts">
                <!-- Search + New Group button -->
                <div class="search-contact">
                    <div class="search-input-group">
                        <app-icon name="search" class="size-17 search-icon"/>
                        <input type="text"
                               v-model="searchContact"
                               class="form-control search-contact-input"
                               :placeholder="$t('search') || 'Buscar…'">
                    </div>
                    <button class="btn btn-primary btn-sm ml-2"
                            @click="openGroupModal"
                            title="Crear Grupo">
                        <app-icon name="plus" class="size-15"/>
                    </button>
                </div>

                <div class="contact-list custom-scrollbar">

                    <!-- ── Chats recientes ── -->
                    <template v-if="recentContacts.length > 0">
                        <div class="contact-category-header" @click="isRecentOpen = !isRecentOpen">
                            <span class="text-muted font-weight-bold text-uppercase text-size-12">
                                Chats recientes ({{ recentContacts.length }})
                            </span>
                            <app-icon :name="isRecentOpen ? 'chevron-down' : 'chevron-right'" class="size-14 text-muted"/>
                        </div>

                        <div v-show="isRecentOpen">
                            <a v-for="contact in recentContacts"
                               :key="`recent-${contact.type}-${contact.id}`"
                               class="contact"
                               :class="{'active': activeContact && activeContact.id === contact.id && activeContact.type === contact.type}"
                               @click.prevent="changeActive(contact)">
                                <contact-icon :contact="contact" :contact-list="contactList" :url-generator="urlGenerator"/>
                                <div class="contact-info">
                                    <p class="mb-0 font-weight-semibold">{{ contact.full_name }}</p>
                                    <small class="text-muted text-truncate d-block" style="max-width:140px" v-if="contact.last_message">
                                        {{ contact.last_message }}
                                    </small>
                                </div>
                                <span v-if="unreadCounts[contact.id] > 0"
                                      class="badge badge-danger badge-pill ml-auto">
                                    {{ unreadCounts[contact.id] }}
                                </span>
                            </a>
                        </div>
                    </template>

                    <!-- ── Todos los contactos ── -->
                    <div class="contact-category-header mt-2" @click="isContactsOpen = !isContactsOpen">
                        <span class="text-muted font-weight-bold text-uppercase text-size-12">
                            Contactos ({{ filteredNewContacts.length }})
                        </span>
                        <app-icon :name="isContactsOpen ? 'chevron-down' : 'chevron-right'" class="size-14 text-muted"/>
                    </div>

                    <div v-show="isContactsOpen">
                        <a v-for="contact in filteredNewContacts"
                           :key="`contact-${contact.type}-${contact.id}`"
                           class="contact"
                           :class="{'active': activeContact && activeContact.id === contact.id && activeContact.type === contact.type}"
                           @click.prevent="changeActive(contact)">
                            <contact-icon :contact="contact" :contact-list="contactList" :url-generator="urlGenerator"/>
                            <div class="contact-info">
                                <p class="mb-0">{{ contact.full_name }}</p>
                                <small v-if="contact.type === 'group'" class="text-muted text-size-11">
                                    {{ (contact.groupMembers || []).length }} miembros
                                </small>
                            </div>
                            <span v-if="unreadCounts[contact.id] > 0"
                                  class="badge badge-danger badge-pill ml-auto">
                                {{ unreadCounts[contact.id] }}
                            </span>
                        </a>
                        <div v-if="filteredNewContacts.length === 0" class="p-2 text-center text-muted text-size-12">
                            No hay contactos
                        </div>
                    </div>

                </div>
            </div>

            <!-- ═══════════════════════════ CHAT AREA ═════════════════════════════ -->
            <div class="chat-messages">
                <div class="h-100 d-flex flex-column" v-if="activeContact && activeContact.id">

                    <!-- Header -->
                    <div class="message-header">
                        <div class="contact-title">
                            <h4 class="mb-0">{{ activeContact.full_name }}</h4>
                        </div>
                        <div class="contact-user">
                            <div v-if="activeContact.type === 'group'" class="d-flex align-items-center">
                                <template v-for="(avatar, i) in findUsers(activeContact.groupMembers)">
                                    <app-avatar v-if="i < 3"
                                                :key="`header-avatar-${avatar.id}`"
                                                :img="avatar.profile_picture ? urlGenerator(avatar.profile_picture.path) : null"
                                                class="ml-n2 border border-white rounded-circle"
                                                :title="avatar.full_name"/>
                                </template>
                            </div>
                            <div class="avatars-w-40" v-else>
                                <app-avatar :alt-text="activeContact.full_name"
                                            :img="activeContact.profile_picture ? urlGenerator(activeContact.profile_picture.path) : null"
                                            :shadow="true"
                                            :title="activeContact.full_name"/>
                            </div>
                        </div>
                    </div>

                    <!-- Messages body -->
                    <div class="message-body" v-chat-scroll>
                        <div v-if="loadingMessages"
                             class="d-flex h-100 justify-content-center align-items-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando…</span>
                            </div>
                        </div>

                        <template v-else>
                            <div v-if="userMessageLists.length === 0"
                                 class="d-flex h-100 justify-content-center align-items-center">
                                <p class="text-muted">No hay mensajes aún. ¡Sé el primero en escribir!</p>
                            </div>

                            <div v-for="msg in userMessageLists"
                                 :key="msg.id || msg.temp_id"
                                 class="message"
                                 :class="{
                                     'reply-message': isMine(msg),
                                     'sending': msg.is_sending
                                 }">

                                <div class="avatars-w-40" v-if="msg.user">
                                    <app-avatar :alt-text="msg.user.full_name"
                                                :img="msg.user.profile_picture ? urlGenerator(msg.user.profile_picture.path) : null"
                                                :shadow="true"
                                                :title="msg.user.full_name"/>
                                </div>

                                <!-- Attachments -->
                                <template v-if="msg.attachments && msg.attachments.length">
                                    <div v-for="att in msg.attachments"
                                         :key="att.id || 'tmp'"
                                         class="chat-attachment">
                                        <img class="chat-message-image img-thumbnail"
                                             :src="att.is_local ? att.path : urlGenerator(att.path)"
                                             alt="Imagen adjunta"/>
                                        <div v-if="msg.is_sending" class="image-sending-overlay">
                                            <div class="spinner-border text-light spinner-border-sm" role="status"></div>
                                        </div>
                                        <div class="chat-attachment-name text-muted text-size-12 mt-1"
                                             v-if="att.original_filename">
                                            <app-icon name="paperclip" class="size-12"/>
                                            {{ att.original_filename }}
                                        </div>
                                    </div>
                                </template>

                                <!-- Text -->
                                <div class="text" v-if="msg.message">
                                    <span v-html="msg.message"></span>
                                    <span v-if="msg.is_sending" class="ml-2 text-muted">
                                        <i class="fas fa-spinner fa-spin text-size-10"></i>
                                    </span>
                                </div>

                                <div class="chat-date">
                                    <span class="text-muted">{{ moment(msg.created_at).format(momentFormattingString) }}</span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Message input -->
                    <div class="message-editor">
                        <div class="attached-file-options">
                            <label :for="`chat-file-${activeContact.id}`" class="option p-1 text-primary mb-0" style="cursor:pointer">
                                <app-icon name="paperclip" class="size-18"/>
                            </label>
                            <input type="file"
                                   class="d-none"
                                   :id="`chat-file-${activeContact.id}`"
                                   @change="sendImage($event)"/>
                        </div>
                        <div class="message-input">
                            <div class="message-input-group">
                                <input type="text"
                                       class="form-control"
                                       v-model="messageText"
                                       @keyup.enter="sendMessage()"
                                       placeholder="Escribe un mensaje…">
                                <div class="message-input-append">
                                    <!-- Emoji picker -->
                                    <div class="btn-group dropdown-emoji">
                                        <button type="button" class="btn" data-toggle="dropdown">
                                            <app-icon name="smile" class="size-15"/>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="#" v-for="emoji in emojiList"
                                               class="emoji"
                                               @click.prevent="messageText += emoji.code"
                                               v-html="emoji.code"
                                               :key="emoji.id"></a>
                                        </div>
                                    </div>
                                    <a href="#"
                                       :class="{'disabled': !messageText, 'text-primary': messageText}"
                                       class="btn btn-send"
                                       @click.prevent="sendMessage()">
                                        <app-icon name="send" class="size-15"/>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="h-100 d-flex justify-content-center align-items-center">
                    <p class="text-muted">Selecciona un chat para comenzar</p>
                </div>
            </div>

            <!-- ═══════════════════════════ RIGHT DETAIL ══════════════════════════ -->
            <div class="chat-contact-details" v-if="activeContact && activeContact.id">
                <div class="d-flex flex-column align-items-center">
                    <div v-if="activeContact.type === 'group'" class="mb-2">
                        <div class="d-flex justify-content-center flex-wrap">
                            <template v-for="(avatar, i) in findUsers(activeContact.groupMembers)">
                                <app-avatar v-if="i < 4"
                                            :key="`detail-avatar-${avatar.id}`"
                                            :img="avatar.profile_picture ? urlGenerator(avatar.profile_picture.path) : null"
                                            class="mr-1 mb-1"
                                            :title="avatar.full_name"/>
                            </template>
                        </div>
                    </div>
                    <div class="avatars-w-40" v-else>
                        <app-avatar :alt-text="activeContact.full_name"
                                    :img="activeContact.profile_picture ? urlGenerator(activeContact.profile_picture.path) : null"
                                    :shadow="true"
                                    :title="activeContact.full_name"/>
                    </div>
                    <p class="mt-2 mb-0 font-weight-bold text-center">{{ activeContact.full_name }}</p>
                    <small v-if="activeContact.type === 'group'" class="text-muted">
                        {{ (activeContact.groupMembers || []).length }} Miembros
                    </small>
                    <small v-else class="text-muted">{{ activeContact.email }}</small>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════ CREATE GROUP MODAL ════════════════════════ -->
        <div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Nuevo Grupo</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre del Grupo</label>
                            <input type="text" class="form-control" v-model="newGroup.name"
                                   placeholder="Ej: Equipo de Ventas">
                        </div>
                        <div class="form-group">
                            <label>Seleccionar Miembros</label>
                            <div style="max-height:200px; overflow-y:auto;">
                                <div v-for="user in userContacts"
                                     :key="`modal-user-${user.id}`"
                                     class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           :id="`user-check-${user.id}`"
                                           :value="user.id"
                                           v-model="newGroup.members">
                                    <label class="custom-control-label d-flex align-items-center"
                                           :for="`user-check-${user.id}`">
                                        {{ user.full_name }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button"
                                class="btn btn-primary"
                                @click="createGroup"
                                :disabled="!newGroup.name || newGroup.members.length === 0">
                            Crear Grupo
                        </button>
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

// Inline sub-component to render the contact avatar/icon in the sidebar
const ContactIcon = {
    name: 'ContactIcon',
    props: ['contact', 'contactList', 'urlGenerator'],
    template: `
        <div class="contact-icon">
            <template v-if="contact.type === 'group'">
                <div class="d-flex">
                    <template v-for="(m, i) in groupMembers">
                        <app-avatar v-if="i < 2"
                                    :key="'ci-'+m.id"
                                    :img="m.profile_picture ? urlGenerator(m.profile_picture.path) : null"
                                    style="width:25px;height:25px;"
                                    class="mr-1"/>
                    </template>
                    <span v-if="contact.groupMembers && contact.groupMembers.length > 2"
                          class="badge badge-secondary rounded-circle d-flex align-items-center justify-content-center"
                          style="width:25px;height:25px;">
                        +{{ contact.groupMembers.length - 2 }}
                    </span>
                </div>
            </template>
            <div v-else class="avatars-w-40">
                <app-avatar :alt-text="contact.full_name"
                            :img="contact.profile_picture ? urlGenerator(contact.profile_picture.path) : null"
                            :shadow="true"
                            :title="contact.full_name"/>
            </div>
        </div>
    `,
    computed: {
        groupMembers() {
            if (!this.contact.groupMembers) return [];
            return this.contactList.filter(u => this.contact.groupMembers.includes(u.id) && u.type !== 'group');
        }
    }
};

export default {
    name: 'Chat',
    mixins: [FormMixin],
    components: { ContactIcon },

    data() {
        return {
            momentFormattingString: 'hh:mm A DD-MM-YY',
            searchContact: '',
            moment,
            messageText: '',
            contactList: [],
            userMessageLists: [],
            loadingMessages: false,
            activeContact: null,

            isRecentOpen:   true,
            isContactsOpen: true,

            file_upload: '',
            urlGenerator,
            newGroup: { name: '', members: [] },
            unreadCounts: {},
            emojiList: [
                {id: 1, code: '&#9994;'}, {id: 2, code: '&#9995;'}, {id: 3, code: '&#9996;'},
                {id: 4, code: '&#128074;'}, {id: 5, code: '&#128076;'}, {id: 6, code: '&#128077;'},
                {id: 7, code: '&#128078;'}, {id: 8, code: '&#128079;'}, {id: 9, code: '&#128148;'},
                {id: 10, code: '&#128149;'}, {id: 11, code: '&#128150;'}, {id: 12, code: '&#128153;'},
            ],
        };
    },

    computed: {
        /** All user-type contacts (for group modal member picker) */
        userContacts() {
            return this.contactList.filter(c => c.type !== 'group');
        },

        /** Contacts with whom at least one message has been exchanged, sorted by recency */
        recentContacts() {
            return this.contactList
                .filter(c => c.has_messages)
                .filter(c => c.full_name.toLowerCase().includes(this.searchContact.toLowerCase()))
                .sort((a, b) => {
                    const unreadDiff = (this.unreadCounts[b.id] || 0) - (this.unreadCounts[a.id] || 0);
                    if (unreadDiff !== 0) return unreadDiff;
                    const ta = a.last_message_at ? new Date(a.last_message_at) : 0;
                    const tb = b.last_message_at ? new Date(b.last_message_at) : 0;
                    return tb - ta;
                });
        },

        /** Contacts with no message history yet (includes groups without messages) */
        filteredNewContacts() {
            return this.contactList
                .filter(c => !c.has_messages)
                .filter(c => c.full_name.toLowerCase().includes(this.searchContact.toLowerCase()));
        },
    },

    methods: {
        /** Find user-type contacts by a list of user IDs (for group avatar rendering) */
        findUsers(userIds) {
            if (!userIds || !Array.isArray(userIds)) return [];
            return this.contactList.filter(u => u.type !== 'group' && userIds.includes(u.id));
        },

        /** Return true if the logged-in user sent this message */
        isMine(msg) {
            if (typeof user === 'undefined') return false;
            return msg.user && (msg.user.id === user.id || msg.user.id === 'me');
        },

        openGroupModal() {
            this.newGroup = { name: '', members: [] };
            $('#createGroupModal').modal('show');
        },

        async createGroup() {
            try {
                await axios.post('chat/groups', {
                    name: this.newGroup.name,
                    members: this.newGroup.members,
                });
                $('#createGroupModal').modal('hide');
                this.$toastr && this.$toastr.s('Grupo creado exitosamente');
                await this.getAllUser();
            } catch (error) {
                this.$toastr && this.$toastr.e(error.response?.data?.message || 'Error al crear grupo');
            }
        },

        changeActive(contact) {
            if (this.activeContact && this.activeContact.id === contact.id && this.activeContact.type === contact.type) return;
            this.activeContact = contact;
            this.userMessageLists = [];
            this.getUserMessages(contact.id);
            if (contact.type !== 'group') {
                this.markMessagesAsRead(contact.id);
            }
        },

        async markMessagesAsRead(contactId) {
            try {
                await axios.post(`messages/${contactId}/mark-as-read`);
                this.$set(this.unreadCounts, contactId, 0);
                await this.getUnreadCounts();
            } catch (e) {
                console.error('Error marking as read', e);
            }
        },

        /** Optimistic message builder */
        createTempMessage(text, type = 'text', fileData = null) {
            return {
                temp_id:     'temp_' + Date.now(),
                message:     type === 'text' ? text : null,
                created_at:  moment().toISOString(),
                user:        { id: (typeof user !== 'undefined' ? user.id : 'me') },
                attachments: fileData ? [fileData] : [],
                is_sending:  true,
            };
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$el.querySelector('.message-body');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        sendMessage() {
            if (!this.messageText.trim()) return;

            const tempMsg = this.createTempMessage(this.messageText, 'text');
            this.userMessageLists.push(tempMsg);
            this.scrollToBottom();

            const formData = {
                message:     this.messageText,
                receiver_id: this.activeContact.id,
            };
            if (this.activeContact.type === 'group') formData.is_group = true;

            this.messageText = '';
            this.submitFromFixin('post', 'messages', formData);
        },

        sendImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.file_upload = file;

            const tempMsg = this.createTempMessage(null, 'file', {
                path:              URL.createObjectURL(file),
                original_filename: file.name,
                is_local:          true,
            });
            this.userMessageLists.push(tempMsg);
            this.scrollToBottom();

            const formData = new FormData();
            formData.append('receiver_id', this.activeContact.id);
            formData.append('file_upload', file);
            if (this.activeContact.type === 'group') formData.append('is_group', 'true');

            event.target.value = null;
            this.submitFromFixin('post', 'messages', formData);
        },

        afterSuccess(response) {
            this.file_upload = '';
            if (this.activeContact) {
                this.getUserMessages(this.activeContact.id);
            }
            // Refresh sidebar so recent/contact sections stay up-to-date
            this.getAllUser();
        },

        async getAllUser() {
            try {
                const response = await axios.get('chat/users');
                this.contactList = response.data;
                await this.getUnreadCounts();
            } catch (e) {
                console.error('Error loading contacts', e);
            }
        },

        async getUnreadCounts() {
            try {
                const response = await axios.get('messages-unread-count');
                const counts = {};
                response.data.by_sender.forEach(item => {
                    counts[item.id] = item.count;
                });
                this.unreadCounts = counts;
                this.$root.$emit('chat-unread-count', response.data.total);
            } catch (e) {
                console.error('Error loading unread counts', e);
            }
        },

        async getUserMessages(id) {
            this.loadingMessages = true;
            const requestedId    = id;
            const isGroup        = this.activeContact && this.activeContact.type === 'group';

            try {
                const response = await axios.get(`user-messages/${id}?is_group=${isGroup}`);
                if (this.activeContact && this.activeContact.id === requestedId) {
                    this.userMessageLists = response.data;
                }
            } catch (e) {
                console.error('Error loading messages', e);
            } finally {
                if (this.activeContact && this.activeContact.id === requestedId) {
                    this.loadingMessages = false;
                }
            }
        },
    },

    mounted() {
        if (typeof user !== 'undefined' && typeof Echo !== 'undefined') {
            try {
                Echo.private(`chat.${user.id}`)
                    .listen('ChatEvent', (e) => {
                        const senderId = e.message.sender_id;
                        let isCurrentChat = false;

                        if (this.activeContact) {
                            if (e.message.chat_group_id) {
                                isCurrentChat = this.activeContact.type === 'group'
                                    && this.activeContact.id === e.message.chat_group_id;
                            } else {
                                isCurrentChat = this.activeContact.type !== 'group'
                                    && (this.activeContact.id === senderId
                                        || this.activeContact.id === e.message.receiver_id);
                            }
                        }

                        if (isCurrentChat) {
                            this.getUserMessages(this.activeContact.id);
                        }

                        this.getUnreadCounts();
                        this.getAllUser(); // refresh last_message in sidebar
                    });
            } catch (e) {
                console.error('Error setting up Echo', e);
            }
        }
    },

    created() {
        this.getAllUser();
    },
};
</script>

<style lang="scss">
.contact-category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 15px;
    cursor: pointer;
    background-color: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 4px;
    transition: background-color 0.2s;
    user-select: none;

    &:hover { background-color: #e9ecef; }
}

.custom-scrollbar {
    overflow-y: auto;
    max-height: calc(100vh - 200px);
}

.attached-file-options {
    display: flex;
    align-items: center;
}

.chat-message-image {
    max-height: 200px;
    max-width: 150px;
    margin-right: 10px;
}

.chat-attachment {
    display: inline-block;
    margin-right: 10px;
    margin-bottom: 10px;
    position: relative;
}

.chat-attachment-name {
    max-width: 150px;
    word-wrap: break-word;
    text-align: center;
}

.chat-date {
    position: absolute;
    font-size: 0.5rem;
    top: 4.5rem;
    right: 65px;
}

.contact {
    position: relative;
    display: flex;
    align-items: center;

    .badge-pill {
        position: absolute;
        right: 10px;
    }
}

.message.sending { opacity: 0.7; }

.image-sending-overlay {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 4px;
}
</style>
