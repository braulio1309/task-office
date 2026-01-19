<template>
    <li class="nav-item dropdown">
        <a href="#"
           @click.prevent="loadUnreadMessages"
           id="chatNotificationDropdown"
           class="d-flex align-items-center nav-link count-indicator dropdown-toggle"
           data-toggle="dropdown">
            <app-icon :name="'message-square'"/>
            <span class="count-symbol bg-danger" v-if="totalUnread > 0">{{ totalUnread }}</span>
        </a>
        <div v-if="totalUnread > 0" 
             class="dropdown-menu dropdown-menu-right navbar-dropdown notification-dropdown"
             aria-labelledby="chatNotificationDropdown">
            <h6 class="p-primary mb-0 primary-text-color">
                <a href="/chat">{{ $t('chat_messages') }}</a>
                <span class="badge badge-primary badge-sm badge-circle float-right">
                    {{ totalUnread }}
                </span>
            </h6>
            <div class="dropdown-divider"/>
            <div class="dropdown-items-wrapper custom-scrollbar">
                <p class="text-center text-muted py-3">
                    {{ $t('you_have_unread_messages', {count: totalUnread}) }}
                </p>
            </div>
        </div>
        <div v-else
             class="dropdown-menu dropdown-menu-right navbar-dropdown notification-dropdown no-notification-dropdown p-primary"
             aria-labelledby="chatNotificationDropdown">
            <p class="text-center font-size-80 m-0 pt-2 pb-0">
                {{ $t('no_new_messages') }}
            </p>
        </div>
    </li>
</template>

<script>
import axios from 'axios';

export default {
    name: "ChatNotificationDropdown",
    data() {
        return {
            totalUnread: 0,
            intervalId: null
        }
    },
    methods: {
        async loadUnreadMessages() {
            try {
                const response = await axios.get('messages-unread-count');
                this.totalUnread = response.data.total;
            } catch (error) {
                console.error("Error loading unread messages", error);
            }
        },
        startPolling() {
            // Poll every 30 seconds for unread messages
            this.intervalId = setInterval(() => {
                this.loadUnreadMessages();
            }, 30000);
        }
    },
    mounted() {
        this.loadUnreadMessages();
        this.startPolling();
        
        // Listen for updates from chat component
        this.$root.$on('chat-unread-count', (count) => {
            this.totalUnread = count;
        });
        
        // Listen for Echo events if available
        if (typeof user !== 'undefined' && typeof Echo !== 'undefined') {
            try {
                Echo.private(`chat.${user.id}`)
                    .listen('ChatEvent', (e) => {
                        // Refresh unread count when new message arrives
                        this.loadUnreadMessages();
                    });
            } catch (error) {
                console.error("Error setting up Echo listener", error);
            }
        }
    },
    beforeDestroy() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
        this.$root.$off('chat-unread-count');
    }
}
</script>

<style scoped>
.count-symbol {
    position: absolute;
    top: 8px;
    right: 8px;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}
</style>
