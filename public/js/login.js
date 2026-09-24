document.addEventListener("DOMContentLoaded", function () {

/* =====================================================
   ANIMASI UANG BERTABURAN
===================================================== */

const moneyContainer =
    document.getElementById("moneyContainer");

if (moneyContainer) {

    const moneyCount = 25;

    for (let i = 0; i < moneyCount; i++) {

        const money =
            document.createElement("div");

        money.classList.add("money");

        money.textContent = "Rp";

        /* Posisi horizontal acak */
        money.style.left =
            Math.random() * 100 + "%";

        /* Ukuran acak */
        const scale =
            0.65 + Math.random() * 0.65;

        money.style.scale = scale;

        /* Kecepatan acak */
        const duration =
            10 + Math.random() * 10;

        money.style.animationDuration =
            duration + "s";

        /* Membuat uang tidak jatuh bersamaan */
        money.style.animationDelay =
            -(Math.random() * duration) + "s";

        moneyContainer.appendChild(money);
    }
}

    /* =====================================================
       PASSWORD TOGGLE
    ===================================================== */

    const password =
        document.getElementById("password");

    const passwordToggle =
        document.getElementById("passwordToggle");

    if (password && passwordToggle) {

        passwordToggle.addEventListener(
            "click",
            function () {

                const icon =
                    passwordToggle.querySelector("i");

                if (password.type === "password") {

                    password.type = "text";

                    icon.classList.remove(
                        "bi-eye"
                    );

                    icon.classList.add(
                        "bi-eye-slash"
                    );

                } else {

                    password.type = "password";

                    icon.classList.remove(
                        "bi-eye-slash"
                    );

                    icon.classList.add(
                        "bi-eye"
                    );
                }
            }
        );
    }


    /* =====================================================
       REMEMBER ME
       (hanya mengisi ulang username, TIDAK menyimpan status login)
    ===================================================== */

    const username =
        document.getElementById("username");

    const rememberMe =
        document.getElementById("rememberMe");

    const savedUsername =
        localStorage.getItem(
            "eslip_username"
        );

    if (
        savedUsername &&
        username &&
        rememberMe
    ) {

        username.value =
            savedUsername;

        rememberMe.checked =
            true;
    }


    /* Kalau sesi login masih aktif (mis. user balik ke login.html
       lewat tombol back), langsung lempar ke dashboard */
    if (sessionStorage.getItem("eslip_logged_in") === "true") {
        window.location.replace("/dashboard");
        return;
    }


    /* =====================================================
       LOGIN
    ===================================================== */

    const loginForm =
        document.getElementById("loginForm");

    const loginButton =
        document.getElementById("loginButton");

    if (loginForm) {

        loginForm.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();

                const usernameValue =
                    username.value.trim();

                const passwordValue =
                    password.value.trim();

                if (!usernameValue) {

                    alert(
                        "Silakan masukkan username."
                    );

                    username.focus();

                    return;
                }

                if (!passwordValue) {

                    alert(
                        "Silakan masukkan password."
                    );

                    password.focus();

                    return;
                }


                if (rememberMe.checked) {

                    localStorage.setItem(
                        "eslip_username",
                        usernameValue
                    );

                } else {

                    localStorage.removeItem(
                        "eslip_username"
                    );
                }


                loginButton.disabled = true;

                const text =
                    loginButton.querySelector("span");

                const icon =
                    loginButton.querySelector("i");

                text.textContent =
                    "Memproses...";

                icon.className =
                    "bi bi-arrow-repeat";


                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content');

                fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: JSON.stringify({
                        username: usernameValue,
                        password: passwordValue,
                    }),
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok) {
                            const pesan =
                                (result.data.errors &&
                                    Object.values(result.data.errors)[0][0]) ||
                                result.data.message ||
                                'Username/email atau password salah.';
                            alert(pesan);

                            loginButton.disabled = false;
                            text.textContent = 'Login';
                            icon.className = 'bi bi-arrow-right';
                            return;
                        }

                        window.location.href = result.data.redirect || '/dashboard';
                    })
                    .catch(function () {
                        alert('Tidak dapat menghubungi server. Coba lagi.');

                        loginButton.disabled = false;
                        text.textContent = 'Login';
                        icon.className = 'bi bi-arrow-right';
                    });
            }
        );
    }


    /* =====================================================
       FORGOT PASSWORD
    ===================================================== */

    const forgotPassword =
        document.getElementById("forgotPassword");

    if (forgotPassword) {

        forgotPassword.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                alert(
                    "Silakan hubungi administrator untuk reset password."
                );
            }
        );
    }

});