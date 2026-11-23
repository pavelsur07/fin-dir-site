// Yandex.Metrika loader. Счётчик подключается только на production-страницах:
// технические страницы переопределяют блок analytics пустым. Загрузка -- только после
// выбора «Принять» в уведомлении о cookie (VF_CONSENT из analytics.js); без выбора
// и при «Только необходимые» к mc.yandex.ru нет ни одного запроса.
(() => {
    const counterId = '105455340';

    const load = () => {
        window.ym = window.ym || function () {
            (window.ym.a = window.ym.a || []).push(arguments);
        };
        window.ym.l = Date.now();

        const script = document.createElement('script');
        script.async = true;
        script.src = 'https://mc.yandex.ru/metrika/tag.js';

        const firstScript = document.getElementsByTagName('script')[0];
        firstScript.parentNode.insertBefore(script, firstScript);

        window.ym(counterId, 'init', {
            clickmap: true,
            trackLinks: true,
            accurateTrackBounce: true,
            webvisor: true,
        });
    };

    if (window.VF_CONSENT) {
        window.VF_CONSENT.whenAccepted(load);
    }
})();
