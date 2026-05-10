import { watch } from 'vue';

export default defineNuxtPlugin(() => {
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

    const bootstrapInitialState = async () => {
        await authStore.ensureSession();
        await syncBootstrap();
    };

    void bootstrapInitialState().catch(() => {
        appShellStore.resetForGuest();
    });

    watch(
        () => authStore.isAuthenticated,
        (isAuthenticated, wasAuthenticated) => {
            if (isAuthenticated === wasAuthenticated) {
                return;
            }

            void syncBootstrap(true);
        },
    );
});
