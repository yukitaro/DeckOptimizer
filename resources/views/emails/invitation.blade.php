<p>You’ve been invited to join {{ config('app.name') }}!</p>
<p><a href="{{ $link }}">Click here to create your account</a></p>
<p>This link will expire on {{ \Carbon\Carbon::parse($expires)->toDayDateTimeString() }}.</p>
