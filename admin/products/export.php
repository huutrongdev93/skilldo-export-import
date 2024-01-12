<?php
Class AdminExportProduct {
    static function buttonHeading($actionList): void
    {
        echo '<a href="#" class="btn btn-blue btn-blue-bg" id="js_export_product_btn_modal"><i class="fa-light fa-download"></i> Xuất dữ liệu</a>';
    }
    static function modal(): void
    {
        Plugin::partial(EXIM_NAME, 'admin/views/products/product-modal');
    }
}
add_filter('admin_product_action_bar_heading', 'AdminExportProduct::buttonHeading');
if(Template::isPage('products_index')) {
    add_action('admin_footer', 'AdminExportProduct::modal');
}