<x-mail::message>
# Hi {{ $recipientName }},

{{ $intro }}

<x-mail::panel>
@foreach($items as $item)
- **{{ $item['title'] }}**@if(!empty($item['detail'])) &mdash; {{ $item['detail'] }}@endif

@endforeach
</x-mail::panel>

<x-mail::button :url="$link">{{ $buttonText }}</x-mail::button>

Thank you,<br>
{{ config('app.name') }}
</x-mail::message>
