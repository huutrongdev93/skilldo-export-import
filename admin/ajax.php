<?php

use JetBrains\PhpStorm\NoReturn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SkillDo\Http\Request;

class ExportAjax {
    #[NoReturn]
    static function products(Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $exportType = $request->input('exportType');

            $args       = Qr::set();

            if($exportType === 'pageCurrent') {
                $productsId = $request->input('products');
                if(!have_posts($productsId)) {
                    response()->error(trans('Không có sản phẩm nào để xuất'));
                }
                $args->whereIn('id', $productsId);
            }

            if($exportType === 'productsCheck') {
                $productsId = $request->input('products');
                if(!have_posts($productsId)) {
                    response()->error(trans('Không có sản phẩm nào để xuất'));
                }
                $args->whereIn('id', $productsId);
            }

            if($exportType === 'searchCurrent') {

                $search = $request->input('search');

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

            $attributeItems     = AttributesItem::gets();

            $attributesCount    = 0;

            $brandsId = [];

            foreach ($products as $product) {
                $productsId[$product->id] = $product->id;
                $brandsId[] = $product->brand_id;
            }

            $relationships = model('relationships')->where('object_type', 'products')->where('value', 'products_categories')->whereIn('object_id', $productsId)->fetch();

            $attributes_items_relationship = model('products_attribute_item')->whereIn('product_id', $productsId)->fetch();

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

                        $item->items = [];

                        $maxAttr = 0;

                        foreach ($attributes_items_relationship as $relationship) {

                            if($relationship->variation_id !== $item->id) {
                                continue;
                            }

                            foreach ($attributeItems as $attrItem) {
                                if($attrItem->id !== $relationship->item_id) {
                                    continue;
                                }
                                $item->items[$relationship->attribute_id] = $attrItem;
                                $maxAttr++;
                            }
                        }

                        ksort($item->items);

                        if($maxAttr > $attributesCount) {
                            $attributesCount = $maxAttr;
                        }

                        $productsExport[] = $item;
                    }
                }
                else {
                    $productsExport[] = $product;
                }
            }

            unset($categoriesId);

            unset($products);

            $productsExport = apply_filters('export_product_data', $productsExport);

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
                'code'          => [
                    'label' => 'Mã sản phẩm',
                    'value' => function($item) {
                        return $item->code;
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
                                        $name = $attr->title;
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
                'label' => 'Khối lượng ('.Prd::weightUnit().')',
                'value' => function($item) {
                    return $item->weight;
                },
            ];
            $headerSheet['long'] = [
                'label' => 'Dài (cm)',
                'value' => function($item) {
                    return $item->long;
                },
            ];
            $headerSheet['width'] = [
                'label' => 'Rộng (cm)',
                'value' => function($item) {
                    return $item->width;
                },
            ];
            $headerSheet['height'] = [
                'label' => 'Cao (cm)',
                'value' => function($item) {
                    return $item->height;
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

            $headerSheet = apply_filters('export_product_header', $headerSheet);

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

            $path = Url::base().$filePathData.$filename;

            response()->success(trans('ajax.load.success'), $path);
        }

        response()->error(trans('Xuất dữ liệu không thành công'));
    }
    #[NoReturn]
    static function inventory(Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $exportType = $request->input('exportType');

            $args       = Qr::set()->orderBy('parent_id');

            if($exportType === 'pageCurrent') {
                $productsId = $request->input('products');
                if(!have_posts($productsId)) {
                    response()->error(trans('Không có sản phẩm nào để xuất'));
                }
                $args->whereIn('id', $productsId);
            }

            if($exportType === 'productsCheck') {
                $productsId = $request->input('products');
                if(!have_posts($productsId)) {
                    response()->error(trans('Không có sản phẩm nào để xuất'));
                }
                $args->whereIn('id', $productsId);
            }

            if($exportType === 'searchCurrent') {

                $search = $request->input('search');

                if(!empty($search['keyword'])) {
                    $args->where(function ($query) use ($search) {
                        $query->where('product_name', 'like', '%'.$search['keyword'].'%');
                        $query->orWhere('product_code', 'like', '%'.$search['keyword'].'%');
                    });
                }

                $branch_id = (int)($search['branch_id'] ?? 1);

                if($branch_id == 0) $branch_id = 1;

                $args->where('branch_id', $branch_id);

                if(!empty($search['status'])) {
                    $args->where('status', $search['status']);
                }

                $args = apply_filters('admin_inventories_controllers_index_args', $args);
            }

            $objects = Inventory::gets($args);

            $variationsId = [];

            foreach ($objects as $object) {

                $object->optionName = '';

                if($object->parent_id != 0) {
                    $variationsId[] = $object->product_id;
                }
            }

            if (have_posts($variationsId)) {

                //Attributes Item
                $attributes_items_relationship = model('products_attribute_item')->whereIn('variation_id', $variationsId)->fetch();

                $attributes_items_relationship_id = [];

                foreach ($attributes_items_relationship as $item) {
                    $attributes_items_relationship_id[] = $item->item_id;
                }

                $attributes_items_relationship_id = array_unique($attributes_items_relationship_id);

                $attributesItem = AttributesItem::whereIn('id', $attributes_items_relationship_id)->fetch();

                foreach ($objects as $item) {

                    foreach ($attributes_items_relationship as $attribute_item_relationship) {

                        if ($item->product_id == $attribute_item_relationship->variation_id) {

                            foreach ($attributesItem as $attributeItem) {

                                if ($attributeItem->id == $attribute_item_relationship->item_id) {

                                    $item->optionName .= $attributeItem->title . ' - ';

                                    break;
                                }
                            }
                        }
                    }

                    $item->optionName = trim($item->optionName, ' - ');
                }
            }

            $objects = apply_filters('export_inventory_data', $objects);

            $excelCharacters = [
                'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
                'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
                'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ'
            ];

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
                        return $item->product_id;
                    },
                ],
                'code'          => [
                    'label' => 'Mã sản phẩm',
                    'value' => function($item) {
                        return $item->product_code;
                    },
                ],
                'title'         => [
                    'label' => 'Tên',
                    'value' => function($item) {
                        return $item->product_name;
                    },
                ],
                'attributes'         => [
                    'label' => 'Thuộc tính',
                    'value' => function($item) {
                        return $item->optionName ?? '';
                    },
                ],
                'branch_name'    => [
                    'label' => 'Chi nhánh',
                    'value' => function($item) {
                        return $item->branch_name;
                    },
                ],
                'stock'       => [
                    'label' => 'Tồn kho',
                    'value' => function($item) {
                        return $item->stock;
                    },
                    'width' => 20
                ],
                'reserved'        => [
                    'label' => 'Khách đặt',
                    'value' => function($item) {
                        return $item->reserved;
                    },
                ],
                'status'        => [
                    'label' => 'Trạng thái',
                    'value' => function($item) {
                        return InventoryHelper::status($item->status,'label');
                    },
                ],
            ];

            $headerSheet = apply_filters('export_inventory_header', $headerSheet);

            $alignment['horizontal'] = [
                'right' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                'left'  => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'center' => PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ];

            $alignment['vertical'] = [
                'top'    => PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                'center' => PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ];

            $spreadsheet = new Spreadsheet();

            $sheet = $spreadsheet->setActiveSheetIndex(0);

            $sheet->setTitle('Kho hàng');

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

            foreach ($objects as $keyProduct => $item) {
                $i = 0;
                foreach ($headerSheet as $header) {
                    if($item->status == 'outstock') {
                        $styleBody['fill']['startColor']['argb'] = 'FFE4E4';
                    }
                    if($item->status == 'instock') {
                        $styleBody['fill']['startColor']['argb'] = 'E6F7FF';
                    }

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

            $filename = 'inventories_'.md5(time()).'_'.date('d-m-Y').'.xlsx';

            $writer->save($filePathData.$filename);

            $path = Url::base().$filePathData.$filename;

            response()->success(trans('ajax.load.success'), $path);
        }

        response()->error(trans('Xuất dữ liệu không thành công'));
    }
    #[NoReturn]
    static function order(Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $exportType = $request->input('exportType');
            
            $args       = Qr::set()->orderByDesc('created');
            
            if($exportType === 'pageCurrent') {
                $ordersId = $request->input('orders');
                if(!have_posts($ordersId)) {
                    response()->error(trans('Không có đơn hàng nào để xuất'));
                }
                $args->whereIn('id', $ordersId);
            }
            
            if($exportType === 'orderCheck') {
                $ordersId = $request->input('orders');
                if(!have_posts($ordersId)) {
                    response()->error(trans('Không có đơn hàng nào để xuất'));
                }
                $args->whereIn('id', $ordersId);
            }

            if($exportType === 'searchCurrent') {

                $search = $request->input('search');

                if(!empty($search['name'])) {
                    $args->setMetaQuery('billing_fullname', $search['name'], 'like');
                }

                if(!empty($search['phone'])) {
                    $args->setMetaQuery('billing_phone', $search['phone'], 'like');
                }

                if(!empty($search['status'])) {
                    $args->where('status', $search['status']);
                }

                if(!empty($search['time'])) {

                    $time = explode(' - ', $search['time']);

                    if(have_posts($time) && count($time) == 2) {
                        $time[0] = str_replace('/', '-', $time[0]);
                        $time[1] = str_replace('/', '-', $time[1]);
                        $timeStart = date('Y-m-d', strtotime($time[0])).' 00:00:00';
                        $timeEnd   = date('Y-m-d', strtotime($time[1])).' 23:59:59';
                        $args->where('created', '>=', $timeStart);
                        $args->where('created', '<=', $timeEnd);
                    }
                }

                $args = apply_filters('admin_order_controllers_index_args', $args);
            }

            $orders = Order::gets($args);

            $orderExport = [];

            foreach ($orders as $order) {

                $billingAddress = $order->billing_address.', '. PrdCartHelper::billingAddress($order);

                $billingAddress = trim($billingAddress,',');

                $order->billing_address = $billingAddress;

                foreach ($order->items as $key => $product) {

                    $product->option = (is_serialized($product->option))?@unserialize($product->option):$product->option;

                    if(isset($product->option) && have_posts($product->option)) {

                        $attributes = '';

                        foreach ($product->option as $attribute) {
                            $attributes .= $attribute.' / ';
                        }
                        $attributes = trim(trim($attributes), '/' );
                    }

                    $item = [];

                    if($key == 0) {
                        $item = [...(array)$order];
                    }

                    $item['product_quantity'] = $product->quantity;

                    $item['product_name'] = $product->title.((!empty($attributes)) ? ' ('.$attributes.')' : '');

                    $item = apply_filters('export_order_item_data', $item, ['order' => $order, 'product' => $product]);

                    $orderExport[] = (object)$item;
                }
            }

            $orderExport = apply_filters('export_order_data', $orderExport);

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
                'code' => [
                    'label' => 'Mã đơn hàng',
                    'value' => function($item) {
                        return $item->code ?? '';
                    },
                ],
                'created'        => [
                    'label' => 'Ngày đặt hàng',
                    'value' => function($item) {
                        return (!empty($item->created)) ? date('d/m/Y H:i', strtotime($item->created)) : '';
                    },
                ],
                'billing_fullname' => [
                    'label' => 'Tên người nhận hàng',
                    'value' => function($item) {
                        return $item->billing_fullname ?? '';
                    },
                ],
                'billing_phone' => [
                    'label' => 'Số điện thoại',
                    'value' => function($item) {
                        return $item->billing_phone ?? '';
                    },
                ],
                'billing_email' => [
                    'label' => 'Email',
                    'value' => function($item) {
                        return $item->billing_email ?? '';
                    },
                ],
                'billing_address' => [
                    'label' => 'Địa chỉ nhận hàng',
                    'value' => function($item) {
                        return $item->billing_address ?? '';
                    },
                ],

                'productName' => [
                    'label' => 'Tên sản phẩm',
                    'value' => function($item) {
                        return $item->product_name ?? '';
                    },
                ],
                'productQuantity'        => [
                    'label' => 'Số lượng',
                    'value' => function($item) {
                        return $item->product_quantity ?? '';
                    },
                ],
                'shipping'        => [
                    'label' => 'Vận chuyển',
                    'value' => function($item) {
                        return isset($item->_shipping_price) ? Prd::price($item->_shipping_price) : 0;
                    },
                ],
                'discount'        => [
                    'label' => 'Khuyến mãi',
                    'value' => function($item) {
                        return isset($item->_discount_price) ? Prd::price($item->_discount_price) : 0;
                    },
                ],
                'total'        => [
                    'label' => 'Tổng tiền',
                    'value' => function($item) {
                        return (!empty($item->total)) ? Prd::price($item->total) : '';
                    },
                ],
                'status' => [
                    'label' => 'Tình trạng',
                    'value' => function($item) {
                        return (!empty($item->status)) ? OrderHelper::status($item->status, 'label') : '';
                    },
                ],
                'status_pay' => [
                    'label' => 'Trạng thái',
                    'value' => function($item) {
                        return (!empty($item->status_pay)) ? OrderHelper::statusPay($item->status_pay, 'label') : '';
                    },
                ],
                'order_note'        => [
                    'label' => 'Ghi chú',
                    'value' => function($item) {
                        return (!empty($item->order_note)) ? $item->order_note : '';
                    },
                ],
            ];

            $headerSheet = apply_filters('export_order_header', $headerSheet);

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

            $sheet->setTitle('Đơn hàng');

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

            foreach ($orderExport as $keyProduct => $item) {

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

            $filename = 'order_'.md5(time()).'_'.date('d-m-Y').'.xlsx';

            $writer->save($filePathData.$filename);

            $path = Url::base().$filePathData.$filename;

            response()->success(trans('ajax.load.success'), $path);
        }

        response()->error(trans('xuất đơn hàng không thành công'));
    }
}
Ajax::admin('ExportAjax::products');
Ajax::admin('ExportAjax::inventory');
Ajax::admin('ExportAjax::order');