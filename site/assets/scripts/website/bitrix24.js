// Виджет «ВебЧат» Битрикс24 (открытые линии) на всех публичных страницах.
// Параметр запроса меняется раз в минуту — так рекомендует Битрикс24, чтобы загрузчик не залипал в кеше.
(() => {
    const script = document.createElement('script');
    script.async = true;
    script.src = 'https://cdn-ru.bitrix24.ru/b39497014/crm/site_button/loader_4_cs40it.js?' + (Date.now() / 60000 | 0);

    const firstScript = document.getElementsByTagName('script')[0];
    firstScript.parentNode.insertBefore(script, firstScript);
})();
