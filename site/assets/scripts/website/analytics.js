window.VF_ANALYTICS = {
    ymCounterId: '105455340',
};

// Выбор посетителя в уведомлении о cookie (LEGAL_RULES.md, раздел 7). Скрипт подключён
// первым defer-скриптом в <head>: metrika.js, bitrix24.js и navigation.js читают выбор
// только отсюда. Необязательные сервисы (Метрика, чат, источник перехода) работают
// лишь при выборе 'all'; без выбора и при 'necessary' не загружаются.
window.VF_CONSENT = (() => {
    const storageKey = 'vf_cookie_choice';
    const eventName = 'vf:cookie-choice';

    // Ключ прежнего баннера (vf_cookie_notice_accepted) согласием не считается: тот баннер
    // не давал отказаться, Метрика грузилась в любом случае. Такой посетитель выбирает заново.
    const choice = () => {
        try {
            const stored = JSON.parse(window.localStorage.getItem(storageKey));
            if (stored && (stored.choice === 'all' || stored.choice === 'necessary')
                && typeof stored.expires === 'number' && stored.expires > Date.now()) {
                return stored.choice;
            }
        } catch (error) {
            // Хранилище недоступно: выбора нет, необязательные сервисы не загружаются.
        }

        return null;
    };

    const accepted = () => choice() === 'all';

    // Вызывает callback сразу, если «Принять» уже выбрано, иначе -- один раз после выбора.
    const whenAccepted = (callback) => {
        if (accepted()) {
            callback();

            return;
        }

        const listener = (event) => {
            if (event.detail && event.detail.choice === 'all') {
                document.removeEventListener(eventName, listener);
                callback();
            }
        };
        document.addEventListener(eventName, listener);
    };

    return { storageKey, eventName, choice, accepted, whenAccepted };
})();
