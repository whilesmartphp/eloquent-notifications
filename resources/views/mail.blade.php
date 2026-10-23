<x-mail::message>
{{ $recipientName ? 'Hi '.$recipientName.',' : 'Hi,' }}

{{ $notification->body ?? $notification->title }}

@if ($notification->actionUrl)
<x-mail::button :url="$notification->actionUrl">
{{ $notification->actionLabel ?? 'Open' }}
</x-mail::button>
@endif

@if ($unsubscribeUrl)
<x-mail::subcopy>
[Stop these emails]({{ $unsubscribeUrl }})
</x-mail::subcopy>
@endif
</x-mail::message>
