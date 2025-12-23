<?php
/**
 * Yandex Metrika
 *
 * Подключение счетчика Яндекс.Метрики
 * Через стандартные WordPress-хуки:
 *  - wp_head       — основной JS
 *  - wp_body_open  — noscript fallback
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Основной код Метрики (в <head>)
 */
add_action( 'wp_head', function () {
    ?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {
                if (document.scripts[j].src === r) { return; }
            }
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],
                k.async=1,k.src=r,a.parentNode.insertBefore(k,a);
        })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=105455340', 'ym');

        ym(105455340, 'init', {
            ssr: true,
            webvisor: true,
            clickmap: true,
            accurateTrackBounce: true,
            trackLinks: true
        });
    </script>
    <!-- /Yandex.Metrika counter -->
    <?php
}, 20 );

/**
 * Noscript-версия (сразу после <body>)
 */
add_action( 'wp_body_open', function () {
    ?>
    <!-- Yandex.Metrika noscript -->
    <noscript>
        <div>
            <img src="https://mc.yandex.ru/watch/105455340"
                 style="position:absolute; left:-9999px;"
                 alt="" />
        </div>
    </noscript>
    <!-- /Yandex.Metrika noscript -->
    <?php
});
