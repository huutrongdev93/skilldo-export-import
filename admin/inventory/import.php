<?php
Class AdminImportInventory {
    static function buttonHeading(): void
    {
        echo Admin::button('blue', [
            'href' => Url::admin('plugins?page=import-inventory'),
            'class' => 'btn-blue-bg',
            'id' => 'js_import_inventory_btn_modal',
            'icon' => '<i class="fa-light fa-upload"></i>',
            'text' => trans('import.data')
        ]);
    }
    static function page(): void
    {
        $branches = Branch::gets();

        $branchOptions = [];

        foreach ($branches as $branch) {
            $branchOptions[$branch->id] = $branch->name;
        }

        Plugin::view(EXIM_NAME, 'inventory/import', ['branchOptions' => $branchOptions]);
    }
    static function numberImport($key  = null) {
        $numberKey = [
            'id',
            'code',
            'title',
            'attributes',
            'stock',
        ];

        foreach ($numberKey as $i => $title) {
            $number[$title] = $i + 1;
        }

        $number = apply_filters('import_inventory_column_list', $number);

        if(!empty($key)) return Arr::get($number, $key);

        return $number;
    }
    static function creatData($importData, $numberRow): array
    {
        $rowData = [
            'numberRow' => $numberRow,
            'errors'    => [],
        ];

        $importData[self::numberImport('id')] = (int)trim($importData[self::numberImport('id')]);

        $rowData['action'] = 'upload';

        $rowData['id']          = $importData[self::numberImport('id')];

        $rowData['title']       = Str::clear($importData[self::numberImport('title')]);

        $rowData['code']        = Str::clear($importData[self::numberImport('code')]);

        $rowData['stock']       = (int)trim($importData[self::numberImport('stock')]);

        return apply_filters('import_inventory_row_data', $rowData, $importData, $numberRow);
    }

    static function fileExcelDemo(): void
    {
        if(Admin::is()) {
            $segment = Url::segment();
            if(!empty($segment[2])
                && $segment[2] == 'plugins'
                && Request::get('page') == 'import-products'
                && Request::get('file-download') == 'products-import-add') {
                $excelFilePath  = Path::plugin(EXIM_NAME).'/assets/excel/products-demo-add.xlsx';
                $downloadFileName = 'products-demo-add.xlsx';
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');
                readfile($excelFilePath);
                die;
            }
        }
    }
}
add_action('admin_inventories_action_bar_heading', 'AdminImportInventory::buttonHeading');
add_action('template_redirect', 'AdminImportInventory::fileExcelDemo');
AdminMenu::add('import-inventory', 'import-inventory', 'import-inventory', ['callback' => 'AdminImportInventory::page', 'hidden' => true]);