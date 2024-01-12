<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Database\Capsule\Manager as DB;
class ProductsExportAjax {
    static function export($ci, $model): void
    {
        $result['status'] 	= 'error';

        $result['message'] 	= 'Xuất dữ liệu không thành công';

        if(Request::post()) {

            $exportType = Request::post('exportType');

            $args       = Qr::set();

            if($exportType === 'pageCurrent') {
                $productsId = Request::post('products');
                if(!have_posts($productsId)) {
                    $result['message'] = 'Không có sản phẩm nào để xuất';
                    echo json_encode($result);
                    return;
                }
                $args->whereIn('id', $productsId);
            }

            if($exportType === 'productsCheck') {
                $productsId = Request::post('products');
                if(!have_posts($productsId)) {
                    $result['message'] = 'Không có sản phẩm nào để xuất';
                    echo json_encode($result);
                    return;
                }
                $args->whereIn('id', $productsId);
            }

            if($exportType === 'searchCurrent') {

                $search = Request::post('search');

                if(!empty($search['category'])) {
                    $args->whereByCategory($search['category']);
                }

                if(!empty($search['collection']) && !empty(Prd::collections($search['collection']))) {
                    $args->where($search['collection'], 1);
                }

                if(!empty($search['keyword'])) {
                    $args->where('title', 'like',  '%'.$search['keyword'].'%');
                }

                # [Total decoders]
                $args = apply_filters('admin_product_controllers_index_args_count', $args);
            }

            $products = Product::gets($args);

            $productsExport = [];

            $categoriesId   = [];

            $productsId   = [];

            $attributes         = Attributes::gets();

            $attributeItems     = Attributes::getsItem();

            $attributesCount    = 0;

            $brandsId = [];

            foreach ($products as $product) {
                $productsId[$product->id] = $product->id;
                $brandsId[] = $product->brand_id;
            }

            $relationships = model('relationships')->gets(Qr::set('object_type', 'products')->where('value', 'products_categories')->whereIn('object_id', $productsId));

            unset($productsId);

            foreach ($relationships as $relationship) {
                $categoriesId[$relationship->category_id] = $relationship->category_id;
            }

            $categories = ProductCategory::gets(Qr::set()->whereIn('id', $categoriesId)->select('id', 'name'));

            unset($categoriesId);

            $brands = Brands::gets(Qr::set()->whereIn('id', $brandsId)->select('id', 'name'));

            foreach ($products as $product) {

                $categoriesId = [];

                foreach ($relationships as $relationship) {
                    if($relationship->object_id == $product->id) {
                        $categoriesId[] = $relationship->category_id;
                    }
                }

                $product->categories = [];

                if(have_posts($categoriesId)) {
                    foreach ($categoriesId as $cateId) {
                        foreach ($categories as $category) {
                            if($category->id == $cateId) {
                                $product->categories[] = $category;
                                break;
                            }
                        }
                    }
                }

                $product->brandName = '';

                foreach ($brands as $brand) {
                    if($product->brand_id == $brand->id) {
                        $product->brandName = $brand->name;
                    }
                }

                if($product->hasVariation != 0) {
                    $productsVariation = Product::gets(Qr::set('parent_id', $product->id)->where('type', 'variations')->where('status', '<>', 'draft'));
                    foreach ($productsVariation as $item) {
                        $item->excerpt          = $product->excerpt;
                        $item->content          = $product->content;
                        $item->seo_title        = $product->seo_title;
                        $item->seo_description  = $product->seo_description;
                        $item->seo_keywords     = $product->seo_keywords;
                        $item->categories       = $product->categories;
                        $item->brandName        = $product->brandName;
                        $item->public           = $product->public;

                        foreach (Prd::collections() as $collectionKey  => $collection) {
                            if(isset($product->{$collectionKey})) {
                                $item->{$collectionKey} = $product->{$collectionKey};
                            }
                        }

                        $metadata = Metadata::get('products', $item->id );
                        $item->items = [];
                        $maxAttr = 0;
                        foreach ($metadata as $key_meta => $meta_value) {
                            if(str_starts_with($key_meta, 'attribute_op_')) {
                                $item->items[substr($key_meta, 13)] = $meta_value;
                                $maxAttr++;
                                unset($metadata->{$key_meta});
                            }
                        }
                        if($maxAttr > $attributesCount) $attributesCount = $maxAttr;
                        $item = (object)array_merge( (array)$metadata, (array)$item);
                        $productsExport[] = $item;
                    }
                }
                else {
                    $productsExport[] = $product;
                }
            }

            unset($categoriesId);
            unset($products);

            $excelCharacters = [
                'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
                'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
                'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ'
            ];

            $spreadsheet = new Spreadsheet();

            $styleHeader = [
                'font' => [ 'bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => 'left', 'vertical'   => 'center'],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000'],
                    ],
                ],
            ];

