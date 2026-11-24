{{-- CSS + JS halaman Profile (ATPM & Dealer). Parameter: $emailUrl, $passwordUrl --}}
<style>
    .afsel-ui .pf-head { display: flex; align-items: center; gap: 14px; padding-bottom: 16px; margin-bottom: 14px; border-bottom: 1px solid var(--mz-line); }
    .afsel-ui .pf-avatar {
        flex: 0 0 56px; width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        background: var(--mz-ink); color: #fff; font-weight: 700; font-size: 1.15rem; box-shadow: inset 0 -3px 0 var(--mz-red);
    }
    .afsel-ui .pf-name { font-weight: 700; font-size: 1.05rem; color: var(--mz-ink); word-break: break-word; }
    .afsel-ui .pf-list { display: grid; grid-template-columns: 140px 1fr; gap: 10px 12px; margin: 0; font-size: 13px; }
    .afsel-ui .pf-list dt { font-weight: 600; color: var(--mz-muted); }
    .afsel-ui .pf-list dd { margin: 0; color: var(--mz-ink); word-break: break-word; }
    @media (max-width: 576px) {
        .afsel-ui .pf-list { grid-template-columns: 1fr; gap: 2px; }
        .afsel-ui .pf-list dd { margin-bottom: 8px; }
    }
</style>

<script>
    $(function () {
        const csrf = '{{ csrf_token() }}';

        const clearErrors = form => {
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        };
        const showErrors = (form, errors) => {
            Object.entries(errors || {}).forEach(([name, msgs]) => {
                const el = form.querySelector(`[name="${name}"]`);
                if (!el) return;
                el.classList.add('is-invalid');
                const fb = el.parentElement.querySelector('.invalid-feedback');
                if (fb) fb.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
            });
        };

        const submit = (form, url, confirmText, onSuccess) => {
            clearErrors(form);
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const data = Object.fromEntries(new FormData(form).entries());

            Swal.fire({
                title: 'Konfirmasi', text: confirmText, icon: 'question',
                showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal', confirmButtonColor: '#1b2027',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                preConfirm: () => axios.put(url, data, { headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } })
                    .then(res => res.data)
                    .catch(err => {
                        const d = err.response?.data;
                        showErrors(form, d?.errors);
                        Swal.showValidationMessage(err.response?.status === 429
                            ? 'Terlalu banyak percobaan. Silakan tunggu 1 menit.'
                            : (d?.message || 'Terjadi kesalahan. Silakan coba lagi.'));
                        return false;
                    }),
            }).then(result => {
                if (!result.isConfirmed || !result.value) return;
                onSuccess(data);
                Swal.fire({ title: 'Berhasil', text: result.value.message, icon: 'success', timer: 2000, showConfirmButton: false });
            });
        };

        const formEmail = document.getElementById('form-email');
        formEmail.addEventListener('submit', () => submit(formEmail, @json($emailUrl), 'Simpan email baru?', data => {
            $('#pf-email').text(data.email);
            formEmail.current_password.value = '';
        }));

        const formPassword = document.getElementById('form-password');
        formPassword.addEventListener('submit', () => {
            if (formPassword.password.value !== formPassword.password_confirmation.value) {
                clearErrors(formPassword);
                showErrors(formPassword, { password_confirmation: 'Konfirmasi password baru tidak sama.' });
                return;
            }
            submit(formPassword, @json($passwordUrl), 'Ubah password akun Anda?', () => formPassword.reset());
        });

        $('#show-password').on('change', function () {
            formPassword.querySelectorAll('input[type="password"], input[data-pw]').forEach(el => {
                el.dataset.pw = '1';
                el.type = this.checked ? 'text' : 'password';
            });
        });
    });
</script>
