// Виджет «ВебЧат» Битрикс24 (открытые линии) на всех публичных страницах.
// Загрузка -- только после выбора «Принять» в уведомлении о cookie (VF_CONSENT из
// analytics.js): виджет пишет свои cookie и передаёт переписку в Битрикс24.
// Параметр запроса меняется раз в минуту — так рекомендует Битрикс24, чтобы загрузчик не залипал в кеше.
(() => {
    const load = () => {
        const script = document.createElement('script');
        script.async = true;
        script.src = 'https://cdn-ru.bitrix24.ru/b39497014/crm/site_button/loader_4_cs40it.js?' + (Date.now() / 60000 | 0);

        const firstScript = document.getElementsByTagName('script')[0];
        firstScript.parentNode.insertBefore(script, firstScript);
    };

    if (window.VF_CONSENT) {
        window.VF_CONSENT.whenAccepted(load);
    }
})();
