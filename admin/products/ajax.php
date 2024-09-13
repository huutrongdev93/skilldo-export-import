<?php
use JetBrains\PhpStorm\NoReturn;
use Illuminate\Database\Capsule\Manager as DB;
use SkillDo\Http\Request;
use SkillDo\Validate\Rule;

class ProductsImportAjax {
    #[NoReturn]
    static function addUpload(Request $request, $model): void
    {
        if($request->isMethod('post') && $request->hasFile('file')) {

            $validate = $request->validate([
                'file' => Rule::make('File sản phẩm')->notEmpty()->file(['xlsx', 'xls'], [
                    'min' => 1,
                    'max' => '2mb'
                ])
            ]);

            if ($validate->fails()) {
                response()->error($validate->errors());
            }

            $myPath = EXIM_NAME.'/files/imports/products/excel/add';

            $path = $request->file('file')->store($myPath, ['disk' => 'plugin']);

            if (empty($path)) {
                response()->error(trans('File not found'));
            }

            $filePath = FCPATH.'views/plugins/'.$path;

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/';

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

            $add = 0;

            if(is_dir($dataPath.'add')) {
                deleteDirectory($dataPath.'add');
            }
            if(!is_dir($dataPath.'add')) {
                mkdir($dataPath.'add', 0755);
            }

            foreach (new DirectoryIterator($dataPath.'errors/upload') as $fileInfo) {
                if(!$fileInfo->isDot()) {
                    unlink($fileInfo->getPathname());
                }
            }

            $categories = ProductCategory::gets(Qr::set()->select('id', 'name'));

            $brands = Brand::gets(Qr::set()->select('id', 'name'));

            foreach ($schedules as $numberRow => $schedule) {

                $pathLog = $dataPath;

                if($numberRow == 1) continue;

                if(count($schedule) < 23) {
                    $errors++;
                    continue;
                }

                $rowData = AdminImportProduct::creatDataAdd($schedule, $numberRow, $categories, $brands);

                if(!empty($rowData['errors'])) {
                    $errors++;
                    $fileName = $dataPath.'errors/upload/product_'.$numberRow.'.json';
                    file_put_contents($fileName, json_encode($rowData));
                    continue;
                }

                $add++;
                $pathLog .= 'add/'.$rowData['parent_id'];

                if(!file_exists($pathLog)) mkdir($pathLog, 0777);

                if($rowData['parent_id'] == 0) {
                    $fileName = 'product_0_'.$numberRow.'.json';
                }
                else {
                    $fileName = 'product_'.$rowData['parent_id'].'.json';
                }

                $fileName = $pathLog.'/'.$fileName;

                file_put_contents($fileName, "data:" . json_encode($rowData) . "\n", FILE_APPEND);
            }

            response()->success(trans('ajax.uploadFile.success'), [
                'errors'    => $errors,
                'add'       => $add,
                'upload'    => $upload,
            ]);
        }

        response()->error(trans('ajax.uploadFile.error'));
    }
    #[NoReturn]
    static function addImport(Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/';

            $success = [
                'add' => 0,
            ];

            $failed = [];

            $listProductsDefault = [];

            $listProductsVariable = [];

            $inventories = [];

            //Thêm mới sản phẩm
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataPath.'add')) as $file) {
                if($file->getFileName() == '.') continue;
                $fullPath = trim((string)$file, '.');
                if(is_dir($fullPath)) continue;
                $fullPath = str_replace($dataPath.'add\\', '', $fullPath);
                $fullPath = str_replace($dataPath.'add/', '', $fullPath);
                $fullPath = trim((string)$fullPath, '\\');
                $fullPath = trim((string)$fullPath, '/');

                $products = trim(file_get_contents($dataPath.'add/'.$fullPath));
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

            if(have_posts($listProductsDefault)) {

                foreach($listProductsDefault as $products) {

                    foreach ($products as $product) {

                        $categoriesUpdate = [];

                        if(!empty($product->categories)) {

                            $categories = ProductCategory::gets(Qr::set()->whereIn('name', $product->categories)->select('id', 'name'));

                            if(!have_posts($categories)) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Không tìm thấy danh mục bạn nhập',
                                ];
                                continue;
                            }
                            $checkError = true;
                            foreach ($product->categories as $categoryName) {
                                $checkError = true;
                                foreach ($categories as $cate) {
                                    if($cate->name == $categoryName) {
                                        $categoriesUpdate[] = $cate->id;
                                        $checkError = false;
                                        break;
                                    }
                                }
                                if($checkError) {
                                    $failed[] = [
                                        'id'        => $product->id,
                                        'title'     => $product->title,
                                        'message'   => 'Không tìm thấy danh mục '.$categoryName,
                                    ];
                                    continue;
                                }
                            }
                            if($checkError) continue;
                        }

                        $productUp = [
                            'title'         => $product->title,
                            'code'          => $product->code,
                            'public'        => $product->public,
                            'excerpt'       => $product->excerpt,
                            'price'         => $product->price,
                            'price_sale'    => $product->price_sale,
                            'image'         => FileHandler::handlingUrl($product->image),
                            'weight'        => $product->weight,
                            'long'        => $product->long,
                            'width'        => $product->width,
                            'height'        => $product->height,
                            'seo_title'     => $product->seo_title,
                            'seo_description'   => $product->seo_description,
                            'seo_keyword'   => $product->seo_keyword,
                            'taxonomies'  => [
                                'products_categories' => $categoriesUpdate
                            ]
                        ];

                        foreach (Prd::collections() as $collectionKey => $collection) {
                            $productUp[$collectionKey] = $product->{$collectionKey};
                        }

                        $productUp = apply_filters('import_product_add_default_data_before_insert', $productUp, $product);

                        $errors = Product::insert($productUp);

                        if(is_skd_error($errors)) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message'   => $errors->errors[0],
                            ];
                            continue;
                        }

                        $success['add']++;

                        $inventories[] = [
                            'product_id' => $errors,
                            'product_code' => $product->code,
                            'product_name' => $product->title,
                            'parent_id' => 0,
                            'stock' => 0,
                            'reserved' => 0,
                            'status' => 'outstock',
                        ];
                    }
                }
            }

            if(have_posts($listProductsVariable)) {

                $attributes = Attributes::gets();

                $attributesItem = AttributesItem::gets();

                foreach($listProductsVariable as $products) {

                    $countAttr = 0;

                    foreach ($products as $productKey => $product) {

                        if (!isset($product->attributes) || !have_posts($product->attributes)) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message'   => 'Sản phẩm chưa có thuộc tính',
                            ];
                            unset($products[$productKey]);
                            continue;
                        }

                        $product->attributes = (array)$product->attributes;

                        if(empty($countAttr)) {
                            $countAttr = count($product->attributes);
                        }

                        if($countAttr != count($product->attributes)) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message'   => 'Số lượng thuộc tính trong nhóm không đồng điều',
                            ];
                            unset($products[$productKey]);
                            continue;
                        }

                        $product->relationships = [];

                        foreach ($product->attributes as $attTitle => $attItemTitle) {

                            $checkAttributeError = $attTitle;

                            foreach ($attributes as $attribute) {
                                if($attTitle == $attribute->title) {
                                    $checkAttributeError = false;
                                    $checkAttributeItemError = $attItemTitle;
                                    foreach ($attributesItem as $attributeItem) {
                                        if($attItemTitle == $attributeItem->title && $attributeItem->option_id == $attribute->id) {
                                            $checkAttributeItemError = false;
                                            $product->attributes[$attribute->id][$attributeItem->id] = $attributeItem;
                                            $product->relationships[$attribute->id] = $attributeItem->id;
                                            break;
                                        }
                                    }
                                    break;
                                }
                            }

                            unset($product->attributes[$attTitle]);

                            if($checkAttributeError !== false) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Không tìm thấy thuộc tính '.$checkAttributeError.' trong hệ thống',
                                ];
                                unset($products[$productKey]);
                                break;
                            }

                            if(isset($checkAttributeItemError) && $checkAttributeItemError !== false) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Không tìm thấy giá trị thuộc tính '.$checkAttributeItemError.' trong hệ thống',
                                ];
                                unset($products[$productKey]);
                                break;
                            }
                        }
                    }

                    if(!empty($products)) {

                        $products = array_values($products);

                        //Thêm mới biến thể vào sản phẩm có trước
                        if(is_numeric($products[0]->parent_id)) {

                            $productMaim = Product::get($products[0]->parent_id);

                            if (!have_posts($productMaim)) {
                                foreach ($products as $product) {
                                    $failed[] = [
                                        'numberRow' => $product->numberRow,
                                        'title'     => $product->title,
                                        'message'   => 'Không lấy được sản phẩm chính của nhóm sản phẩm ' . $products[0]->parent_id,
                                    ];
                                }
                                continue;
                            }

                            if(!empty($products)) {

                                $productVariations = Variation::where('parent_id', $productMaim->id)->fetch();

                                if(have_posts($productVariations)) {

                                    $productVariationId = [];

                                    foreach ($productVariations as $productVariation) {
                                        $productVariation->attributes = [];
                                        $productVariation->attributesItem = [];
                                        $productVariation->relationships = [];
                                        $productVariationId[] = $productVariation->id;
                                    }

                                    $model = model('products_attribute_item');

                                    $relationships = $model::whereIn('variation_id', $productVariationId)->select('product_id', 'variation_id', 'attribute_id', 'item_id')->fetch();

                                    foreach ($productVariations as $productVariation) {

                                        foreach ($relationships as $relations) {
                                            if ($relations->variation_id == $productVariation->id) {
                                                $productVariation->attributes[] = $relations->attribute_id;
                                                $productVariation->attributesItem[] = $relations->item_id;
                                                $productVariation->relationships[$relations->attribute_id] = $relations->item_id;
                                            }
                                        }

                                        $productVariation->attributes = array_unique($productVariation->attributes);

                                        $productVariation->attributesItem = array_unique($productVariation->attributesItem);
                                    }

                                    $attributesProduct = $productVariations[0]->attributes;

                                    foreach ($products as $productKey => $product) {

                                        $attrs = array_keys($product->relationships);

                                        if(!empty(array_diff($attrs, $attributesProduct))) {
                                            $failed[] = [
                                                'numberRow' => $product->numberRow,
                                                'title'     => $product->title,
                                                'message'   => 'Thuộc tính không có sẳn trong sản phẩm',
                                            ];
                                            unset($products[$productKey]);
                                            continue;
                                        }

                                        $checkVariationError = false;

                                        foreach ($productVariations as $productVariation) {
                                            if(empty(array_diff($product->relationships, $productVariation->relationships))) {
                                                $failed[] = [
                                                    'numberRow' => $product->numberRow,
                                                    'title'     => $product->title,
                                                    'message'   => 'Biến thể có thuộc tính như vầy đã tồn tại',
                                                ];
                                                $checkVariationError = true;
                                                break;
                                            }
                                        }

                                        if($checkVariationError) {
                                            unset($products[$productKey]);
                                            continue;
                                        }
                                    }
                                }

                                if(!empty($products)) {

                                    $attributesCompare = [];

                                    foreach ($products as $productKey => $product) {

                                        $attributesCompare[$productKey] = [
                                            'numberRow' => $product->numberRow,
                                            'title'     => $product->title,
                                            'relationships' => $product->relationships,
                                        ];

                                        foreach ($attributesCompare as $key => $productVariation) {

                                            if($key == $productKey) continue;

                                            if(empty(array_diff($product->relationships, $productVariation['relationships']))) {
                                                $failed[] = [
                                                    'numberRow' => $productVariation['numberRow'],
                                                    'title'     => $productVariation['title'],
                                                    'message'   => 'Biến thể có thuộc tính như vầy đang bị trùng với biến thể khác',
                                                ];
                                                unset($products[$productKey]);
                                                if(isset($products[$key])) {
                                                    $failed[] = [
                                                        'numberRow' => $products[$key]->numberRow,
                                                        'title'     => $products[$key]->title,
                                                        'message'   => 'Biến thể có thuộc tính như vầy đang bị trùng với biến thể khác',
                                                    ];
                                                    unset($products[$key]);
                                                }
                                                break;
                                            }
                                        }
                                    }

                                    if(!empty($products)) {

                                        $isChangeProductMain = false;

                                        foreach ($products as $productKey => $product) {

                                            $productAdd = [
                                                'title'         => $product->title,
                                                'public'        => $product->public,
                                                'excerpt'       => $product->excerpt,
                                                'price'         => $product->price,
                                                'price_sale'    => $product->price_sale,
                                                'image'         => FileHandler::handlingUrl($product->image),
                                                'weight'        => $product->weight,
                                                'long'        => $product->long,
                                                'width'        => $product->width,
                                                'height'        => $product->height,
                                                'parent_id'     => $productMaim->id,
                                                'type'          => 'variations',
                                                'status'        => 'public'
                                            ];

                                            $productAdd = apply_filters('import_product_add_variation_data_before_insert', $productAdd, $product);

                                            $variationId = Product::insert($productAdd);

                                            if (is_skd_error($variationId)) {
                                                $failed[] = [
                                                    'numberRow' => $product->numberRow,
                                                    'title'     => $product->title,
                                                    'message'   => $variationId->first(),
                                                ];
                                                continue;
                                            }

                                            $success['add']++;

                                            $relationshipAdd = [];

                                            foreach ($product->relationships as $attrId => $attrItemId) {

                                                $relationshipAdd[] = [
                                                    'product_id' => $productMaim->id,
                                                    'variation_id' => $variationId,
                                                    'item_id' => $attrItemId,
                                                    'attribute_id' => $attrId,
                                                ];
                                            }

                                            if(!empty($relationshipAdd)) {
                                                DB::table('products_attribute_item')->insert($relationshipAdd);
                                            }

                                            if ($product->default == 1) {

                                                $isChangeProductMain = true;

                                                Product::updateMeta($productMaim->id, 'default', $variationId);

                                                $productMaim->title = $product->title;
                                                $productMaim->public = $product->public;
                                                $productMaim->code = $product->code;
                                                $productMaim->excerpt = $product->excerpt;
                                                $productMaim->price = $product->price;
                                                $productMaim->price_sale = $product->price_sale;
                                                $productMaim->image = FileHandler::handlingUrl($product->image);
                                                $productMaim->weight = $product->weight;
                                                $productMaim->long = $product->long;
                                                $productMaim->width = $product->width;
                                                $productMaim->height = $product->height;
                                                $productMaim->brand_id = $product->brand_id;
                                                $productMaim->seo_title = $product->seo_title;
                                                $productMaim->seo_description = $product->seo_description;
                                                $productMaim->seo_keyword = $product->seo_keyword;
                                                $productMaim->taxonomies = [
                                                    'products_categories' => $product->categories
                                                ];

                                                foreach (Prd::collections() as $collectionKey => $collection) {
                                                    $productMaim->{$collectionKey} = $product->{$collectionKey};
                                                }
                                            }

                                            $inventories[] = [
                                                'product_id' => $variationId,
                                                'product_code' => $product->code,
                                                'product_name' => $product->title,
                                                'parent_id' => $productMaim->id,
                                                'stock' => 0,
                                                'reserved' => 0,
                                                'status' => 0,
                                            ];
                                        }

                                        if($isChangeProductMain || $productMaim->hasVariation == 0) {
                                            $productMaim->hasVariation = 1;
                                            Product::insert((array)$productMaim);
                                        }
                                    }
                                }
                            }

                        }
                        //Thêm mới sản phẩm có biến thể
                        else {

                            $attributesCompare = [];

                            foreach ($products as $productKey => $product) {

                                $attributesCompare[$productKey] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'relationships' => $product->relationships,
                                ];

                                foreach ($attributesCompare as $key => $productVariation) {

                                    if($key == $productKey) continue;

                                    if(empty(array_diff($product->relationships, $productVariation['relationships']))) {
                                        $failed[] = [
                                            'numberRow' => $productVariation['numberRow'],
                                            'title'     => $productVariation['title'],
                                            'message'   => 'Biến thể có thuộc tính như vầy đang bị trùng với biến thể khác',
                                        ];
                                        unset($products[$productKey]);
                                        if(isset($products[$key])) {
                                            $failed[] = [
                                                'numberRow' => $products[$key]->numberRow,
                                                'title'     => $products[$key]->title,
                                                'message'   => 'Biến thể có thuộc tính như vầy đang bị trùng với biến thể khác',
                                            ];
                                            unset($products[$key]);
                                        }
                                        break;
                                    }
                                }
                            }

                            if(!empty($products)) {

                                $productMain = [];

                                foreach ($products as $productKey => $product) {

                                    if($product->default == 1) {

                                        $productMain = [
                                            'numberRow'     => $product->numberRow,
                                            'title'         => $product->title,
                                            'public'        => $product->public,
                                            'excerpt'       => $product->excerpt,
                                            'price'         => $product->price,
                                            'price_sale'    => $product->price_sale,
                                            'image'         => FileHandler::handlingUrl($product->image),
                                            'weight'        => $product->weight,
                                            'long'        => $product->long,
                                            'width'        => $product->width,
                                            'height'        => $product->height,
                                            'seo_title'     => $product->seo_title,
                                            'seo_description'   => $product->seo_description,
                                            'seo_keyword'   => $product->seo_keyword,
                                            'categories'    => $product->categories
                                        ];
                                        foreach (Prd::collections() as $collectionKey => $collection) {
                                            $productMain[$collectionKey] = $product->{$collectionKey};
                                        }
                                        break;
                                    }
                                }

                                if(empty($productMain)) {
                                    foreach ($products as $product) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title'     => $product->title,
                                            'message'   => 'Không tìm thấy sản phẩm chính cho nhóm ' . $product->parent_id,
                                        ];
                                    }
                                    continue;
                                }

                                //Thêm mới sản phẩm chính
                                if(isset($productMain['categories'])) {
                                    $productMain['taxonomies'] = [
                                        'products_categories' => $productMain['categories']
                                    ];
                                    unset($productMain['categories']);
                                }

                                $productMain['hasVariation'] = 1;

                                $productId = Product::insert($productMain);

                                if(is_skd_error($productId)) {
                                    $failed[] = [
                                        'numberRow' => $productMain['numberRow'],
                                        'title'     => $productMain['title'],
                                        'message'   => $productId->first(),
                                    ];
                                    continue;
                                }

                                foreach ($products as $productKey => $product) {

                                    $productAdd = [
                                        'title'         => $product->title,
                                        'public'        => $product->public,
                                        'excerpt'       => $product->excerpt,
                                        'price'         => $product->price,
                                        'price_sale'    => $product->price_sale,
                                        'image'         => FileHandler::handlingUrl($product->image),
                                        'weight'        => $product->weight,
                                        'long'        => $product->long,
                                        'width'        => $product->width,
                                        'height'        => $product->height,
                                        'parent_id'     => $productId,
                                        'type'          => 'variations',
                                        'status'        => 'public'
                                    ];

                                    $productAdd = apply_filters('import_product_add_variation_data_before_insert', $productAdd, $product);

                                    $variationId = Product::insert($productAdd);

                                    if (is_skd_error($variationId)) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title'     => $product->title,
                                            'message'   => $variationId->first(),
                                        ];
                                        continue;
                                    }

                                    $success['upload']++;

                                    $relationshipAdd = [];

                                    foreach ($product->relationships as $attr => $attrItem) {

                                        $relationshipAdd[] = [
                                            'product_id' => $productId,
                                            'variation_id' => $variationId,
                                            'item_id' => $attr,
                                            'attribute_id' => $attrItem,
                                        ];
                                    }

                                    if(!empty($relationshipAdd)) {
                                        DB::table('products_attribute_item')->insert($relationshipAdd);
                                    }

                                    if ($product->default == 1) {
                                        Product::updateMeta($productId, 'default', $variationId);
                                    }

                                    $inventories[] = [
                                        'product_id' => $variationId,
                                        'product_code' => $product->code,
                                        'product_name' => $product->title,
                                        'parent_id' => $productId,
                                        'stock' => 0,
                                        'reserved' => 0,
                                        'status' => 0,
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            //Thêm kho hàng
            if(have_posts($inventories) && class_exists('Inventory') && class_exists('Branch')) {

                $branches = Branch::gets();

                if(have_posts($branches)) {

                    foreach ($inventories as $key => $invent) {
                        foreach ($branches as $keyBrand => $branch) {
                            $invent['branch_id']    = $branch->id;
                            $invent['branch_name']  = $branch->name;
                            if($keyBrand == 0) {
                                $inventories[$key] = $invent;
                            }
                            else {
                                $inventories[] = $invent;
                            }
                        }
                    }

                    DB::table('inventories')->insert($inventories);
                }
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

            response()->success(trans('ajax.add.success'), [
                'errors'    => count($failed),
                'add'       => $success['add'],
                'failed'    => $failed
            ]);
        }

        response()->error(trans('ajax.update.error'));
    }
    #[NoReturn]
    static function upload(Request $request, $model): void
    {
        if($request->isMethod('post') && $request->hasFile('file')) {

            $validate = $request->validate([
                'file' => Rule::make('File sản phẩm')->notEmpty()->file(['xlsx', 'xls'], [
                    'min' => 1,
                    'max' => '2mb'
                ]),
                'columnMain' => Rule::make('Trường cập nhật')->notEmpty()->in(['id', 'code'])
            ]);

            if ($validate->fails()) {
                response()->error($validate->errors());
            }

            $columnMain = Str::lower($request->input('columnMain'));

            $myPath = EXIM_NAME.'/files/imports/products/excel';

            $path = $request->file('file')->store($myPath, ['disk' => 'plugin']);

            if (empty($path)) {
                response()->error(trans('File not found'));
            }

            $filePath = FCPATH.'views/plugins/'.$path;

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/';

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

            $categories = ProductCategory::gets(Qr::set()->select('id', 'name'));

            $brands = Brand::gets(Qr::set()->select('id', 'name'));

            $rowDatas = [];

            foreach ($schedules as $numberRow => $schedule) {

                if($numberRow == 1) continue;

                if(count($schedule) < 23) {
                    $errors++;
                    continue;
                }

                $rowData = AdminImportProduct::creatData($schedule, $numberRow, $categories, $brands);

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
                    $rowData['errors'][] = 'không tìm thấy sản phẩm có id '.$rowData['id'].' trong hệ thống';
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

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/';

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

            if(have_posts($listProductsDefault)) {

                foreach($listProductsDefault as $products) {

                    foreach ($products as $product) {

                        $checkVariation = Product::count(Qr::set('type', 'variations')->where('parent_id', $product->id)->where('status', '<>', 'draft'));

                        if($checkVariation != 0) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message'   => 'Sản phẩm này có biến thể nhưng không có thuộc tính',
                            ];
                            continue;
                        }

                        $productUp = [
                            'id'            => $product->id,
                            'title'         => $product->title,
                            'code'          => $product->code,
                            'public'        => $product->public,
                            'excerpt'       => $product->excerpt,
                            'price'         => $product->price,
                            'price_sale'    => $product->price_sale,
                            'image'         => FileHandler::handlingUrl($product->image),
                            'weight'        => $product->weight,
                            'long'        => $product->long,
                            'width'        => $product->width,
                            'height'        => $product->height,
                            'brand_id'      => $product->brand_id,
                            'seo_title'     => $product->seo_title,
                            'seo_description'   => $product->seo_description,
                            'seo_keyword'   => $product->seo_keyword,
                            'taxonomies'  => [
                                'products_categories' => $product->categories
                            ]
                        ];

                        foreach (Prd::collections() as $collectionKey => $collection) {
                            $productUp[$collectionKey] = $product->{$collectionKey};
                        }

                        $productUp = apply_filters('import_product_update_default_data_before_insert', $productUp, $product);

                        $errors = Product::insert($productUp);

                        if(is_skd_error($errors)) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message'   => $errors->errors[0],
                            ];
                            continue;
                        }

                        $success['upload']++;
                    }
                }
            }

            if(have_posts($listProductsVariable)) {

                $attributes = Attributes::gets();

                $attributesItem = AttributesItem::gets();

                foreach($listProductsVariable as $products) {

                    $countAttr = 0;

                    $defaultId = 0;

                    $defaultError = false;

                    $attributesGroupId = [];

                    foreach ($products as $productKey => $product) {

                        if($product->default == 1) {
                            if(!empty($defaultId)) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Nhóm có nhiều hơn 1 sản phẩm chính',
                                ];
                                unset($products[$productKey]);
                                continue;
                            }
                            $defaultId = $product->id;
                        }

                        if (!isset($product->attributes) || !have_posts($product->attributes)) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message'   => 'Sản phẩm chưa có thuộc tính',
                            ];
                            if($product->default == 1) {
                                $defaultError = true;
                            }
                            unset($products[$productKey]);
                            continue;
                        }

                        $product->attributes = (array)$product->attributes;

                        if(empty($countAttr)) {
                            $countAttr = count($product->attributes);
                        }

                        if($countAttr != count($product->attributes)) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message'   => 'Số lượng thuộc tính trong nhóm không đồng điều',
                            ];
                            if($product->default == 1) {
                                $defaultError = true;
                            }
                            unset($products[$productKey]);
                            continue;
                        }

                        $product->relationships = [];

                        foreach ($product->attributes as $attTitle => $attItemTitle) {

                            $checkAttributeError = $attTitle;

                            foreach ($attributes as $attribute) {
                                if($attTitle == $attribute->title) {
                                    $checkAttributeError = false;
                                    $checkAttributeItemError = $attItemTitle;
                                    foreach ($attributesItem as $attributeItem) {
                                        if($attItemTitle == $attributeItem->title && $attributeItem->option_id == $attribute->id) {
                                            $checkAttributeItemError = false;
                                            $product->attributes[$attribute->id][$attributeItem->id] = $attributeItem;
                                            $product->relationships[$attribute->id] = $attributeItem->id;
                                            break;
                                        }
                                    }
                                    break;
                                }
                            }

                            unset($product->attributes[$attTitle]);

                            if($checkAttributeError !== false) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Không tìm thấy thuộc tính '.$checkAttributeError.' trong hệ thống',
                                ];
                                if($product->default == 1) {
                                    $defaultError = true;
                                }
                                unset($products[$productKey]);
                                break;
                            }

                            if(isset($checkAttributeItemError) && $checkAttributeItemError !== false) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Không tìm thấy giá trị thuộc tính '.$checkAttributeItemError.' trong hệ thống',
                                ];
                                if($product->default == 1) {
                                    $defaultError = true;
                                }
                                unset($products[$productKey]);
                                break;
                            }
                        }

                        $attributesGroupId = array_merge($attributesGroupId, array_keys($product->relationships));

                        if($product->default == 1) {
                            $defaultId = $product->id;
                        }
                    }

                    if($defaultError) {
                        foreach ($products as $product) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message'   => 'Sản phẩm chính của nhóm đã bị lỗi khi cập nhật',
                            ];
                        }
                        continue;
                    }

                    if(empty($products)) {
                        continue;
                    }

                    $products = array_values($products);

                    if(empty($defaultId)) {
                        $defaultId = (int)Product::getMeta($products[0]->parent_id, 'default', true);
                    }

                    if(empty($defaultId)) {
                        foreach ($products as $product) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message' => 'Không xác định được id sản phẩm chính của nhóm sản phẩm ' . $product->parent_id,
                            ];
                        }
                        continue;
                    }

                    $productMaim = Product::get(Qr::set($products[0]->parent_id));

                    if(!have_posts($productMaim)) {
                        foreach ($products as $product) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message' => 'Không lấy được sản phẩm chính của nhóm sản phẩm ' . $product->parent_id,
                            ];
                        }
                        continue;
                    }

                    if(!empty($products)) {

                        $attributesGroupId = array_unique($attributesGroupId);

                        $productVariations = Variation::where('parent_id', $productMaim->id)->fetch();

                        if(!have_posts($productVariations)) {
                            foreach ($products as $product) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Không tìm thấy sản phẩm này để cập nhật',
                                ];
                            }
                            continue;
                        }

                        $model = model('products_attribute_item');

                        $relationships = $model::where('product_id', $productMaim->id)->select('id', 'product_id', 'variation_id', 'attribute_id', 'item_id')->fetch();

                        $relationshipsVariation = [];

                        foreach ($relationships as $relations) {
                            if (!isset($relationshipsVariation[$relations->variation_id])) {
                                $relationshipsVariation[$relations->variation_id] = [];
                            }
                            $relationshipsVariation[$relations->variation_id][$relations->attribute_id] = $relations->item_id;
                        }

                        //Xóa các sản phẩm cần cập nhật
                        foreach ($productVariations as $productKey => $productVariation) {
                            $productVariation->attributes = array_keys($relationshipsVariation[$productVariation->id] ?? []);
                            $productVariation->attributesItem = array_values($relationshipsVariation[$productVariation->id] ?? []);
                            $productVariation->relationships = array_keys($relationshipsVariation[$productVariation->id] ?? []);
                            foreach ($products as $product) {
                                if($product->id == $productVariation->id) {
                                    unset($productVariations[$productKey]);
                                    break;
                                }
                            }
                        }

                        //Các sản phẩm giữ nguyên
                        if(have_posts($productVariations)) {

                            $attributesGroupIdOld = [];

                            foreach ($productVariations as $productVariation) {
                                if(isset($relationshipsVariation[$productVariation->id])) {
                                    unset($relationshipsVariation[$productVariation->id]);
                                }
                                $attributesGroupIdOld = array_merge($attributesGroupIdOld, $productVariation->attributes);
                            }

                            $attributesGroupIdOld = array_unique($attributesGroupIdOld);

                            $attributesGroupId = array_unique(array_merge($attributesGroupIdOld, $attributesGroupId));

                            foreach ($products as $product) {

                                $attrs = array_keys($product->relationships);

                                if(!empty(array_diff($attrs, $attributesGroupIdOld))) {
                                    $failed[] = [
                                        'numberRow' => $product->numberRow,
                                        'title'     => $product->title,
                                        'message'   => 'Thuộc tính không trùng với thuộc tính các biến thể khác',
                                    ];
                                    unset($products[$productKey]);
                                    continue;
                                }

                                $checkVariationError = false;
                                foreach ($productVariations as $productVariation) {
                                    if(empty(array_diff($product->relationships, $productVariation->relationships))) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title'     => $product->title,
                                            'message'   => 'Biến thể có giá trị thuộc tính như vầy đã tồn tại',
                                        ];
                                        $checkVariationError = true;
                                        break;
                                    }
                                }
                                if($checkVariationError) {
                                    unset($products[$productKey]);
                                    continue;
                                }
                            }
                        }

                        if(!empty($products)) {

                            $attributesCompare = [];

                            foreach ($products as $productKey => $product) {

                                $attributesCompare[$productKey] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'relationships' => $product->relationships,
                                ];

                                foreach ($attributesCompare as $key => $productVariation) {

                                    if($key == $productKey) continue;

                                    if(empty(array_diff($product->relationships, $productVariation['relationships']))) {
                                        $failed[] = [
                                            'numberRow' => $productVariation['numberRow'],
                                            'title'     => $productVariation['title'],
                                            'message'   => 'Biến thể có giá trị thuộc tính như vầy đang bị trùng với biến thể khác',
                                        ];
                                        unset($products[$productKey]);
                                        if(isset($products[$key])) {
                                            $failed[] = [
                                                'numberRow' => $products[$key]->numberRow,
                                                'title'     => $products[$key]->title,
                                                'message'   => 'Biến thể có giá trị thuộc tính như vầy đang bị trùng với biến thể khác',
                                            ];
                                            unset($products[$key]);
                                        }
                                        break;
                                    }
                                }
                            }

                            if(!empty($products)) {

                                $productMainUp = [];

                                $relationshipsUp = [];

                                $relationshipsAdd = [];

                                if($productMaim->hasVariation == 0) {
                                    $productMainUp['hasVariation'] = 1;
                                }

                                foreach ($products as $productKey => $product) {

                                    $productUp = [
                                        'title'         => $product->title,
                                        'public'        => $product->public,
                                        'excerpt'       => $product->excerpt,
                                        'price'         => $product->price,
                                        'price_sale'    => $product->price_sale,
                                        'image'         => FileHandler::handlingUrl($product->image),
                                        'weight'        => $product->weight,
                                        'long'        => $product->long,
                                        'width'        => $product->width,
                                        'height'        => $product->height,
                                    ];

                                    $productUp = apply_filters('import_product_update_variation_data_before_insert', $productUp, $product);

                                    $variationId = $model->table('products')
                                        ->where('id', $product->id)
                                        ->update($productUp);

                                    if (is_skd_error($variationId)) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title'     => $product->title,
                                            'message'   => $variationId->first(),
                                        ];
                                        continue;
                                    }

                                    $success['upload']++;

                                    if (!empty($relationshipsVariation[$product->id])) {
                                        foreach ($relationshipsVariation[$product->id] as $attId => $attItemId) {
                                            if(!empty($product->relationships[$attId])) {
                                                //cập nhật
                                                if($product->relationships[$attId] != $attItemId) {
                                                    $relationshipsUp[$product->id][$attId] = [
                                                        'old' => $attItemId,
                                                        'new' => $product->relationships[$attId]
                                                    ];
                                                }
                                                unset($product->relationships[$attId]);
                                                unset($relationshipsVariation[$product->id][$attId]);
                                            }
                                        }
                                    }

                                    if(!empty($product->relationships)) {
                                        foreach ($product->relationships as $attrId => $attrItemId) {
                                            $relationshipsAdd[] = [
                                                'product_id' => $productMaim->id,
                                                'variation_id' => $product->id,
                                                'item_id' => $attrItemId,
                                                'attribute_id' => $attrId,
                                            ];
                                        }
                                    }

                                    if ($product->default == 1) {

                                        Product::updateMeta($productMaim->id, 'default', $product->id);

                                        $columns = [
                                            'title','public', 'code', 'excerpt',
                                            'price', 'price_sale', 'image', 'weight', 'long', 'width', 'height',
                                            'brand_id', 'seo_title', 'seo_description', 'seo_keyword'
                                        ];

                                        foreach ($columns as $column) {
                                            if($product->{$column} != $productMaim->{$column}) {
                                                $productMainUp[$column] = $product->{$column};
                                            }
                                        }

                                        $productMaim->taxonomies = [
                                            'products_categories' => $product->categories
                                        ];

                                        foreach (Prd::collections() as $collectionKey => $collection) {
                                            if($product->{$collectionKey} != $productMaim->{$collectionKey}) {
                                                $productMainUp[$collectionKey] = $product->{$collectionKey};
                                            }
                                        }
                                    }
                                }

                                //cập nhật nhóm attributes
                                if(have_posts($attributesGroupId)) {

                                    $relationshipsAttribute = $model->table('products_attribute')::where('product_id', $productMaim->id)->fetch();

                                    $relationshipsAttributeDelete = [];

                                    if(have_posts($relationshipsAttribute)) {
                                        foreach ($relationshipsAttribute as $resKey => $relationship) {
                                            if (($key = array_search($relationship->attribute_id, $attributesGroupId)) !== false) {
                                                unset($attributesGroupId[$key]);
                                                unset($relationshipsAttribute[$resKey]);
                                            }
                                            else {
                                                $relationshipsAttributeDelete[] = $relationship->id;
                                            }
                                        }
                                    }
                                    //Thêm thuộc tính vào sản phẩm
                                    if(have_posts($attributesGroupId)) {

                                        $relationshipsAttributeAdd = [];

                                        foreach ($attributesGroupId as $attrId) {
                                            $relationshipsAttributeAdd[] = [
                                                'product_id'    => $productMaim->id,
                                                'attribute_id'  => $attrId
                                            ];
                                        }

                                        DB::table('products_attribute')->insert($relationshipsAttributeAdd);
                                    }

                                    //Xóa thuộc tính
                                    if(have_posts($relationshipsAttributeDelete)) {
                                        $model->table('products_attribute')::whereIn('id', $relationshipsAttributeDelete)->remove();
                                    }
                                }

                                //Thêm mới relationships
                                if(have_posts($relationshipsAdd)) {
                                    DB::table('products_attribute_item')->insert($relationshipsAdd);
                                }

                                //Cập nhật
                                if(have_posts($relationshipsUp)) {

                                    $relationshipsUpBatch = [];

                                    foreach ($relationshipsUp as $variationId => $attributeData) {
                                        foreach ($attributeData as $attId => $attItem) {
                                            foreach ($relationships as $relations) {
                                                if($relations->variation_id == $variationId && $attId == $relations->attribute_id) {
                                                    if($attItem['old'] == $relations->item_id) {
                                                        $relationshipsUpBatch[] = [
                                                            'id' => $relations->id,
                                                            'item_id' => $attItem['new']
                                                        ];
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if(have_posts($relationshipsUpBatch)) {
                                        $model->table('products_attribute_item')::updateBatch($relationshipsUpBatch, 'id');
                                    }
                                }

                                //Xóa
                                if(have_posts($relationshipsVariation)) {

                                    $relationshipsDelete = [];

                                    foreach ($relationshipsVariation as $variationId => $attributeData) {
                                        foreach ($attributeData as $attId => $attItemId) {
                                            foreach ($relationships as $relations) {
                                                if($relations->variation_id == $variationId && $attId == $relations->attribute_id) {
                                                    if($attItemId == $relations->item_id) {
                                                        $relationshipsDelete[] = $relations->id;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    if(have_posts($relationshipsDelete)) {
                                        $model->table('products_attribute_item')::whereIn('id', $relationshipsDelete)->remove();
                                    }
                                }

                                //Cập nhật sản phẩm chính
                                if(have_posts($productMainUp)) {
                                    $productMainUp['id'] = $productMaim->id;
                                    Product::insert($productMainUp, $productMaim);
                                }
                            }
                        }
                    }
                }
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

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/errors/upload';
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

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/errors/import';

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
Ajax::admin('ProductsImportAjax::addUpload');
Ajax::admin('ProductsImportAjax::addImport');
Ajax::admin('ProductsImportAjax::upload');
Ajax::admin('ProductsImportAjax::import');
Ajax::admin('ProductsImportAjax::uploadError');
Ajax::admin('ProductsImportAjax::importError');