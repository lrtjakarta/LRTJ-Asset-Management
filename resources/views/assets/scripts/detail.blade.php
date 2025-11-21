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
        });
    </script>
    @include('assets.scripts.detail-transfer', ['asset' => $asset])
    @include('assets.scripts.detail-disposal', ['asset' => $asset])
    @include('assets.scripts.detail-return', ['asset' => $asset])
    @include('assets.scripts.detail-acquisition', ['asset' => $asset])
    @include('assets.scripts.detail-stock-opname', ['asset' => $asset])
@endpush
