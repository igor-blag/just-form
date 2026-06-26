(function () {
    'use strict';

    var loadTime = Date.now();

    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('.jf-form');

        forms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                submitForm(form);
            });
        });
    });

    function submitForm(form) {
        var btn = form.querySelector('.jf-submit');
        var response = form.querySelector('.jf-response');
        var originalText = btn.textContent;

        btn.disabled = true;
        btn.textContent = 'Отправка...';
        response.textContent = '';
        response.className = 'jf-response';

        var formData = new FormData(form);
        formData.append('action', 'just_form_submit');
        formData.append('jf_nonce', JF.nonce);
        formData.append('jf_load_time', loadTime.toString());

        fetch(JF.ajaxUrl, {
            method: 'POST',
            body: formData,
        })
        .then(function (res) {
            return res.json();
        })
        .then(function (data) {
            if (data.success) {
                form.reset();
                response.className = 'jf-response jf-success';
            } else {
                response.className = 'jf-response jf-error';
            }
            var msg = data.data ? data.data.message : data.message;
            response.textContent = msg || 'Неизвестная ошибка';
        })
        .catch(function () {
            response.className = 'jf-response jf-error';
            response.textContent = 'Ошибка соединения. Проверьте интернет и попробуйте снова.';
        })
        .finally(function () {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }
})();
