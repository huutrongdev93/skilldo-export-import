<?php
Class AdminExportOrder {
    static function buttonHeading(): void
    {
        echo Admin::button('blue', [
            'class' => 'btn-blue-bg',
            'id' => 'js_export_order_btn_modal',
            'icon' => '<i class="fa-light fa-download"></i>',
            'text' => trans('export.data')
        ]);
    }
    static function modal(): void
    {
        Plugin::view(EXIM_NAME, 'order/export-modal');
    }
}
add_action('admin_order_action_bar_heading', 'AdminExportOrder::buttonHeading');
add_action('admin_footer', 'AdminExportOrder::modal');
