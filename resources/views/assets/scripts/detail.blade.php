@push('scripts')
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        (function() {
            // Delete asset button (same as before)
            $('#btnDeleteAsset').on('click', function() {
                Swal.fire({
                    title: 'Delete?',
                    text: 'This item will be moved to trash.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel'
                }).then(res => {
                    if (res.isConfirmed) $('#assetDeleteForm').trigger('submit');
                });
            });

            // Print RFID single asset - pilih ukuran dulu
            $('#btnPrintRfidSingle').on('click', async function(e) {
                e.preventDefault();

                const last = localStorage.getItem('rfid_label_size') || '100x40';

                const {
                    value: size
                } = await Swal.fire({
                    title: 'Pilih ukuran label RFID',
                    input: 'radio',
                    inputOptions: {
                        '100x40': '100 x 40 mm',
                        '60x25': '60 x 25 mm'
                    },
                    inputValue: last,
                    showCancelButton: true,
                    confirmButtonText: 'Print',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    inputValidator: (v) => !v ? 'Pilih ukuran label dulu.' : undefined
                });

                if (!size) return;

                localStorage.setItem('rfid_label_size', size);

                $('#input-label-size-rfid-single').val(size);
                $('#print-rfid-single-form').trigger('submit');
            });
        })();
    </script>
    @include('assets.scripts.detail-transfer', ['asset' => $asset])
    @include('assets.scripts.detail-disposal', ['asset' => $asset])
    @include('assets.scripts.detail-return', ['asset' => $asset])
    @include('assets.scripts.detail-acquisition', ['asset' => $asset])
    @include('assets.scripts.detail-stock-opname', ['asset' => $asset])
@endpush
