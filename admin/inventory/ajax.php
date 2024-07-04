<?php
use JetBrains\PhpStorm\NoReturn;
use Illuminate\Database\Capsule\Manager as DB;
use SkillDo\Http\Request;
use SkillDo\Validate\Rule;

class InventoryImportAjax {
    #[NoReturn]
    static function upload(Request $request, $model): void
    {
        if($request->isMethod('post') && $request->hasFile('file')) {

            $validate = $request->validate([
                'file' => Rule::make('File sản phẩm')->notEmpty()->file(['xlsx', 'xls'], [
                    'min' => 1,
                    'max' => '2mb'
                ]),
                'columnMain' => Rule::make('Trường cập nhật')->notEmpty()->in(['id', 'code']),
                'branchId' => Rule::make('Kho hàng')->notEmpty()->integer()->min(1)
            ]);

            if ($validate->fails()) {
                response()->error($validate->errors());
            }

            $columnMain = Str::lower($request->input('columnMain'));

            $myPath = EXIM_NAME.'/files/imports/inventory/excel';

            $path = $request->file('file')->store($myPath, ['disk' => 'plugin']);

            if (empty($path)) {
                response()->error(trans('File not found'));
            }

            $filePath = FCPATH.'views/plugins/'.$path;

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/inventory/';

            $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');

            $spreadsheet = $reader->load($filePath);

            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();

            $highestColumn = $worksheet->getHighestColumn();

            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $schedules = [];

            for ($row = 1; $row <= $highestRow; $row++) {
                $empty = false;
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
                    if ($cell->isFormula()) {
                        $cellValue = $cell->getOldCalculatedValue();
                    } else {
                        $cellValue = $cell->getValue();
                    }
                    $schedules[$row][$col] = (string)$cellValue;
                    if(!empty($schedules[$row][$col])) {
                        $empty = true;
                    }
                }
                if(!$empty) {
                    array_pop($schedules);
                    break;
                }
            }

            if(!have_posts($schedules)) {
                $result['message'] 	= 'Không lấy được dữ liệu từ file excel này';
                echo json_encode($result);
                return;
            }

            $errors = 0;

            $upload = 0;

            if(is_dir($dataPath.'upload')) {
                deleteDirectory($dataPath.'upload');
            }
            if(!is_dir($dataPath.'upload')) {
                mkdir($dataPath.'upload', 0755);
            }

            foreach (new DirectoryIterator($dataPath.'errors/upload') as $fileInfo) {
                if(!$fileInfo->isDot()) {
                    unlink($fileInfo->getPathname());
                }
            }

            $rowDatas = [];

            foreach ($schedules as $numberRow => $schedule) {

                if($numberRow == 1) continue;

                if(count($schedule) < 5) {
                    $errors++;
                    continue;
                }

                $rowData = AdminImportInventory::creatData($schedule, $numberRow);

                if($columnMain == 'code') {
                    $rowData['errors'][] = 'Mã sản phẩm không được bỏ trống';
                }

                if(!empty($rowData['errors'])) {
                    $errors++;
                    $fileName = $dataPath.'errors/upload/product_'.$rowData['numberRow'].'.json';
                    file_put_contents($fileName, json_encode($rowData));
                    continue;
                }

                $rowDatas[] = $rowData;
            }

            $productsId = [];

            foreach($rowDatas as $rowData) {
                $productsId[] = $rowData[$columnMain];
            }

            $products = Product::whereIn($columnMain, $productsId)
                ->where('type', '<>', '')
                ->where('trash', '<>', null)
                ->where('public', '<>', null)
                ->select('id', 'code', 'parent_id')
                ->fetch();

            foreach ($rowDatas as $rowData) {

                $isset = false;

                foreach ($products as $product) {
                    if($product->{$columnMain} == $rowData[$columnMain]) {
                        $isset = true;
                        $rowData['id']        = $product->id;
                        $rowData['parent_id'] = $product->parent_id;
                        break;
                    }
                }

                if(!$isset) {
                    $rowData['errors'][] = 'không tìm thấy sản phẩm có '.$columnMain.' '.$rowData[$columnMain].' trong hệ thống';
                    $errors++;
                    $fileName = $dataPath.'errors/upload/product_'.$rowData['numberRow'].'.json';
                    file_put_contents($fileName, json_encode($rowData));
                    continue;
                }

                $upload++;

                $pathLog = $dataPath.'upload/'.$rowData['parent_id'];

                if(!file_exists($pathLog)) {
                    mkdir($pathLog, 0777);
                }

                if($rowData['parent_id'] == 0) {
                    $fileName = 'product_0_'.$rowData['numberRow'].'.json';
                }
                else {
                    $fileName = 'product_'.$rowData['parent_id'].'.json';
                }

                $fileName = $pathLog.'/'.$fileName;

                file_put_contents($fileName, "data:" . json_encode($rowData) . "\n", FILE_APPEND);
            }

            response()->success(trans('ajax.uploadFile.success'), [
                'errors'    => $errors,
                'upload'    => $upload,
            ]);
        }

