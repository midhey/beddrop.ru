export interface LaravelPaginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    [key: string]: any;
}

export type PaginationMeta = Omit<LaravelPaginated<never>, 'data'>;

export const extractPaginationMeta = <T>(
    response: LaravelPaginated<T>,
): PaginationMeta => {
    const { data: _items, ...meta } = response;
    return meta;
};

export const mapLaravelPagination = <T>(
    response: LaravelPaginated<T>,
) => ({
    items: response.data,
    pagination: extractPaginationMeta(response),
});
