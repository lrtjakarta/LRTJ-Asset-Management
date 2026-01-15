<script>
    (function() {
        const SO = {
            data: @json(route('stockopname.data', $asset->uuid)),
        };

        const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));

        function fmtJakarta(iso, type) {
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

        let dtSo = null;

        function initSoTable() {
            if ($.fn.DataTable.isDataTable('#tbl-so')) {
                dtSo = $('#tbl-so').DataTable();
                return;
            }

            dtSo = $('#tbl-so').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: SO.data,
                    type: 'GET',
                },
                order: [
                    [16, 'desc']
                ],
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                columns: [
                    // 0) Transaction Number
                    {
                        data: 'code',
                        name: 'code'
                    },

                    // 1) Asset Code (link)
                    {
                        data: 'asset_code',
                        name: 'asset_code',
                        render: function(data, type, row) {
                            if (type !== 'display') return data;
                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row
                                .asset_uuid));
                            const text = row.asset_code ?? data ?? '';
                            return `<a href="${url}" class="text-primary fw-semibold">${$('<div>').text(text).html()}</a>`;
                        }
                    },

                    // 2) Asset Description
                    {
                        data: 'asset_description',
                        name: 'asset_description',
                        searchable: true,
                        render: d => d ? $('<div>').text(d).html() : ''
                    },

                    // 3) Project
                    {
                        data: 'project',
                        name: 'project',
                        searchable: true,
                        render: d => d ? $('<div>').text(d).html() : ''
                    },

                    // 4) Location
                    {
                        data: 'asset_location',
                        name: 'asset_location',
                        searchable: true,
                        render: d => d ? $('<div>').text(d).html() : ''
                    },

                    // 5) Owner
                    {
                        data: 'owner',
                        name: 'owner',
                        searchable: true,
                        render: d => d ? $('<div>').text(d).html() : ''
                    },

                    // 6) User
                    {
                        data: 'user',
                        name: 'user',
                        searchable: true,
                        render: d => d ? $('<div>').text(d).html() : ''
                    },

                    // 7) Maintenance
                    {
                        data: 'maintenance',
                        name: 'maintenance',
                        searchable: true,
                        render: d => d ? $('<div>').text(d).html() : ''
                    },

                    // 8) Status
                    {
                        data: 'asset_status',
                        name: 'asset_status',
                        searchable: true,
                        render: d => d ? $('<div>').text(d).html() : ''
                    },

                    // 9) Source
                    {
                        data: 'source',
                        name: 'source'
                    },

                    // 10) Type
                    {
                        data: 'type',
                        name: 'type'
                    },

                    // 11) Detail
                    {
                        data: 'detail',
                        name: 'detail',
                        orderable: false,
                        searchable: true
                    },

                    // 12) Note
                    {
                        data: 'note',
                        name: 'note',
                        render: d => d ? $('<div>').text(d).html() : ''
                    },

                    // 13) Requester
                    {
                        data: 'pic_request_uid',
                        name: 'pic_request_uid'
                    },

                    // 14) Approver
                    {
                        data: 'pic_approve_uid',
                        name: 'pic_approve_uid'
                    },

                    // 15) File
                    {
                        data: 'file',
                        name: 'file',
                        orderable: false,
                        searchable: false,
                        defaultContent: ''
                    },

                    // 16) Updated
                    {
                        data: 'updated_at',
                        name: 'updated_at',
                        render: fmtJakarta
                    },

                    // 17) Actions
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        defaultContent: ''
                    },
                ],
            });
        }

        $(document).on('shown.bs.tab', 'button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]', function(e) {
            const target = $(e.target).attr('data-bs-target') || $(e.target).attr('href') || '';
            if (target === '#tab_stock_opname') {
                initSoTable();
                setTimeout(() => dtSo && dtSo.columns.adjust().draw(false), 50);
            }
        });

        $(function() {
            if ($('#tab_stock_opname').hasClass('active') || $('#tab_stock_opname').hasClass('show')) {
                initSoTable();
            }
        });
    })();
</script>
