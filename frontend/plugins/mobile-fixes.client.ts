export default defineNuxtPlugin(() => {
  if (import.meta.server) return;

  const handleFocusIn = (e: FocusEvent) => {
    const target = e.target as HTMLElement;
    const isInput = ['INPUT', 'TEXTAREA'].includes(target.tagName);
    
    if (isInput) {
      // Задержка позволяет клавиатуре начать анимацию открытия
      setTimeout(() => {
        // Проверяем, не перекрыт ли элемент
        const rect = target.getBoundingClientRect();
        const isOffscreen = rect.top < 0 || rect.bottom > window.innerHeight;

        if (isOffscreen) {
          target.scrollIntoView({
            block: 'center',
            behavior: 'smooth',
          });
        }
      }, 300);
    }
  };

  window.addEventListener('focusin', handleFocusIn);

  return {
    provide: {
      mobileFixes: {
        cleanup: () => {
          window.removeEventListener('focusin', handleFocusIn);
        }
      }
    }
  };
});
