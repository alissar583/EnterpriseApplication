@component('mail::message')
<h1>'We have received your request to enable two-fator authentication'</h1>

<p>'You can use the following code to recover your account:'</p>

@component('mail::panel')
{{ $code }}
@endcomponent

<p>'The allowed duration of the code is five minutes from the time the message was sent'</p>

@endcomponent
