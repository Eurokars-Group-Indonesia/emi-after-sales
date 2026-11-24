{{-- Helper request JSON + konfirmasi SweetAlert untuk halaman Access Control --}}
<script>
    window.afselRequest = function (opts) {
        const o = Object.assign({ method: 'post', data: {}, confirm: 'Simpan perubahan?', confirmIcon: 'question', reload: true }, opts);

        return Swal.fire({
            title: 'Konfirmasi',
            text: o.confirm,
            icon: o.confirmIcon,
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal',
            // Warna mengikuti tema halaman (After Sales: Soul Red/grafit, Sales: biru) lewat CSS variable
            confirmButtonColor: (getComputedStyle(document.body).getPropertyValue(o.confirmIcon === 'warning' ? '--mz-red' : '--mz-ink').trim()
                || (o.confirmIcon === 'warning' ? '#a6192e' : '#1b2027')),
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: () => axios({
                method: o.method,
                url: o.url,
                data: o.data,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            .then(res => {
                if (res.data && res.data.status === false) {
                    Swal.showValidationMessage(res.data.message || 'Gagal menyimpan data.');
                    return false;
                }
                return res.data;
            })
            .catch(err => {
                Swal.showValidationMessage(err.response?.data?.message || 'Terjadi kesalahan. Silakan coba lagi.');
                return false;
            }),
        }).then(result => {
            if (!result.isConfirmed || !result.value) {
                return null;
            }
            return Swal.fire({ title: 'Berhasil', text: result.value.message, icon: 'success', timer: 1600, showConfirmButton: false })
                .then(() => {
                    if (o.reload) {
                        window.location.reload();
                    }
                    return result.value;
                });
        });
    };

    /** Ambil nilai form menjadi object (checkbox → 1/0). */
    window.afselFormData = function (form) {
        const data = {};
        form.querySelectorAll('input, select, textarea').forEach(el => {
            if (!el.name || el.disabled) return;
            if (el.type === 'checkbox') {
                data[el.name] = el.checked ? 1 : 0;
            } else {
                data[el.name] = el.value === '' ? null : el.value;
            }
        });
        return data;
    };
</script>
