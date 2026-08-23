<template>
    <div class="container-fluid fairness">
        <h3>Provably Fair</h3>
        <p>
            Every RATINO outcome derives from HMAC-SHA256(server_seed, "client_seed:nonce:round").
            Open your game history to grab the seeds, then verify any round below.
        </p>
        <div class="fairness-form">
            <input v-model="serverSeed" placeholder="Server seed">
            <input v-model="clientSeed" placeholder="Client seed">
            <input v-model="nonce" type="number" placeholder="Nonce">
            <button class="btn btn-primary" @click="verify">Verify</button>
        </div>
        <div class="fairness-result" v-if="result">
            <div v-for="(r, i) in result" :key="i">Round {{ i }}: {{ r }}</div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            serverSeed: '',
            clientSeed: '',
            nonce: 0,
            result: null
        };
    },
    methods: {
        verify() {
            const rounds = [];
            for (let i = 0; i < 3; i++) {
                const hash = CryptoJS ? null : null;
                rounds.push(hash);
            }
            // uses webcrypto HMAC
            const floats = [];
            (async () => {
                const enc = new TextEncoder();
                const key = await crypto.subtle.importKey('raw', enc.encode(this.serverSeed), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
                for (let round = 0; round < 3; round++) {
                    const sig = await crypto.subtle.sign('HMAC', key, enc.encode(`${this.clientSeed}:${this.nonce}:${round}`));
                    const bytes = new Uint8Array(sig);
                    const chunks = [];
                    for (let c = 0; c < 4; c++) {
                        let acc = 0;
                        for (let b = 0; b < 4; b++) acc += bytes[c * 4 + b] / (256 ** (b + 1));
                        chunks.push(acc);
                    }
                    floats.push(chunks.map((f) => f.toFixed(8)).join(' | '));
                }
                this.result = floats;
            })();
        }
    }
};
</script>

<style lang="scss" scoped>
.fairness {
    max-width: 640px;
    margin: 40px auto;

    .fairness-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin: 20px 0;
    }

    .fairness-result {
        margin-top: 16px;
        padding: 14px;
        background: rgba(255, 255, 255, .04);
        border-radius: 6px;
        font-family: monospace;
    }
}
</style>
