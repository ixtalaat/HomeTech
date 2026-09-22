<x-mail::message>
# New support request: {{ $subjectLine }}

**From:** {{ $senderName }} ({{ $senderEmail }})

{{ $body }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
