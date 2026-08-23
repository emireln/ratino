<template>
    <div class="profile-modal-content">
        <div class="profile-header">
            <div class="profile-avatar-container">
                <img :src="previewAvatar || currentAvatar" class="profile-current-avatar" alt="Avatar">
                <label class="avatar-upload-badge" title="Upload custom photo">
                    <i class="fas fa-camera"></i>
                    <input type="file" ref="fileInput" @change="onFileSelected" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="d-none">
                </label>
            </div>
            <div class="profile-title">{{ store.user ? store.user.name : 'Player Profile' }}</div>
            <div class="profile-vip-pill" :style="{ background: vipColor(store.user ? store.user.vipLevel : 0) }">
                <i class="fas fa-crown"></i> VIP {{ store.user ? (store.user.vipLevel || 0) : 0 }}
            </div>
        </div>

        <div class="profile-tabs">
            <button :class="['tab-btn', tab === 'edit' ? 'active' : '']" @click="tab = 'edit'">
                <i class="fas fa-user-edit"></i> Edit Profile
            </button>
            <button :class="['tab-btn', tab === 'stats' ? 'active' : '']" @click="tab = 'stats'">
                <i class="fas fa-chart-pie"></i> Account Stats
            </button>
        </div>

        <!-- Edit Profile Tab -->
        <div class="tab-pane" v-if="tab === 'edit'">
            <div class="form-group mb-3">
                <label class="form-label">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user icon"></i>
                    <input v-model="name" type="text" placeholder="Enter username (4-64 chars)" maxlength="64">
                </div>
                <small class="form-hint">Alphanumeric, underscores, and dashes only.</small>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">Choose Avatar Preset</label>
                <div class="avatar-presets-grid">
                    <div v-for="preset in presetAvatars" :key="preset.id"
                        :class="['preset-avatar-card', selectedPreset === preset.url ? 'selected' : '']"
                        @click="selectPreset(preset.url)">
                        <img :src="preset.url" :alt="preset.name">
                        <span class="preset-name">{{ preset.name }}</span>
                    </div>
                </div>
            </div>

            <div class="profile-action-footer">
                <button class="btn btn-save-profile" @click="saveProfile" :disabled="saving || !isChanged">
                    <span v-if="saving"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                    <span v-else><i class="fas fa-check"></i> Save Changes</span>
                </button>
            </div>
        </div>

        <!-- Account Stats Tab -->
        <div class="tab-pane" v-if="tab === 'stats'">
            <div class="stats-cards-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-coins text-warning"></i></div>
                    <div class="stat-data">
                        <div class="stat-val" v-if="store.currencies[store.currency]">
                            <unit :to="store.currency" :value="store.currencies[store.currency].balance.real"></unit>
                        </div>
                        <div class="stat-label">Chips Balance</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-crown text-danger"></i></div>
                    <div class="stat-data">
                        <div class="stat-val">VIP Tier {{ store.user ? (store.user.vipLevel || 0) : 0 }}</div>
                        <div class="stat-label">Loyalty Status</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-star text-warning"></i></div>
                    <div class="stat-data">
                        <div class="stat-val">{{ favoriteCount }} Games</div>
                        <div class="stat-label">Favorited</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shield-alt text-success"></i></div>
                    <div class="stat-data">
                        <div class="stat-val">Provably Fair</div>
                        <div class="stat-label">HMAC-SHA256</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-signout-section">
            <button class="btn btn-profile-signout" @click="logout">
                <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
            </button>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../../stores/app';
import { useModal } from '../../composables/useModal';
import client from '../../lib/whisper';

const VIP_COLORS = ['#cd7f32', '#c0c0c0', '#ffd700', '#00d2ff', '#b9f2ff', '#e056fd', '#ff2a55'];

