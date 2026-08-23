<template>
    <div class="room-modal-content">
        <div class="room-header">
            <div class="room-icon-badge">
                <i class="fas fa-users"></i>
            </div>
            <div class="room-title">Play in Rooms with Friends</div>
            <div class="room-subtitle">Create or join private multiplayer tables and share the action</div>
        </div>

        <div class="current-room-card" v-if="activeRoom">
            <div class="card-top">
                <span class="active-badge"><i class="fas fa-circle"></i> ACTIVE ROOM</span>
                <button class="btn btn-leave" @click="leaveRoom(activeRoom.code)">Leave</button>
            </div>
            <div class="room-name-display">{{ activeRoom.name }}</div>
            <div class="room-code-share">
                <div class="code-box">
                    <span class="code-label">ROOM CODE:</span>
                    <span class="code-val">{{ activeRoom.code }}</span>
                </div>
                <button class="btn btn-copy" @click="copyCode(activeRoom.code)">
                    <i class="fas fa-copy"></i> Copy Code
                </button>
            </div>
        </div>

        <div class="room-actions-tabs">
            <button :class="['tab-btn', tab === 'join' ? 'active' : '']" @click="tab = 'join'">Join Room</button>
            <button :class="['tab-btn', tab === 'create' ? 'active' : '']" @click="tab = 'create'">Create Room</button>
        </div>

        <div class="tab-pane" v-if="tab === 'join'">
            <div class="join-input-group">
                <input v-model="joinCode" type="text" placeholder="Enter 6-character room code (e.g. RAT123)" @keyup.enter="joinRoom">
                <button class="btn btn-primary-action" @click="joinRoom" :disabled="!joinCode.trim()">Join</button>
            </div>

            <div class="rooms-list-header" v-if="rooms.length">
                <span>Available Public & Shared Rooms</span>
                <button class="btn-refresh" @click="loadRooms"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="rooms-list" v-if="rooms.length">
                <div class="room-item" v-for="r in rooms" :key="r.code">
                    <template v-if="editingCode === r.code">
                        <div class="room-edit-group">
                            <input v-model="editName" type="text" maxlength="32" @keyup.enter="saveEdit(r.code)">
                            <button class="btn btn-join-sm" @click="saveEdit(r.code)">Save</button>
                            <button class="btn btn-join-sm btn-cancel-sm" @click="cancelEdit">Cancel</button>
                        </div>
                    </template>
                    <div class="room-item-info">
                        <div class="item-name">{{ r.name }}</div>
                        <div class="item-meta"><i class="fas fa-user-friends"></i> {{ r.members }} players &bull; Code: <strong>{{ r.code }}</strong></div>
                    </div>
                    <div class="room-item-actions">
                        <template v-if="r.owner">
                            <button class="btn btn-join-sm" @click="startEdit(r)"><i class="fas fa-pen"></i></button>
                            <button class="btn btn-join-sm btn-delete-sm" @click="deleteRoom(r.code)"><i class="fas fa-trash"></i></button>
                        </template>
                        <button v-else class="btn btn-join-sm" @click="joinDirect(r.code)">Join</button>
                    </div>
                </div>
            </div>
            <div class="rooms-empty" v-else-if="loadingRooms">
                <i class="fas fa-spinner fa-spin"></i> Loading rooms...
            </div>
            <div class="rooms-empty" v-else>
                No public rooms currently active. Create one to get started!
            </div>
        </div>

        <div class="tab-pane" v-if="tab === 'create'">
            <div class="create-form">
                <label class="form-label">Room Name</label>
                <input v-model="createName" type="text" placeholder="e.g. High Rollers Lounge" @keyup.enter="createRoom">
                <button class="btn btn-primary-action mt-3" @click="createRoom" :disabled="!createName.trim() || creating">
                    <span v-if="creating"><i class="fas fa-spinner fa-spin"></i> Creating...</span>
                    <span v-else><i class="fas fa-plus"></i> Create Private Room</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { whisper } from '../../lib/whisper';

