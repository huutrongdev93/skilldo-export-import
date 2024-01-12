<tr class="js_column">
    <td class="column-row">
        <p><?php echo $item->numberRow;?></p>
    </td>
    <td class="column-id">
        <p><?php echo (isset($item->id)) ? $item->id : '';?></p>
    </td>
    <td class="column-parent">
        <p><?php echo (isset($item->parent_id)) ? $item->parent_id : '';?></p>
    </td>
    <td class="column-title">
        <p><?php echo $item->title;?></p>
    </td>
    <td class="column-errors">
        <?php
        if(isset($item->errors) && is_skd_error($item->errors)) {
            foreach ($item->errors as $error) {
                echo '<p>'.$error.'</p>';
            }
        }
        ?>
    </td>
</tr>