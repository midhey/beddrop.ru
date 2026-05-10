const matchesProtectedPrefix = (path: string, prefix: string) => {
    return path === prefix || path.startsWith(`${prefix}/`);
};

export default defineNuxtRouteMiddleware(async (to) => {
    if (import.meta.server) {
        return;
    }

    const needsOrdersAccess = matchesProtectedPrefix(to.path, '/orders');
    const needsCourierAccess = matchesProtectedPrefix(to.path, '/courier');
    const needsRestaurantAccess = matchesProtectedPrefix(to.path, '/restaurants/manage');
    const needsAdminAccess = matchesProtectedPrefix(to.path, '/admin');

    if (!needsOrdersAccess && !needsCourierAccess && !needsRestaurantAccess && !needsAdminAccess) {
        return;
    }

    const authStore = useAuthStore();
    await authStore.ensureSession();

    if (!authStore.isAuthenticated) {
        return navigateTo('/');
    }

    const appShellStore = useAppShellStore();
    await appShellStore.ensureBootstrapped(needsAdminAccess);

    if (!authStore.isAuthenticated) {
        return navigateTo('/');
    }

    if (needsCourierAccess && !appShellStore.hasCourierAccess) {
        return navigateTo('/');
    }

    if (needsRestaurantAccess && !appShellStore.hasRestaurantsAccess) {
        return navigateTo('/');
    }

    if (needsAdminAccess && !authStore.isAdmin) {
        return navigateTo('/');
    }
});
