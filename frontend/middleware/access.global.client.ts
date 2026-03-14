const matchesProtectedPrefix = (path: string, prefix: string) => {
    return path === prefix || path.startsWith(`${prefix}/`);
};

export default defineNuxtRouteMiddleware(async (to) => {
    const needsOrdersAccess = matchesProtectedPrefix(to.path, '/orders');
    const needsCourierAccess = matchesProtectedPrefix(to.path, '/courier');
    const needsRestaurantAccess = matchesProtectedPrefix(to.path, '/restaurants/manage');

    if (!needsOrdersAccess && !needsCourierAccess && !needsRestaurantAccess) {
        return;
    }

    const authStore = useAuthStore();

    if (!authStore.isAuthenticated) {
        return navigateTo('/');
    }

    const appShellStore = useAppShellStore();
    await appShellStore.ensureBootstrapped();

    if (!authStore.isAuthenticated) {
        return navigateTo('/');
    }

    if (needsCourierAccess && !appShellStore.hasCourierAccess) {
        return navigateTo('/');
    }

    if (needsRestaurantAccess && !appShellStore.hasRestaurantsAccess) {
        return navigateTo('/');
    }
});
