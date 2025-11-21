{{-- History Tabs --}}
<div class="card py-3 mt-6">
    <div class="card-header card-header-tabs-line">
        <div class="card-title">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">Logbook / History</span>
            </h3>
        </div>
        <div class="card-toolbar">
            <ul class="nav nav-tabs nav-bold nav-tabs-line" id="historyTabs" role="tablist">
                @canAction('ACQUISITION','R')
                <li class="nav-item" role="presentation">
                    <a class="nav-link active fw-bold" id="tab-acq-link" data-bs-toggle="tab" href="#tab_acquisition"
                        role="tab" aria-controls="tab_acquisition" aria-selected="false">Acquisition</a>
                </li>
                @endcanAction
                @canAction('MOVEMENT','R')
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-bold" id="tab-transfer-link" data-bs-toggle="tab" href="#tab_transfer"
                        role="tab" aria-controls="tab_transfer" aria-selected="true">Movement</a>
                </li>
                @endcanAction
                @canAction('RETURN','R')
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-bold" id="tab-return-link" data-bs-toggle="tab" href="#tab_return"
                        role="tab" aria-controls="tab_return" aria-selected="false">Return</a>
                </li>
                @endcanAction
                @canAction('DISPOSAL','R')
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-bold" id="tab-disposal-link" data-bs-toggle="tab" href="#tab_disposal"
                        role="tab" aria-controls="tab_disposal" aria-selected="false">Disposal</a>
                </li>
                @endcanAction
                @canAction('STOCK_OPN','R')
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-bold" id="tab-so-link" data-bs-toggle="tab" href="#tab_stock_opname"
                        role="tab" aria-controls="tab_stock_opname" aria-selected="false">Stock Opname</a>
                </li>
                @endcanAction
            </ul>
        </div>
    </div>


    <div class="card-body">
        <div class="tab-content" id="historyTabsContent">
            @canAction('ACQUISITION','R')
            <div class="tab-pane fade show active" id="tab_acquisition" role="tabpanel" aria-labelledby="tab-acq-link">
                <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                    id="tbl-acq">
                    <thead>
                        <tr class="table-light">
                            <th class="min-w-160px">Transaction Number</th>
                            <th class="min-w-380px">Details</th>
                            <th class="min-w-220px">Note</th>
                            <th class="min-w-160px">Requester</th>
                            <th class="min-w-180px">Created</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endcanAction
            @canAction('MOVEMENT','R')
            <div class="tab-pane fade" id="tab_transfer" role="tabpanel" aria-labelledby="tab-transfer-link">
                <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                    id="tbl-transfers">
                    <thead>
                        <tr class="table-light">
                            <th class="min-w-200px">Transaction Number</th>
                            <th class="min-w-100px">Type</th>
                            <th class="min-w-150px">Before</th>
                            <th class="min-w-150px">After</th>
                            <th class="min-w-100px">Requester</th>
                            <th class="min-w-100px">Approver</th>
                            <th class="min-w-250px">Note</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-160px">Flow</th>
                            <th class="min-w-200px">File</th>
                            <th class="min-w-200px">Updated</th>
                            <th class="min-w-200px">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endcanAction
            @canAction('RETURN','R')
            <div class="tab-pane fade" id="tab_return" role="tabpanel" aria-labelledby="tab-return-link">
                <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                    id="tbl-returns">
                    <thead>
                        <tr class="table-light">
                            <th class="min-w-180px">Transaction Number</th>
                            <th class="min-w-180px">MOV/DSP Code</th>
                            <th class="min-w-220px">Type</th>
                            <th class="min-w-300px">Details</th>
                            <th class="min-w-220px">Note</th>
                            <th class="min-w-140px">Requester</th>
                            <th class="min-w-180px">Created</th>
                            <th class="min-w-140px">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endcanAction
            @canAction('DISPOSAL','R')
            <div class="tab-pane fade" id="tab_disposal" role="tabpanel" aria-labelledby="tab-disposal-link">
                <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                    id="tbl-disposal">
                    <thead>
                        <tr class="table-light">
                            <th class="min-w-200px">Transaction Number</th>
                            <th class="min-w-100px">Reason</th>
                            <th class="min-w-100px">Requester</th>
                            <th class="min-w-100px">Approver</th>
                            <th class="min-w-250px">Note</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-200px">File</th>
                            <th class="min-w-200px">Updated</th>
                            <th class="min-w-200px">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endcanAction
            @canAction('STOCK_OPN','R')
            <div class="tab-pane fade" id="tab_stock_opname" role="tabpanel" aria-labelledby="tab-so-link">
                <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                    id="tbl-so">
                    <thead>
                        <tr class="table-light">
                            <th class="min-w-170px">Transaction Number</th>
                            <th class="min-w-120px">Source</th>
                            <th class="min-w-140px">Type</th>
                            <th class="min-w-300px">Detail</th>
                            <th class="min-w-220px">Note</th>
                            <th class="min-w-140px">Requester</th>
                            <th class="min-w-140px">Approver</th>
                            <th class="min-w-160px">File</th>
                            <th class="min-w-180px">Updated</th>
                            <th class="min-w-160px">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endcanAction
        </div>
    </div>
</div>
{{-- End History Tabs --}}
