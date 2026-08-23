# Game Porting Guide (Vue 2 → Vue 3)

Port one game component at a time from the OLD repo:
`C:\Users\emirl\Desktop\ratino\resources\js\components\views\games\<Game>.vue`

into the NEW frontend:
`C:\Users\emirl\Desktop\ratino\v2\frontend\src\components\views\games\<Game>.vue`

**Reference implementation: `src/components/views/games/Limbo.vue` — read it FIRST.**

## Global API available in every component (no imports needed)

All from the global mixin (`src/composables/useCasino.js`):

- `this.whisper(event, data)` — game action transport
- `this.updateGameInstance(tap)` — mutates shared gameInstance state
- `this.pushStats(game)` — records session stats
- `this.resultPopup(game)` — win/loss popup (adds stats automatically for quick games)
- `this.sendTurn(data, callback)` / `this.finishExtended(sendServerRequest, cb)` / `this.isExtendedGameStarted()` / `this.extendedGameId()`
- `this.playSound(src, timeout)` / `this.chain(times, ms, cb)` / `this.random(min, max)`
- `this.rawBitcoin(currency, value)` / `this.applyHouseEdge(payout)` / `this.abbreviate(v)`
- `this.getCookie/setCookie`
- computed: `this.gameInstance`, `this.games`, `this.user`, `this.isGuest`, `this.demo`, `this.currency`, `this.currencies`, `this.sound`, `this.quick`, `this.unit`, `this.sessionStats`
- `this.$store.dispatch(name, ...)` / `this.$store.commit(name, ...)` — pinia shim (actions: setGameInstance, setCurrency, setUnit, setSoundState, setQuickState, setDemo, updateData, update, toggleSidebar, pushRecentGame, setUserData, logout)
- `this.$bus` (mitt emitter: `.emit(event, payload)`, `.on(event, fn)`) — replaces the old `import Bus from '../../bus'`
- `this.$toast.success/error/info` — replaces `this.$toast` from izitoast
- `window.$gameRef` — current game component instance (set by GameView)

## Required component contract (unchanged from old)

Each game component MUST expose:
- `getSidebarComponents()` — array of `{ name, data }` for the sidebar
- `getClientData()` — object sent as play `data`
- `callback(response)` — called after Play whisper returns
- `multiplayerEvent(event, data)` — for multiplayer games (Crash, Slide, Baccarat)

## Transform rules

1. `import Bus from '../../../bus';` → remove; use `this.$bus`.
   `Bus.$emit('x', d)` → `this.$bus.emit('x', d)`; `Bus.$on(...)` → `this.$bus.on(...)` (save unsubscribe fns, clean up in `beforeUnmount`).
2. `@import "resources/sass/variables";` → `@import "../../../sass/variables";`
   The `themed()` mixin + `t()` function exist in `src/sass/_variables.scss` (copied from old repo).
3. `this.$i18n.t('key')` → `this.$t('key')` (templates already use `$t`).
4. `<content-placeholders>` / `<content-placeholders-img>` → delete (plain loader ok).
5. `<overlay-scrollbars :options=...>` → use `<div class="os-scroll">` + CSS `overflow-y:auto`, or keep raw div with class. Replace `overlay-scrollbars` component with a plain `<div>`.
6. Vue 2 → Vue 3 fixes:
   - `$emit` stays. No `.sync` (use events or watch).
   - `v-model` on custom components → `:modelValue` + `@update:modelValue`, or keep local.
   - No filters (`{{ x | f }}` → method call).
   - `beforeDestroy` → `beforeUnmount`.
   - `destroyed` → `unmounted`.
   - `this.$refs.X` unchanged. `this.$parent` avoid.
   - `_.forEach` etc. — lodash global `_` IS available (window._).
   - `$('.play-button')` jQuery — window.$ available, keep as-is.
   - `v-html` on game-defined strings OK.
7. Multiplayer games: replace old whisper-driven events with Reverb:
   - `this.whisper('GameData', { api_id })` still returns game state (players/history/timestamp).
   - Bus events `event:multiplayerBettingStateChange`, `event:multiplayerTimerStart`, `event:multiplayerGameBet`, `event:multiplayerGameFinished`, `event:multiplayerBetCancellation`, `event:multiplayerDataUpdate` are emitted by the echo client automatically (channel `multiplayer.<game_id>`); subscribe in `mounted()` via `this.$bus.on('event:multiplayerGameFinished', handler)` etc. Unsubscribe in `beforeUnmount`.
   - Keep the old `multiplayerEvent(event, data)` dispatch helper (called by GameView's event bridge).
8. Seeds/fairness: `response.server_seed` shape unchanged.
9. Keep styles identical where possible; the theme system is the same.
10. Do NOT change game logic, payouts, or API calls. This is a mechanical port.

## Verify

- `npm run build` from `C:\Users\emirl\Desktop\ratino\v2\frontend` must pass (run it, fix all errors).
- No behavior change vs the old component.
