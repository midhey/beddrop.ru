import { watch } from 'vue';

export default defineNuxtPlugin(() => {
    const authStore = useAuthStore();
    const appShellStore = useAppShellStore();

    const syncBootstrap = async (isAuthenticated: boolean, force = false) => {
        if (!isAuthenticated) {
            appShellStore.resetForGuest();
            return;
        }

        await appShellStore.ensureBootstrapped(force).catch(() => {
        });
    };

    syncBootstrap(authStore.isAuthenticated);

    watch(
        () => authStore.isAuthenticated,
        (isAuthenticated, wasAuthenticated) => {
            if (isAuthenticated === wasAuthenticated) {
                return;
            }

            syncBootstrap(isAuthenticated, true);
        },
    );
});
