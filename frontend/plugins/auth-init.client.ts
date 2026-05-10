import { watch } from 'vue';

export default defineNuxtPlugin(async () => {
    const authStore = useAuthStore();
    const appShellStore = useAppShellStore();

    const syncBootstrap = async (force = false) => {
        if (!authStore.isAuthenticated) {
            appShellStore.resetForGuest();
            return;
        }

        await appShellStore.ensureBootstrapped(force).catch(() => {
        });
    };

    await authStore.ensureSession();
    await syncBootstrap();

    watch(
        () => authStore.isAuthenticated,
        (isAuthenticated, wasAuthenticated) => {
            if (isAuthenticated === wasAuthenticated) {
                return;
            }

            syncBootstrap(true);
        },
    );
});
