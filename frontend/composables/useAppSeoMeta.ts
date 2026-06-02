import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import { useHead, useRoute, useRuntimeConfig, useSeoMeta } from '#imports';

type AppSeoMetaOptions = {
    title: MaybeRefOrGetter<string>;
    description: MaybeRefOrGetter<string>;
    image?: MaybeRefOrGetter<string>;
    robots?: MaybeRefOrGetter<string>;
    type?: MaybeRefOrGetter<'website' | 'article'>;
};

const trimTrailingSlash = (value: string) => value.replace(/\/+$/, '');

const toAbsoluteUrl = (baseUrl: string, path: string) => {
    if (/^https?:\/\//i.test(path)) return path;

    const normalizedPath = path.startsWith('/') ? path : `/${path}`;
    return `${trimTrailingSlash(baseUrl)}${normalizedPath}`;
};

export const useAppSeoMeta = (options: AppSeoMetaOptions) => {
    const route = useRoute();
    const config = useRuntimeConfig();
    const siteUrl = computed(() => {
        return trimTrailingSlash(config.public.siteUrl || 'https://beddrop.ru');
    });
    const title = computed(() => toValue(options.title));
    const description = computed(() => toValue(options.description));
    const imageUrl = computed(() => toAbsoluteUrl(siteUrl.value, toValue(options.image) || '/images/logo.webp'));
    const canonicalUrl = computed(() => toAbsoluteUrl(siteUrl.value, route.path || '/'));
    const robots = computed(() => toValue(options.robots) || 'index,follow');
    const type = computed(() => toValue(options.type) || 'website');

    useSeoMeta({
        title,
        description,
        ogTitle: title,
        ogDescription: description,
        ogType: type,
        ogUrl: canonicalUrl,
        ogImage: imageUrl,
        twitterTitle: title,
        twitterDescription: description,
        twitterImage: imageUrl,
        twitterCard: 'summary_large_image',
        robots,
    });

    useHead(() => ({
        link: [
            {
                rel: 'canonical',
                href: canonicalUrl.value,
            },
        ],
    }));
};
