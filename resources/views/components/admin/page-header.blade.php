<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        @isset($title)
            <h1 class="h4 mb-1">{{ $title }}</h1>
        @endisset

        @isset($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endisset

        {{ $slot ?? '' }}
    </div>

    @isset($actions)
        <div>
            {{ $actions }}
        </div>
    @endisset
</div>