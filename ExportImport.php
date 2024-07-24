<?php
const EXIM_NAME = 'ExportImport';

class ExportImport {

    private string $name = EXIM_NAME;

    public function active(): void
    {
        $filesPath = Path::plugin(EXIM_NAME).'/files';

        if(!is_dir($filesPath)) {
            mkdir($filesPath, 0755);
            mkdir($filesPath.'/imports', 0755);
            mkdir($filesPath.'/imports/products', 0755);
            mkdir($filesPath.'/imports/products/add', 0755);
            mkdir($filesPath.'/imports/products/excel', 0755);
            mkdir($filesPath.'/imports/products/upload', 0755);
            mkdir($filesPath.'/imports/products/errors', 0755);
            mkdir($filesPath.'/imports/products/errors/import', 0755);
            mkdir($filesPath.'/imports/products/errors/upload', 0755);

            mkdir($filesPath, 0755);
            mkdir($filesPath.'/imports/inventory', 0755);
            mkdir($filesPath.'/imports/inventory/excel', 0755);
            mkdir($filesPath.'/imports/inventory/upload', 0755);
            mkdir($filesPath.'/imports/inventory/errors', 0755);
            mkdir($filesPath.'/imports/inventory/errors/import', 0755);
            mkdir($filesPath.'/imports/inventory/errors/upload', 0755);
        }
    }

    static function assets(): void
    {
        $asset = Path::plugin(EXIM_NAME).'/';
        if(Admin::is()) {
            Admin::asset()->location('header')->add(EXIM_NAME, $asset.'assets/css/style.admin.css');
            Admin::asset()->location('footer')->add(EXIM_NAME, $asset.'assets/js/script.admin.js');
        }
    }
}

include 'admin/ajax.php';
include 'admin/file-demo.php';
include 'admin/products/export.php';
include 'admin/products/import.php';
include 'admin/products/ajax.php';

include 'admin/inventory/export.php';
include 'admin/inventory/import.php';
include 'admin/inventory/ajax.php';

include 'admin/order/export.php';

add_action('admin_init', 'ExportImport::assets');