@php
    $panelId   = 'job-invoices-'.$job->id;
    $modalId   = $panelId.'-upload-modal';
    $openBtnId = $panelId.'-open';
    $closeBtnId= $panelId.'-close';
    $advId     = $panelId.'-adv';
    $invoices  = $job->invoices()->latest('id')->get();
@endphp

@once
    @push('styles')
        @include('admin.jobs.index-partials._styles')
    @endpush
@endonce

<div class="sf-jobs-page">
    <div class="sf-card">
        @include('admin.jobs.invoices-panel-partials._header')

        <div class="sf-card-body">
            @if($invoices->isEmpty())
                @include('admin.jobs.invoices-panel-partials._empty_state')
            @else
                @include('admin.jobs.invoices-panel-partials._table')

                <div class="mt-4">
                    <button id="{{ $openBtnId }}-below" type="button" class="sf-btn-primary">
                        + Upload Another
                    </button>
                </div>
            @endif
        </div>
    </div>

    @include('admin.jobs.invoices-panel-partials._upload_modal')
    @include('admin.jobs.invoices-panel-partials._scripts')
</div>
