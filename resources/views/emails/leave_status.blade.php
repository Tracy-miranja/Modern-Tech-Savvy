<x-mail::message>
# Leave Request Update

Hello {{ $leave->employee->name }},

Your leave request from **{{ optional($leave->start_date)->format('d M Y') }}** to **{{ optional($leave->end_date)->format('d M Y') }}**
has been **{{ strtoupper($leave->status) }}**.

<x-mail::panel>
Reason: {{ $leave->reason }}
</x-mail::panel>

Thanks,  
{{ config('app.name') }}
</x-mail::message>
