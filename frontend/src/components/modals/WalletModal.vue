<template>
    <div class="wallet-modal-content">
        <div class="wallet-header">
            <div class="wallet-icon-badge">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="wallet-title">RATINO Vault & Drops</div>
            <div class="wallet-subtitle">Manage your Chips virtual balance and claim weekly money drops</div>
        </div>

        <div class="wallet-balance-cards">
            <div class="balance-card primary-card">
                <div class="card-label"><i class="fas fa-coins"></i> Real Chips Balance</div>
                <div class="card-value" v-if="store.currencies[store.currency]">
                    <unit :to="store.currency" :value="store.currencies[store.currency].balance.real"></unit>
                    <span class="currency-tag">{{ store.currencies[store.currency].name }}</span>
                </div>
            </div>
            <div class="balance-card demo-card">
                <div class="card-label"><i class="fas fa-gamepad"></i> Demo Practice Balance</div>
                <div class="card-value" v-if="store.currencies[store.currency]">
                    <unit :to="store.currency" :value="store.currencies[store.currency].balance.demo"></unit>
                    <span class="currency-tag">DEMO</span>
                </div>
            </div>
        </div>

        <div class="wallet-actions-section">
            <div class="drop-banner">
                <div class="drop-info">
                    <div class="drop-tag"><i class="fas fa-gift"></i> WEEKLY MONEY DROP</div>
                    <div class="drop-amount">+5,000 CHIPS</div>
                    <div class="drop-timer" v-if="!info.can_claim_weekly">
                        Next drop available in: <span class="countdown">{{ formatTime(info.next_weekly_in) }}</span>
                    </div>
                    <div class="drop-timer available" v-else>
                        Your weekly drop is ready to claim!
                    </div>
                </div>
                <button class="btn btn-claim-drop" @click="claimWeeklyDrop" :disabled="!info.can_claim_weekly || claimingDrop">
                    <span v-if="claimingDrop"><i class="fas fa-spinner fa-spin"></i> Claiming...</span>
                    <span v-else-if="info.can_claim_weekly"><i class="fas fa-hand-holding-usd"></i> Claim 5,000 Chips</span>
                    <span v-else><i class="fas fa-lock"></i> Claimed</span>
                </button>
            </div>

            <div class="quick-reloads">
                <div class="reload-card">
                    <div class="reload-left">
                        <div class="reload-title"><i class="fas fa-faucet"></i> Emergency Chips Refill</div>
                        <div class="reload-desc">Get +500 Chips if your balance drops below 50</div>
                    </div>
                    <button class="btn btn-faucet" @click="claimFaucet" :disabled="!info.can_claim_faucet || claimingFaucet">
                        <span v-if="claimingFaucet"><i class="fas fa-spinner fa-spin"></i></span>
                        <span v-else>Refill (+500)</span>
                    </button>
                </div>

                <div class="reload-card">
                    <div class="reload-left">
                        <div class="reload-title"><i class="fas fa-sync-alt"></i> Reset Demo Practice Funds</div>
                        <div class="reload-desc">Reset your demo balance to +10,000 credits anytime</div>
                    </div>
                    <button class="btn btn-demo-reload" @click="reloadDemo">
                        <span>Reset (+10k)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../../stores/app';
import { whisper } from '../../lib/whisper';

