<?php
Class AdminImportProduct {
    static function buttonHeading($actionList): void
    {
        echo '<a href="'.Url::admin('plugins?page=import-products').'" class="btn btn-blue btn-blue-bg" id="js_import_product_btn_modal"><i class="fa-light fa-upload"></i> Nhập dữ liệu</a>';
    }
    static function page(): void
    {
        Plugin::partial(EXIM_NAME, 'admin/views/products/import-product', []);
    }
    static function numberImport($key  = null) {
        $numberKey = [
            'id',
            'parent_id',
            'default',
            'title',
            'code',
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

        if(empty($importData[self::numberImport('id')])) {
            $rowData['action'] = 'add';
        }
        else {
            $rowData['action'] = 'upload';
        }

        $rowData['id']          = $importData[self::numberImport('id')];

        $rowData['parent_id']   = trim($importData[self::numberImport('parent_id')]);

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

        $importData[self::numberImport('public')] = Str::lower(trim(Str::clear($importData[self::numberImport('public')])));
        if($importData[self::numberImport('public')] != 'yes' && $importData[self::numberImport('public')] != 'no') {
            $rowData['errors'][] = 'Hiển thị sản phẩm không đúng định dạng';
        }
        $rowData['public'] = ($importData[self::numberImport('public')] == 'yes') ? 1 : 0;

        //Attributes
        if(!empty($importData[self::numberImport('attr1')])) {
            if(empty($importData[self::numberImport('attrValue1')])) {
                $rowData['errors'][] = 'Giá trị thuộc tính 1 chưa được điền';
            }
            else {
                $importData[self::numberImport('attr1')] = trim(Str::clear($importData[self::numberImport('attr1')]));
                $importData[self::numberImport('attrValue1')] = trim(Str::clear($importData[self::numberImport('attrValue1')]));
                $rowData['attributes'][$importData[self::numberImport('attr1')]] = $importData[self::numberImport('attrValue1')];
                if(!empty($importData[self::numberImport('attr2')])) {
                    if(empty($importData[self::numberImport('attrValue2')])) {
                        $rowData['errors'][] = 'Giá trị thuộc tính 2 chưa được điền';
                    }
                    else {
                        $importData[self::numberImport('attr2')] = trim(Str::clear($importData[self::numberImport('attr2')]));
                        $importData[self::numberImport('attrValue2')] = trim(Str::clear($importData[self::numberImport('attrValue2')]));
                        $rowData['attributes'][$importData[self::numberImport('attr2')]] = $importData[self::numberImport('attrValue2')];

                        if(!empty($importData[self::numberImport('attr3')])) {
                            if(empty($importData[self::numberImport('attrValue3')])) {
                                $rowData['errors'][] = 'Giá trị thuộc tính 3 chưa được điền';
                            }
                            else {
                                $importData[self::numberImport('attr3')] = trim(Str::clear($importData[self::numberImport('attr3')]));
                                $importData[self::numberImport('attrValue3')] = trim(Str::clear($importData[self::numberImport('attrValue3')]));
                                $rowData['attributes'][$importData[self::numberImport('attr3')]] = $importData[self::numberImport('attrValue3')];
                            }
                        }
                    }
                }
                else {
                    if(!empty($importData[self::numberImport('attr3')])) {
                        $rowData['errors'][] = 'Thuộc tính 2 chưa được điền thì không thể nhập thuộc tính 3';
                    }
                }
            }
        }
        else {
            if(!empty($importData[self::numberImport('attr2')])) {
                $rowData['errors'][] = 'Thuộc tính 1 chưa được điền thì không thể nhập thuộc tính 2';
            }
            if(!empty($importData[self::numberImport('attr3')])) {
                $rowData['errors'][] = 'Thuộc tính 1 chưa được điền thì không thể nhập thuộc tính 3';
            }
        }

        $rowData['price']       = Str::price($importData[self::numberImport('price')]);

        $rowData['price_sale']  = Str::price($importData[self::numberImport('price_sale')]);

        $rowData['image']       = Str::clear($importData[self::numberImport('image')]);

        $rowData['weight']      = Str::price($importData[self::numberImport('weight')]);

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
        return $rowData;
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
add_filter('admin_product_action_bar_heading', 'AdminImportProduct::buttonHeading');
add_action('template_redirect', 'AdminImportProduct::fileExcelDemo');
AdminMenu::add('import-products', 'import-products', 'import-products', ['callback' => 'AdminImportProduct::page', 'hidden' => true]);