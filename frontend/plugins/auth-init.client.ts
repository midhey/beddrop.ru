export default defineNuxtPlugin(() => {
    const auth = useAuthStore();

    if (auth.accessToken && !auth.user) {
        auth.profile(true).catch(() => {
        });
    }
});