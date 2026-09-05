function initBookAuthorSelect() {
    var el = document.getElementById('bookform-authorids');
    if (!el || typeof TomSelect === 'undefined' || !window.bookAuthorSearchUrl) {
        return;
    }
    if (el.tomselect) {
        return;
    }

    new TomSelect(el, {
        plugins: ['remove_button'],
        persist: false,
        maxItems: null,
        valueField: 'id',
        labelField: 'text',
        searchField: 'text',
        placeholder: 'Search authors',
        hideSelected: true,
        closeAfterSelect: false,
        loadThrottle: 200,
        preload: 'focus',
        load: function (query, callback) {
            var self = this;
            if (self._authorAbort) {
                self._authorAbort.abort();
            }
            self._authorAbort = new AbortController();
            var url = window.bookAuthorSearchUrl + (window.bookAuthorSearchUrl.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(query);
            fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                signal: self._authorAbort.signal,
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Search failed');
                    }
                    return response.json();
                })
                .then(function (items) {
                    self.clearOptions();
                    callback(items);
                })
                .catch(function () {
                    callback();
                });
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBookAuthorSelect);
} else {
    initBookAuthorSelect();
}
