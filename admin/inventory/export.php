<?php
Class AdminExportInventory {
    static function buttonHeading(): void
    {
        echo Admin::button('blue', [
            'class' => 'btn-blue-bg',
            'id' => 'js_export_inventory_btn_modal',
            'icon' => '<i class="fa-light fa-download"></i>',
            'text' => trans('export.data')
        ]);
    }
    static function modal(): void
    {
        Plugin::view(EXIM_NAME, 'inventory/export-modal');
    }
}
add_action('admin_inventories_action_bar_heading', 'AdminExportInventory::buttonHeading');
add_action('admin_footer', 'AdminExportInventory::modal');
