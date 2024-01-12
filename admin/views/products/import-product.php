<div class="ui-title-bar__group">
	<h1 class="ui-title-bar__title">Import Sản phẩm</h1>
	<div class="ui-title-bar__des">Cập nhật thêm mới sản phẩm bằng file excel</div>
</div>
<div class="box">
	<div class="box-content p-2">
		<form method="post" enctype="multipart/form-data" class="" id="js_import_products_form">
            <?php Admin::loading();?>
			<div class="row">
				<div class="col-md-4">
					<p>Tải danh sách sản phẩm lên hệ thống</p>
					<div class="upload-main-wrapper">
						<div class="upload-wrapper">
							<input accept="application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" type="file" name="file" id="js_import_product_input_file">
							<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="340.531" height="419.116" viewBox="0 0 340.531 419.116">
								<g id="files-new" clip-path="url(#clip-files-new)">
									<path id="Union_2" data-name="Union 2" d="M-2904.708-8.885A39.292,39.292,0,0,1-2944-48.177V-388.708A39.292,39.292,0,0,1-2904.708-428h209.558a13.1,13.1,0,0,1,9.3,3.8l78.584,78.584a13.1,13.1,0,0,1,3.8,9.3V-48.177a39.292,39.292,0,0,1-39.292,39.292Zm-13.1-379.823V-48.177a13.1,13.1,0,0,0,13.1,13.1h261.947a13.1,13.1,0,0,0,13.1-13.1V-323.221h-52.39a26.2,26.2,0,0,1-26.194-26.195v-52.39h-196.46A13.1,13.1,0,0,0-2917.805-388.708Zm146.5,241.621a14.269,14.269,0,0,1-7.883-12.758v-19.113h-68.841c-7.869,0-7.87-47.619,0-47.619h68.842v-18.8a14.271,14.271,0,0,1,7.882-12.758,14.239,14.239,0,0,1,14.925,1.354l57.019,42.764c.242.185.328.485.555.671a13.9,13.9,0,0,1,2.751,3.292,14.57,14.57,0,0,1,.984,1.454,14.114,14.114,0,0,1,1.411,5.987,14.006,14.006,0,0,1-1.411,5.973,14.653,14.653,0,0,1-.984,1.468,13.9,13.9,0,0,1-2.751,3.293c-.228.2-.313.485-.555.671l-57.019,42.764a14.26,14.26,0,0,1-8.558,2.847A14.326,14.326,0,0,1-2771.3-147.087Z" transform="translate(2944 428)" fill="var(--theme-color)"></path>
								</g>
							</svg>
							<span class="file-upload-text">Chọn File upload</span>
						</div>
					</div>
					<p class="mb-0 mt-2" style="color:#ccc">Chấp nhận file đuôi .xls và .xlsx</p>
					<div class="text-right mt-2">
						<button class="btn btn-blue btn-blue-bg" type="button" id="js_import_product_support">Xem hướng dẫn</button>
						<button class="btn btn-blue">Upload File</button>
					</div>
				</div>
				<div class="col-md-4">
					<p class="heading">Kết quả phân tích</p>
					<div class="import-result-alert" id="js_import_result_upload" style="display: none">
						<p class="add">Có <span>0</span> sản phẩm sẽ được <b>thêm mới</b></p>
						<p class="upload">Có <span>0</span> sản phẩm sẽ được <b>cập nhật</b> thông tin</p>
						<p class="error">Có <span>0</span> sản phẩm <b>lỗi</b> không thể đăng</p>
						<div class="text-right mt-2">
							<button class="btn btn-blue" id="js_import_product_submit">Nhập dữ liệu</button>
							<button class="btn btn-red" id="js_import_product_upload_error">Xem lỗi</button>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<p class="heading">Kết quả import</p>
					<div class="import-result-alert" id="js_import_result_import" style="display: none">
						<p class="add">Có <span>0</span> sản phẩm đã <b>thêm mới</b></p>
						<p class="upload">Có <span>0</span> sản phẩm đã <b>cập nhật</b> thông tin</p>
						<p class="error">Có <span>0</span> sản phẩm <b>lỗi</b></p>
						<div class="text-right mt-2">
							<button class="btn btn-red" id="js_import_product_import_error">Xem lỗi</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<div class="box" id="js_import_support_box" style="display: block">
	<div class="box-content p-2">
		<p class="heading">Thêm mới sản phẩm</p>
		<p>1. Trường <b>ID</b> bỏ trống hoặc điền 0</p>
		<p>2. Sản phẩm không có biến thể trường <b>Id Nhóm</b> bỏ trống hoặc điền 0</p>
		<p>3. Sản phẩm có biến thể trường <b>Id Nhóm</b> điền chữ nếu thêm sản phẩm có biến thể mới hoàn toàn ví dụ : A,B,C</p>
		<p>4. Thêm biến thể mới vào sản phẩm có trước thì trường <b>Id Nhóm</b> điền id nhóm</p>
		<div class="mb-2">
			<a href="<?php echo Url::admin('plugins?page=import-products&file-download=products-import-add');?>" download class="btn btn-blue btn-blue-bg ms-0">Tải file xls mẫu thêm sản phẩm</a>
		</div>
		<p class="heading">Cập nhật sản phẩm</p>
		<p>1. Sản phẩm có id trùng id sản phẩm trên website sẽ được cập nhật</p>
		<p>2. Sản phẩm có nhiều biến thế trường "Sản phẩm chính" là "Yes" sẽ được cập nhật cho sản phẩm chính</p>
		<p>3. Một nhóm sản phẩm chỉ có một sản phẩm được để "Sản phẩm chính" là "Yes"</p>
		<p class="heading">Lưu ý chung</p>
		<p>1. Tên danh mục sản phẩm phải có sẳn trên website</p>
		<p>2. Có thể thêm nhiều danh mục bằng dấu ","</p>
		<p>ví dụ: <i style="font-style: italic">điện thoại, máy tính bảng</i></p>
		<p>3. Tên thuộc tính phải và giá trị thuộc tính phải có sẳn trên website</p>
		<p>4. Tên thương hiệu phải có sẳn trên website</p>
		<p>5. Danh mục, thuộc tính, giá trị thuộc tính, thương hiệu phân biệt hoa thường</p>
		<p>5. Tối đa 3 thuộc tính trên 1 sản phẩm</p>
	</div>
