<script>
    (function() {
        const ACQ = {
            latest: '{{ route('acquisition.latest', $asset->uuid) }}',
            store: '{{ route('acquisition.store', $asset->uuid) }}',
            data: '{{ route('acquisition.data', $asset->uuid) }}',
        };

        // Lock / unlock fields based on existing acquisition
        function setExistingMode(isExisting) {
            const lockInputs = [
                'input[name="price"]',
                'input[name="useful_life_month"]',
                'input[name="actual_date"]',
                'input[name="capitalization_date"]',
                '#nilai_pajak'
            ];

            lockInputs.forEach(sel => {
                const $el = $(sel);
                $el.prop('readonly', isExisting);
                $el.toggleClass('bg-light', isExisting);
            });

            $('select[name="is_pajak"]')
                .prop('disabled', isExisting)
                .toggleClass('bg-light', isExisting);

            // quantity & note always editable
            $('input[name="quantity"]').prop('readonly', false).removeClass('bg-light');
            $('textarea[name="note"]').prop('readonly', false).removeClass('bg-light');
            $('#acq-uom').prop('disabled', false);
        }

        function clearAcqForm() {
            if ($('#formAcq')[0]) {
                $('#formAcq')[0].reset();
            }
            if ($('#acq-uom').data('select2')) {
                $('#acq-uom').val(null).trigger('change');
            }
            setExistingMode(false);
        }

        // Open Modal & Prefill from latest acquisition
        $('#btnOpenAcq').on('click', function(e) {
            e.preventDefault();

            clearAcqForm();

            $.getJSON(ACQ.latest)
                .done(res => {
                    const today = new Date().toISOString().slice(0, 10);

                    if (!res || !res.value) {
                        // no data at all → treat as new, fully editable
                        $('input[name="actual_date"]').val(today);
                        $('input[name="capitalization_date"]').val(today);
                        setExistingMode(false);
                        $('#modal-acq').modal('show');
                        return;
                    }

                    const v = res.value;

                    $('input[name="quantity"]').val(v.quantity ?? '');
                    $('input[name="price"]').val(v.price ?? '');
                    $('input[name="useful_life_month"]').val(v.useful_life_month ?? '');
                    $('select[name="is_pajak"]').val((v.is_pajak ?? 0) ? '1' : '0');

                    if (typeof v.nilai_pajak !== 'undefined' && v.nilai_pajak !== null) {
                        $('#nilai_pajak').val(v.nilai_pajak);
                    }

                    if (v.actual_date) {
                        $('input[name="actual_date"]').val(String(v.actual_date).substring(0, 10));
                    } else {
                        $('input[name="actual_date"]').val(today);
                    }

                    if (v.capitalization_date) {
                        $('input[name="capitalization_date"]').val(String(v.capitalization_date)
                            .substring(0, 10));
                    } else {
                        $('input[name="capitalization_date"]').val(today);
                    }

                    const kode = v.kode_uom;
                    const text = v.uom_text || kode;
                    if (kode) {
                        const opt = new Option(text, kode, true, true);
                        $('#acq-uom').append(opt).trigger('change');
                    }

                    // if total == 0 or null → editable
                    // if total != 0 → disabled (existing acquisition already applied)
                    const totalRaw = v.total;
                    const shouldLock =
                        totalRaw !== null &&
                        totalRaw !== undefined &&
                        Number(totalRaw) !== 0;

                    setExistingMode(shouldLock);

                    $('#modal-acq').modal('show');
                })
                .fail(() => {
                    const today = new Date().toISOString().slice(0, 10);
                    $('input[name="actual_date"]').val(today);
                    $('input[name="capitalization_date"]').val(today);
                    setExistingMode(false);
                    $('#modal-acq').modal('show');
                });
        });

        // UOM Select2
        $('#acq-uom').select2({
            dropdownParent: $('#modal-acq'),
            placeholder: 'Select UOM…',
            width: '100%',
            allowClear: true,
            ajax: {
                url: '{{ route('master.uom.options') }}',
                dataType: 'json',
                delay: 150,
                data: params => ({
                    q: params.term || '',
                    page: params.page || 1
                }),
                processResults: d => d
            }
        });

        // === Submit Acquisition with YES/NO Swal ===
        $('#formAcq').off('submit').on('submit', function(e) {
            e.preventDefault();

            const payload = {
                quantity: $('input[name="quantity"]').val(),
                kode_uom: $('#acq-uom').val(),
                price: $('input[name="price"]').val(),
                useful_life_month: $('input[name="useful_life_month"]').val(),
                is_pajak: $('select[name="is_pajak"]').val(),
                actual_date: $('input[name="actual_date"]').val(),
                capitalization_date: $('input[name="capitalization_date"]').val(),
                nilai_pajak: $('input[name="nilai_pajak"]').val(),
                note: $('textarea[name="note"]').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            Swal.fire({
                title: 'Save this acquisition?',
                text: 'This will update the acquisition data for this asset.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then(result => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Saving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.post(ACQ.store, payload)
                    .done(() => {
                        $('#modal-acq').modal('hide');
                        Swal.fire('Saved', 'Acquisition saved.', 'success');

                        if ($.fn.DataTable.isDataTable('#tbl-acq')) {
                            $('#tbl-acq').DataTable().ajax.reload(null, false);
                        }

                        // refresh summary/value
                        setTimeout(() => location.reload(), 400);
                    })
                    .fail(x => {
                        Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                    });
            });
        });

        // Acquisition History DataTable
        const tblAcq = $('#tbl-acq').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: ACQ.data,
                type: 'GET'
            },
            order: [[3, 'desc']], // created_at desc
            dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                "<'table-responsive'tr>" +
                "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
            columns: [
                { data: 'acq_code', name: 'acq_code' },
                { data: 'detail', name: 'detail', orderable: false, searchable: true },
                {
                    data: 'note',
                    name: 'note',
                    render: d => d ? $('<div>').text(d).html() : ''
                },
                { data: 'pic_request_uid', name: 'pic_request_uid' },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function(iso, type) {
                        if (!iso) return '';
                        if (type === 'sort' || type === 'type') return iso;

                        const d = new Date(iso);
                        const dateStr = new Intl.DateTimeFormat('en-GB', {
                            timeZone: 'Asia/Jakarta',
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric'
                        }).format(d);
                        const timeStr = new Intl.DateTimeFormat('en-GB', {
                            timeZone: 'Asia/Jakarta',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d);

                        return `${dateStr} ${timeStr}`;
                    }
                }
            ]
        });

        // Delete Acquisition Row
        $('#tbl-acq').on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            if (!id) return;

            Swal.fire({
                title: 'Delete?',
                text: 'Moved to trash',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6'
            }).then(r => {
                if (!r.isConfirmed) return;

                Swal.fire({
                    title: 'Deleting…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.ajax({
                    url: '{{ route('acquisition.destroy', ':id') }}'.replace(':id', id),
                    type: 'DELETE'
                })
                .done(() => {
                    Swal.fire('Deleted', '', 'success');
                    tblAcq.ajax.reload(null, false);
                })
                .fail(x => {
                    Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                });
            });
        });

    })();
</script>
