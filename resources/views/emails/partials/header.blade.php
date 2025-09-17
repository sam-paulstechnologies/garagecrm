@php
  $showGreeting = !empty($greeting);
  $showTitle    = !empty($title);
@endphp

@if($showGreeting || $showTitle)
  <tr>
    <td class="inner">
      @if($showGreeting)
        <p style="margin-top:0;">{{ $greeting }}</p>
      @endif
      @if($showTitle)
        <h1 style="margin-bottom:0.5rem;">{{ $title }}</h1>
      @endif
    </td>
  </tr>
@endif
