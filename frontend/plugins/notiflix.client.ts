import Notiflix from "notiflix";

export default defineNuxtPlugin(() => {
    Notiflix.Notify.init({
        position: 'right-bottom',
        timeout: 4000,
        clickToClose: true,
        pauseOnHover: true,
        fontSize: '16px',
        width: '320px',
        distance: '16px',
        notifyPadding: '20px',
        showOnlyTheLastOne: false,
        warning: { background: '#facc15' },
        failure: { background: '#ed4137' },
        success: { background: '#16a34a' },
    });

    Notiflix.Block.init({
        svgSize: '32px',
        svgColor: '#ffffff',
        backgroundColor: 'rgba(15,23,42,0.75)',
        messageColor: '#e5e7eb',
    });

    return {
        provide: {
            notify: Notiflix.Notify,
            block: Notiflix.Block,
        },
    };
})
