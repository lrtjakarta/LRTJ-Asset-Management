@extends('layouts.app')

@push('head')
    <style>
        .card.card-fullscreen {
            position: fixed !important;
            inset: 0 !important;
            z-index: 1080 !important;
            /* above drawers/menus */
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
            border-radius: 0 !important;
        }

        body.fs-lock {
            overflow: hidden;
            /* prevent page scroll behind fullscreen card */
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function($) {
            const FS_BTN = '[data-card="fullscreen"]';

            // Click (delegated)
            $(document).on('click', FS_BTN, function(e) {
                e.preventDefault();

                const $btn = $(this);
                const $card = $btn.closest('.card')[0]; // DOM node

                if (!$card) return;

                const canFullscreen = !!($card.requestFullscreen || $card.webkitRequestFullscreen || $card
                    .msRequestFullscreen);

                const fallbackEnter = function() {
                    $($card).addClass('card-fullscreen');
                    $('body').addClass('fs-lock');
                    $btn.attr('data-state', 'on');
                };

                const fallbackExit = function() {
                    $($card).removeClass('card-fullscreen');
                    $('body').removeClass('fs-lock');
                    $btn.attr('data-state', 'off');
                };

                const enterFS = async function() {
                    if (document.fullscreenElement || document.webkitFullscreenElement || document
                        .msFullscreenElement) return;
                    if ($card.requestFullscreen) await $card.requestFullscreen();
                    else if ($card.webkitRequestFullscreen) await $card.webkitRequestFullscreen();
                    else if ($card.msRequestFullscreen) await $card.msRequestFullscreen();
                    else fallbackEnter();
                };

                const exitFS = async function() {
                    if (document.exitFullscreen) await document.exitFullscreen();
                    else if (document.webkitExitFullscreen) await document.webkitExitFullscreen();
                    else if (document.msExitFullscreen) await document.msExitFullscreen();
                    else fallbackExit();
                };

                const isNativeOn =
                    document.fullscreenElement === $card ||
                    document.webkitFullscreenElement === $card ||
                    document.msFullscreenElement === $card;

                const isFallbackOn = $($card).hasClass('card-fullscreen');

                if (canFullscreen) {
                    (isNativeOn ? exitFS() : enterFS()).catch(fallbackEnter);
                } else {
                    isFallbackOn ? fallbackExit() : fallbackEnter();
                }
            });

            // Keep button state/icons synced with native FS changes
            const syncButtons = function() {
                const active = document.fullscreenElement || document.webkitFullscreenElement || document
                    .msFullscreenElement;
                $(FS_BTN).each(function() {
                    const $btn = $(this);
                    const card = $btn.closest('.card')[0];
                    const inThis = !!(active && card === active);
                    $btn.attr('data-state', inThis ? 'on' : 'off');
                });
            };

            $(document).on('fullscreenchange webkitfullscreenchange MSFullscreenChange', syncButtons);

            // ESC also exits fallback mode
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    const $fs = $('.card.card-fullscreen');
                    if ($fs.length) {
                        $fs.removeClass('card-fullscreen');
                        $('body').removeClass('fs-lock');
                    }
                }
            });
        })(jQuery);
    </script>
@endpush



@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Assets
                </h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">
                        Assets
                    </li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->

            </div>

        </div>
        <!--end::Toolbar container-->
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-fluid">
            <!--begin::Row-->
            <div class="row g-5 gx-xl-10 mb-5 mb-xl-10">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <div class="card mb-5 mb-xl-8">
                            <!--begin::Header-->
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-3 mb-1">Assets Data</span>
                                </h3>
                                <div class="card-toolbar">
                                    <button class="btn btn-sm btn-danger">
                                        <i class="ki-duotone ki-plus fs-2"></i>Add New
                                    </button>
                                    &nbsp;
                                    <button class="btn btn-sm btn-danger" data-card="fullscreen" title="Fullscreen">
                                        <i class="ki-duotone ki-exit-right-corner fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i> Fullscreen
                                    </button>
                                </div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Body-->
                            <div class="card-body py-3">
                            </div>
                            <!--begin::Body-->
                        </div>
                    </div>
                </div>


            </div>
            <!--end::Row-->
        </div>
        <!--end::Content container-->
    </div>
@endsection
