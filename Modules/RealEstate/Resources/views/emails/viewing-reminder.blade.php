@component('mail::message')
# Property Viewing Reminder

Hello,

This is a reminder about your upcoming property viewing appointment.

---

## Appointment Details

@component('mail::panel')
**Date:** {{ $appointmentDate->format('l, F j, Y') }}

**Time:** {{ $appointmentDate->format('g:i A') }}

**Property:** {{ $appointmentData['property_title'] ?? 'Property Viewing' }}

**Address:** {{ $appointmentData['address'] ?? 'To be confirmed' }}
@endcomponent

---

## Customer Details

**Name:** {{ $appointmentData['customer_name'] }}

**Phone:** {{ $appointmentData['customer_phone'] ?? 'N/A' }}

**Email:** {{ $appointmentData['customer_email'] ?? 'N/A' }}

@if(!empty($appointmentData['notes']))
---

## Notes

{{ $appointmentData['notes'] }}
@endif

---

@component('mail::subcopy')
**Reminder:** Please ensure you are prepared and on time for this viewing. If the customer needs to reschedule, please contact them in advance.
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
