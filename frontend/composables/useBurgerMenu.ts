import { ref, onMounted, onBeforeUnmount } from 'vue';
import { lockScroll, unlockScroll } from '~/assets/utils/dom';

interface UseBurgerMenuOptions {
    breakpoint?: number;
}

export function useBurgerMenu(options: UseBurgerMenuOptions = {}) {
    const breakpoint = options.breakpoint ?? 768;

    const isOpen = ref(false);
    const burgerRef = ref<HTMLElement | null>(null);
    const menuRef = ref<HTMLElement | null>(null);


    const setMenuOffset = () => {
        if (typeof window === 'undefined') return;
        const burgerEl = burgerRef.value;
        const menuEl = menuRef.value;
        if (!burgerEl || !menuEl) return;

        if (window.innerWidth <= breakpoint - 0.2) {
            const h = Math.round(burgerEl.getBoundingClientRect().height);
            burgerEl.style.setProperty('--header-h', `${h}px`);
            menuEl.style.setProperty('--header-h', `${h}px`);
        } else {
            burgerEl.style.removeProperty('--header-h');
            menuEl.style.removeProperty('--header-h');
        }
    };

    const openBurger = () => {
        if (isOpen.value) return;
        isOpen.value = true;
        lockScroll();
        setMenuOffset();
    };

    const closeBurger = () => {
        if (!isOpen.value) return;
        isOpen.value = false;
        unlockScroll();
    };

    const toggleBurger = () => {
        isOpen.value ? closeBurger() : openBurger();
    };

    let mq: MediaQueryList | null = null;
    let mqListener: ((e: MediaQueryListEvent) => void) | null = null;
    let resizeListener: (() => void) | null = null;
    let keydownListener: ((e: KeyboardEvent) => void) | null = null;

    onMounted(() => {
        if (typeof window === 'undefined') return;

        setMenuOffset();

        resizeListener = () => setMenuOffset();
        window.addEventListener('resize', resizeListener);

        mq = window.matchMedia(`(min-width: ${breakpoint}px)`);
        mqListener = (event: MediaQueryListEvent) => {
            setMenuOffset();
            if (event.matches && isOpen.value) {
                closeBurger();
            }
        };
        mq.addEventListener('change', mqListener);

        keydownListener = (e: KeyboardEvent) => {
            if (e.key === 'Escape' && isOpen.value) {
                closeBurger();
            }
        };
        window.addEventListener('keydown', keydownListener);
    });

    onBeforeUnmount(() => {
        if (typeof window === 'undefined') return;

        if (resizeListener) window.removeEventListener('resize', resizeListener);
        if (mq && mqListener) mq.removeEventListener('change', mqListener);
        if (keydownListener) window.removeEventListener('keydown', keydownListener);
    });

    return {
        isOpen,
        burgerRef,
        menuRef,

        openBurger,
        closeBurger,
        toggleBurger,
    };
}
