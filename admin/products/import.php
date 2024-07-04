<?php
Class AdminImportProduct {
    static function buttonHeading(): void
    {
        echo Admin::button('blue', [
            'href' => Url::admin('plugins?page=import-products'),
            'class' => 'btn-blue-bg',
            'id' => 'js_import_product_btn_modal',
            'icon' => '<i class="fa-light fa-upload"></i>',
            'text' => trans('import.data')
        ]);
    }
    static function page(): void
    {
        Plugin::view(EXIM_NAME, 'products/import', []);
    }
    static function numberImportAdd($key  = null) {

        $numberKey = [
            'parent_id',
            'code',
            'default',
            'title',
            'categories',
            'excerpt',
            'public',
            'attr1',
            'attrValue1',
            'attr2',
            'attrValue2',
            'attr3',
            'attrValue3',
            'price',
            'price_sale',
            'image',
            'weight',
            'long',
            'width',
            'height',
            'brand',
        ];

        foreach ($numberKey as $i => $title) {
            $number[$title] = $i + 1;
        }

        $stt = $i + 2;

        foreach (Prd::collections() as $collectionKey => $collection) {
            $number[$collectionKey] = $stt;
            $stt++;
        }

        $number['seo_title']        = $stt++;
        $number['seo_description']  = $stt++;
        $number['seo_keyword']      = $stt;

        $number = apply_filters('import_product_add_column_list', $number);

        if(!empty($key)) return Arr::get($number, $key);

        return $number;
    }
    static function creatDataAdd($importData, $numberRow, $productCategories, $brands = []): array
    {
        $rowData = [
            'numberRow' => $numberRow,
            'errors'    => [],
        ];

        $rowData['action'] = 'add';

        $rowData['parent_id']   = trim($importData[self::numberImportAdd('parent_id')]);

        $importData[self::numberImportAdd('default')]          = Str::lower(trim(Str::clear($importData[self::numberImportAdd('default')])));
        if($importData[self::numberImportAdd('default')] != 'yes' && $importData[self::numberImportAdd('default')] != 'no') {
            $rowData['errors'][] = 'Hiển thị sản phẩm không đúng định dạng';
        }
        $rowData['default']     = ($importData[self::numberImportAdd('default')] == 'yes') ? 1 : 0;

        if(empty($importData[self::numberImportAdd('title')])) {
            $rowData['errors'][] = 'Tên sản phẩm không được bỏ trống';
        }
        $rowData['title']       = Str::clear($importData[self::numberImportAdd('title')]);
        $rowData['code']        = Str::clear($importData[self::numberImportAdd('code')]);
        $rowData['categories']  = [];
        $categoriesName = explode(',', Str::clear($importData[self::numberImportAdd('categories')]));
        foreach ($categoriesName as $categoryName) {
            $categoryName = trim($categoryName);
            if(empty($categoryName)) continue;
            $checkError = true;
            foreach ($productCategories as $productCategory) {
                if($categoryName == $productCategory->name) {
                    $rowData['categories'][] = $productCategory->id;
                    $checkError = false;
                    break;
                }
            }
            if($checkError) {
                $rowData['errors'][] = 'Không tìm thấy danh mục '.$categoryName;
            }
        }
        $rowData['excerpt'] = $importData[self::numberImportAdd('excerpt')];

        $publicNum = self::numberImportAdd('public');

        $importData[$publicNum] = Str::lower(trim(Str::clear($importData[$publicNum])));

        if($importData[$publicNum] != 'yes' && $importData[$publicNum] != 'no') {
            $rowData['errors'][] = 'Hiển thị sản phẩm không đúng định dạng ('.$importData[$publicNum].')';
        }

        $rowData['public'] = ($importData[$publicNum] == 'yes') ? 1 : 0;

        //Attributes
        $attr1Num = self::numberImportAdd('attr1');
        $attrValue1Num = self::numberImportAdd('attrValue1');
        $attr2Num = self::numberImportAdd('attr2');
        $attrValue2Num = self::numberImportAdd('attrValue2');
        $attr3Num = self::numberImportAdd('attr3');
        $attrValue3Num = self::numberImportAdd('attrValue3');
        if(!empty($importData[$attr1Num])) {
            if(empty($importData[$attrValue1Num])) {
                $rowData['errors'][] = 'Giá trị thuộc tính 1 chưa được điền';
            }
            else {
                $importData[$attr1Num] = trim(Str::clear($importData[$attr1Num]));
                $importData[$attrValue1Num] = trim(Str::clear($importData[$attrValue1Num]));
                $rowData['attributes'][$importData[$attr1Num]] = $importData[$attrValue1Num];

                //Attributes 2
                if(!empty($importData[$attr2Num])) {
                    if(empty($importData[$attrValue2Num])) {
                        $rowData['errors'][] = 'Giá trị thuộc tính 2 chưa được điền';
                    }
                    else {
                        $importData[$attr2Num] = trim(Str::clear($importData[$attr2Num]));
                        $importData[$attrValue2Num] = trim(Str::clear($importData[$attrValue2Num]));
                        $rowData['attributes'][$importData[$attr2Num]] = $importData[$attrValue2Num];

                        if(!empty($importData[$attr3Num])) {
                            if(empty($importData[$attrValue3Num])) {
                                $rowData['errors'][] = 'Giá trị thuộc tính 3 chưa được điền';
                            }
                            else {
                                $importData[$attr3Num] = trim(Str::clear($importData[$attr3Num]));
                                $importData[$attrValue3Num] = trim(Str::clear($importData[$attrValue3Num]));
                                $rowData['attributes'][$importData[$attr3Num]] = $importData[$attrValue3Num];
                            }
                        }
                    }
                }
                else {
                    if(!empty($importData[$attr3Num])) {
                        $rowData['errors'][] = 'Thuộc tính 2 chưa được điền thì không thể nhập thuộc tính 3';
                    }
                }
            }
        }
        else {
            if(!empty($importData[$attr2Num])) {
                $rowData['errors'][] = 'Thuộc tính 1 chưa được điền thì không thể nhập thuộc tính 2';
            }
            if(!empty($importData[$attr3Num])) {
                $rowData['errors'][] = 'Thuộc tính 1 chưa được điền thì không thể nhập thuộc tính 3';
            }
        }

        $rowData['price']       = Str::price($importData[self::numberImportAdd('price')]);

        $rowData['price_sale']  = Str::price($importData[self::numberImportAdd('price_sale')]);

        $rowData['image']       = Str::clear($importData[self::numberImportAdd('image')]);

        $rowData['weight']      = Str::price($importData[self::numberImportAdd('weight')]);

        $rowData['long']      = Str::price($importData[self::numberImportAdd('long')]);

        $rowData['height']      = Str::price($importData[self::numberImportAdd('height')]);

        $rowData['width']      = Str::price($importData[self::numberImportAdd('width')]);

        $rowData['brand_id']  = 0;

        $brandName = trim(Str::clear($importData[self::numberImportAdd('brand')]));

        if(!empty($brandName)) {
            if(!have_posts($brands)) {
                $rowData['errors'][] = 'Không tìm thấy thương hiệu '.$brandName;
            }
            else {
                $checkError = true;
                foreach ($brands as $brand) {
                    if($brandName == $brand->name) {
                        $rowData['brand_id'] = $brand->id;
                        $checkError = false;
                        break;
                    }
                }
                if($checkError) {
                    $rowData['errors'][] = 'Không tìm thấy thương hiệu '.$brandName;
                }
            }
        }

        foreach (Prd::collections() as $collectionKey => $collection) {
            $number = self::numberImportAdd($collectionKey);
            $importData[$number] = Str::lower(trim(Str::clear($importData[$number])));
            if($importData[$number] != 'yes' && $importData[$number] != 'no') {
                $rowData['errors'][] = $collection['name'].' không đúng định dạng '.$number;
            }
            else {
                $rowData[$collectionKey] = ($importData[$number] == 'yes') ? 1 : 0;
            }
        }

        $rowData['seo_title'] = trim(Str::clear($importData[self::numberImportAdd('seo_title')]));

        $rowData['seo_description'] = trim(Str::clear($importData[self::numberImportAdd('seo_description')]));

        $rowData['seo_keyword'] = trim(Str::clear($importData[self::numberImportAdd('seo_keyword')]));

        return apply_filters('import_product_add_row_data', $rowData, $importData, $numberRow, $productCategories, $brands);
    }
    static function numberImport($key  = null) {
        $numberKey = [
            'id',
            'code',
            'default',
            'title',
            'categories',
            'excerpt',
            'public',
            'attr1',
            'attrValue1',
            'attr2',
            'attrValue2',
            'attr3',
            'attrValue3',
            'price',
            'price_sale',
            'image',
            'weight',
            'long',
            'width',
            'height',
            'brand',
        ];

        foreach ($numberKey as $i => $title) {
            $number[$title] = $i + 1;
        }

        $stt = $i + 2;

        foreach (Prd::collections() as $collectionKey => $collection) {
            $number[$collectionKey] = $stt;
            $stt++;
        }

        $number['seo_title']        = $stt++;

        $number['seo_description']  = $stt++;

        $number['seo_keyword']      = $stt;

        $number = apply_filters('import_product_update_column_list', $number);

        if(!empty($key)) return Arr::get($number, $key);

        return $number;
    }
    static function creatData($importData, $numberRow, $productCategories, $brands = []): array
    {
        $rowData = [
            'numberRow' => $numberRow,
            'errors'    => [],
        ];

        $importData[self::numberImport('id')] = (int)trim($importData[self::numberImport('id')]);

        $rowData['action'] = 'upload';

        $rowData['id']          = $importData[self::numberImport('id')];

        $importData[self::numberImport('default')]          = Str::lower(trim(Str::clear($importData[self::numberImport('default')])));

        if($importData[self::numberImport('default')] != 'yes' && $importData[self::numberImport('default')] != 'no') {
            $rowData['errors'][] = 'Hiển thị sản phẩm không đúng định dạng';
        }

        $rowData['default']     = ($importData[self::numberImport('default')] == 'yes') ? 1 : 0;

        if(empty($importData[self::numberImport('title')])) {
            $rowData['errors'][] = 'Tên sản phẩm không được bỏ trống';
        }
        $rowData['title']       = Str::clear($importData[self::numberImport('title')]);

        $rowData['code']        = Str::clear($importData[self::numberImport('code')]);

        //Category
        $rowData['categories']  = [];
        $categoriesName = explode(',', Str::clear($importData[self::numberImport('categories')]));
        foreach ($categoriesName as $categoryName) {
            $categoryName = trim($categoryName);
            if(empty($categoryName)) continue;
            $checkError = true;
            foreach ($productCategories as $productCategory) {
                if($categoryName == $productCategory->name) {
                    $rowData['categories'][] = $productCategory->id;
                    $checkError = false;
                    break;
                }
            }
            if($checkError) {
                $rowData['errors'][] = 'Không tìm thấy danh mục '.$categoryName;
            }
        }
        $rowData['excerpt'] = $importData[self::numberImport('excerpt')];

        //Public
        $publicNum = self::numberImport('public');
        $importData[$publicNum] = Str::lower(trim(Str::clear($importData[$publicNum])));
        if($importData[$publicNum] != 'yes' && $importData[$publicNum] != 'no') {
            $rowData['errors'][] = 'Hiển thị sản phẩm không đúng định dạng ('.$importData[$publicNum].')';
        }
        $rowData['public'] = ($importData[$publicNum] == 'yes') ? 1 : 0;

        //Attributes
        //Attributes
        $attr1Num = self::numberImport('attr1');
        $attrValue1Num = self::numberImport('attrValue1');
        $attr2Num = self::numberImport('attr2');
        $attrValue2Num = self::numberImport('attrValue2');
        $attr3Num = self::numberImport('attr3');
        $attrValue3Num = self::numberImport('attrValue3');
        if(!empty($importData[$attr1Num])) {
            if(empty($importData[$attrValue1Num])) {
                $rowData['errors'][] = 'Giá trị thuộc tính 1 chưa được điền';
            }
            else {
                $importData[$attr1Num] = trim(Str::clear($importData[$attr1Num]));
                $importData[$attrValue1Num] = trim(Str::clear($importData[$attrValue1Num]));
                $rowData['attributes'][$importData[$attr1Num]] = $importData[$attrValue1Num];

                //Attributes 2
                if(!empty($importData[$attr2Num])) {
                    if(empty($importData[$attrValue2Num])) {
                        $rowData['errors'][] = 'Giá trị thuộc tính 2 chưa được điền';
                    }
                    else {
                        $importData[$attr2Num] = trim(Str::clear($importData[$attr2Num]));
                        $importData[$attrValue2Num] = trim(Str::clear($importData[$attrValue2Num]));
                        $rowData['attributes'][$importData[$attr2Num]] = $importData[$attrValue2Num];

                        if(!empty($importData[$attr3Num])) {
                            if(empty($importData[$attrValue3Num])) {
                                $rowData['errors'][] = 'Giá trị thuộc tính 3 chưa được điền';
                            }
                            else {
                                $importData[$attr3Num] = trim(Str::clear($importData[$attr3Num]));
                                $importData[$attrValue3Num] = trim(Str::clear($importData[$attrValue3Num]));
                                $rowData['attributes'][$importData[$attr3Num]] = $importData[$attrValue3Num];
                            }
                        }
                    }
                }
                else {
                    if(!empty($importData[$attr3Num])) {
                        $rowData['errors'][] = 'Thuộc tính 2 chưa được điền thì không thể nhập thuộc tính 3';
                    }
                }
            }
        }
        else {
            if(!empty($importData[$attr2Num])) {
                $rowData['errors'][] = 'Thuộc tính 1 chưa được điền thì không thể nhập thuộc tính 2';
            }
            if(!empty($importData[$attr3Num])) {
                $rowData['errors'][] = 'Thuộc tính 1 chưa được điền thì không thể nhập thuộc tính 3';
            }
        }

        $rowData['price']       = Str::price($importData[self::numberImport('price')]);

        $rowData['price_sale']  = Str::price($importData[self::numberImport('price_sale')]);

        $rowData['image']       = Str::clear($importData[self::numberImport('image')]);

        $rowData['weight']      = Str::price($importData[self::numberImport('weight')]);

        $rowData['long']      = Str::price($importData[self::numberImport('long')]);

        $rowData['height']      = Str::price($importData[self::numberImport('height')]);

        $rowData['width']      = Str::price($importData[self::numberImport('width')]);

        $rowData['brand_id']  = 0;

        $brandName = trim(Str::clear($importData[self::numberImport('brand')]));

        if(!empty($brandName)) {
            if(!have_posts($brands)) {
                $rowData['errors'][] = 'Không tìm thấy thương hiệu '.$brandName;
            }
            else {
                $checkError = true;
                foreach ($brands as $brand) {
                    if($brandName == $brand->name) {
                        $rowData['brand_id'] = $brand->id;
                        $checkError = false;
                        break;
                    }
                }
                if($checkError) {
                    $rowData['errors'][] = 'Không tìm thấy thương hiệu '.$brandName;
                }
            }
        }

        foreach (Prd::collections() as $collectionKey => $collection) {
            $number = self::numberImport($collectionKey);
            $importData[$number] = Str::lower(trim(Str::clear($importData[$number])));
            if($importData[$number] != 'yes' && $importData[$number] != 'no') {
                $rowData['errors'][] = $collection['name'].' không đúng định dạng '.$number;
            }
            else {
                $rowData[$collectionKey] = ($importData[$number] == 'yes') ? 1 : 0;
            }
        }

        $rowData['seo_title'] = trim(Str::clear($importData[self::numberImport('seo_title')]));

        $rowData['seo_description'] = trim(Str::clear($importData[self::numberImport('seo_description')]));

        $rowData['seo_keyword'] = trim(Str::clear($importData[self::numberImport('seo_keyword')]));

        return apply_filters('import_product_update_row_data', $rowData, $importData, $numberRow, $productCategories, $brands);
    }

}
add_action('admin_product_action_bar_heading', 'AdminImportProduct::buttonHeading');

AdminMenu::add('import-products', 'import-products', 'import-products', [
    'callback' => 'AdminImportProduct::page', 'hidden' => true
]);