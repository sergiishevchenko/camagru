(function() {
    var form = document.querySelector('.auth-form form');
    if (!form) return;

    var errorContainer = form.closest('.auth-form').querySelector('.error');
    var successContainer = form.closest('.auth-form').querySelector('.success');
    var authLink = form.closest('.auth-form').querySelector('.auth-link');

    function showError(msg) {
        if (errorContainer) {
            errorContainer.innerHTML = msg;
            errorContainer.style.display = 'block';
            if (successContainer) successContainer.style.display = 'none';
        } else {
            var div = document.createElement('div');
            div.className = 'error';
            div.textContent = msg;
            form.insertBefore(div, form.firstChild);
        }
    }

    function showErrors(messages) {
        var msg = Array.isArray(messages) ? '<ul><li>' + messages.join('</li><li>') + '</li></ul>' : messages;
        showError(msg);
    }

    function showSuccess(msg) {
        if (successContainer) {
            successContainer.innerHTML = msg;
            successContainer.style.display = 'block';
            if (errorContainer) errorContainer.style.display = 'none';
        } else {
            var div = document.createElement('div');
            div.className = 'success';
            div.innerHTML = msg;
            form.insertBefore(div, form.firstChild);
        }
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
                if (res.redirect) {
                    window.location.href = res.redirect;
                    return;
                }
                showSuccess(res.message || 'Success.');
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