export default {
    setup() {
        return { store: useAppStore() };
    },
    data() {
        const user = useAppStore().user || {};
        return {
            tab: 'edit',
            name: user.name || '',
            selectedPreset: '',
            selectedFile: null,
            previewAvatar: null,
            saving: false,
            presetAvatars: [
                { id: '1', name: 'Ace', url: '/img/misc/incognito-default.png' },
                { id: '2', name: 'Crown', url: '/img/game/blackjack.svg' },
                { id: '3', name: 'Dice', url: '/img/game/dice.svg' },
                { id: '4', name: 'Diamond', url: '/img/game/diamonds.svg' },
                { id: '5', name: 'Coin', url: '/img/game/coinflip.png' },
                { id: '6', name: 'Wheel', url: '/img/game/wheel.svg' },
                { id: '7', name: 'Crash', url: '/img/game/crash.svg' },
                { id: '8', name: 'Cards', url: '/img/game/baccarat.png' }
            ]
        };
    },
    computed: {
        currentAvatar() {
            return this.store.user ? (this.store.user.avatar || '/img/misc/incognito-default.png') : '/img/misc/incognito-default.png';
        },
        isChanged() {
            if (!this.store.user) return false;
            return (this.name.trim() !== this.store.user.name) || Boolean(this.selectedFile) || (this.selectedPreset && this.selectedPreset !== this.store.user.avatar);
        },
        favoriteCount() {
            try {
                const favs = JSON.parse(localStorage.getItem('ratino_favorites') || '[]');
                return favs.length;
            } catch (e) {
                return 0;
            }
        }
    },
    methods: {
        vipColor(level) {
            return VIP_COLORS[level] || VIP_COLORS[0];
        },
        selectPreset(url) {
            this.selectedPreset = url;
            this.selectedFile = null;
            this.previewAvatar = url;
        },
        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                this.$toast.error('Avatar file size must be less than 5MB');
                return;
            }

            this.selectedFile = file;
            this.selectedPreset = '';
            this.previewAvatar = URL.createObjectURL(file);
        },
        saveProfile() {
            if (this.saving) return;

            if (this.name.trim().length < 4 || this.name.trim().length > 64) {
                this.$toast.error('Username must be between 4 and 64 characters');
                return;
            }

            this.saving = true;

            const formData = new FormData();
            formData.append('name', this.name.trim());

            if (this.selectedFile) {
                formData.append('avatar_file', this.selectedFile);
            } else if (this.selectedPreset) {
                formData.append('avatar', this.selectedPreset);
            }

            client.post('/api/auth/profile', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then((res) => {
                const updatedUser = res.data && res.data.data ? res.data.data : res.data;
                if (updatedUser && updatedUser.name) {
                    this.store.setUser(updatedUser);
                    this.$toast.success('Profile updated successfully!');
                    this.selectedFile = null;
                    this.previewAvatar = null;
                }
            }).catch((err) => {
                const msg = err.response && err.response.data && err.response.data.message ? err.response.data.message : 'Error updating profile';
                this.$toast.error(msg);
            }).finally(() => {
                this.saving = false;
            });
        },
        logout() {
            client.post('/api/auth/logout').finally(() => {
                this.store.logout();
                useModal().close();
                this.$router.push('/');
                this.$toast.info('Signed out successfully.');
            });
        }
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.profile-modal-content {
    width: 100%;
}

.profile-signout-section {
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    justify-content: center;

    .btn-profile-signout {
        width: 100%;
        padding: 12px 20px;
        background: rgba(229, 31, 73, 0.1);
        border: 1px solid rgba(229, 31, 73, 0.25);
        color: #ff4d6d;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.95em;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s ease;

        &:hover {
            background: #e51f49;
            color: #fff;
            border-color: #e51f49;
        }
    }
}

.profile-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 22px;

    .profile-avatar-container {
        position: relative;
        width: 80px;
        height: 80px;
        margin-bottom: 12px;

        .profile-current-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(229, 31, 73, 0.5);
            box-shadow: none;
        }

        .avatar-upload-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e51f49;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75em;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            transition: transform 0.2s ease;

            &:hover {
                transform: scale(1.15);
            }
        }
    }

    .profile-title {
        font-size: 1.35em;
        font-weight: 700;
        color: #fff;
    }

    .profile-vip-pill {
        font-size: 0.72em;
        font-weight: 800;
        color: #fff;
        padding: 2px 10px;
        border-radius: 12px;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 5px;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }
}

.profile-tabs {
    display: flex;
    gap: 8px;
    background: rgba(0, 0, 0, 0.25);
    padding: 4px;
    border-radius: 10px;
    margin-bottom: 18px;

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
    }
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;

    .icon {
        position: absolute;
        left: 14px;
        color: rgba(255, 255, 255, 0.4);
    }

    input {
        padding-left: 40px !important;
        width: 100%;
    }
}

.form-hint {
    font-size: 0.75em;
    color: rgba(255, 255, 255, 0.45);
    margin-top: 4px;
    display: block;
}

.avatar-presets-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;

    .preset-avatar-card {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 10px 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s ease;

        img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .preset-name {
            font-size: 0.72em;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
        }

        &:hover {
            border-color: rgba(229, 31, 73, 0.5);
            background: rgba(229, 31, 73, 0.1);
            transform: translateY(-2px);
        }

        &.selected {
            border-color: #e51f49;
            background: rgba(229, 31, 73, 0.2);
            box-shadow: none;
        }
    }
}

.profile-action-footer {
    margin-top: 20px;

    .btn-save-profile {
        width: 100%;
        background: #e51f49 !important;
        color: #fff !important;
        border: none !important;
        padding: 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.95em;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: none;

        &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        &:hover:not(:disabled) {
            background: #ff2a55 !important;
            box-shadow: none;
            transform: none;
        }
    }
}

.stats-cards-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;

    .stat-card {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 14px;

        .stat-icon {
            font-size: 1.6em;
        }

        .stat-data {
            .stat-val {
                font-size: 1.05em;
                font-weight: 800;
                color: #fff;
            }

            .stat-label {
                font-size: 0.75em;
                color: rgba(255, 255, 255, 0.5);
                margin-top: 2px;
            }
        }
    }
}

@media(max-width: 576px) {
    .avatar-presets-grid { grid-template-columns: repeat(2, 1fr); }
    .stats-cards-grid { grid-template-columns: 1fr; }
}
</style>
