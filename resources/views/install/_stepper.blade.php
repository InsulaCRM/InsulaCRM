@php
    $steps = [
        1 => __('Get Started'),
        2 => __('Requirements'),
        3 => __('Database'),
        4 => __('Setup'),
        5 => __('Complete'),
    ];
@endphp
<div class="steps steps-green steps-counter mb-4">
    @foreach($steps as $num => $label)
        <span class="step-item {{ $num <= $currentStep ? 'active' : '' }}">{{ $label }}</span>
    @endforeach
</div>
