<form method="post" enctype="multipart/form-data" class="" id="js_import_add_products_form">
    {!! Admin::loading() !!}
    <div class="row">
        <div class="col-md-4">
            <p>Tải danh sách sản phẩm cần <b>thêm</b> lên hệ thống</p>
            <div class="upload-main-wrapper">
                <div class="upload-wrapper">
                    <input accept="application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" type="file" name="file" id="js_import_add_product_input_file">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="340.531" height="419.116" viewBox="0 0 340.531 419.116">
                        <g id="files-new" clip-path="url(#clip-files-new)">
                            <path id="Union_2" data-name="Union 2" d="M-2904.708-8.885A39.292,39.292,0,0,1-2944-48.177V-388.708A39.292,39.292,0,0,1-2904.708-428h209.558a13.1,13.1,0,0,1,9.3,3.8l78.584,78.584a13.1,13.1,0,0,1,3.8,9.3V-48.177a39.292,39.292,0,0,1-39.292,39.292Zm-13.1-379.823V-48.177a13.1,13.1,0,0,0,13.1,13.1h261.947a13.1,13.1,0,0,0,13.1-13.1V-323.221h-52.39a26.2,26.2,0,0,1-26.194-26.195v-52.39h-196.46A13.1,13.1,0,0,0-2917.805-388.708Zm146.5,241.621a14.269,14.269,0,0,1-7.883-12.758v-19.113h-68.841c-7.869,0-7.87-47.619,0-47.619h68.842v-18.8a14.271,14.271,0,0,1,7.882-12.758,14.239,14.239,0,0,1,14.925,1.354l57.019,42.764c.242.185.328.485.555.671a13.9,13.9,0,0,1,2.751,3.292,14.57,14.57,0,0,1,.984,1.454,14.114,14.114,0,0,1,1.411,5.987,14.006,14.006,0,0,1-1.411,5.973,14.653,14.653,0,0,1-.984,1.468,13.9,13.9,0,0,1-2.751,3.293c-.228.2-.313.485-.555.671l-57.019,42.764a14.26,14.26,0,0,1-8.558,2.847A14.326,14.326,0,0,1-2771.3-147.087Z" transform="translate(2944 428)" fill="var(--theme-color)"></path>
                        </g>
                    </svg>
                    <span class="file-upload-text">Chọn File upload</span>
                </div>
            </div>
            <p class="mb-2 mt-2" style="color:#ccc">Chấp nhận file đuôi .xls và .xlsx</p>
            <div class="footer-content text-right mt-2">
                <a href="{!! Url::admin('plugins?page=import-file-demo&file-download=products-demo-add') !!}" download class="btn btn-blue btn-blue-bg ms-0">Tải file mẫu</a>
                <button class="btn btn-blue btn-blue-bg" type="button" id="js_import_add_product_support">Hướng dẫn</button>
                <button class="btn btn-blue">Upload File</button>
            </div>
        </div>
        <div class="col-md-4">
            <p class="heading">Kết quả phân tích</p>
            <div class="import-result-alert" id="js_import_add_result_upload" style="display: none">
                <p class="add">Có <span>0</span> sản phẩm sẽ được <b>thêm mới</b></p>
                <p class="error">Có <span>0</span> sản phẩm <b>lỗi</b> không thể đăng</p>
                <div class="text-right mt-2">
                    <button class="btn btn-blue" id="js_import_add_product_submit">Nhập dữ liệu</button>
                    <button class="btn btn-red" id="js_import_add_product_analysis_error">Xem lỗi</button>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <p class="heading">Kết quả import</p>
            <div class="import-result-alert" id="js_import_add_result_import" style="display: none">
                <p class="add">Có <span>0</span> sản phẩm đã <b>thêm mới</b></p>
                <p class="error">Có <span>0</span> sản phẩm <b>lỗi</b></p>
                <div class="text-right mt-2">
                    <button class="btn btn-red" id="js_import_add_product_import_error">Xem lỗi</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    $(function () {

        const importAddProduct = new ImportAddProductHandel();

        $(document)
            .on('submit', '#js_import_add_products_form', function () {
                return importAddProduct.upload($(this))
            })
            .on('change', '#js_import_add_product_input_file', function () {
                return importAddProduct.changeFile($(this))
            })
            .on('click', '#js_import_add_product_analysis_error', function () {
                importAddProduct.showUploadError($(this))
                return false;
            })
            .on('click', '#js_import_add_product_submit', function () {
                return importAddProduct.import($(this))
            })
            .on('click', '#js_import_add_product_support', function () {
                return importAddProduct.clickSupport($(this))
            })
            .on('click', '#js_import_add_product_import_error', function () {
                return importAddProduct.showImportError($(this))
            })
    })
</script>