            $styleBody = [
                'alignment' => [
                    'vertical' => PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'horizontal' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'E6F7FF',
                    ],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000'],
                    ],
                ],
            ];

            $headerSheet = [
                'id'            => [
                    'label' => 'ID',
                    'value' => function($item) {
                        return $item->id;
                    },
                ],
                'master'        => [
                    'label' => 'Id Nhóm',
                    'value' => function($item) {
                        return $item->parent_id;
                    },
                ],
                'isMaster'      => [
                    'label' => 'Sản phẩm chính',
                    'value' => function($item) {
                        if($item->parent_id != 0) {
                            $defaultId = (int)Product::getMeta($item->parent_id, 'default', true);
                            return ($defaultId == $item->id) ? 'Yes' : 'No';
                        }
                        return 'Yes';
                    },
                    'width' => 10
                ],
                'title'         => [
                    'label' => 'Tên',
                    'value' => function($item) {
                        return $item->title;
                    },
                ],
                'code'          => [
                    'label' => 'Mã sản phẩm',
                    'value' => function($item) {
                        return $item->code;
                    },
                ],
                'categories'    => [
                    'label' => 'Danh mục',
                    'value' => function($item) {
                        $categoryName = [];
                        foreach ($item->categories as $category) {
                            $categoryName[] = $category->name;
                        }
                        return (have_posts($categoryName)) ? implode(', ', $categoryName) : '';
                    },
                ],
                'excerpt'       => [
                    'label' => 'Mô tả',
                    'value' => function($item) {
                        return $item->excerpt;
                    },
                    'width' => 20
                ],
                'public'        => [
                    'label' => 'Hiển thị',
                    'value' => function($item) {
                        return ($item->public) ? 'Yes' : 'No';
                    },
                ],
            ];
            if($attributesCount > 0) {
                if($attributesCount < 3) $attributesCount = 3;
                for($i = 1; $i <= $attributesCount; $i++) {
                    $headerSheet['attr'.$i] = [
                        'label' => 'Thuộc tính '.$i,
                        'value' => function($item) use ($i, $attributes) {
                            $name = '';
                            if(!empty($item->items) && have_posts($item->items)) {
                                $current = 1;
                                foreach ($item->items as $attrId => $attr) {
                                    if($current == $i) {
                                        foreach ($attributes as $attribute) {
                                            if($attribute->id == $attrId) {
                                                $name = $attribute->title;
                                                break;
                                            }
                                        }
                                        break;
                                    }
                                    $current++;
                                }
                            }
                            return $name;
                        },
                    ];
                    $headerSheet['attrValue'.$i] = [
                        'label' => 'Giá trị thuộc tính '.$i,
                        'value' => function($item) use ($i, $attributeItems) {
                            $name = '';
                            if(!empty($item->items) && have_posts($item->items)) {
                                $current = 1;
                                foreach ($item->items as $attrId => $attr) {
                                    if($current == $i) {
                                        foreach ($attributeItems as $attribute) {
                                            if($attribute->id == $attr) {
                                                $name = $attribute->title;
                                                break;
                                            }
                                        }
                                        break;
                                    }
                                    $current++;
                                }
                            }
                            return $name;
                        },
                    ];
                }
            }
            else {
                for($i = 1; $i <= 3; $i++) {
                    $headerSheet['attr'.$i] = [
                        'label' => 'Thuộc tính '.$i,
                        'value' => function($item) {
                            return '';
                        },
                    ];
                    $headerSheet['attrValue'.$i] = [
                        'label' => 'Giá trị thuộc tính '.$i,
                        'value' => function($item) {
                            return '';
                        },
                    ];
                }
            }

            $headerSheet['Giá'] = [
                'label' => 'Giá',
                'value' => function($item) {
                    return $item->price;
                },
            ];
            $headerSheet['Giá Khuyến mãi'] = [
                'label' => 'Giá Khuyến mãi',
                'value' => function($item) {
                    return $item->price_sale;
                },
            ];
            $headerSheet['image'] = [
                'label' => 'Ảnh đại diện',
                'value' => function($item) {
                    return Template::imgLink($item->image);
                },
            ];
            $headerSheet['weight'] = [
                'label' => 'Khối lượng',
                'value' => function($item) {
                    return $item->weight;
                },
            ];
            $headerSheet['brand'] = [
                'label' => 'Thương hiệu',
                'value' => function($item) {
                    return $item->brandName;
                },
            ];
            foreach (Prd::collections() as $collectionKey => $collectionValue) {
                $headerSheet[$collectionKey] = [
                    'label' => $collectionValue['name'],
                    'value' => function($item) use ($collectionKey) {
                        return (!empty($item->{$collectionKey})) ? 'Yes' : 'No';
                    },
                ];
            }
            $headerSheet['seo_title'] = [
                'label' => 'Seo Title',
                'value' => function($item) {
                    return $item->seo_title;
                },
                'width' => 20
            ];
            $headerSheet['seo_description'] = [
                'label' => 'Seo Description',
                'value' => function($item) {
                    return $item->seo_description;
                },
                'width' => 20
            ];
            $headerSheet['seo_keyword'] = [
                'label' => 'Seo Keyword',
                'value' => function($item) {
                    return $item->seo_description;
                },
                'width' => 20
            ];

            $alignment['horizontal'] = [
                'right' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                'left'  => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'center' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ];

            $alignment['vertical'] = [
                'top'    => PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                'center' => PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ];

            $sheet = $spreadsheet->setActiveSheetIndex(0);

            $sheet->setTitle('Sản phẩm');

            $sheet->getDefaultRowDimension()->setRowHeight(20);

            $sheet->getDefaultRowDimension()->setRowHeight(20);

            $key = 0;

            foreach ($headerSheet as $headerKey => $item) {
                $headerSheet[$headerKey]['cell'] =  $excelCharacters[$key].'1';
                if(!empty($item['width'])) {
                    $sheet->getColumnDimension($excelCharacters[$key])->setWidth($item['width']);
                }
                else {
                    $sheet->getColumnDimension($excelCharacters[$key])->setAutoSize(true);
                }
                $key++;
            }

            foreach ($headerSheet as $headerKey => $headerData) {

                $sheet->setCellValue($headerData['cell'], $headerData['label']);

                $style = (isset($headerData['style'])) ? $headerData['style'] : $styleHeader;

                if(isset($style['alignment']['horizontal'])) {
                    $style['alignment']['horizontal'] = $alignment['horizontal'][$style['alignment']['horizontal']];
                }

                if(isset($style['alignment']['vertical'])) {
                    $style['alignment']['vertical'] = $alignment['vertical'][$style['alignment']['vertical']];
                }

                if(!empty($style)) {
                    $sheet->getStyle($headerData['cell'])->applyFromArray($style);
                }
            }

            $rows = [];

            foreach ($productsExport as $keyProduct => $item) {
                $i = 0;
                foreach ($headerSheet as $header) {
                    $rows[] = [
                        'cell'  => $excelCharacters[$i] .($keyProduct+2),
                        'value' => $header['value']($item),
                        'style' => $styleBody
                    ];
                    $i++;
                }
            }

            foreach ($rows as $row) {
                $sheet->setCellValue($row['cell'], $row['value']);
                $sheet->getPageMargins()->setTop(2);
                $sheet->getPageMargins()->setRight(2);
                $sheet->getPageMargins()->setLeft(2);
                $sheet->getPageMargins()->setBottom(2);
                $sheet->getStyle($row['cell'])->applyFromArray($row['style']);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);

            $filePathData = Path::upload('export/');

            if(!file_exists($filePathData)) {
                mkdir($filePathData, 0755);
                chmod($filePathData, 0755);
            }

            $filename = 'products_'.md5(time()).'_'.date('d-m-Y').'.xlsx';

            $writer->save($filePathData.$filename);

            $result['path'] 	    = Url::base().$filePathData.$filename;

            $result['status'] 	    = 'success';

            $result['message'] 	    = 'Load dữ liệu thành công';
        }

        echo json_encode($result);
    }
}
Ajax::admin('ProductsExportAjax::export');