export default {
    data() {
        return {
            tab: 'join',
            rooms: [],
            activeRoom: null,
            joinCode: '',
            createName: '',
            creating: false,
            loadingRooms: false,
            editingCode: null,
            editName: ''
        };
    },
    methods: {
        loadRooms() {
            this.loadingRooms = true;
            whisper('Room', { action: 'list' }).then((res) => {
                this.rooms = res.rooms || [];
            }).catch(() => {}).finally(() => {
                this.loadingRooms = false;
            });
        },
        createRoom() {
            if (!this.createName.trim() || this.creating) return;
            this.creating = true;
            whisper('Room', { action: 'create', name: this.createName.trim() }).then((res) => {
                this.$toast.success(`Room "${res.name}" created! Code: ${res.code}`);
                this.activeRoom = res;
                localStorage.setItem('ratino_active_room', JSON.stringify(res));
                this.createName = '';
                this.loadRooms();
            }).catch((err) => {
                this.$toast.error(typeof err === 'string' ? err : 'Error creating room');
            }).finally(() => {
                this.creating = false;
            });
        },
        joinRoom() {
            if (!this.joinCode.trim()) return;
            this.joinDirect(this.joinCode.trim().toUpperCase());
        },
        joinDirect(code) {
            whisper('Room', { action: 'join', code }).then((res) => {
                this.$toast.success(`Joined room "${res.name}"!`);
                this.activeRoom = res;
                localStorage.setItem('ratino_active_room', JSON.stringify(res));
                this.joinCode = '';
                this.loadRooms();
            }).catch(() => {
                this.$toast.error('Unknown or invalid room code');
            });
        },
        leaveRoom(code) {
            whisper('Room', { action: 'leave', code }).then(() => {
                this.$toast.info('Left room');
                this.activeRoom = null;
                localStorage.removeItem('ratino_active_room');
                this.loadRooms();
            });
        },
        startEdit(room) {
            this.editingCode = room.code;
            this.editName = room.name;
        },
        cancelEdit() {
            this.editingCode = null;
            this.editName = '';
        },
        saveEdit(code) {
            const name = this.editName.trim();
            if (!name) return;
            whisper('Room', { action: 'update', code, name }).then((res) => {
                this.$toast.success(`Room renamed to "${res.name}"`);
                if (this.activeRoom && this.activeRoom.code === code) {
                    this.activeRoom = res;
                    localStorage.setItem('ratino_active_room', JSON.stringify(res));
                }
                this.cancelEdit();
                this.loadRooms();
            }).catch((c) => {
                this.$toast.error(c === 5 ? 'Only the room owner can edit this room' : 'Error renaming room');
            });
        },
        deleteRoom(code) {
            whisper('Room', { action: 'delete', code }).then(() => {
                this.$toast.info(`Room ${code} deleted`);
                if (this.activeRoom && this.activeRoom.code === code) {
                    this.activeRoom = null;
                    localStorage.removeItem('ratino_active_room');
                }
                this.loadRooms();
            }).catch((c) => {
                this.$toast.error(c === 5 ? 'Only the room owner can delete this room' : 'Error deleting room');
            });
        },
        copyCode(code) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code).then(() => {
                    this.$toast.success(`Room code ${code} copied to clipboard!`);
                });
            }
        }
    },
    mounted() {
        this.loadRooms();
        const saved = localStorage.getItem('ratino_active_room');
        if (saved) {
            try { this.activeRoom = JSON.parse(saved); } catch (e) {}
        }
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.room-modal-content {
    width: 100%;
}

.room-header {
    text-align: center;
    margin-bottom: 20px;

    .room-icon-badge {
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
    }

    .room-title {
        font-size: 1.3em;
        font-weight: 700;
        color: #fff;
    }

    .room-subtitle {
        font-size: 0.85em;
        color: rgba(255, 255, 255, 0.55);
        margin-top: 3px;
    }
}

.current-room-card {
    background: linear-gradient(135deg, rgba(229, 31, 73, 0.18) 0%, rgba(26, 27, 34, 0.9) 100%);
    border: 1px solid rgba(229, 31, 73, 0.35);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 18px;

    .card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;

        .active-badge {
            font-size: 0.72em;
            font-weight: 800;
            color: #2fd672;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 6px;

            i { font-size: 0.7em; }
        }

        .btn-leave {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.75);
            padding: 4px 10px;
            font-size: 0.78em;
            border-radius: 4px;

            &:hover {
                background: rgba(229, 31, 73, 0.3);
                color: #fff;
            }
        }
    }

    .room-name-display {
        font-size: 1.2em;
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px;
    }

    .room-code-share {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(0, 0, 0, 0.35);
        padding: 8px 14px;
        border-radius: 8px;

        .code-label {
            font-size: 0.75em;
            color: rgba(255, 255, 255, 0.5);
            margin-right: 6px;
        }

        .code-val {
            font-size: 1.1em;
            font-weight: 800;
            color: #ff3b64;
            letter-spacing: 0.08em;
        }

        .btn-copy {
            background: rgba(229, 31, 73, 0.2);
            border: 1px solid rgba(229, 31, 73, 0.4);
            color: #fff;
            padding: 5px 12px;
            font-size: 0.8em;
            border-radius: 6px;
            font-weight: 600;

            &:hover {
                background: #e51f49;
            }
        }
    }
}

.room-actions-tabs {
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

        &.active {
            background: #e51f49;
            color: #fff;
        }
    }
}

.join-input-group {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;

    input { flex: 1; }
}

.btn-primary-action {
    background: linear-gradient(135deg, #ff2a55 0%, #e51f49 100%) !important;
    color: #fff !important;
    border: none !important;
    padding: 10px 20px;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;

    &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
}

.rooms-list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8em;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 8px;

    .btn-refresh {
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;

        &:hover { color: #fff; }
    }
}

.rooms-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 200px;
    overflow-y: auto;

    .room-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.07);

        .item-name {
            font-size: 0.95em;
            font-weight: 600;
            color: #fff;
        }

        .item-meta {
            font-size: 0.78em;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 2px;
        }

        .room-item-actions {
            display: flex;
            gap: 6px;
        }

        .btn-delete-sm {
            &:hover {
                background: #b91c1c;
                border-color: #b91c1c;
            }
        }

        .room-edit-group {
            display: flex;
            flex: 1;
            gap: 6px;

            input { flex: 1; }
        }

        .btn-cancel-sm {
            opacity: .7;

            &:hover { background: rgba(255, 255, 255, 0.12); }
        }

        .btn-join-sm {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 5px 14px;
            font-size: 0.8em;
            font-weight: 600;
            border-radius: 6px;

            &:hover {
                background: #e51f49;
                border-color: #e51f49;
            }
        }
    }
}

.rooms-empty {
    text-align: center;
    padding: 24px 10px;
    font-size: 0.86em;
    color: rgba(255, 255, 255, 0.45);
}

.create-form {
    .form-label {
        display: block;
        font-size: 0.82em;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 6px;
    }

    .btn-primary-action {
        width: 100%;
        margin-top: 14px;
    }
}
</style>
