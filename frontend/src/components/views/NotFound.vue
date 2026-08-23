<template>
    <div class="not-found">
        <h1>404</h1>
        <p>Sorry, this page isn't available.</p>
        <router-link to="/" class="btn btn-primary">Go home</router-link>
    </div>
</template>

<style lang="scss" scoped>
.not-found {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;

    h1 { font-size: 4em; font-weight: 800; }
}
</style>