class ProductsImportAjax {
    static function upload($ci, $model): void
    {
        $result['status'] 	= 'error';

        $result['message'] 	= 'Cập nhật dữ liệu thất bại!';

        if(Request::post() && isset($_FILES['file']) ) {

            $extension = FileHandler::extension($_FILES['file']['name']);

            if($extension != 'xlsx' && $extension != 'xls') {
                $result['message'] 	= 'Định dạng file không đúng';
                echo json_encode($result);
                return;
            }

            $myPath = Path::plugin(EXIM_NAME).'/files/imports/products/excel';

            if(!is_dir($myPath)) {
                mkdir($myPath, 0755);
            }

            $configUpload = [
                'upload_path' => $myPath,
                'allowed_types' => '*',
                'remove_spaces' => true,
                'detect_mime' => true,
                'mod_mime_fix' => true,
                'max_size' => '20000',
                'file_name' => Str::slug(basename($_FILES['file']['name'], '.'.$extension)).'-'.time().'.'.$extension
            ];

            $ci->load->library('upload', $configUpload);

            if (!$ci->upload->do_upload('file')) {
                $result['message'] = $ci->upload->display_errors();
                echo json_encode($result);
                return;
            }
            else {

                $data = $ci->upload->data();

                $filePath = $myPath.'/'.$data['file_name'];

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

                foreach ($schedules as $numberRow => $schedule) {

                    $pathLog = $dataPath;

                    if($numberRow == 1) continue;

                    if(count($schedule) < 23) {
                        $errors++;
                        continue;
                    }
                    $rowData = AdminImportProduct::creatData($schedule, $numberRow, $categories, $brands);

                    if(!empty($rowData['errors'])) {
                        $errors++;
                        $fileName = $dataPath.'errors/upload/product_'.$numberRow.'.json';
                        file_put_contents($fileName, json_encode($rowData));
                        continue;
                    }

                    if($rowData['action'] == 'add') {
                        $add++;
                        $pathLog .= 'add/'.$rowData['parent_id'];
                    }
                    else {
                        $upload++;
                        $pathLog .= 'upload/'.$rowData['parent_id'];
                    }

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

                $result['status'] 	= 'success';
                $result['data'] 	= [
                    'errors'    => $errors,
                    'add'       => $add,
                    'upload'    => $upload,
                ];
                $result['message'] 	= 'Cập nhật dữ liệu thành công.';
            }
        }

        echo json_encode($result);
    }
    static function import($ci, $model): void
    {
        $result['status'] 	= 'error';

        $result['message'] 	= 'Cập nhật dữ liệu thất bại!';

        if(Request::post()) {

            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/';

            $success = [
                'add' => 0,
                'upload' => 0
            ];

            $failed = [];

            //Cập nhật sản phẩm
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
                else {

                    $defaultId = (int)Product::getMeta($products[1]->parent_id, 'default', true);

                    if (empty($defaultId)) {
                        foreach ($products as $product) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message' => 'Không xác định được id sản phẩm chính của nhóm sản phẩm ' . $product->parent_id,
                            ];
                        }
                        continue;
                    }

                    $productMaim = Product::get(Qr::set($products[1]->parent_id));

                    if (!have_posts($productMaim)) {
                        foreach ($products as $product) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title'     => $product->title,
                                'message' => 'Không lấy được sản phẩm chính của nhóm sản phẩm ' . $product->parent_id,
                            ];
                        }
                        continue;
                    }

                    $productVariations = Variation::getsByProduct($products[1]->parent_id);

                    $productAttributes = Attributes::gets(['product_id' => $products[1]->parent_id]);

                    $attributesItemsNew = [];

                    $attributesNew = [];

                    $countAttr = [];

                    $checkErrorGroup = false;

                    foreach ($productVariations as $productVariation) {
                        $countAttr[$productVariation->id] = count((array)$productVariation->items);
                    }

                    foreach ($products as $product) {

                        if ($product->id == $defaultId) {
                            $productMaim->title = $product->title;
                            $productMaim->public = $product->public;
                            $productMaim->code = $product->code;
                            $productMaim->excerpt = $product->excerpt;
                            $productMaim->price = $product->price;
                            $productMaim->price_sale = $product->price_sale;
                            $productMaim->image = FileHandler::handlingUrl($product->image);
                            $productMaim->weight = $product->weight;
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

                        if (!isset($product->attributes) || !have_posts($product->attributes)) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title' => $product->title,
                                'message' => 'Sản phẩm chưa có thuộc tính',
                            ];
                            continue;
                        }

                        $checkErrorVa = true;

