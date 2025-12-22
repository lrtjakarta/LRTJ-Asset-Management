@extends('layouts.app')

@section('content')
    {{-- Toolbar --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Bulk Upload</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('assets.index') }}" class="text-muted text-hover-primary">Assets</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Bulk Upload</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            {{-- Messages --}}
            @php
                $successMsg = $success ?? session('success');
                $errorMsg = $error ?? session('error');
            @endphp

            @if ($successMsg)
                <div class="alert alert-success">{{ $successMsg }}</div>
            @endif

            @if ($errorMsg)
                <div class="alert alert-danger">{!! $errorMsg !!}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-bold mb-2">Upload failed:</div>
                    <ul class="mb-0 ps-6">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-6">
                {{-- Upload file --}}
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header align-items-center justify-content-between">
                            <div class="d-flex flex-column">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-3 mb-1">Upload Excel</span>
                                </h3>
                            </div>
                        </div>
                        <form action="{{ route('assets.upload.excel') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="mb-6">
                                    <label class="form-label required">Excel File</label>
                                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv"
                                        required>
                                    <div class="form-text">Choose the filled template file to import.</div>
                                </div>

                                {{-- <hr class="my-5"> --}}

                                <div class="">
                                    <div class="fw-semibold mb-2">Notes:</div>
                                    <ul class="mb-0 ps-6">
                                        <li>You must use the template from this website</li>
                                        <li>Never change template's header</li>
                                        <li>Data in template serve as example. Please erase it before you upload it</li>
                                        <li>System check Asset Number Parent and Child (Combination). If exists it will
                                            become update function based on what changed in cells. If cell is empty then the
                                            former data stay as it is (not changed to null)</li>
                                        <li>If Parent and Child (Combination) doesn't exists it will inserted as new data
                                        </li>
                                        <li>Accepted files: <strong>.xlsx, .xls, .csv</strong> (max 20 MB)</li>
                                        <li>Codes must exist in master tables (Status, Location, User Code, etc.)</li>
                                        <li>Dates use <em>YYYY-MM-DD</em></li>
                                    </ul>
                                </div>

                                <hr class="my-5">

                                @canAction('ASSETS','R')
                                <a href="{{ route('assets.download.template') }}" class="btn btn-light-danger"><i
                                        class="ki-duotone ki-down-square fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i> Download Template
                                </a>
                                @endcanAction
                                &nbsp;
                                @canAction('ASSETS','C')
                                <button type="submit" class="btn btn-danger">
                                    <i class="ki-duotone ki-up-square fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i> Upload
                                </button>
                                @endcanAction
                            </div>
                        </form>

                        @php
                            $summary = $import_summary ?? session('import_summary');
                        @endphp

                        @if (!empty($summary))
                            <div class="card-body border-top pt-6">
                                <h5 class="fw-bold mb-4">Import Summary</h5>
                                @php($s = $summary)
                                <div class="row g-6 mb-4">
                                    <div class="col-sm-4">
                                        <div class="border rounded p-4">
                                            <div class="text-muted">Rows</div>
                                            <div class="fs-1 fw-bold">{{ $s['rows'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="border rounded p-4">
                                            <div class="text-muted">Inserted</div>
                                            <div class="fs-1 fw-bold text-success">{{ $s['inserted'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="border rounded p-4">
                                            <div class="text-muted">Updated</div>
                                            <div class="fs-1 fw-bold text-primary">{{ $s['updated'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>

                                @if (!empty($s['failed_rows']))
                                    <div class="card mt-6">
                                        <div class="card-header">
                                            <h3 class="card-title fw-bold">Failed Rows Detail</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-3">
                                                    <thead>
                                                        <tr class="fw-bold text-muted">
                                                            <th class="min-w-70px">Row</th>
                                                            <th class="min-w-200px">Asset Code</th>
                                                            <th class="min-w-120px">Parent</th>
                                                            <th class="min-w-120px">Child</th>
                                                            <th class="min-w-300px">Reason</th>
                                                            <th class="min-w-250px">Hint</th>
                                                            <!--<th class="min-w-120px">SQLState</th>-->
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($s['failed_rows'] as $f)
                                                            <tr>
                                                                <td>{{ $f['row'] ?? '-' }}</td>
                                                                <td class="fw-semibold">{{ $f['asset_code'] ?? '-' }}</td>
                                                                <td>{{ $f['parent'] ?? '-' }}</td>
                                                                <td>{{ $f['child'] ?? '-' }}</td>
                                                                <td>{{ $f['reason'] ?? '-' }}</td>
                                                                <td>{{ $f['hint'] ?? '-' }}</td>
                                                                <!--<td>{{ $f['sqlstate'] ?? '-' }}</td>-->
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                
                                            <div class="text-muted mt-3">
                                                Tip: baris yang gagal bisa kamu filter dari kolom <b>Asset Code</b> lalu perbaiki di Excel, upload ulang.
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
