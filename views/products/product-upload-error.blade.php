<tr class="js_column">
    <td class="column-row">
        <p>{{$item->numberRow}}</p>
    </td>
    <td class="column-id">
        <p>{!! (isset($item->id)) ? $item->id : '' !!}</p>
    </td>
    <td class="column-parent">
        <p>{!! (isset($item->parent_id)) ? $item->parent_id : '' !!}</p>
    </td>
    <td class="column-title">
        <p>{!! $item->title !!}</p>
    </td>
    <td class="column-errors">
        @if(isset($item->errors))
            @foreach ($item->errors as $error)
                <p>{{$error}}</p>
            @endforeach
        @endif
    </td>
</tr>