        response()->error(trans('ajax.uploadFile.error'));
    }
    #[NoReturn]
    static function import(Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $branchId = (int)$request->input('branchId');

            if(empty($branchId)) {
                response()->error(trans('Bạn chưa chọn kho hàng để điều chỉnh số lượng'));
            }

            $branch = Branch::get($branchId);

            if(!have_posts($branch)) {
                response()->error(trans('Kho hàng bạn chọn không tồn tại'));
            }

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/inventory/';

            $success = [
                'upload' => 0
            ];

            $failed = [];

            $listProductsDefault = [];

            $listProductsVariable = [];

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataPath.'upload')) as $file) {

                if($file->getFileName() == '.') continue;

                $fullPath = trim((string)$file, '.');

                if(is_dir($fullPath)) continue;

                $fullPath = str_replace($dataPath.'upload\\', '', $fullPath);

                $fullPath = str_replace($dataPath.'upload/', '', $fullPath);

                $fullPath = trim((string)$fullPath, '\\');

                $fullPath = trim((string)$fullPath, '/');

                $products = trim(file_get_contents($dataPath.'upload/'.$fullPath));

                $products = explode('data:', $products);

                foreach ($products as $key => $product) {
                    if(empty($product)) {
                        unset($products[$key]);
                        continue;
                    }

                    $products[$key] = json_decode($product);
                }

                if(Str::is('0\\*', $fullPath) || Str::is('0/*', $fullPath)) {
                    $listProductsDefault[] = $products;
                }
                else {
                    $listProductsVariable[] = $products;
                }
            }

            $productInStock = [];

            $productOutStock = [];

            if(have_posts($listProductsDefault)) {

                $inventoriesUp = [];

                $inventoriesAdd = [];

                $productsId = [];

                foreach($listProductsDefault as $products) {
                    foreach ($products as $product) {
                        $productsId[] = $product->id;
                    }
                }

                $inventories = Inventory::whereIn('product_id', $productsId)->where('branch_id', $branchId)->fetch();

                foreach($listProductsDefault as $products) {

                    foreach ($products as $product) {

                        $inventory = [
                            'product_id'    => $product->id,
                            'product_name'  => $product->title,
                            'product_code'  => $product->code,
                            'parent_id'     => $product->parent_id,
                            'stock'         => $product->stock,
                            'status'        => ($product->stock > 0) ? 'instock' : 'outstock',
                            'branch_name'   => $branch->name,
                        ];

                        $checkNoChange = false;

                        foreach ($inventories as $inData) {
                            if($inData->product_id == $product->id && $inData->branch_id == $branchId) {
                                $inventory['id'] = $inData->id;
                                $inventory['stockOld'] = $inData->stock;
                                $inventory['numberRow'] = $product->numberRow;
                                if($inventory['stock'] == $inData->stock) {
                                    $success['upload']++;
                                    $checkNoChange = true;
                                }
                                break;
                            }
                        }

                        //Số lượng tồn kho không thay đổi
                        if($checkNoChange) {
                            continue;
                        }

                        $inventory = apply_filters('import_inventory_data_before_insert', $inventory, $product);

                        if(!empty($inventory['id'])) {
                            $inventoriesUp[] = $inventory;
                        }
                        else {
                            $inventory['branch_id'] = $branchId;
                            $inventoriesAdd[] = $inventory;
                        }

                        if($inventory['status'] == 'outstock') {
                            $productOutStock[] = $product->id;
                        }
                        else {
                            $productInStock[] = $product->id;
                        }
                    }
                }

                if(have_posts($inventoriesAdd)) {
                    $success['upload'] += count($inventoriesAdd);
                    DB::table('inventories')->insert($inventoriesAdd);
                }

                if(have_posts($inventoriesUp)) {

                    $inventoriesUpFilter = [];

                    foreach ($inventoriesUp as $inUp) {
                        unset($inUp['stockOld']);
                        unset($inUp['numberRow']);
                        $inventoriesUpFilter[] = $inUp;
                    }

                    $model->table('inventories')::updateBatch($inventoriesUpFilter, 'id');

                    $inventoriesId = [];

                    foreach ($inventoriesUp as $inUp) {
                        $inventoriesId[] = $inUp['id'];
                    }

                    $inventories = Inventory::whereIn('id', $inventoriesId)->where('branch_id', $branchId)->fetch();

                    $inventoriesHistory = [];

                    foreach ($inventoriesUp as $inUp) {
                        foreach ($inventories as $inventory) {
                            if ($inventory->id == $inUp['id']) {
                                if($inventory->stock == $inUp['stock']) {
                                    $success['upload']++;
                                    $inventoriesHistory[] = [
                                        'inventory_id' => $inventory->id,
                                        'message'       => InventoryHistory::message('Cập nhật bằng Excel', [
                                            'stockBefore'   => $inUp['stockOld'],
                                            'stockAfter'    => $inUp['stock'],
                                        ]),
                                        'type'      => 'stock',
                                        'action'    => ($inUp['stock'] > $inUp['stockOld']) ? 'cong' : 'tru'
                                    ];
                                }
                                else {
                                    $failed[] = [
                                        'numberRow' => $inUp['numberRow'],
                                        'title'     => $inUp['product_name'],
                                        'message'   => 'Cập nhật tồn kho sản phẩm không thành công',
                                    ];
                                }
                                break;
                            }
                        }
                    }

                    if(have_posts($inventoriesHistory)) {
                        DB::table('inventories_history')->insert($inventoriesHistory);
                    }
                }
            }

            if(have_posts($listProductsVariable)) {

                $inventoriesUp = [];

                $inventoriesAdd = [];

                foreach($listProductsVariable as $products) {

                    $status = 'outstock';

                    $products = array_values($products);

                    $inventories = Inventory::where('parent_id', $products[0]->parent_id)->where('branch_id', $branchId)->fetch();

                    foreach ($products as $product) {

                        $inventory = [
                            'product_id'    => $product->id,
                            'product_name'  => $product->title,
                            'product_code'  => $product->code,
                            'parent_id'     => $product->parent_id,
                            'stock'         => $product->stock,
                            'status'        => ($product->stock > 0) ? 'instock' : 'outstock',
                            'branch_name'   => $branch->name,
                        ];

                        $checkNoChange = false;

                        foreach ($inventories as $key => $inData) {
                            if($inData->product_id == $product->id && $inData->branch_id == $branchId) {
                                $inventory['id'] = $inData->id;
                                $inventory['stockOld'] = $inData->stock;
                                $inventory['numberRow'] = $product->numberRow;
                                if($inventory['stock'] == $inData->stock) {
                                    $success['upload']++;
                                    $checkNoChange = true;
                                }
                                unset($inventories[$key]);
                                break;
                            }
                        }

                        //Số lượng tồn kho không thay đổi
                        if($checkNoChange) {
                            continue;
                        }

                        $inventory = apply_filters('import_inventory_data_before_insert', $inventory, $product);

                        if(!empty($inventory['id'])) {
                            $inventoriesUp[] = $inventory;
                        }
                        else {
                            $inventory['branch_id'] = $branchId;
                            $inventoriesAdd[] = $inventory;
                        }

                        if($inventory['status'] == 'outstock') {
                            $productOutStock[] = $product->id;
                        }
                        else {
                            $status = 'instock';
                            $productInStock[] = $product->id;
                        }
                    }

                    if($status == 'outstock') {
                        foreach ($inventories as $key => $inData) {
                            if($inData->status == 'instock') {
                                $status = 'instock';
                                break;
                            }
                        }
                    }

                    if($status == 'outstock') {
                        $productOutStock[] = $products[0]->parent_id;
                    }

                    if($status == 'instock') {
                        $productInStock[] = $products[0]->parent_id;
                    }
                }

                if(have_posts($inventoriesAdd)) {
                    $success['upload'] += count($inventoriesAdd);
                    DB::table('inventories')->insert($inventoriesAdd);
                }

                if(have_posts($inventoriesUp)) {

                    $inventoriesUpFilter = [];

                    foreach ($inventoriesUp as $inUp) {
                        unset($inUp['stockOld']);
                        unset($inUp['numberRow']);
                        $inventoriesUpFilter[] = $inUp;
                    }

                    $model->table('inventories')::updateBatch($inventoriesUpFilter, 'id');

                    $inventoriesId = [];

                    foreach ($inventoriesUp as $inUp) {
                        $inventoriesId[] = $inUp['id'];
                    }

                    $inventories = Inventory::whereIn('id', $inventoriesId)->where('branch_id', $branchId)->fetch();

                    $inventoriesHistory = [];

                    foreach ($inventoriesUp as $inUp) {
                        foreach ($inventories as $inventory) {
                            if ($inventory->id == $inUp['id']) {
                                if($inventory->stock == $inUp['stock']) {
                                    $success['upload']++;
                                    $inventoriesHistory[] = [
                                        'inventory_id' => $inventory->id,
                                        'message'       => InventoryHistory::message('Cập nhật bằng Excel', [
                                            'stockBefore'   => $inUp['stockOld'],
                                            'stockAfter'    => $inUp['stock'],
                                        ]),
                                        'type'      => 'stock',
                                        'action'    => ($inUp['stock'] > $inUp['stockOld']) ? 'cong' : 'tru'
                                    ];
                                }
                                else {
                                    $failed[] = [
                                        'numberRow' => $inUp['numberRow'],
                                        'title'     => $inUp['product_name'],
                                        'message'   => 'Cập nhật tồn kho sản phẩm không thành công',
                                    ];
                                }
                                break;
                            }
                        }
                    }

                    if(have_posts($inventoriesHistory)) {
                        DB::table('inventories_history')->insert($inventoriesHistory);
                    }
                }
            }

            if(have_posts($productInStock)) {
                $model->table('products')::whereIn('id', $productInStock)->update(['stock_status' => 'instock']);
            }

            if(have_posts($productOutStock)) {
                $model->table('products')::whereIn('id', $productOutStock)->update(['stock_status' => 'outstock']);
            }

            foreach (new DirectoryIterator($dataPath.'errors/import') as $fileInfo) {
                if(!$fileInfo->isDot()) {
                    unlink($fileInfo->getPathname());
                }
            }

            if(have_posts($failed)) {
                foreach ($failed as $item) {
                    $fileName = $dataPath.'errors/import/product_'.$item['numberRow'].'.json';
                    file_put_contents($fileName, json_encode($item));
                }
            }

            response()->success(trans('ajax.update.success'), [
                'errors'    => count($failed),
                'upload'    => $success['upload'],
                'failed'    => $failed
            ]);
        }

        response()->error(trans('ajax.update.error'));
    }
    #[NoReturn]
    static function uploadError(Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $result = '';

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/inventory/errors/upload';
            //Cập nhật sản phẩm
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataPath)) as $file) {

                if($file->getFileName() == '.') continue;

                $fullPath = trim((string)$file, '.');

                if(is_dir($fullPath)) continue;

                $product = trim(file_get_contents($fullPath));

                $product = json_decode($product);

                if(is_skd_error($product->errors)) {
                    $product->errors = $product->errors->getErrorMessages();
                }

                $result .= Plugin::partial(EXIM_NAME, 'products/product-upload-error', ['item' => $product]);
            }

            response()->success(trans('ajax.load.success'), base64_encode($result));
        }

        response()->error(trans('ajax.load.error'));
    }
    #[NoReturn]
    static function importError(Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $result = '';

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/inventory/errors/import';

            //Cập nhật sản phẩm
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataPath)) as $file) {

                if($file->getFileName() == '.') continue;

                $fullPath = trim((string)$file, '.');

                if(is_dir($fullPath)) continue;

                $product = trim(file_get_contents($fullPath));

                $product = json_decode($product);

                $result .= Plugin::partial(EXIM_NAME, 'products/product-import-error', ['item' => $product]);
            }

            response()->success(trans('ajax.load.success'), base64_encode($result));
        }

        response()->error(trans('ajax.load.success'));
    }
}
Ajax::admin('InventoryImportAjax::upload');
Ajax::admin('InventoryImportAjax::import');
Ajax::admin('InventoryImportAjax::uploadError');
Ajax::admin('InventoryImportAjax::importError');