@props([
    'columns' => [],
    'rows' => [],
])

<table {{ $attributes->class(['w-full border-collapse font-sans text-[13px]']) }}>
    <thead>
        <tr>
            @foreach ($columns as $column)
                <th class="border border-border bg-surface-alt px-3 py-2.5 text-left text-xs font-semibold text-ink">{{ $column }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                @foreach ($row as $cell)
                    <td class="border border-border px-3 py-2.5 text-left text-[13px] text-ink">{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
