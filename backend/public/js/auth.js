(function() {
    var form = document.querySelector('.auth-form form');
    if (!form) return;

    var authLink = form.closest('.auth-form').querySelector('.auth-link');

    function getOrCreateContainer(cls) {
        var c = form.closest('.auth-form').querySelector('.' + cls);
        if (!c) {
            c = document.createElement('div');
            c.className = cls;
            c.style.display = 'none';
            form.parentNode.insertBefore(c, form);
        }
        return c;
    }

    function showError(msg) {
        var el = getOrCreateContainer('error');
        el.textContent = msg;
        el.style.display = 'block';
        var s = form.closest('.auth-form').querySelector('.success');
        if (s) s.style.display = 'none';
    }

    function showErrors(messages) {
        if (!Array.isArray(messages)) {
            showError(messages);
            return;
        }
        var el = getOrCreateContainer('error');
        el.textContent = '';
        var ul = document.createElement('ul');
        for (var i = 0; i < messages.length; i++) {
            var li = document.createElement('li');
            li.textContent = messages[i];
            ul.appendChild(li);
        }
        el.appendChild(ul);
        el.style.display = 'block';
        var s = form.closest('.auth-form').querySelector('.success');
        if (s) s.style.display = 'none';
    }

    function showSuccess(msg) {
        var el = getOrCreateContainer('success');
        el.textContent = msg;
        el.style.display = 'block';
        var e = form.closest('.auth-form').querySelector('.error');
        if (e) e.style.display = 'none';
        return el;
    }

    function getCSRFToken() {
        var input = form.querySelector('input[name="csrf_token"]');
        return input ? input.value : (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    }

    function getFormData() {
        var data = {};
        var inputs = form.querySelectorAll('input:not([type="submit"]):not([type="button"])');
        for (var i = 0; i < inputs.length; i++) {
            var el = inputs[i];
            if (el.name && (el.type !== 'radio' || el.checked) && (el.type !== 'checkbox' || el.checked)) {
                data[el.name] = el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value;
            }
        }
        return data;
    }

    function ajaxNavigate(url) {
        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            document.open();
            document.write(html);
            document.close();
            try {
                window.history.replaceState({}, '', url);
            } catch (e) {}
        })
        .catch(function() {
            window.location.href = url;
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var token = getCSRFToken();
        if (!token) {
            showError('Invalid request');
            return;
        }
        var data = getFormData();
        data.csrf_token = token;
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                var successText = res.message || 'Success.';
                if (res.redirect) {
                    successText = 'Success. You are now authenticated.';
                }
                showSuccess(successText);
                form.reset();
                if (submitBtn) submitBtn.disabled = false;
                if (res.redirect) {
                    setTimeout(function() {
                        ajaxNavigate(res.redirect);
                    }, 250);
                }
            } else {
                if (res.errors && res.errors.length) {
                    showErrors(res.errors);
                } else {
                    showError(res.error || 'An error occurred.');
                }
                if (submitBtn) submitBtn.disabled = false;
            }
        })
        .catch(function() {
            showError('Network error. Please try again.');
            if (submitBtn) submitBtn.disabled = false;
        });
    });
})();
