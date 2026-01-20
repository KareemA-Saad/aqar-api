@component('mail::message')
# Thank You for Your Inquiry!

Hello {{ $inquiry->name }},

Thank you for your interest! We have received your inquiry and our team will contact you shortly.

---

## Your Inquiry Details

**Reference Number:** INQ-{{ $inquiry->id }}

**Submitted On:** {{ $inquiry->created_at->format('F j, Y \a\t g:i A') }}

@if($property)
---

## Property of Interest

**Title:** {{ $property->title }}

@if($property->price_formatted)
**Price:** {{ $property->price_formatted }}
@endif

@if($property->compound)
**Compound:** {{ $property->compound->name }}
@endif

@if($property->bedrooms)
**Bedrooms:** {{ $property->bedrooms }}
@endif

@if($property->area)
**Area:** {{ $property->area }} {{ $property->area_unit }}
@endif
@endif

@if($compound && !$property)
---

## Compound of Interest

**Name:** {{ $compound->name }}

@if($compound->area)
**Location:** {{ $compound->area->name }}
@endif

@if($compound->developer)
**Developer:** {{ $compound->developer->name }}
@endif
@endif

---

## What Happens Next?

@component('mail::panel')
1. **Review:** Our team will review your inquiry
2. **Assignment:** A dedicated agent will be assigned to assist you
3. **Contact:** You will receive a call/email within 24 hours
@endcomponent

---

If you have any urgent questions, feel free to contact us directly.

@component('mail::button', ['url' => $websiteUrl . '/properties'])
Browse More Properties
@endcomponent

Thanks for choosing us!<br>
{{ config('app.name') }} Team
@endcomponent
