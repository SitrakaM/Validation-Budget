@php
$files = $files ?? [];
@endphp
@if(empty($files))
    -
@else
    <div>
        @foreach($files as $file)
            @if($file)
                @php
                    $path = $file;
                    // If the path already includes storage/ prefix, normalize
                    $url = str_starts_with($path, 'http') ? $path : asset('storage/' . ltrim($path, '/'));
                @endphp
                <div><a href="{{ $url }}" target="_blank" rel="noopener">{{ basename($path) }}</a></div>
            @endif
        @endforeach
    </div>
@endif