                        foreach ($productVariations as $productVariation) {
                            if ($productVariation->id == $product->id) {
                                $productVariation->numberRow = $product->numberRow;
                                $productVariation->code = $product->code;
                                $productVariation->price = $product->price;
                                $productVariation->price_sale = $product->price_sale;
                                $productVariation->weight = $product->weight;
                                $productVariation->image = FileHandler::handlingUrl($product->image);
                                $productVariation->itemsUp = [];
                                $countAttr[$productVariation->id] = count((array)$product->attributes);
                                foreach ($product->attributes as $attrG => $attrI) {
                                    $checkErrorGr = true;
                                    foreach ($productAttributes as $productAttribute) {
                                        if ($attrG == $productAttribute['title']) {
                                            $checkError = true;
                                            foreach ($productAttribute['items'] as $item) {
                                                if ($item->title == $attrI) {
                                                    $checkError = false;
                                                    $productVariation->itemsUp[$productAttribute['id']] = $item->id;
                                                    break;
                                                }
                                            }
                                            if ($checkError) {
                                                $attributesItemsNew[$productAttribute['id']][] = $attrI;
                                                $productVariation->itemsSearchI[$productAttribute['id']] = $attrI;
                                            }
                                            $checkErrorGr = false;
                                            break;
                                        }
                                    }
                                    if ($checkErrorGr) {
                                        $attributesNew[$attrG][$attrI] = $attrI;
                                        $productVariation->itemsSearchG[$attrG] = $attrI;
                                    }
                                }
                                $checkErrorVa = false;
                                break;
                            }
                        }

