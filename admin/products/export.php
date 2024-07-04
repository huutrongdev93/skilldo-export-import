<?php
Class AdminExportProduct {
    static function buttonHeading(): void
    {
        echo Admin::button('blue', [
            'class' => 'btn-blue-bg',
            'id' => 'js_export_product_btn_modal',
            'icon' => '<i class="fa-light fa-download"></i>',
            'text' => trans('export.data')
        ]);
    }
    static function modal(): void
    {
        Plugin::view(EXIM_NAME, 'products/export-modal');
    }
}
add_action('admin_product_action_bar_heading', 'AdminExportProduct::buttonHeading');

if(Template::isPage('products_index')) {
    add_action('admin_footer', 'AdminExportProduct::modal');
}