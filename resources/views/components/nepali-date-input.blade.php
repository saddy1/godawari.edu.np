@props([
    'name',
    'value' => null,
    'required' => false,
    'minBs' => null,
    'maxBs' => null,
])
@php
    $adValue = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) ($value ?? '');
    $adValue = old($name, $adValue);
    $bsValue = '';
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $adValue, $parts)) {
        $converter = new \App\Http\Controllers\Hajiri\NepaliCalendarController();
        $converted = $converter->ad_2_bs((int) $parts[1], (int) $parts[2], (int) $parts[3]);
        if ($converted) $bsValue = sprintf('%04d-%02d-%02d', $converted['year'], $converted['month'], $converted['date']);
    }
    $fieldId = 'np-date-'.preg_replace('/[^a-z0-9_-]/i', '-', $name).'-'.substr(md5($name.'-'.$adValue.'-'.uniqid('', true)), 0, 8);
@endphp

<span class="block" data-nepali-date-field>
    <input type="hidden" id="{{$fieldId}}-ad" name="{{$name}}" value="{{$adValue}}">
    <input
        type="text"
        id="{{$fieldId}}-bs"
        value="{{$bsValue}}"
        placeholder="YYYY-MM-DD"
        maxlength="10"
        inputmode="numeric"
        data-ad-target="{{$fieldId}}-ad"
        @if($minBs) data-min-bs="{{$minBs}}" @endif
        @if($maxBs) data-max-bs="{{$maxBs}}" @endif
        @if($required) required @endif
        {{$attributes->class(['nepali-date-picker'])}}
    >
    <small class="mt-1 block text-[9px] font-semibold text-gray-400">Bikram Sambat (BS) · Gregorian date is synchronized automatically</small>
</span>

@once
    @push('scripts')
        @include('partials.nepali-date-picker')
    @endpush
@endonce
