<table id="{{ $id }}" class="{{ $class }}">
    <colgroup>
        @foreach ($columns as $i => $c)
        <col class="con{{ $i }}" />
        @endforeach
    </colgroup>
    <thead>
        <tr>
            @foreach ($columns as $i => $c)
            <th class="head{{ $i }}" style="text-align:center; vertical-align:middle;">
                {{ $c }}
            </th>
            @endforeach
        </tr>
    </thead>

    @if ($footerMode !== 'hidden')
    <tfoot>
        <tr>
            @foreach ($columns as $i => $c)
            <th class="footer{{ $i }}" style="text-align:center; vertical-align:middle;">
                @if ($footerMode === 'columns')
                {{ $c }}
                @endif
            </th>
            @endforeach
        </tr>
    </tfoot>
    @endif

    <tbody>
        @foreach ($data as $d)
        <tr>
            @foreach ($d as $dd)
            <td>{{ $dd }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>

@if (! $noScript)
@include(
config('chumptable.table.script_view'),
[
'id' => $id,
'options' => $options,
'callbacks' => $callbacks,
]
)
@endif