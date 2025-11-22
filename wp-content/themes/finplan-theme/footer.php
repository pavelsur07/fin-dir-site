<footer class="site-footer">
    <div class="site-footer-inner">
        © <?php echo date('Y'); ?> Ваш ФинДиректор — сервис финансового учёта для бизнеса.
    </div>
</footer>
<form
        action="https://chat.2bstock.ru/api/crm/web-forms/B1RaSdSUEJLt3_Y6vQZJA6b3/submit"
        method="post"
        class="vf-lead-form"
>
    <input type="hidden" name="page_url" value="">
    <input type="hidden" name="utm_source" value="">
    <input type="hidden" name="utm_medium" value="">
    <input type="hidden" name="utm_campaign" value="">

    <label>
        Ваше имя*
        <input type="text" name="name" required>
    </label>

    <label>
        Телефон*
        <input type="tel" name="phone" required>
    </label>

    <label>
        Комментарий
        <textarea name="comment"></textarea>
    </label>

    <button type="submit">Отправить</button>
</form>
<script>
    (function () {
        var f = document.querySelector('.vf-lead-form');
        if (!f) return;
        var params = new URLSearchParams(window.location.search);
        f.querySelector('input[name="page_url"]').value = window.location.href;
        ['utm_source','utm_medium','utm_campaign'].forEach(function (k) {
            var v = params.get(k);
            if (v) f.querySelector('input[name="'+k+'"]').value = v;
        });
    })();
</script>

<?php wp_footer(); ?>
</body>
</html>
