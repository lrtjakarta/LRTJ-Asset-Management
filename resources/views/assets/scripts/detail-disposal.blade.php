<script>
    (function() {
        const DS = {
            data: '{{ route('disposal.data', $asset->uuid) }}',
            create: '{{ route('disposal.store') }}',
            show: '{{ route('disposal.show', ':id') }}',
            update: '{{ route('disposal.update', ':id') }}',
            destroy: '{{ route('disposal.destroy', ':id') }}',
            approveStep: id => '{{ route('disposal.approve-step', ':id') }}'.replace(':id', id),
        };

        const FORM_URL_TPL = @json(route('disposal.form', '__UUID__'));
        const BA_URL_TPL = @json(route('disposal.ba', '__UUID__'));

        // --- Helpers ---
        function escapeHtml(text) {
            return $('<div/>').text(text || '').html();
        }

        function formatIDR(v) {
            if (v === null || v === undefined || v === '') return '';
            const n = Number(v);
            if (isNaN(n)) return v;
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(n);
        }

        function isAssetMgtApproved(flowRaw) {
            if (!flowRaw) return false;

            let flow = flowRaw;
            if (typeof flow === 'string') {
                try {
                    flow = JSON.parse(flow);
                } catch (e) {
                    return false;
                }
            }

            if (!flow || !Array.isArray(flow.steps)) return false;

            for (const st of flow.steps) {
                if (st.code === 'asset_mgt') {
                    return !!st.approved_at;
                }
            }
            return false;
        }

        // --- Create / Edit Modal (simple, asset is fixed) ---
        $('#btnOpenDisposal').on('click', function(e) {
            e.preventDefault();
            resetDisposalForm();
            $('#modal-disposal').modal('show');
        });

        function resetDisposalForm() {
            $('#ds-edit-id').val('');
            $('#ds-note').val('');
            $('#ds-reason').val('');
            $('#ds-file').val('');
            $('#ds-remove-file').val('0');
            $('#ds-current-file').addClass('d-none');
            $('#ds-current-file-link').attr('href', '#').text('');
        }

        $('#ds-btn-remove-file').off('click').on('click', function() {
            $('#ds-remove-file').val('1');
            $('#ds-current-file').addClass('d-none');
        });

        $('#formDisposal').off('submit').on('submit', function(e) {
            e.preventDefault();

            const isEdit = !!$('#ds-edit-id').val();
            const id = $('#ds-edit-id').val();
            const fileEl = document.getElementById('ds-file');
            const hasFile = fileEl && fileEl.files && fileEl.files.length > 0;

            const base = {
                asset_uuid: '{{ $asset->uuid }}',
                note: $('#ds-note').val() || '',
                remove_file: $('#ds-remove-file').val() || 0,
                reason: $('#ds-reason').val() || '',
            };

            if (!base.reason) {
                Swal.fire('Reason required', 'Please select a reason.', 'warning');
                return;
            }

            Swal.fire({
                title: isEdit ? 'Update this disposal?' : 'Create this disposal?',
                text: isEdit ? 'Do you want to save the changes?' :
                    'Do you want to create this disposal request?',
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

                let req;
                if (hasFile) {
                    const fd = new FormData();
                    Object.entries(base).forEach(([k, v]) => fd.append(k, v));
                    fd.append('file', fileEl.files[0]);
                    if (isEdit) fd.append('_method', 'PUT');

                    req = $.ajax({
                        url: isEdit ? DS.update.replace(':id', id) : DS.create,
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false
                    });
                } else {
                    req = isEdit ?
                        $.ajax({
                            url: DS.update.replace(':id', id),
                            type: 'PUT',
                            data: base
                        }) :
                        $.post(DS.create, base);
                }

                req.done(() => {
                    $('#modal-disposal').modal('hide');
                    Swal.fire(
                        isEdit ? 'Updated' : 'Success',
                        isEdit ? 'Disposal updated.' : 'Disposal created.',
                        'success'
                    );
                    if (fileEl) fileEl.value = '';
                    $('#ds-remove-file').val('0');
                    $('#tbl-disposal').DataTable().ajax.reload(null, false);
                }).fail(x => {
                    Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                });
            });
        });

        // --- DataTable (per-asset disposal history) ---
        const dsTable = $('#tbl-disposal').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: DS.data,
                type: 'GET'
            },
            order: [
                [7, 'desc']
            ],
            dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                "<'table-responsive'tr>" +
                "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
            columns: [{
                    data: 'disposal_code',
                    name: 'disposal_code'
                },
                {
                    data: 'reason',
                    name: 'reason',
                    defaultContent: '',
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
                    render: d => d ? $('<div>').text(d).html() : ''
                },
                {
                    data: 'workflow_label',
                    name: 'workflow_label',
                    render: (d, t, row) => {
                        if (row.kode_status === 'APR') {
                            return `<span class="badge badge-light-primary">${d || ''}</span>`;
                        } else if (row.kode_status === 'ACC') {
                            return `<span class="badge badge-light-success">${d || ''}</span>`;
                        }
                        return `<span class="badge badge-light-danger">${d || ''}</span>`;
                    }
                },
                {
                    data: 'file',
                    name: 'file',
                    orderable: false,
                    searchable: false,
                    defaultContent: ''
                },
                {
                    data: 'updated_at',
                    name: 'updated_at',
                    render: (iso, type) => {
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
            ],
            rowCallback: function(row, data) {
                const $actions = $('td:last', row);
                const $group = $actions.find('.btn-group');
                if (!$group.length) return;

                const formUrl = (data.form_file_url && data.form_file_url.length) ?
                    data.form_file_url :
                    FORM_URL_TPL.replace('__UUID__', encodeURIComponent(data.uuid));

                const baUrl = (data.ba_file_url && data.ba_file_url.length) ?
                    data.ba_file_url :
                    BA_URL_TPL.replace('__UUID__', encodeURIComponent(data.uuid));

                const formBtn = `
                    <button type="button"
                        class="btn btn-light-info btn-sm btn-ds-form"
                        onclick="window.open('${formUrl}','_blank')">
                        Form
                    </button>
                `;

                const baBtn = `
                    <button type="button"
                        class="btn btn-light-warning btn-sm btn-ds-ba"
                        onclick="window.open('${baUrl}','_blank')">
                        BA
                    </button>
                `;

                // Always allow Form button
                if (!$group.find('.btn-ds-form').length) {
                    $group.prepend(formBtn);
                }

                // BA button only when asset_mgt step approved (same as disposal.blade)
                if (isAssetMgtApproved(data.flow) && !$group.find('.btn-ds-ba').length) {
                    $group.prepend(baBtn);
                }
            }
        });

        // --- Row actions (Edit / Reject / Delete) ---
        $(document).on('click', '.btn-ds-edit', function() {
            const id = $(this).data('id');

            $.getJSON(DS.show.replace(':id', id))
                .done(d => {
                    resetDisposalForm();
                    $('#ds-edit-id').val(d.uuid);
                    $('#ds-note').val(d.note || '');
                    $('#ds-reason').val(d.reason || '');

                    if (d.file_url) {
                        $('#ds-current-file-link')
                            .attr('href', d.file_url)
                            .text(d.file_name || 'Current file');
                        $('#ds-current-file').removeClass('d-none');
                        $('#ds-remove-file').val('0');
                    } else {
                        $('#ds-current-file').addClass('d-none');
                        $('#ds-remove-file').val('0');
                    }

                    $('#modal-disposal').modal('show');
                })
                .fail(() => {
                    Swal.fire('Error', 'Cannot load disposal.', 'error');
                });
        });

        $(document).on('click', '.btn-ds-reject', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Reject this disposal?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then(res => {
                if (!res.isConfirmed) return;

                Swal.fire({
                    title: 'Updating…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.post('{{ route('disposal.reject', ':id') }}'.replace(':id', id))
                    .done(() => {
                        Swal.fire('Rejected', 'Disposal rejected.', 'success');
                        dsTable.ajax.reload(null, false);
                    })
                    .fail(x => {
                        Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                    });
            });
        });

        $(document).on('click', '.btn-ds-delete', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Delete this disposal?',
                text: 'The record will be moved to trash.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then(res => {
                if (!res.isConfirmed) return;

                Swal.fire({
                    title: 'Deleting…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.ajax({
                    url: DS.destroy.replace(':id', id),
                    type: 'DELETE'
                }).done(() => {
                    Swal.fire('Deleted', 'Disposal deleted.', 'success');
                    dsTable.ajax.reload(null, false);
                }).fail(x => {
                    Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                });
            });
        });

        // --- FLOW MODAL (match disposal.blade) ---
        const $flowModal = $('#modal-disposal-flow');
        const $flowSteps = $('#flow-steps');
        const $flowBaInput = $('#flow-ba-file-input');
        const $flowFormInput = $('#flow-form-file-input');
        const $flowBaWrapper = $('#flow-wrap-ba-upload');
        const $flowFormWrapper = $('#flow-wrap-form-upload');
        const $flowAsset = $('#flow-asset');
        const $flowCode = $('#flow-code');
        const $flowReason = $('#flow-reason');
        const $flowAcqDate = $('#flow-acq-date');
        const $flowCommAcq = $('#flow-commercial-acq');
        const $flowCommAccDepr = $('#flow-commercial-acc-depr');
        const $flowCommNbv = $('#flow-commercial-nbv');
        const $flowFormOut = $('#flow-form-file');
        const $flowBaOut = $('#flow-ba-file');

        function openDisposalFlow(id) {
            if (!id) return;

            $('#formDisposalFlow')[0].reset();
            $flowSteps.empty();
            $flowBaWrapper.addClass('d-none');
            $flowFormWrapper.addClass('d-none');
            $('#flow-disposal-id').val(id);

            $flowFormOut.html('');
            $flowBaOut.html('');
            $flowAsset.text('');
            $flowCode.text('');
            $flowReason.text('');
            $flowAcqDate.text('');
            $flowCommAcq.text('');
            $flowCommAccDepr.text('');
            $flowCommNbv.text('');

            Swal.fire({
                title: 'Loading flow…',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            $.getJSON(DS.show.replace(':id', id))
                .done(function(d) {
                    Swal.close();

                    $flowAsset.text(
                        ((d.asset_code || '') + (d.asset_name ? (' - ' + d.asset_name) : '')) || d
                        .asset_uuid
                    );
                    $flowCode.text(d.disposal_code || '');
                    $flowReason.text(d.reason || '');
                    $flowAcqDate.text(d.acquisition_date || '');
                    $flowCommAcq.text(formatIDR(d.commercial_acq_cost));
                    $flowCommAccDepr.text(formatIDR(d.commercial_accum_depr));
                    $flowCommNbv.text(formatIDR(d.commercial_nbv));

                    const formDownloadUrl = FORM_URL_TPL.replace('__UUID__', encodeURIComponent(d.uuid));
                    const baDownloadUrl = BA_URL_TPL.replace('__UUID__', encodeURIComponent(d.uuid));

                    if (d.flow_file_url) {
                        $flowFormOut.html(
                            `<a href="${d.flow_file_url}" target="_blank">${escapeHtml(d.flow_file_name || 'Download Form')}</a>`
                        );
                    } else {
                        $flowFormOut.html(
                            `<a href="${formDownloadUrl}" target="_blank">Download auto-filled form</a>`
                        );
                    }

                    if (isAssetMgtApproved(d.flow)) {
                        if (d.ba_file_url) {
                            $flowBaOut.html(
                                `<a href="${d.ba_file_url}" target="_blank">${escapeHtml(d.ba_file_name || 'Download BA')}</a>`
                            );
                        } else {
                            $flowBaOut.html(
                                `<a href="${baDownloadUrl}" target="_blank">Download auto-filled BA</a>`
                            );
                        }
                    } else {
                        $flowBaOut.html('Not available yet');
                    }

                    renderFlowSteps(d.flow);
                    $flowModal.modal('show');
                })
                .fail(function(x) {
                    Swal.fire('Error', x.responseJSON?.message || 'Cannot load flow', 'error');
                });
        }

        function renderFlowSteps(flowRaw) {
            $flowSteps.empty();
            $flowBaWrapper.addClass('d-none');
            $flowFormWrapper.addClass('d-none');

            if (!flowRaw) return;

            let flow = flowRaw;
            if (typeof flow === 'string') {
                try {
                    flow = JSON.parse(flow);
                } catch (e) {
                    return;
                }
            }
            if (!flow.steps) return;

            let pendingIndex = null;
            flow.steps.forEach(function(st, idx) {
                if (!st.approved_at && pendingIndex === null) {
                    pendingIndex = idx;
                }
            });

            flow.steps.forEach(function(st, idx) {
                let li = $(
                    '<li class="list-group-item d-flex justify-content-between align-items-center"></li>'
                );
                let left = $('<div></div>');
                let right = $('<div class="text-end"></div>');

                left.append('<div class="fw-bold">' + escapeHtml(st.label || st.code || '') + '</div>');
                left.append('<div class="text-muted small">' + escapeHtml(st.role || '') + '</div>');

                if (st.approved_at) {
                    right.append('<span class="badge badge-light-success mb-1">Approved</span><br>');
                    right.append('<span class="small text-muted">' + escapeHtml(st.approved_by || '') +
                        '</span><br>');
                    right.append('<span class="small text-muted">' + escapeHtml(st.approved_at) +
                        '</span>');
                } else if (pendingIndex === idx) {
                    right.append('<span class="badge badge-light-warning">Pending (Next)</span>');
                    if (st.code === 'asset_mgt') {
                        $flowBaWrapper.removeClass('d-none');
                        $flowFormWrapper.removeClass('d-none');
                    }
                } else {
                    right.append('<span class="badge badge-light-secondary">Pending</span>');
                }

                li.append(left).append(right);
                $flowSteps.append(li);
            });
        }

        // open flow from table action
        $(document).on('click', '.btn-ds-approve', function() {
            const id = $(this).data('id');
            openDisposalFlow(id);
        });

        // Approve next step (Flow modal submit)
        $('#formDisposalFlow').off('submit').on('submit', function(e) {
            e.preventDefault();

            const id = $('#flow-disposal-id').val();
            if (!id) return;

            const fd = new FormData(this);

            Swal.fire({
                title: 'Approve next step?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#EA242A',
                cancelButtonColor: '#B5B5B6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then(result => {
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Approving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.ajax({
                    url: DS.approveStep(id),
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                }).done(function(res) {
                    Swal.close();
                    if (res.flow) {
                        renderFlowSteps(res.flow);
                    }
                    dsTable.ajax.reload(null, false);

                    if (res.kode_status === 'ACC') {
                        Swal.fire('Approved', 'Disposal fully approved.', 'success');
                        $flowModal.modal('hide');
                        // refresh asset detail header / status if needed
                        setTimeout(() => location.reload(), 400);
                    } else {
                        Swal.fire('Approved', 'Step approved.', 'success');
                    }
                }).fail(function(x) {
                    Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                });
            });
        });

    })();
</script>
