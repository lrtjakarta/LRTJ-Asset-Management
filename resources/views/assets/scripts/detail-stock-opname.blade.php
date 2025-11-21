<script>
    (function () {
        const SO = {
            data: '{{ route('stockopname.data', $asset->uuid) }}'
        };

        $('#tbl-so').DataTable({
            processing: true,
            serverSide: true, // match global SO
            ajax: {
                url: SO.data,
                type: 'GET'
            },
            order: [[8, 'desc']], // Updated column (index 8)
            dom:
                "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                "<'table-responsive'tr>" +
                "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
            columns: [
                {
                    data: 'code',
                    name: 'code'
                },
                {
                    data: 'source',
                    name: 'source'
                },
                {
                    data: 'type',
                    name: 'type'
                },
                {
                    data: 'detail',
                    name: 'detail',
                    orderable: false,
                    searchable: true
                },
                {
                    data: 'note',
                    name: 'note',
                    render: d => d ? $('<div>').text(d).html() : ''
                },
                {
                    data: 'pic_request_uid',
                    name: 'pic_request_uid'
                },
                {
                    data: 'pic_approve_uid',
                    name: 'pic_approve_uid'
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
                    render: function (iso, type) {
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
                    searchable: false,
                    defaultContent: ''
                }
            ]
        });
    })();
</script>
