<script>
    (function() {
        const R_RET = {
            options: '{{ route('return.options') }}',
            store: '{{ route('return.store') }}',
            data: '{{ route('return.data.asset', $asset->uuid) }}',
            destroy: id => '{{ route('return.destroy', ':id') }}'.replace(':id', id),
        };

        const dtRet = $('#tbl-returns').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: R_RET.data,
                type: 'GET'
            },
            order: [
                [5, 'desc']
            ],
            dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                "<'table-responsive'tr>" +
                "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
            columns: [{
                    data: 'return_code',
                    name: 'return_history.return_code'
                },
                {
                    data: 'source_code',
                    name: 'return_history.source_code'
                },
                {
                    data: 'source_type_label',
                    name: 'return_history.source_type'
                },
                {
                    data: 'source_detail',
                    name: 'source_detail',
                    orderable: false,
                    searchable: true
                },
                {
                    data: 'note',
                    name: 'return_history.note',
                    render: d => d ? $('<div>').text(d).html() : ''
                },
                {
                    data: 'pic_request_uid',
                    name: 'return_history.pic_request_uid'
                },
                {
                    data: 'created_at',
                    name: 'return_history.created_at',
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
                },
                // {
                //     data: 'actions',
                //     name: 'actions',
                //     orderable: false,
                //     searchable: false,
                //     defaultContent: ''
                // }
            ]
        });

        function initReturnSelect() {
            $('#ret-source').select2({
                dropdownParent: $('#modal-return'),
                placeholder: 'Search code / asset…',
                width: '100%',
                allowClear: true,
                ajax: {
                    url: R_RET.options,
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || '',
                        asset_uuid: '{{ $asset->uuid }}'
                    }),
                    processResults: d => d
                }
            }).on('select2:select', function(e) {
                const m = e.params.data || {};
                $('#ret-preview').show();
                $('#ret-asset').text(m.asset_label || '');
                $('#ret-type').text((m.source_type === 'transfer' ? (m.type || '').toUpperCase() :
                    'DISPOSAL'));
                $('#ret-code').text(m.code || '');

                if (m.source_type === 'transfer') {
                    $('#ret-ba-wrap').show();
                    $('#ret-before').text(m.before_label || '');
                    $('#ret-after').text(m.after_label || '');
                } else {
                    $('#ret-ba-wrap').hide();
                    $('#ret-before').text('');
                    $('#ret-after').text('');
                }
            }).on('select2:clear', function() {
                $('#ret-preview').hide();
                $('#ret-asset,#ret-type,#ret-code,#ret-before,#ret-after').text('');
            });
        }

        $('#btnOpenReturn').on('click', function(e) {
            e.preventDefault();

            $('#formReturn')[0].reset();
            if ($('#ret-source').data('select2')) {
                $('#ret-source').val(null).trigger('change');
            } else {
                initReturnSelect();
            }
            $('#ret-preview').hide();
            $('#modal-return').modal('show');
        });

        // Create Return with Yes/No confirmation
        $('#formReturn').off('submit').on('submit', function(e) {
            e.preventDefault();

            const id = $('#ret-source').val();
            if (!id) {
                Swal.fire('Required', 'Please select an item.', 'warning');
                return;
            }

            const note = $('textarea[name="note"]').val() || '';

            // === YES / NO CONFIRMATION ===
            Swal.fire({
                title: 'Create this return?',
                text: 'This will record a return for this asset.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then(result => {
                if (!result.isConfirmed) return;

                // === Saving loader ===
                Swal.fire({
                    title: 'Saving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.post(R_RET.store, {
                    source: id,
                    note: note
                }).done(() => {
                    $('#modal-return').modal('hide');
                    Swal.fire('Success', 'Return recorded.', 'success');

                    if ($.fn.DataTable.isDataTable('#tbl-returns')) {
                        $('#tbl-returns').DataTable().ajax.reload(null, false);
                    }
                    if ($.fn.DataTable.isDataTable('#tbl-transfers')) {
                        $('#tbl-transfers').DataTable().ajax.reload(null, false);
                    }

                    // refresh asset header / status
                    location.reload();
                }).fail(x => {
                    Swal.fire('Error', x.responseJSON?.message || 'Failed to save',
                    'error');
                });
            });
        });

        $('#tbl-returns').on('click', '.btn-ret-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Delete this return?',
                text: 'It will go to trash.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6'
            }).then(res => {
                if (!res.isConfirmed) return;
                Swal.fire({
                    title: 'Deleting…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });
                $.ajax({
                        url: R_RET.destroy(id),
                        type: 'DELETE'
                    })
                    .done(() => {
                        Swal.fire('Deleted', '', 'success');
                        dtRet.ajax.reload(null, false);
                    })
                    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });
        });

    })();
</script>