                        if ($checkErrorVa) {
                            $failed[] = [
                                'numberRow' => $product->numberRow,
                                'title' => $product->title,
                                'message' => 'Không tìm thấy sản phẩm biến thể này trong nhóm ' . $product->parent_id,
                            ];
                        }
                    }

                    $count = Arr::first($countAttr);

                    foreach ($countAttr as $cAttr) {
                        if ($count != $cAttr) {
                            foreach ($products as $product) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title' => $product->title,
                                    'message' => 'Số lượng thuộc tính trong nhóm không đồng điều',
                                ];
                            }
                            $checkErrorGroup = true;
                            break;
                        }
                    }

                    if ($checkErrorGroup) continue;

                    if (!empty($attributesItemsNew) || !empty($attributesNew)) {
                        $checkErrorGroup = false;
                        $attributesMain = Product::getMeta($productMaim->id, 'attributes', true);
                        $attrItemsNew = [];
                        $attrGNew = [];
                        if (!empty($attributesNew)) {
                            $attributesAll = Attributes::gets(Qr::set('id', 'title'));
                            foreach ($attributesNew as $attrName => $attrItemList) {
                                $attr = [];
                                foreach ($attributesAll as $attrM) {
                                    if($attrM->title == $attrName) {
                                        $attr = $attrM;
                                        break;
                                    }
                                }

                                if (!have_posts($attr)) {
                                    foreach ($products as $product) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title' => $product->title,
                                            'message' => 'Không tìm thấy thuộc tính ' . $attrName . ' trong hệ thống',
                                        ];
                                    }
                                    $checkErrorGroup = true;
                                    break;
                                }
                                $attributesMain['_op_' . $attr->id]['name'] = $attr->title;
                                $attributesMain['_op_' . $attr->id]['id'] = $attr->id;
                                $attrItems = Attributes::getsItem(Qr::set('option_id', $attr->id)->whereIn(DB::raw('BINARY title'), $attrItemList)->select('id', 'option_id', 'title'));
                                if (!have_posts($attrItems)) {
                                    foreach ($products as $product) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title' => $product->title,
                                            'message' => 'Không tìm thấy các giá trị thuộc tính trong hệ thống',
                                        ];
                                    }
                                    $checkErrorGroup = true;
                                    break;
                                }
                                if (count($attrItemList) != count($attrItems)) {
                                    foreach ($products as $product) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title' => $product->title,
                                            'message' => 'Không tìm thấy giá trị thuộc tính trong hệ thống',
                                        ];
                                    }
                                    $checkErrorGroup = true;
                                    break;
                                }
                                $attr->items = $attrItems;
                                foreach ($attrItems as $attrItem) {
                                    $attrItemsNew['attribute_op_' . $attr->id][] = $attrItem->id;
                                }
                                $attrGNew[$attr->title] = $attr;
                            }
                            foreach ($productVariations as $productVariation) {
                                if (!empty($productVariation->itemsSearchG)) {
                                    $checkError = true;
                                    foreach ($productVariation->itemsSearchG as $attrN => $attrIN) {
                                        $checkError = true;
                                        foreach ($attrGNew as $attrG) {
                                            if ($attrN == $attrG->title) {
                                                foreach ($attrG->items as $item) {
                                                    if ($attrIN == $item->title) {
                                                        $productVariation->itemsUp[$attrG->id] = $item->id;
                                                        unset($productVariation->itemsSearchG[$attrN]);
                                                        $checkError = false;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$checkError) break;
                                        }
                                        if ($checkError) {
                                            $failed[] = [
                                                'numberRow' => $productVariation->numberRow,
                                                'title'     => $productVariation->title,
                                                'message'   => 'Không tìm thấy cặp thuộc tính ' . $attrN . ':' . $attrIN,
                                            ];
                                            break;
                                        }
                                    }
                                    if ($checkError) {
                                        $checkErrorGroup = true;
                                        break;
                                    }
                                }
                            }
                            if ($checkErrorGroup) continue;
                        }
                        if (!empty($attributesItemsNew)) {
                            $attrINew = [];
                            foreach ($attributesItemsNew as $attrId => $attrItemList) {
                                $attrItems = Attributes::getsItem(Qr::set('option_id', $attrId)->whereIn(DB::raw('BINARY title'), $attrItemList));
                                if (!have_posts($attrItems)) {
                                    foreach ($products as $product) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title' => $product->title,
                                            'message' => 'Không tìm thấy các giá trị thuộc tính trong hệ thống',
                                        ];
                                    }
                                    $checkErrorGroup = true;
                                    break;
                                }
                                if (count($attrItemList) != count($attrItems)) {
                                    foreach ($products as $product) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title' => $product->title,
                                            'message' => 'Không tìm thấy giá trị thuộc tính trong hệ thống',
                                        ];
                                    }
                                    $checkErrorGroup = true;
                                    break;
                                }
                                $attrINew[$attrId] = $attrItems;
                                foreach ($attrItems as $attrItem) {
                                    $attrItemsNew['attribute_op_' . $attrId][] = $attrItem->id;
                                }
                            }
                            foreach ($productVariations as $productVariation) {
                                if (!empty($productVariation->itemsSearchI)) {
                                    $checkError = true;
                                    foreach ($productVariation->itemsSearchI as $attrId => $attrIN) {
                                        $checkError = true;
                                        foreach ($attrINew as $attrKeyId => $attrIList) {
                                            if ($attrId == $attrKeyId) {
                                                foreach ($attrIList as $attrIId) {
                                                    if ($attrIN == $attrIId->title) {
                                                        $productVariation->itemsUp[$attrId] = $attrIId->id;
                                                        unset($productVariation->itemsSearchI[$attrId]);
                                                        $checkError = false;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$checkError) break;
                                        }
                                        if ($checkError) {
                                            $failed[] = [
                                                'id' => $productVariation->id,
                                                'title' => $productVariation->title,
                                                'message' => 'Không tìm thấy cặp thuộc tính ' . $attrN . ':' . $attrIN,
                                            ];
                                            break;
                                        }
                                    }
                                    if ($checkError) {
                                        $checkErrorGroup = true;
                                        break;
                                    }
                                }
                            }
                            if ($checkErrorGroup) continue;
                        }
                        Product::updateMeta($productMaim->id, 'attributes', $attributesMain);
                        if (have_posts($attrItemsNew)) {
                            $model->settable('relationships');
                            foreach ($attrItemsNew as $metaKey => $values) {
                                foreach ($values as $value) {
                                    $model->add([
                                        'object_id'     => $productMaim->id,
                                        'category_id'   => $metaKey,
                                        'object_type'   => 'attributes',
                                        'value'         => $value
                                    ]);
                                }
                            }
                        }
                    }

                    foreach ($productVariations as $productVariation) {

                        if (isset($productVariation->itemsUp)) {

                            $errors = Product::insert((array)$productVariation);

                            if (is_skd_error($errors)) {
                                $failed[] = [
                                    'numberRow' => $productVariation->numberRow,
                                    'title' => $productVariation->title,
                                    'message' => $errors->errors[0],
                                ];
                                continue;
                            }

                            $success['upload']++;

                            if ($productVariation->id == $defaultId) {
                                Product::insert((array)$productMaim);
                            }

                            $diff = false;

                            foreach ($productVariation->items as $key => $value) {
                                if (!isset($productVariation->itemsUp[$key]) || $productVariation->itemsUp[$key] !== $value) {
                                    $diff = true;
                                    break;
                                }
                            }

                            if ($diff) {

                                $itemsUp = [];

                                $listVariationKey = [];

                                foreach ($productVariation->itemsUp as $attrId => $attrItemId) {
                                    foreach ($productVariation->items as $aId => $aIId) {
                                        if ($aId == $attrId && $aIId == $attrItemId) {
                                            $listVariationKey[$productVariation->id][] = $attrId;
                                            $attrId = 'attribute_op_' . $attrId;
                                            $itemsUp[$attrId] = $attrItemId;
                                            unset($productVariation->itemsUp[$attrId]);
                                            break;
                                        }
                                    }
                                }

                                foreach ($productVariation->itemsUp as $attrId => $attrItemId) {
                                    $attrId = 'attribute_op_' . $attrId;
                                    $listVariationKey[$productVariation->id][] = $attrId;
                                    $itemsUp[$attrId] = $attrItemId;
                                    Product::updateMeta($productVariation->id, $attrId, $attrItemId);
                                }
                                $metaBoxData = $model->settable('products_metadata')->gets(Qr::set('object_id', $productVariation->id)->where('meta_key', 'like', 'attribute_op_%'));

                                if (have_posts($metaBoxData)) {
                                    foreach ($metaBoxData as $metaBoxDatum) {
                                        if (!isset($itemsUp[$metaBoxDatum->meta_key]) || $itemsUp[$metaBoxDatum->meta_key] != $metaBoxDatum->meta_value) {
                                            $model->delete(Qr::set($metaBoxDatum->id));
                                        }
                                    }
                                }

                                CacheHandler::delete('metabox_', true);

                                foreach ($listVariationKey as $item) {
                                    if (count($item) != Metadata::count('product', Qr::set('object_id', $productVariation->id)->where('meta_key', 'like', 'attribute_op_%'))) {
                                        $meta = Metadata::get('product', $productVariation->id);
                                        foreach ($meta as $meta_key => $meta_value) {
                                            if (Str::is('attribute_op_*', $meta_key)) {
                                                if (in_array($meta_key, $item) === false) {
                                                    Metadata::delete('product', $productVariation->id, $meta_key);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

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
                    foreach ($products as $product) {
                        $categoriesUpdate = [];
                        if(!empty($product->categories)) {
                            $categories = ProductCategory::gets(Qr::set()->whereIn('name', $product->categories)->select('id', 'name'));
                            if(!have_posts($categories)) {
                                $failed[] = [
                                    'id'        => $product->id,
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
                    }
                }
                else {
                    if(is_numeric($products[1]->parent_id)) {

                        $productMaim = Product::get($products[1]->parent_id);

                        if (!have_posts($productMaim)) {
                            foreach ($products as $product) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Không lấy được sản phẩm chính của nhóm sản phẩm ' . $products[1]->parent_id,
                                ];
                            }
                            continue;
                        }

                        $productVariations = Variation::getsByProduct($productMaim->id);

                        $attributesCompare = [];

                        $productAttributes = Attributes::gets(['product_id' => $productMaim->id]);

                        $attributesItemsNew = [];

                        $countAttr = count((array)$products[1]->attributes);

                        $checkError = false;

                        if(have_posts($productVariations)) {
                            foreach ($productVariations as $productVariation) {
                                if ($countAttr != count((array)$productVariation->items)) {
                                    $failed[] = [
                                        'numberRow' => '',
                                        'title' => $products[1]->title,
                                        'message' => 'Số lượng thuộc tính trong nhóm không đồng điều',
                                    ];
                                    $checkError = true;
                                    break;
                                }
                                $attributesCompare[] = $productVariation->items;
                            }
                        }

                        if($checkError) continue;

                        foreach ($products as $product) {

                            if (!isset($product->attributes) || !have_posts($product->attributes)) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title' => $product->title,
                                    'message' => 'Sản phẩm chưa có thuộc tính',
                                ];
                                continue;
                            }

                            if(!$checkError && $countAttr != count((array)$product->attributes)) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Số lượng thuộc tính trong nhóm không đồng điều',
                                ];
                                $checkError = true;
                                continue;
                            }

                            if(!$checkError) {
                                $product->attributes = (array)$product->attributes;
                                foreach ($product->attributes as $attrG => $attrI) {
                                    $checkError = true;
                                    foreach ($productAttributes as $productAttribute) {
                                        if ($attrG == $productAttribute['title']) {
                                            $checkErrorAttr = true;
                                            foreach ($productAttribute['items'] as $item) {
                                                if ($item->title == $attrI) {
                                                    $checkErrorAttr = false;
                                                    $product->attributes[$productAttribute['id']] = $item->id;
                                                    unset($product->attributes[$attrG]);
                                                    break;
                                                }
                                            }
                                            if ($checkErrorAttr) {
                                                $attributesItemsNew[$productAttribute['id']][] = $attrI;
                                                $product->attributes[$productAttribute['id']] = $attrI;
                                                $product->itemsSearchI[$productAttribute['id']] = $attrI;
                                                unset($product->attributes[$attrG]);
                                            }
                                            $checkError = false;
                                            break;
                                        }
                                    }
                                    if ($checkError) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title'     => $product->title,
                                            'message'   => 'Không tim thấy thuộc tính '.$attrG.' trên hệ thống',
                                        ];
                                    }
                                }
                            }
                        }

                        if($checkError) continue;

                        if(have_posts($attributesCompare)) {
                            foreach ($products as $product) {
                                foreach ($attributesCompare as $variation) {
                                    if($product->attributes == $variation) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title'     => $product->title,
                                            'message'   => 'Thuộc tính sản phẩm đã được sản phẩm khác sử dụng',
                                        ];
                                        $checkError = true;
                                        break;
                                    }
                                }
                            }
                            if($checkError) continue;
                        }

                        if (!empty($attributesItemsNew)) {

                            $attributesMain = Product::getMeta($productMaim->id, 'attributes', true);

                            $attrItemsNew = [];

                            $attrINew = [];

                            foreach ($attributesItemsNew as $attrId => $attrItemList) {
                                $attrItems = Attributes::getsItem(Qr::set('option_id', $attrId)->whereIn(DB::raw('BINARY title'), $attrItemList));
                                if (!have_posts($attrItems)) {
                                    foreach ($products as $product) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title' => $product->title,
                                            'message' => 'Không tìm thấy các giá trị thuộc tính trong hệ thống',
                                        ];
                                    }
                                    $checkError = true;
                                    break;
                                }
                                if (count($attrItemList) != count($attrItems)) {
                                    foreach ($products as $product) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title' => $product->title,
                                            'message' => 'Không tìm thấy giá trị thuộc tính trong hệ thống',
                                        ];
                                    }
                                    $checkError = true;
                                    break;
                                }
                                $attrINew[$attrId] = $attrItems;
                                foreach ($attrItems as $attrItem) {
                                    $attrItemsNew['attribute_op_' . $attrId][] = $attrItem->id;
                                }
                            }

                            if ($checkError) continue;

                            foreach ($products as $product) {
                                if (!empty($product->itemsSearchI)) {
                                    $checkError = true;
                                    foreach ($product->itemsSearchI as $attrId => $attrIN) {
                                        $checkError = true;
                                        foreach ($attrINew as $attrKeyId => $attrIList) {
                                            if ($attrId == $attrKeyId) {
                                                foreach ($attrIList as $attrIId) {
                                                    if ($attrIN == $attrIId->title) {
                                                        $product->attributes[$attrId] = $attrIId->id;
                                                        unset($product->itemsSearchI[$attrId]);
                                                        $checkError = false;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$checkError) break;
                                        }
                                        if ($checkError) {
                                            $failed[] = [
                                                'numberRow' => $product->numberRow,
                                                'title'     => $product->title,
                                                'message'   => 'Không tìm thấy giá trị thuộc tính ' . $attrIN,
                                            ];
                                            break;
                                        }
                                    }
                                    if ($checkError) break;
                                }
                            }

                            if ($checkError) continue;

                            Product::updateMeta($productMaim->id, 'attributes', $attributesMain);

                            if (have_posts($attrItemsNew)) {
                                $model->settable('relationships');
                                foreach ($attrItemsNew as $metaKey => $values) {
                                    foreach ($values as $value) {
                                        $model->add([
                                            'object_id'     => $productMaim->id,
                                            'category_id'   => $metaKey,
                                            'object_type'   => 'attributes',
                                            'value'         => $value
                                        ]);
                                    }
                                }
                            }
                        }

                        foreach ($products as $product) {

                            $variationId = Product::insert([
                                'title'         => $product->title,
                                'public'        => $product->public,
                                'excerpt'       => $product->excerpt,
                                'price'         => $product->price,
                                'price_sale'    => $product->price_sale,
                                'image'         => FileHandler::handlingUrl($product->image),
                                'weight'        => $product->weight,
                                'parent_id'     => $productMaim->id,
                                'type'          => 'variations',
                                'status'        => 'public'
                            ]);

                            if (is_skd_error($variationId)) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => $variationId->errors[0],
                                ];
                                continue;
                            }

                            $success['upload']++;

                            if ($product->default == 1) {

                                Product::updateMeta($productMaim->id, 'default', $variationId);

                                $productMaim->title = $product->title;
                                $productMaim->public = $product->public;
                                $productMaim->code = $product->code;
                                $productMaim->excerpt = $product->excerpt;
                                $productMaim->price = $product->price;
                                $productMaim->price_sale = $product->price_sale;
                                $productMaim->image = FileHandler::handlingUrl($product->image);
                                $productMaim->weight = $product->weight;
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

                                Product::insert((array)$productMaim);
                            }

                            $itemsUp = [];

                            $listVariationKey = [];

                            foreach ($product->attributes as $attrId => $attrItemId) {
                                $attrId = 'attribute_op_' . $attrId;
                                $listVariationKey[$variationId][] = $attrId;
                                $itemsUp[$attrId] = $attrItemId;
                                Product::updateMeta($variationId, $attrId, $attrItemId);
                            }

                            $metaBoxData = $model->settable('products_metadata')->gets(Qr::set('object_id', $variationId)->where('meta_key', 'like', 'attribute_op_%'));

                            if (have_posts($metaBoxData)) {
                                foreach ($metaBoxData as $metaBoxDatum) {
                                    if (!isset($itemsUp[$metaBoxDatum->meta_key]) || $itemsUp[$metaBoxDatum->meta_key] != $metaBoxDatum->meta_value) {
                                        $model->delete(Qr::set($metaBoxDatum->id));
                                    }
                                }
                            }

                            CacheHandler::delete('metabox_', true);

                            foreach ($listVariationKey as $item) {
                                if (count($item) != Metadata::count('product', Qr::set('object_id', $variationId)->where('meta_key', 'like', 'attribute_op_%'))) {
                                    $meta = Metadata::get('product', $variationId);
                                    foreach ($meta as $meta_key => $meta_value) {
                                        if (Str::is('attribute_op_*', $meta_key)) {
                                            if (in_array($meta_key, $item) === false) {
                                                Metadata::delete('product', $variationId, $meta_key);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else {
                        $productDefault     = [];

                        $checkError         = false;

                        $countAttr          = (!empty($products[1]->attributes)) ? count((array)$products[1]->attributes) : 0;

                        $attributesNew      = [];

                        $attributesMain     = [];

                        $productAttributes  = Attributes::gets(Qr::set()->select('id','title'));

                        foreach ($products as $product) {

                            if(!isset($product->attributes) || !have_posts($product->attributes)) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Sản phẩm chưa có thuộc tính',
                                ];
                                $checkError = true;
                                continue;
                            }

                            if(!$checkError && $countAttr != count((array)$product->attributes)) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Số lượng thuộc tính trong nhóm không đồng điều',
                                ];
                                $checkError = true;
                                continue;
                            }

                            if(!$checkError) {
                                $product->attributes = (array)$product->attributes;

                                foreach ($product->attributes as $attrG => $attrI) {
                                    $checkError = true;
                                    foreach ($productAttributes as $productAttribute) {
                                        if ($attrG == $productAttribute->title) {
                                            $product->attributes[$productAttribute->id] = $attrI;
                                            $attributesNew[$productAttribute->id]['title'][] = $productAttribute->title;
                                            $attributesNew[$productAttribute->id]['items'][] = $attrI;
                                            unset($product->attributes[$attrG]);
                                            $checkError = false;
                                            break;
                                        }
                                    }

                                    if ($checkError) {
                                        $failed[] = [
                                            'numberRow' => $product->numberRow,
                                            'title'     => $product->title,
                                            'message'   => 'Không tim thấy thuộc tính '.$attrG.' trên hệ thống',
                                        ];
                                    }
                                }
                            }

                            if($product->default == 1) {
                                $productDefault = [
                                    'numberRow'     => $product->numberRow,
                                    'title'         => $product->title,
                                    'public'        => $product->public,
                                    'excerpt'       => $product->excerpt,
                                    'price'         => $product->price,
                                    'price_sale'    => $product->price_sale,
                                    'image'         => FileHandler::handlingUrl($product->image),
                                    'weight'        => $product->weight,
                                    'seo_title'     => $product->seo_title,
                                    'seo_description'   => $product->seo_description,
                                    'seo_keyword'   => $product->seo_keyword,
                                    'categories'    => $product->categories
                                ];
                                foreach (Prd::collections() as $collectionKey => $collection) {
                                    $productDefault[$collectionKey] = $product->{$collectionKey};
                                }
                            }
                        }

                        if($checkError) continue;

                        if(!have_posts($productDefault)) {
                            foreach ($products as $product) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => 'Không tìm thấy sản phẩm chính cho nhóm ' . $product->parent_id,
                                ];
                            }
                            continue;
                        }

                        $attrGNew = [];

                        foreach ($attributesNew as $attrId => $attrData) {
                            $attributesMain['_op_' . $attrId]['name'] = $attrData['title'];
                            $attributesMain['_op_' . $attrId]['id'] = $attrId;
                            $attrDataItemSearch = array_unique($attrData['items']);
                            $attrItems = Attributes::getsItem(Qr::set('option_id', $attrId)->whereIn(DB::raw('BINARY title'), $attrDataItemSearch)->select('id', 'option_id', 'title'));

                            if(!have_posts($attrItems)) {
                                foreach ($products as $product) {
                                    $failed[] = [
                                        'numberRow' => $product->numberRow,
                                        'title' => $product->title,
                                        'message' => 'Không tìm thấy các giá trị thuộc tính trong hệ thống',
                                    ];
                                }
                                $checkError = true;
                                break;
                            }
                            if(count($attrDataItemSearch) != count($attrItems)) {
                                foreach ($products as $product) {
                                    $failed[] = [
                                        'numberRow' => $product->numberRow,
                                        'title' => $product->title,
                                        'message' => 'Không tìm thấy giá trị thuộc tính trong hệ thống',
                                    ];
                                }
                                $checkError = true;
                                break;
                            }
                            $attrGNew[$attrId] = $attrItems;
                        }

                        if($checkError) continue;

                        foreach ($products as $product) {
                            foreach ($product->attributes as $attrId => $attrIN) {
                                $checkErrorAttr = true;
                                foreach ($attrGNew[$attrId] as $attrItem) {
                                    if ($attrIN == $attrItem->title) {
                                        $product->attributes[$attrId] = $attrItem->id;
                                        $checkErrorAttr = false;
                                        break;
                                    }
                                }
                                if ($checkErrorAttr) {
                                    $failed[] = [
                                        'numberRow' => $product->numberRow,
                                        'title'     => $product->title,
                                        'message'   => 'Không tìm thấy giá trị thuộc tính ' . $attrIN,
                                    ];
                                    $checkError = true;
                                    break;
                                }
                            }
                        }

                        if($checkError) continue;

                        //Thêm mới sản phẩm chính
                        if(isset($productDefault['categories'])) {
                            $productDefault['taxonomies'] = [
                                'products_categories' => $productDefault['categories']
                            ];
                            unset($productDefault['categories']);
                        }

                        $productDefault['hasVariation'] = 1;

                        $productId = Product::insert($productDefault);

                        if(is_skd_error($productId)) {
                            $failed[] = [
                                'numberRow' => $productDefault['numberRow'],
                                'title'     => $productDefault['title'],
                                'message'   => $productId->errors[0],
                            ];
                            continue;
                        }

                        Product::updateMeta($productId, 'attributes', $attributesMain);

                        if(have_posts($attrGNew)) {
                            $model->settable('relationships');
                            foreach ($attrGNew as $attrId => $attrItems) {
                                foreach ($attrItems as $item) {
                                    $model->add([
                                        'object_id'     => $productId,
                                        'category_id'   => 'attribute_op_'.$attrId,
                                        'object_type'   => 'attributes',
                                        'value'         => $item->id
                                    ]);
                                }
                            }
                        }

                        foreach ($products as $product) {

                            $variationId = Product::insert([
                                'title'         => $product->title,
                                'public'        => $product->public,
                                'excerpt'       => $product->excerpt,
                                'price'         => $product->price,
                                'price_sale'    => $product->price_sale,
                                'image'         => FileHandler::handlingUrl($product->image),
                                'weight'        => $product->weight,
                                'parent_id'     => $productId,
                                'type'          => 'variations',
                                'status'        => 'public'
                            ]);

                            if (is_skd_error($variationId)) {
                                $failed[] = [
                                    'numberRow' => $product->numberRow,
                                    'title'     => $product->title,
                                    'message'   => $variationId->errors[0],
                                ];
                                continue;
                            }

                            $success['upload']++;

                            if ($product->default == 1) {
                                Product::updateMeta($productId, 'default', $variationId);
                            }

                            $itemsUp = [];

                            $listVariationKey = [];

                            foreach ($product->attributes as $attrId => $attrItemId) {
                                $attrId = 'attribute_op_' . $attrId;
                                $listVariationKey[$variationId][] = $attrId;
                                $itemsUp[$attrId] = $attrItemId;
                                Product::updateMeta($variationId, $attrId, $attrItemId);
                            }

                            $metaBoxData = $model->settable('products_metadata')->gets(Qr::set('object_id', $variationId)->where('meta_key', 'like', 'attribute_op_%'));

                            if (have_posts($metaBoxData)) {
                                foreach ($metaBoxData as $metaBoxDatum) {
                                    if (!isset($itemsUp[$metaBoxDatum->meta_key]) || $itemsUp[$metaBoxDatum->meta_key] != $metaBoxDatum->meta_value) {
                                        $model->delete(Qr::set($metaBoxDatum->id));
                                    }
                                }
                            }

                            CacheHandler::delete('metabox_', true);

                            foreach ($listVariationKey as $item) {
                                if (count($item) != Metadata::count('product', Qr::set('object_id', $variationId)->where('meta_key', 'like', 'attribute_op_%'))) {
                                    $meta = Metadata::get('product', $variationId);
                                    foreach ($meta as $meta_key => $meta_value) {
                                        if (Str::is('attribute_op_*', $meta_key)) {
                                            if (in_array($meta_key, $item) === false) {
                                                Metadata::delete('product', $variationId, $meta_key);
                                            }
                                        }
                                    }
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

            $result['status'] 	= 'success';

            $result['message'] 	= 'Cập nhật dữ liệu thành công';

            $result['data'] = [
                'errors'    => count($failed),
                'add'       => $success['add'],
                'upload'    => $success['upload'],
            ];
        }

        echo json_encode($result);
    }
    static function uploadError($ci, $model): void
    {
        $result['status'] 	= 'error';
        $result['message'] 	= 'Load dữ liệu thất bại!';
        if(Request::post()) {
            $result['data'] = '';
            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/errors/upload';
            //Cập nhật sản phẩm
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataPath)) as $file) {
                if($file->getFileName() == '.') continue;
                $fullPath = trim((string)$file, '.');
                if(is_dir($fullPath)) continue;
                $product = trim(file_get_contents($fullPath));
                $product = json_decode($product);
                $result['data'] .= Plugin::partial(EXIM_NAME, 'admin/views/products/product-upload-error', ['item' => $product], true);
            }
            $result['data']     = base64_encode($result['data']);
            $result['status'] 	= 'success';
            $result['message'] 	= 'Load dữ liệu thành công!';
        }
        echo json_encode($result);
    }
    static function importError($ci, $model): void
    {
        $result['status'] 	= 'error';
        $result['message'] 	= 'Load dữ liệu thất bại!';
        if(Request::post()) {
            $result['data'] = '';
            $dataPath = Path::plugin(EXIM_NAME).'/files/imports/products/errors/import';
            //Cập nhật sản phẩm
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataPath)) as $file) {
                if($file->getFileName() == '.') continue;
                $fullPath = trim((string)$file, '.');
                if(is_dir($fullPath)) continue;
                $product = trim(file_get_contents($fullPath));
                $product = json_decode($product);
                $result['data'] .= Plugin::partial(EXIM_NAME, 'admin/views/products/product-import-error', ['item' => $product], true);
            }
            $result['data']     = base64_encode($result['data']);
            $result['status'] 	= 'success';
            $result['message'] 	= 'Load dữ liệu thành công!';
        }
        echo json_encode($result);
    }
}
Ajax::admin('ProductsImportAjax::upload');
Ajax::admin('ProductsImportAjax::import');
Ajax::admin('ProductsImportAjax::uploadError');
Ajax::admin('ProductsImportAjax::importError');