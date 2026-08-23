<template>
    <div class="friends-modal-content">
        <div class="friends-header">
            <div class="friends-icon-badge">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="friends-title">Friends & Social Lounge</div>
            <div class="friends-subtitle">Connect with friends, share tables, and play together in real time</div>
        </div>

        <div class="friends-tabs">
            <button :class="['tab-btn', tab === 'friends' ? 'active' : '']" @click="tab = 'friends'">
                Friends <span class="tab-badge" v-if="friends.length">({{ friends.length }})</span>
            </button>
            <button :class="['tab-btn', tab === 'requests' ? 'active' : '']" @click="tab = 'requests'">
                Requests <span class="tab-badge alert-badge" v-if="incomingRequests.length">{{ incomingRequests.length }}</span>
            </button>
            <button :class="['tab-btn', tab === 'add' ? 'active' : '']" @click="tab = 'add'">
                Add Friend
            </button>
        </div>

        <!-- Friends List Tab -->
        <div class="tab-pane" v-if="tab === 'friends'">
            <div class="friends-list" v-if="friends.length">
                <div class="friend-card" v-for="friend in friends" :key="friend.id">
                    <div class="friend-left">
                        <div class="friend-avatar-wrap">
                            <img :src="friend.avatar || '/img/misc/incognito-default.png'" alt="">
                            <span class="status-indicator" :class="{ 'is-online': friend.online }"></span>
                        </div>
                        <div class="friend-info">
                            <div class="friend-name">
                                {{ friend.name }}
                                <span class="badge-vip" :style="{ background: vipColor(friend.vipLevel) }">VIP {{ friend.vipLevel }}</span>
                            </div>
                            <div class="friend-activity">
                                <span v-if="friend.online" class="text-online">
                                    <i class="fas fa-circle"></i> Online
                                    <span v-if="friend.active_game">&bull; Playing {{ friend.active_game }}</span>
                                </span>
                                <span v-else class="text-offline">Offline</span>
                            </div>
                        </div>
                    </div>

                    <div class="friend-actions">
                        <button class="btn btn-invite" @click="inviteFriend(friend)" title="Invite to active room">
                            <i class="fas fa-paper-plane"></i> Invite
                        </button>
                        <button class="btn btn-remove" @click="removeFriend(friend.id)" title="Remove friend">
                            <i class="fas fa-user-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="friends-empty" v-else-if="loading">
                <i class="fas fa-spinner fa-spin"></i> Loading friends...
            </div>
            <div class="friends-empty" v-else>
                <i class="fas fa-users-slash"></i>
                <div class="mt-2">No friends added yet</div>
                <small class="text-muted">Search for players by username in the Add Friend tab to connect!</small>
            </div>
        </div>

        <!-- Incoming Requests Tab -->
        <div class="tab-pane" v-if="tab === 'requests'">
            <div class="friends-list" v-if="incomingRequests.length">
                <div class="friend-card" v-for="req in incomingRequests" :key="req.id">
                    <div class="friend-left">
                        <img :src="req.avatar || '/img/misc/incognito-default.png'" class="req-avatar" alt="">
                        <div class="friend-info">
                            <div class="friend-name">{{ req.name }}</div>
                            <div class="friend-activity text-muted">Sent you a friend request</div>
                        </div>
                    </div>
                    <div class="friend-actions">
                        <button class="btn btn-accept" @click="acceptRequest(req.id)">Accept</button>
                        <button class="btn btn-decline" @click="declineRequest(req.id)">Decline</button>
                    </div>
                </div>
            </div>
            <div class="friends-empty" v-else>
                <i class="fas fa-envelope-open"></i>
                <div class="mt-2">No pending friend requests</div>
            </div>
        </div>

        <!-- Add Friend Tab -->
        <div class="tab-pane" v-if="tab === 'add'">
            <div class="add-friend-box">
                <label class="form-label">Find Player by Username</label>
                <div class="input-action-group">
                    <input v-model="searchUsername" type="text" placeholder="Enter exact username..." @keyup.enter="sendRequest">
                    <button class="btn btn-send-req" @click="sendRequest" :disabled="!searchUsername.trim() || sending">
                        <span v-if="sending"><i class="fas fa-spinner fa-spin"></i></span>
                        <span v-else><i class="fas fa-user-plus"></i> Send Request</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { whisper } from '../../lib/whisper';

const VIP_COLORS = ['#cd7f32', '#c0c0c0', '#ffd700', '#00d2ff', '#b9f2ff', '#e056fd', '#ff2a55'];