</div>
<div class="box" id="js_import_upload_error_box" style="display: none">
	<div class="box-content p-2">
		<div class="table-responsive">
			<table class="display table table-striped media-table">
				<thead>
				<tr>
					<th class="manage-column column">Dòng</th>
					<th class="manage-column column">Id</th>
					<th class="manage-column column">Id nhóm</th>
					<th class="manage-column column">Tên sản phẩm</th>
					<th class="manage-column column">Lỗi</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
<div class="box" id="js_import_import_error_box" style="display: none">
	<div class="box-content p-2">
		<div class="table-responsive">
			<table class="display table table-striped media-table">
				<thead>
				<tr>
					<th class="manage-column column">Dòng</th>
					<th class="manage-column column">Tên sản phẩm</th>
					<th class="manage-column column">Lỗi</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
<script>
	$(function () {

		const importProduct = new ImportProductHandel();

		$(document)
			.on('submit', '#js_import_products_form', function () {
				return importProduct.upload($(this))
			})
			.on('change', '#js_import_product_input_file', function () {
				return importProduct.changeFile($(this))
			})
			.on('click', '#js_import_product_upload_error', function () {
				return importProduct.uploadError($(this))
			})
			.on('click', '#js_import_product_submit', function () {
				return importProduct.import($(this))
			})
			.on('click', '#js_import_product_support', function () {
				return importProduct.clickSupport($(this))
			})
			.on('click', '#js_import_product_import_error', function () {
				return importProduct.importError($(this))
			})
	})
</script>