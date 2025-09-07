<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function () {
        let oTable = $('#{{ $id }}').DataTable({
            @foreach ($options as $k => $o)
                {!! json_encode($k) !!}:
                @if (is_array($o))
                    @if (array_keys($o) === range(0, count($o) - 1)) {{-- numerik array --}}
                        [
                            @foreach ($o as $r)
                                @if (is_array($r))
                                    {!! json_encode($r) !!},
                                @elseif (is_string($r) && substr(ltrim($r), 0, 8) === 'function')
                                    {!! $r !!},
                                @else
                                    {!! json_encode($r) !!},
                                @endif
                            @endforeach
                        ],
                    @else
                        {
                            @foreach ($o as $x => $r)
                                {!! json_encode($x) !!}:
                                @if (is_array($r))
                                    {!! json_encode($r) !!},
                                @elseif (is_string($r) && substr(ltrim($r), 0, 8) === 'function')
                                    {!! $r !!},
                                @else
                                    {!! json_encode($r) !!},
                                @endif
                            @endforeach
                        },
                    @endif
                @elseif (is_string($o) && substr(ltrim($o), 0, 8) === 'function')
                    {!! $o !!},
                @else
                    {!! json_encode($o) !!},
                @endif
            @endforeach

            @foreach ($callbacks as $k => $o)
                {!! json_encode($k) !!}: {!! $o !!},
            @endforeach
        });

        // custom values tersedia via $values array
    });
</script>