export default {
    data() {
        return {
            tab: 'friends',
            friends: [],
            incomingRequests: [],
            searchUsername: '',
            loading: false,
            sending: false
        };
    },
    methods: {
        vipColor(level) {
            return VIP_COLORS[level] || VIP_COLORS[0];
        },
        loadList() {
            this.loading = true;
            whisper('Friend', { action: 'list' }).then((res) => {
                this.friends = res.friends || [];
                this.incomingRequests = res.incoming_requests || [];
            }).catch(() => {}).finally(() => {
                this.loading = false;
            });
        },
        sendRequest() {
            if (!this.searchUsername.trim() || this.sending) return;
            this.sending = true;
            whisper('Friend', { action: 'send_request', username: this.searchUsername.trim() }).then((res) => {
                this.$toast.success(res.message || 'Friend request sent!');
                this.searchUsername = '';
                this.loadList();
            }).catch((err) => {
                this.$toast.error(typeof err === 'string' ? err : 'Unable to send request');
            }).finally(() => {
                this.sending = false;
            });
        },
        acceptRequest(userId) {
            whisper('Friend', { action: 'accept_request', user_id: userId }).then((res) => {
                this.$toast.success(res.message || 'Friend added!');
                this.loadList();
            }).catch(() => {
                this.$toast.error('Failed to accept request');
            });
        },
        declineRequest(userId) {
            whisper('Friend', { action: 'reject_request', user_id: userId }).then(() => {
                this.$toast.info('Request declined');
                this.loadList();
            });
        },
        removeFriend(userId) {
            whisper('Friend', { action: 'remove', user_id: userId }).then(() => {
                this.$toast.info('Removed friend');
                this.loadList();
            });
        },
        inviteFriend(friend) {
            let activeRoom = null;
            const saved = localStorage.getItem('ratino_active_room');
            if (saved) {
                try { activeRoom = JSON.parse(saved); } catch (e) {}
            }

            const roomCode = activeRoom ? activeRoom.code : 'MAIN';
            const roomName = activeRoom ? activeRoom.name : 'Casino Lounge';

            whisper('Friend', {
                action: 'invite_room',
                friend_id: friend.id,
                room_code: roomCode,
                room_name: roomName
            }).then(() => {
                this.$toast.success(`Invite to ${roomName} sent to ${friend.name}!`);
            }).catch(() => {
                this.$toast.error('Failed to send room invite');
            });
        }
    },
    mounted() {
        this.loadList();
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.friends-modal-content {
    width: 100%;
}

.friends-header {
    text-align: center;
    margin-bottom: 20px;

    .friends-icon-badge {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: rgba(229, 31, 73, 0.15);
        border: 1px solid rgba(229, 31, 73, 0.35);
        color: #e51f49;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4em;
        margin: 0 auto 10px;
        box-shadow: 0 0 20px rgba(229, 31, 73, 0.25);
    }

    .friends-title {
        font-size: 1.3em;
        font-weight: 700;
        color: #fff;
    }

    .friends-subtitle {
        font-size: 0.85em;
        color: rgba(255, 255, 255, 0.55);
        margin-top: 3px;
    }
}

.friends-tabs {
    display: flex;
    gap: 8px;
    background: rgba(0, 0, 0, 0.25);
    padding: 4px;
    border-radius: 10px;
    margin-bottom: 16px;

    .tab-btn {
        flex: 1;
        text-align: center;
        padding: 9px;
        border-radius: 7px;
        font-size: 0.88em;
        font-weight: 700;
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;

        &.active {
            background: #e51f49;
            color: #fff;
        }

        .alert-badge {
            background: #ff3b64;
            color: #fff;
            font-size: 0.7em;
            padding: 1px 6px;
            border-radius: 10px;
        }
    }
}

.friends-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 280px;
    overflow-y: auto;

    .friend-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.07);

        .friend-left {
            display: flex;
            align-items: center;
            gap: 12px;

            .friend-avatar-wrap {
                position: relative;
                width: 38px;
                height: 38px;

                img {
                    width: 100%;
                    height: 100%;
                    border-radius: 50%;
                    object-fit: cover;
                }

                .status-indicator {
                    position: absolute;
                    bottom: 0;
                    right: 0;
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                    background: #6c757d;
                    border: 2px solid #1c1d25;

                    &.is-online {
                        background: #2fd672;
                        box-shadow: 0 0 6px #2fd672;
                    }
                }
            }

            .req-avatar {
                width: 36px;
                height: 36px;
                border-radius: 50%;
            }

            .friend-name {
                font-size: 0.95em;
                font-weight: 700;
                color: #fff;
                display: flex;
                align-items: center;
                gap: 6px;

                .badge-vip {
                    font-size: 0.65em;
                    color: #fff;
                    padding: 1px 5px;
                    border-radius: 4px;
                    font-weight: 800;
                }
            }

            .friend-activity {
                font-size: 0.78em;
                margin-top: 2px;

                .text-online {
                    color: #2fd672;
                    i { font-size: 0.65em; }
                }

                .text-offline {
                    color: rgba(255, 255, 255, 0.4);
                }
            }
        }

        .friend-actions {
            display: flex;
            align-items: center;
            gap: 6px;

            .btn {
                padding: 6px 12px;
                font-size: 0.82em;
                font-weight: 600;
                border-radius: 6px;
                cursor: pointer;
            }

            .btn-invite {
                background: rgba(229, 31, 73, 0.18);
                border: 1px solid rgba(229, 31, 73, 0.35);
                color: #ff3b64;

                &:hover {
                    background: #e51f49;
                    color: #fff;
                }
            }

            .btn-remove {
                background: rgba(255, 255, 255, 0.06);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: rgba(255, 255, 255, 0.5);

                &:hover {
                    background: rgba(229, 31, 73, 0.3);
                    color: #fff;
                }
            }

            .btn-accept {
                background: #2fd672;
                border: none;
                color: #000;
                font-weight: 700;
            }

            .btn-decline {
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.15);
                color: #fff;
            }
        }
    }
}

.friends-empty {
    text-align: center;
    padding: 30px 10px;
    font-size: 0.88em;
    color: rgba(255, 255, 255, 0.5);

    i {
        font-size: 2.4em;
        opacity: 0.4;
        margin-bottom: 8px;
    }
}

.add-friend-box {
    .form-label {
        font-size: 0.82em;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 6px;
        display: block;
    }

    .input-action-group {
        display: flex;
        gap: 8px;

        input { flex: 1; }

        .btn-send-req {
            background: linear-gradient(135deg, #ff2a55 0%, #e51f49 100%) !important;
            color: #fff !important;
            border: none !important;
            padding: 10px 18px;
            font-weight: 700;
            border-radius: 8px;
            white-space: nowrap;

            &:disabled { opacity: 0.5; }
        }
    }
}
</style>
