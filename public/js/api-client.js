/* =========================================================
   js/api-client.js
   Helper fetch() bersama untuk seluruh halaman E-Slip Gaji.
   Menyertakan header CSRF otomatis (meta[name="csrf-token"])
   dan melempar Error berisi pesan dari server saat gagal.
   ========================================================= */
window.Api = (function () {
  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function request(method, url, body) {
    var opts = {
      method: method,
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
    };

    if (body instanceof FormData) {
      opts.body = body;
    } else if (body !== undefined) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }

    return fetch(url, opts).then(function (response) {
      if (response.status === 204) return null;

      return response.json().catch(function () { return null; }).then(function (data) {
        if (!response.ok) {
          var message =
            (data && data.message) ||
            (data && data.errors && Object.values(data.errors)[0][0]) ||
            'Terjadi kesalahan (' + response.status + ').';
          var err = new Error(message);
          err.status = response.status;
          err.data = data;
          throw err;
        }
        return data;
      });
    });
  }

  return {
    get: function (url) { return request('GET', url); },
    post: function (url, body) { return request('POST', url, body); },
    put: function (url, body) { return request('PUT', url, body); },
    patch: function (url, body) { return request('PATCH', url, body); },
    delete: function (url) { return request('DELETE', url); },
  };
})();
