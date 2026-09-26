<template>
    <div class="help-modal-content">
        <div class="help-header">
            <div class="help-icon-badge">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="help-title">Help & Support Center</div>
            <div class="help-subtitle">Provably Fair fairness guides, gameplay rules, and virtual economy FAQ</div>
        </div>

        <div class="help-tabs">
            <button :class="['tab-btn', tab === 'fairness' ? 'active' : '']" @click="tab = 'fairness'">
                <i class="fas fa-shield-alt"></i> Provably Fair
            </button>
            <button :class="['tab-btn', tab === 'faq' ? 'active' : '']" @click="tab = 'faq'">
                <i class="fas fa-info-circle"></i> FAQ & Chips
            </button>
            <button :class="['tab-btn', tab === 'games' ? 'active' : '']" @click="tab = 'games'">
                <i class="fas fa-dice"></i> Game Guides
            </button>
            <button :class="['tab-btn', tab === 'policy' ? 'active' : '']" @click="tab = 'policy'">
                <i class="fas fa-file-contract"></i> Policy
            </button>
        </div>

        <!-- 1. Provably Fair Tab -->
        <div class="tab-pane" v-if="tab === 'fairness'">
            <div class="help-card">
                <div class="card-headline"><i class="fas fa-lock text-success"></i> 100% Cryptographic Provable Fairness</div>
                <p>Every game outcome on RATINO is predetermined and mathematically verified using the industry-standard <strong>HMAC-SHA256</strong> algorithm. Neither the server nor the player can alter the outcome after a bet is initiated.</p>
                
                <div class="formula-box">
                    <code>result = HMAC_SHA256(server_seed, "client_seed:nonce:round")</code>
                </div>

                <div class="seed-info-grid">
                    <div class="seed-item">
                        <div class="seed-label">Your Client Seed</div>
                        <div class="seed-val">{{ store.user ? (store.user.client_seed || 'Default Client Seed') : 'Generated upon sign in' }}</div>
                    </div>
                    <div class="seed-item">
                        <div class="seed-label">Server Seed Security</div>
                        <div class="seed-val">Hashed with SHA-256 before game, revealed upon finish.</div>
                    </div>
                </div>

                <div class="help-action-row">
                    <router-link to="/fairness" class="btn btn-help-action" @click="closeModal">
                        <i class="fas fa-external-link-alt mr-2"></i> Open Provably Fair Verifier
                    </router-link>
                </div>
            </div>
        </div>

        <!-- 2. FAQ & Chips Tab -->
        <div class="tab-pane" v-if="tab === 'faq'">
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-q"><i class="fas fa-coins text-warning"></i> How do I get more Chips?</div>
                    <div class="faq-a">
                        <ul>
                            <li><strong>Welcome Bonus:</strong> 10,000 Chips on account registration.</li>
                            <li><strong>Weekly Money Drop:</strong> Claim 5,000 Chips every 7 days in the Vault.</li>
                            <li><strong>Emergency Faucet:</strong> If your balance drops below 50, reload 500 Chips anytime.</li>
                            <li><strong>Weekly & Daily Challenges:</strong> Complete in-game bounties for up to 25,000 Chips!</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-q"><i class="fas fa-users text-primary"></i> How to play with friends in private rooms?</div>
                    <div class="faq-a">
                        Open the <strong>Rooms</strong> modal from the header to create a 6-digit room or join your friend's room. You can also send instant 1-click room invites from the Friends list!
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-q"><i class="fas fa-crown text-danger"></i> How does the VIP Loyalty program work?</div>
                    <div class="faq-a">
                        Every wager with Chips accumulates VIP progress. Higher tiers (Bronze, Silver, Gold, Platinum, Diamond, Elite) unlock higher rakeback percentages and milestone rewards.
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Game Guides Tab -->
        <div class="tab-pane" v-if="tab === 'games'">
            <div class="game-guide-grid">
                <div class="game-guide-card">
                    <div class="game-guide-title"><i class="fas fa-bomb text-danger"></i> Mines</div>
                    <div class="game-guide-desc">Pick 2 to 24 mines on a 5x5 grid. Uncover gems to increase your multiplier and cash out anytime before hitting a mine.</div>
                </div>
                <div class="game-guide-card">
                    <div class="game-guide-title"><i class="fas fa-chart-line text-success"></i> Crash</div>
                    <div class="game-guide-desc">Watch the multiplier rocket rise into the sky. Cash out before the inevitable crash to secure your multiplier!</div>
                </div>
                <div class="game-guide-card">
                    <div class="game-guide-title"><i class="fas fa-dice text-warning"></i> Dice</div>
                    <div class="game-guide-desc">Set your win chance and roll over/under. Enjoy seamless instant rolls and provably fair outcomes.</div>
                </div>
                <div class="game-guide-card">
                    <div class="game-guide-title"><i class="fas fa-bullseye text-primary"></i> Plinko</div>
                    <div class="game-guide-desc">Drop bouncy balls down a peg pyramid with adjustable rows and risk tiers (Low, Medium, High).</div>
                </div>
                <div class="game-guide-card">
                    <div class="game-guide-title"><i class="fas fa-clone text-info"></i> Blackjack</div>
                    <div class="game-guide-desc">Classic 21 blackjack against the dealer. Hit, Stand, Double Down, or Split your pairs.</div>
                </div>
                <div class="game-guide-card">
                    <div class="game-guide-title"><i class="fas fa-circle-notch text-warning"></i> Roulette</div>
                    <div class="game-guide-desc">Place bets on numbers, red/black, dozens, or columns with realistic physics wheel spins.</div>
                </div>
            </div>
        </div>

        <!-- 4. Policy Tab -->
        <div class="tab-pane" v-if="tab === 'policy'">
            <div class="help-card policy-card">
                <div class="card-headline"><i class="fas fa-info-circle text-info"></i> Virtual Social Gaming Platform</div>
                <p>RATINO is an exclusive virtual casino simulation engineered for friends to play and compete together.</p>
                <ul class="policy-bullets">
                    <li><strong>No Real Money:</strong> All bets are placed with virtual CHIPS credits. There are no real deposits, crypto wallets, or cash withdrawals.</li>
                    <li><strong>100% Free to Play:</strong> Faucet reloads, weekly money drops, and challenge bounties ensure you can always continue playing.</li>
                    <li><strong>Fair & Transparent:</strong> Transparent house edge and open provably fair verifier on every single round.</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../../stores/app';
