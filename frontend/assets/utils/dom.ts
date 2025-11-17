// Глобальный guard на случай SSR / Nuxt
const canUseDOM = typeof window !== 'undefined' && typeof document !== 'undefined';

export const isCoarsePointer = (): boolean => {
    if (!canUseDOM || !window.matchMedia) return false;
    return window.matchMedia('(pointer: coarse)').matches;
};

export const isNoHover = (): boolean => {
    if (!canUseDOM || !window.matchMedia) return false;
    return window.matchMedia('(hover: none)').matches;
};

// универсальный лок/анлок скролла (совместим с .lock в utilities.scss)
let _scrollY = 0;

export function lockScroll(): void {
    if (!canUseDOM) return;

    _scrollY = window.scrollY || document.documentElement.scrollTop || 0;

    const body = document.body;
    body.style.position = 'fixed';
    body.style.top = `-${_scrollY}px`;
    body.style.left = '0';
    body.style.right = '0';
    body.style.width = '100%';

    document.documentElement.classList.add('lock');
}

export function unlockScroll(): void {
    if (!canUseDOM) return;

    const body = document.body;
    const html = document.documentElement;

    // Если мы не в "залоганном" состоянии — просто выходим,
    // чтобы не триггерить scrollTo из watch(immediate: true) и onBeforeUnmount.
    const isLocked =
        html.classList.contains('lock') || body.style.position === 'fixed';

    if (!isLocked) return;

    body.style.position = '';
    body.style.top = '';
    body.style.left = '';
    body.style.right = '';
    body.style.width = '';

    html.classList.remove('lock');

    // Текущая позиция
    const currentY = window.scrollY || html.scrollTop || 0;

    // Если уже на нужной позиции — ничего не делаем
    if (currentY === _scrollY) return;

    // Временно выключаем плавный скролл
    const prevScrollBehavior = html.style.scrollBehavior;
    html.style.scrollBehavior = 'auto';

    window.scrollTo({
        top: _scrollY,
        left: 0,
        behavior: 'auto',
    });

    // Возвращаем то, что было
    html.style.scrollBehavior = prevScrollBehavior;
}

// slide helpers
export function slideUp(el: HTMLElement | null, duration = 300): void {
    if (!el || el.classList.contains('js-slide')) return;

    el.classList.add('js-slide');

    const startHeight = `${el.offsetHeight}px`;
    el.style.overflow = 'hidden';
    el.style.height = startHeight;
    el.style.transition = `height ${duration}ms ease`;

    requestAnimationFrame(() => {
        el.style.height = '0px';
    });

    window.setTimeout(() => {
        el.hidden = true;
        el.style.removeProperty('height');
        el.style.removeProperty('overflow');
        el.style.removeProperty('transition');
        el.classList.remove('js-slide');
    }, duration);
}

export function slideDown(el: HTMLElement | null, duration = 300): void {
    if (!el || el.classList.contains('js-slide')) return;

    el.classList.add('js-slide');
    el.hidden = false;

    el.style.overflow = 'hidden';
    el.style.height = '0px';

    const targetHeight = `${el.offsetHeight}px`;
    el.style.transition = `height ${duration}ms ease`;

    requestAnimationFrame(() => {
        el.style.height = targetHeight;
    });

    window.setTimeout(() => {
        el.style.removeProperty('height');
        el.style.removeProperty('overflow');
        el.style.removeProperty('transition');
        el.classList.remove('js-slide');
    }, duration);
}

export function slideToggle(el: HTMLElement | null, duration = 300): void {
    if (!el) return;

    if (el.hidden || el.offsetHeight === 0) {
        slideDown(el, duration);
    } else {
        slideUp(el, duration);
    }
}

// wrap
export function wrap(el: HTMLElement | null, wrapper: HTMLElement | null): void {
    if (!el || !wrapper || !el.parentNode) return;

    el.parentNode.insertBefore(wrapper, el);
    wrapper.appendChild(el);
}

// соседний элемент
export function getSibling<T extends Element>(
    el: T | null,
    next: boolean = true
): T | null {
    if (!el) return null;
    const sib = (next ? el.nextElementSibling : el.previousElementSibling) as T | null;
    return sib || el;
}

// фон яркий?
export function isBackgroundBright(el: HTMLElement): boolean {
    const val = getComputedStyle(el).getPropertyValue('background-color');
    const nums = val.match(/[\d.]+/g)?.map(Number) ?? [255, 255, 255, 1];
    const [r, g, b] = nums;
    return 0.299 * r + 0.587 * g + 0.114 * b > 127.5;
}

// позиция слайдера под табом
export function setSlider(
    slider: HTMLElement,
    wrapper: HTMLElement,
    item: HTMLElement
): void {
    const wr = wrapper.getBoundingClientRect();
    const it = item.getBoundingClientRect();

    slider.style.left = `${it.left - wr.left}px`;
    slider.style.top = `${it.top - wr.top + item.offsetHeight - 1}px`;
    slider.style.width = `${it.width}px`;
}

// русская множественность через Intl
export function plural(
    count: number,
    forms: { one: string; few: string; many: string }
): string {
    const cat = new Intl.PluralRules('ru-RU').select(count);
    switch (cat) {
        case 'one':
            return forms.one;
        case 'few':
            return forms.few;
        default:
            return forms.many;
    }
}