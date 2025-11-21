{{-- ===== Return Modal ===== --}}
<div class="modal fade" id="modal-return" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formReturn">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-5">
                        <div class="col-md-12">
                            <label class="form-label required">Select Transfer/Disposal</label>
                            <select id="ret-source" class="form-select" required></select>
                            <div class="form-text">Shows only latest accepted per asset (transfer-by-type, disposal)
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="p-3 border rounded bg-light" id="ret-preview" style="display:none">
                                <div><strong>Asset:</strong> <span id="ret-asset"></span></div>
                                <div><strong>Type:</strong> <span id="ret-type"></span></div>
                                <div id="ret-ba-wrap" style="display:none;">
                                    <strong>Before → After:</strong>
                                    <span id="ret-before"></span>
                                    <span class="mx-1">→</span>
                                    <span id="ret-after" class="fw-bold"></span>
                                </div>
                                <div class="mt-1"><strong>Code:</strong> <span id="ret-code"></span></div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Optional note…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Create Return</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- ===== Acquisition Modal ===== --}}
<div class="modal fade" id="modal-acq" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formAcq">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Acquisition — {{ $asset->asset_code }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label required">Quantity</label>
                            <input type="number" step="0.0001" min="0" class="form-control" name="quantity"
                                required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">UOM</label>
                            <select name="kode_uom" id="acq-uom" class="form-select" required></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Price</label>
                            <input type="number" step="0.0001" min="0" class="form-control" name="price"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">Useful Life (Months)</label>
                            <input type="number" step="1" min="0" class="form-control"
                                name="useful_life_month" required>
                            <div class="form-text">Year will be auto-calculated</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Include Tax (VAT-IN)?</label>
                            <select name="is_pajak" class="form-select" required>
                                <option value="1" selected>Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">Nilai Pajak (%)</label>
                            <input type="number" class="form-control" id="nilai_pajak" name="nilai_pajak"
                                value = "{{ ENV('NILAI_PAJAK') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">Actual Date</label>
                            <input type="date" class="form-control" name="actual_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Capitalization Date</label>
                            <input type="date" class="form-control" name="capitalization_date" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Note</label>
                            <textarea class="form-control" rows="3" name="note" placeholder="Optional note…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- ===== Transfer Modal ===== --}}
<div class="modal fade" id="modal-transfer" tabindex="-1" aria-labelledby="modalTransferLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formTransfer" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="asset_uuid" value="{{ $asset->uuid }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTransferLabel">Request Movement — {{ $asset->asset_code }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <label class="form-label required">Transfer Type</label>
                            <select name="type" id="tf-type" class="form-select" required>
                                <option value="owner">Owner</option>
                                <option value="user">User</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="status">Status</option>
                                <option value="location">Location</option>
                            </select>
                            <div class="form-text">Select Movement Type</div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label required">Move To</label>
                            <select id="tf-target" class="form-select" required></select>
                            <input type="hidden" name="after[value]" id="tf-target-hidden">
                            <div class="form-text" id="tf-target-help">Select the new target.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Optional note…"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Attachment (optional)</label>
                            <input type="file" name="file" id="tf-file" class="form-control"
                                accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv,.txt">
                            <div class="form-text">PDF / image / office docs (max 20MB).</div>

                            {{-- visible only when editing and a file exists --}}
                            <div id="tf-current-file" class="mt-2 d-none">
                                <a id="tf-current-file-link" href="#" target="_blank"></a>
                                <button type="button" class="btn btn-sm btn-light-danger ms-2"
                                    id="btn-remove-file">Remove</button>
                                <input type="hidden" name="remove_file" id="tf-remove-file" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- Approve Transfer flow modal (multi-step) --}}
<div class="modal fade" id="modal-transfer-approve" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <input type="hidden" id="tf-approve-id">
            <div class="modal-header">
                <h5 class="modal-title">Approve Movement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="tf-flow-steps">
                    {{-- steps rendered by JS --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Disposal Modal (Asset Detail) ===== --}}
<div class="modal fade" id="modal-disposal" tabindex="-1" aria-labelledby="modalDisposalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formDisposal" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="ds-edit-id">
                <input type="hidden" name="asset_uuid" value="{{ $asset->uuid }}">
                <input type="hidden" name="remove_file" id="ds-remove-file" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalDisposalLabel">Request Disposal — {{ $asset->asset_code }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-5">
                        {{-- Reason (match disposal.blade) --}}
                        <div class="col-md-12">
                            <label for="ds-reason" class="form-label required">Reason</label>
                            <select name="reason" id="ds-reason" class="form-select" required>
                                <option value="" selected disabled>-- Select Reason --</option>
                                <option value="Sale">Sale</option>
                                <option value="Waste">Waste</option>
                                <option value="Donate">Donate</option>
                                <option value="Held">Held</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Note</label>
                            <textarea name="note" id="ds-note" class="form-control" rows="3" placeholder="Optional note…"></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Attachment (optional)</label>
                            <input type="file" name="file" id="ds-file" class="form-control"
                                accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv,.txt">
                            <div class="form-text">PDF / image / office docs (max 20MB).</div>

                            {{-- visible only when editing and a file exists --}}
                            <div id="ds-current-file" class="mt-2 d-none">
                                <a id="ds-current-file-link" href="#" target="_blank"></a>
                                <button type="button" class="btn btn-sm btn-light-danger ms-2"
                                    id="ds-btn-remove-file">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Flow / Approval / BA Upload Modal (Asset Detail) ===== --}}
<div class="modal fade" id="modal-disposal-flow" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formDisposalFlow" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="flow-disposal-id" name="uuid" value="">

                <div class="modal-header">
                    <h5 class="modal-title">Disposal Approval Flow</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-4">
                        <div><strong>Asset:</strong> <span id="flow-asset"></span></div>
                        <div><strong>Transaction Number:</strong> <span id="flow-code"></span></div>
                        <div><strong>Reason:</strong> <span id="flow-reason"></span></div>
                        <div><strong>Acquisition Date:</strong> <span id="flow-acq-date"></span></div>
                        <div><strong>Commercial Acquisition Cost (IDR):</strong> <span id="flow-commercial-acq"></span>
                        </div>
                        <div><strong>Commercial Accumulated Depreciation (IDR):</strong> <span
                                id="flow-commercial-acc-depr"></span></div>
                        <div><strong>Commercial Net Book Value (IDR):</strong> <span id="flow-commercial-nbv"></span>
                        </div>
                        <div class="mt-2">
                            <strong>Form Disposal:</strong>
                            <span id="flow-form-file"></span>
                        </div>
                        <div class="mt-2">
                            <strong>Berita Acara:</strong>
                            <span id="flow-ba-file"></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Flow Steps</label>
                        <ul class="list-group" id="flow-steps"></ul>
                    </div>

                    <div class="mb-4 d-none" id="flow-wrap-ba-upload">
                        <label class="form-label required">Upload Berita Acara Disposal</label>
                        <input type="file" name="ba_file" id="flow-ba-file-input" class="form-control"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.bmp">
                        <div class="form-text">Required on final step by Asset Management.</div>
                    </div>
                    <div class="mb-4 d-none" id="flow-wrap-form-upload">
                        <label class="form-label required">Upload Form Disposal</label>
                        <input type="file" name="flow_file" id="flow-form-file-input" class="form-control"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.bmp">
                        <div class="form-text">Optional: upload signed / scanned form disposal.</div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger" id="btn-flow-approve">
                        Approve Next Step
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