import { useModal } from '../../composables/useModal';

export default {
    setup() {
        return { store: useAppStore() };
    },
    data() {
        return {
            tab: 'fairness'
        };
    },
    methods: {
        closeModal() {
            useModal().close();
        }
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.help-modal-content {
    width: 100%;
}

.help-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 20px;

    .help-icon-badge {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        background: rgba(229, 31, 73, 0.12);
        color: #e51f49;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4em;
        margin-bottom: 10px;
    }

    .help-title {
        font-size: 1.25em;
        font-weight: 700;
        color: #fff;
        margin-bottom: 4px;
    }

    .help-subtitle {
        font-size: 0.85em;
        color: rgba(255, 255, 255, 0.6);
        max-width: 420px;
        line-height: 1.4;
    }
}

.help-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 18px;
    background: rgba(255, 255, 255, 0.03);
    padding: 4px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.06);

    .tab-btn {
        flex: 1;
        padding: 8px 10px;
        border-radius: 6px;
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.65);
        font-size: 0.82em;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;

        &:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }

        &.active {
            background: rgba(229, 31, 73, 0.15);
            color: #ff3366;
            border: 1px solid rgba(229, 31, 73, 0.3);
        }
    }
}

.help-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    padding: 16px;

    .card-headline {
        font-weight: 700;
        font-size: 1.05em;
        color: #fff;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    p {
        font-size: 0.88em;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.5;
        margin-bottom: 12px;
    }

    .formula-box {
        background: #14151b;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 14px;
        overflow-x: auto;

        code {
            color: #ff6b8b;
            font-size: 0.85em;
            font-family: monospace;
        }
    }

    .seed-info-grid {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 14px;

        .seed-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 6px;
            padding: 8px 12px;

            .seed-label {
                font-size: 0.75em;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: rgba(255, 255, 255, 0.45);
                margin-bottom: 2px;
            }

            .seed-val {
                font-size: 0.84em;
                font-family: monospace;
                color: #fff;
                word-break: break-all;
            }
        }
    }

    .help-action-row {
        display: flex;
        justify-content: center;

        .btn-help-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            font-weight: 700;
            font-size: 0.88em;
            text-decoration: none;
            transition: all 0.2s ease;

            &:hover {
                background: rgba(255, 255, 255, 0.1);
                border-color: rgba(255, 255, 255, 0.2);
            }
        }
    }
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 10px;

    .faq-item {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        padding: 12px 14px;

        .faq-q {
            font-weight: 700;
            font-size: 0.92em;
            color: #fff;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .faq-a {
            font-size: 0.85em;
            color: rgba(255, 255, 255, 0.65);
            line-height: 1.45;

            ul {
                margin: 6px 0 0 16px;
                padding: 0;

                li {
                    margin-bottom: 4px;
                }
            }
        }
    }
}

.game-guide-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;

    .game-guide-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 8px;
        padding: 12px;

        .game-guide-title {
            font-weight: 700;
            font-size: 0.9em;
            color: #fff;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .game-guide-desc {
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.4;
        }
    }
}

.policy-bullets {
    margin: 8px 0 0 16px;
    padding: 0;

    li {
        font-size: 0.85em;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 6px;
        line-height: 1.45;
    }
}

@media(max-width: 600px) {
    .game-guide-grid {
        grid-template-columns: 1fr;
    }
    .help-tabs {
        flex-wrap: wrap;
    }
}
</style>