export default {
    setup() {
        return { store: useAppStore() };
    },
    data() {
        return {
            info: {
                credits: 0,
                demo_credits: 0,
                can_claim_weekly: false,
                next_weekly_in: 0,
                weekly_amount: 5000,
                can_claim_faucet: false
            },
            claimingDrop: false,
            claimingFaucet: false,
            timer: null
        };
    },
    methods: {
        loadInfo() {
            whisper('Wallet', { action: 'info' }).then((data) => {
                this.info = data;
            }).catch(() => {});
        },
        claimWeeklyDrop() {
            this.claimingDrop = true;
            whisper('Wallet', { action: 'weekly_drop' }).then((res) => {
                this.$toast.success(res.message || 'Claimed 5,000 Chips!');
                this.store.updateData();
                this.store.update();
                this.loadInfo();
            }).catch((err) => {
                this.$toast.error(typeof err === 'string' ? err : 'Drop unavailable yet');
            }).finally(() => {
                this.claimingDrop = false;
            });
        },
        claimFaucet() {
            this.claimingFaucet = true;
            whisper('Wallet', { action: 'faucet' }).then((res) => {
                this.$toast.success(res.message || 'Added 500 Chips reload!');
                this.store.updateData();
                this.store.update();
                this.loadInfo();
            }).catch(() => {
                this.$toast.error('Refill only available when balance is low (<50)');
            }).finally(() => {
                this.claimingFaucet = false;
            });
        },
        reloadDemo() {
            whisper('Wallet', { action: 'reload_demo' }).then(() => {
                this.$toast.success('Demo credits reloaded to practice!');
                this.store.updateData();
                this.store.update();
                this.loadInfo();
            });
        },
        formatTime(seconds) {
            if (!seconds || seconds <= 0) return 'Available Now';
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            if (days > 0) return `${days}d ${hours}h ${mins}m`;
            return `${hours}h ${mins}m`;
        }
    },
    mounted() {
        this.loadInfo();
        this.timer = setInterval(() => {
            if (this.info.next_weekly_in > 0) {
                this.info.next_weekly_in--;
                if (this.info.next_weekly_in === 0) this.info.can_claim_weekly = true;
            }
        }, 1000);
    },
    beforeUnmount() {
        clearInterval(this.timer);
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.wallet-modal-content {
    width: 100%;
}

.wallet-header {
    text-align: center;
    margin-bottom: 22px;

    .wallet-icon-badge {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: rgba(229, 31, 73, 0.15);
        border: 1px solid rgba(229, 31, 73, 0.35);
        color: #e51f49;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5em;
        margin: 0 auto 12px;
        box-shadow: none;
    }

    .wallet-title {
        font-size: 1.35em;
        font-weight: 700;
        color: #fff;
    }

    .wallet-subtitle {
        font-size: 0.86em;
        color: rgba(255, 255, 255, 0.55);
        margin-top: 4px;
    }
}

.wallet-balance-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 22px;

    .balance-card {
        padding: 16px 18px;
        border-radius: 12px;
        background: #22232c;
        border: 1px solid rgba(255, 255, 255, 0.08);

        &.primary-card {
            border-color: rgba(229, 31, 73, 0.3);
            background: linear-gradient(135deg, #251d24 0%, #1f1f27 100%);
        }

        .card-label {
            font-size: 0.78em;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;

            i { color: #e51f49; }
        }

        .card-value {
            font-size: 1.3em;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: baseline;
            gap: 6px;

            .currency-tag {
                font-size: 0.65em;
                color: #e51f49;
                font-weight: 700;
            }
        }
    }
}

.drop-banner {
    background: linear-gradient(135deg, rgba(229, 31, 73, 0.2) 0%, rgba(229, 31, 73, 0.05) 100%);
    border: 1px solid rgba(229, 31, 73, 0.35);
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;

    .drop-tag {
        font-size: 0.72em;
        font-weight: 800;
        letter-spacing: 0.1em;
        color: #ff3b64;
        margin-bottom: 2px;
    }

    .drop-amount {
        font-size: 1.4em;
        font-weight: 800;
        color: #fff;
    }

    .drop-timer {
        font-size: 0.82em;
        color: rgba(255, 255, 255, 0.6);
        margin-top: 2px;

        &.available {
            color: #2fd672;
            font-weight: 600;
        }

        .countdown {
            color: #fff;
            font-weight: 700;
        }
    }

    .btn-claim-drop {
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.95em;
        background: #e51f49 !important;
        color: #fff !important;
        border: none !important;
        box-shadow: none !important;
        white-space: nowrap;
        cursor: pointer;
        transition: all 0.2s ease;

        &:hover:not(:disabled) {
            background: #ff2a55 !important;
            box-shadow: none !important;
            transform: none;
        }

        &:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: rgba(255, 255, 255, 0.1) !important;
            box-shadow: none !important;
        }
    }
}

.quick-reloads {
    display: flex;
    flex-direction: column;
    gap: 10px;

    .reload-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.07);

        .reload-title {
            font-size: 0.9em;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;

            i { color: #e51f49; }
        }

        .reload-desc {
            font-size: 0.78em;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 2px;
        }

        .btn {
            padding: 8px 16px;
            font-size: 0.85em;
            font-weight: 600;
            border-radius: 6px;
            white-space: nowrap;
        }

        .btn-faucet {
            background: rgba(229, 31, 73, 0.18);
            border: 1px solid rgba(229, 31, 73, 0.4);
            color: #ff3b64;

            &:hover:not(:disabled) {
                background: #e51f49;
                color: #fff;
            }

            &:disabled {
                opacity: 0.4;
                cursor: not-allowed;
            }
        }

        .btn-demo-reload {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;

            &:hover {
                background: rgba(255, 255, 255, 0.15);
            }
        }
    }
}

@media(max-width: 576px) {
    .wallet-balance-cards { grid-template-columns: 1fr; }
    .drop-banner { flex-direction: column; align-items: flex-start; }
    .btn-claim-drop { width: 100%; text-align: center; }
}
</style>
