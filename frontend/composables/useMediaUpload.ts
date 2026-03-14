import { ref } from 'vue';
import { useApiHelpers } from '~/composables/useApiHelpers';

export interface Media {
    id: number;
    disk: string;
    path: string;
    mime: string | null;
    size_bytes: number | null;
    url: string;
    created_at: string;
    updated_at: string;
}

export function useMediaUpload() {
    const { $api } = useNuxtApp();
    const { handleApiError, errorMessage } = useApiHelpers();

    const uploading = ref(false);

    const uploadMedia = async (file: File): Promise<Media> => {
        uploading.value = true;
        errorMessage.value = null;

        try {
            const formData = new FormData();
            formData.append('file', file);

            const { data } = await $api.post<{ media: Media }>(
                '/media',
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                },
            );

            return data.media;
        } catch (e) {
            handleApiError(e);
            throw e;
        } finally {
            uploading.value = false;
        }
    };

    const deleteMedia = async (id: number) => {
        errorMessage.value = null;

        try {
            await $api.delete(`/media/${id}`);
        } catch (e) {
            handleApiError(e);
            throw e;
        }
    };

    return {
        uploading,
        errorMessage,
        uploadMedia,
        deleteMedia,
    };
}