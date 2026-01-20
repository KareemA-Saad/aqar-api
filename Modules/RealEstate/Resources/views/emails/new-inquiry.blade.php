@component('mail::message')
# New Property Inquiry

Hello,

You have received a new property inquiry. Please review the details below and contact the customer as soon as possible.

---

## Customer Details

**Name:** {{ $inquiry->name }}

**Email:** {{ $inquiry->email }}

**Phone:** {{ $inquiry->phone }}

@if($property)
---

## Property Details

**Title:** {{ $property->title }}

**Reference:** {{ $property->reference_number ?? 'N/A' }}

**Price:** {{ $property->price_formatted }}

@if($property->compound)
**Compound:** {{ $property->compound->name }}
@endif

@if($property->area)
**Location:** {{ $property->area->name }}
@endif
@endif

@if($compound && !$property)
---

## Compound Details

**Name:** {{ $compound->name }}

@if($compound->area)
**Area:** {{ $compound->area->name }}
@endif

@if($compound->developer)
**Developer:** {{ $compound->developer->name }}
@endif
@endif

@if($inquiry->message)
---

## Customer Message

{{ $inquiry->message }}
@endif

---

@component('mail::button', ['url' => $adminUrl])
View Inquiry
@endcomponent

Please respond to this inquiry within 24 hours.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
