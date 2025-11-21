<script>
    (function() {
        const R = {
            usercode: '{{ route('master.user_code.options') }}',
            status: '{{ route('master.status.options') }}',
            location: '{{ route('master.location.options') }}',
            transfers: '{{ route('transfer.data', $asset->uuid) }}',
            create: '{{ route('transfer.store') }}',
            show: id => '{{ route('transfer.show', ':id') }}'.replace(':id', id),
            approveStep: id => '{{ route('transfer.approve-step', ':id') }}'.replace(':id', id),
        };
        const ROUTE = {
            update: id => '{{ route('transfer.update', ':id') }}'.replace(':id', id),
            destroy: id => '{{ route('transfer.destroy', ':id') }}'.replace(':id', id),
            approve: id => '{{ route('transfer.approve', ':id') }}'.replace(':id', id),
            reject: id => '{{ route('transfer.reject', ':id') }}'.replace(':id', id),
        };

        // we need this to know when asset_mgt can send signed_form
        let currentFlowType = null; // owner/user/maintenance/location

        // ========== OPEN CREATE ==========
        $('#btnOpenTransfer').on('click', function(e) {
            e.preventDefault();
            $('#formTransfer')[0].reset();
            $('#formTransfer').removeData('edit-id');
            $('#tf-type').val('owner');
            $('#tf-target-hidden').val('');
            $('#tf-current-file').addClass('d-none');
            $('#tf-remove-file').val('0');
            const fileInput = document.getElementById('tf-file');
            if (fileInput) fileInput.value = '';

            initTargetSelect('owner');
            $('#modal-transfer').modal('show');
        });

        // ========== SELECT2 HELPERS ==========
        function initSelect2(el, url, extra = {}) {
            $(el).empty().select2({
                dropdownParent: $('#modal-transfer'),
                placeholder: 'Select...',
                width: '100%',
                allowClear: true,
                ajax: {
                    url,
                    dataType: 'json',
                    delay: 150,
                    data: function(params) {
                        const base = {
                            q: params.term || '',
                            page: params.page || 1
                        };
                        if (typeof extra === 'function') return Object.assign(base, extra());
                        if (extra && typeof extra === 'object') return Object.assign(base, extra);
                        return base;
                    },
                    processResults: data => data
                }
            }).on('select2:select', function(e) {
                const id = e.params.data.id;
                if (this.id === 'tf-target') {
                    $('#tf-target-hidden').val(id);
                }
            });
        }

        function initTargetSelect(type) {
            $('#tf-target').off('select2:select').val(null).trigger('change');
            $('#tf-target-hidden').val('');

            if (type === 'owner' || type === 'user' || type === 'maintenance') {
                initSelect2('#tf-target', R.usercode);
                $('#tf-target-help').text('Pick a User Code (department) as new ' + type + '.');
            } else if (type === 'status') {
                initSelect2('#tf-target', R.status, {
                    type: 'Asset'
                });
                $('#tf-target-help').text('Pick the new Asset Status (type = Asset).');
            } else if (type === 'location') {
                initSelect2('#tf-target', R.location);
                $('#tf-target-help').text('Pick the new Location.');
            }
        }

        $('#tf-type').on('change', function() {
            initTargetSelect(this.value);
        });

        // ========== DATATABLE (ASSET HISTORY) ==========
        const dt = $('#tbl-transfers').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: R.transfers,
                type: 'GET'
            },
            order: [
                [8, 'desc']
            ],
            dom: "<'row mb-2'" +
                "<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>" +
                ">" +
                "<'table-responsive'tr>" +
                "<'row'" +
                "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
                ">",
            searching: true,
            columns: [{
                    data: 'transfer_code',
                    name: 'transfer_code'
                },
                {
                    data: 'type',
                    name: 'type',
                    render: d => (d || '').toUpperCase()
                },
                {
                    data: 'before_display',
                    name: 'before_display'
                },
                {
                    data: 'after_display',
                    name: 'after_display'
                },
                {
                    data: 'pic_request_uid',
                    name: 'pic_request_uid'
                },
                {
                    data: 'pic_approve_uid',
                    name: 'pic_approve_uid',
                    defaultContent: ''
                },
                {
                    data: 'note',
                    name: 'note',
                    render: d => (d ? $('<div>').text(d).html() : '')
                },
                {
                    data: 'workflow_label',
                    name: 'workflow_label',
                    render: function(data, type, row) {
                        if (row.kode_status == 'APR') {
                            return `<span class="badge badge-light-primary">${data || ''}</span>`;
                        } else if (row.kode_status == 'ACC') {
                            return `<span class="badge badge-light-success">${data || ''}</span>`;
                        } else {
                            return `<span class="badge badge-light-danger">${data || ''}</span>`;
                        }
                    }
                },
                {
                    data: 'flow_label',
                    name: 'flow_label',
                    orderable: false,
                    searchable: false,
                    render: function(data, type) {
                        if (type !== 'display') return data;
                        return data || '';
                    }
                },
                {
                    data: 'file',
                    name: 'file',
                    defaultContent: ''
                },
                {
                    data: 'updated_at',
                    name: 'updated_at',
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
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // ========== EDIT (from row) ==========
        $('#tbl-transfers').on('click', '.btn-tf-edit', function() {
            const row = dt.row($(this).closest('tr')).data();
            if (!row) return;

            $('#modal-transfer').modal('show');
            $('#formTransfer').data('edit-id', row.uuid);
            $('#tf-type').val(row.type).trigger('change');

            if (row.file_url) {
                $('#tf-current-file').removeClass('d-none');
                $('#tf-current-file-link').attr('href', row.file_url).text(row.file_name || 'Current file');
                $('#tf-remove-file').val('0');
            } else {
                $('#tf-current-file').addClass('d-none');
                $('#tf-remove-file').val('0');
            }

            $('#btn-remove-file').off('click').on('click', function() {
                $('#tf-remove-file').val('1');
                $('#tf-current-file').addClass('d-none');
            });

            // we only know after_code from row
            setTimeout(() => {
                const code = row.after_code || '';
                const opt = new Option(code, code, true, true);
                $('#tf-target').append(opt).trigger('change');
                $('#tf-target-hidden').val(code);
            }, 300);

            $('textarea[name="note"]').val(row.note || '');
        });

        // ========== CREATE / UPDATE SUBMIT ==========
        $('#formTransfer').off('submit').on('submit', function(e) {
            e.preventDefault();

            const isEdit = !!$('#formTransfer').data('edit-id');
            const id = $('#formTransfer').data('edit-id');

            const baseFields = {
                asset_uuid: '{{ $asset->uuid }}',
                type: $('#tf-type').val(),
                note: $('textarea[name="note"]').val() || '',
                'after[value]': $('#tf-target-hidden').val() || '',
                remove_file: $('#tf-remove-file').length ? ($('#tf-remove-file').val() || 0) : 0
            };

            if (!baseFields['after[value]']) {
                Swal.fire('Target required', 'Please select a movement target.', 'warning');
                return;
            }

            const fileInput = document.getElementById('tf-file');
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

            let req;
            if (hasFile) {
                const fd = new FormData();
                Object.entries(baseFields).forEach(([k, v]) => fd.append(k, v));
                fd.append('file', fileInput.files[0]);

                const url = isEdit ? ROUTE.update(id) : '{{ route('transfer.store', $asset->uuid) }}';
                if (isEdit) fd.append('_method', 'PUT');

                req = $.ajax({
                    url,
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                });
            } else {
                const payload = baseFields;
                req = isEdit ?
                    $.ajax({
                        url: ROUTE.update(id),
                        type: 'PUT',
                        data: payload
                    }) :
                    $.post('{{ route('transfer.store', $asset->uuid) }}', payload);
            }

            Swal.fire({
                title: 'Saving…',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            req.done(() => {
                $('#modal-transfer').modal('hide');
                Swal.fire('Success', isEdit ? 'Movement updated.' : 'Movement created.', 'success');
                dt.ajax.reload(null, false);
                if (fileInput) fileInput.value = '';
                if ($('#tf-remove-file').length) $('#tf-remove-file').val('0');
            }).fail(x => {
                Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
            });
        });

        $('#btn-remove-file').off('click').on('click', function() {
            $('#tf-remove-file').val('1');
            $('#tf-current-file').addClass('d-none');
        });

        // ========== APPROVE / REJECT / DELETE ==========
        $('#tbl-transfers').on('click', '.btn-tf-approve', function() {
            const id = $(this).data('id');
            const row = dt.row($(this).closest('tr')).data();
            if (!row) return;

            const type = (row.type || '').toLowerCase();

            if (['owner', 'user', 'maintenance', 'location'].includes(type)) {
                // use multi-step flow modal
                $('#tf-approve-id').val(id);

                $.get(R.show(id))
                    .done(d => {
                        currentFlowType = (d.type || '').toLowerCase();
                        const titleType = (d.type || '').toUpperCase();
                        $('.modal-title', '#modal-transfer-approve')
                            .text('Approve Movement ' + titleType);
                        renderApproveFlowModal(d);
                        $('#modal-transfer-approve').modal('show');
                    })
                    .fail(() => {
                        Swal.fire('Error', 'Cannot load transfer', 'error');
                    });
            } else {
                // simple approve (e.g. status)
                Swal.fire({
                    title: 'Approve this movement?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Approve',
                    cancelButtonText: 'Cancel'
                }).then(res => {
                    if (!res.isConfirmed) return;
                    Swal.fire({
                        title: 'Applying…',
                        didOpen: () => Swal.showLoading(),
                        allowOutsideClick: false
                    });
                    $.post(ROUTE.approve(id)).done(() => {
                        Swal.fire({
                            title: 'Approved',
                            text: 'Movement applied to asset.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 500,
                            timerProgressBar: true
                        }).then(() => {
                            location.reload();
                        });
                    }).fail(x => {
                        Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                    });
                });
            }
        });

        $('#tbl-transfers').on('click', '.btn-tf-reject', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Reject this movement?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6'
            }).then(res => {
                if (!res.isConfirmed) return;
                Swal.fire({
                    title: 'Updating…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });
                $.post(ROUTE.reject(id)).done(() => {
                    Swal.fire('Rejected', 'Movement rejected.', 'success');
                    dt.ajax.reload(null, false);
                }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });
        });

        $('#tbl-transfers').on('click', '.btn-tf-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Delete this movement?',
                text: 'It will go to trash.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
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
                    url: ROUTE.destroy(id),
                    type: 'DELETE'
                }).done(() => {
                    Swal.fire('Deleted', 'Request moved to trash.', 'success');
                    dt.ajax.reload(null, false);
                }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });
        });

        // ========== FLOW MODAL (SAME LOGIC AS MOVEMENT PAGE) ==========
        function renderApproveFlowModal(data) {
            const container = $('#tf-flow-steps').empty();

            let flow = data && data.flow ? data.flow : data;
            if (typeof flow === 'string') {
                try {
                    flow = JSON.parse(flow);
                } catch (e) {
                    flow = {};
                }
            }

            const steps = Array.isArray(flow?.steps) ? flow.steps : [];
            if (!steps.length) {
                container.append('<div class="text-muted">No flow defined.</div>');
                return;
            }

            // find first pending index
            let nextIdx = null;
            steps.forEach((s, idx) => {
                if (!s.approved_at && nextIdx === null) {
                    nextIdx = idx;
                }
            });

            steps.forEach((step, idx) => {
                const approvedAt = step.approved_at;
                const approvedBy = step.approved_by;
                const isPending = !approvedAt;
                const isActiveStep = isPending && idx === nextIdx;
                const stepCode = step.code || '';
                const isAssetMgt = (stepCode === 'asset_mgt');
                const allowSignedForm =
                    isAssetMgt && ['owner', 'user', 'maintenance'].includes(currentFlowType);

                const statusBadge = approvedAt ?
                    `<span class="badge badge-light-success">Approved</span>` :
                    `<span class="badge badge-light-warning">${isActiveStep ? 'Pending (current)' : 'Pending'}</span>`;

                const approverInfo = approvedAt ?
                    `<div class="small text-muted">by ${escapeHtml(approvedBy || '-')} at ${escapeHtml(approvedAt)}</div>` :
                    '';

                const btn = isPending ?
                    `<button type="button"
                               class="btn btn-sm btn-danger mt-2 btn-flow-approve-step"
                               data-step-code="${escapeHtml(stepCode)}"
                               ${isActiveStep ? '' : 'disabled'}>
                           Approved by ${escapeHtml(step.role || step.label || 'Role')}
                       </button>` :
                    '';

                let extra = '';
                if (allowSignedForm) {
                    extra = `
                        <div class="mt-3">
                            <label class="form-label">Signed transfer form (optional)</label>
                            <input type="file"
                                   class="form-control tf-signed-form-input"
                                   accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv,.txt"
                                   ${isActiveStep ? '' : 'disabled'}>
                            <div class="form-text">Upload signed asset transfer form.</div>
                        </div>
                    `;
                }

                container.append(`
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">${escapeHtml(step.label || 'Step')}</div>
                                <div class="small text-muted">${escapeHtml(step.role || '')}</div>
                                ${approverInfo}
                            </div>
                            <div>${statusBadge}</div>
                        </div>
                        ${extra}
                        ${btn}
                    </div>
                `);
            });
        }

        function escapeHtml(text) {
            return $('<div/>').text(text || '').html();
        }

        // approve one step (with optional signed_form)
        $('#tf-flow-steps').on('click', '.btn-flow-approve-step', function() {
            const id = $('#tf-approve-id').val();
            if (!id) return;

            const $btn = $(this);
            const stepCode = String($btn.data('step-code') || '');

            Swal.fire({
                title: 'Approve this step?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then(result => {
                if (!result.isConfirmed) return;

                $btn.prop('disabled', true);

                const fd = new FormData();

                // asset_mgt step for owner/user/maintenance can send signed_form
                if (
                    stepCode === 'asset_mgt' && ['owner', 'user', 'maintenance'].includes(
                        currentFlowType)
                ) {
                    const fileInput = $btn.closest('.border').find('.tf-signed-form-input')[0];
                    if (fileInput && fileInput.files && fileInput.files.length > 0) {
                        fd.append('signed_form', fileInput.files[0]);
                    }
                }

                Swal.fire({
                    title: 'Approving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.ajax({
                    url: R.approveStep(id),
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                }).done(res => {
                    if (res.flow) {
                        renderApproveFlowModal({
                            flow: res.flow
                        });
                    }

                    if (res.completed) {
                        $('#modal-transfer-approve').modal('hide');
                        Swal.fire('Approved', 'Movement completed.', 'success');
                    } else {
                        Swal.fire('Approved', 'Step approved.', 'success');
                    }

                    dt.ajax.reload(null, false);
                }).fail(x => {
                    $btn.prop('disabled', false);
                    Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                });
            });
        });

    })();
</script>
