<div class="ui-title-bar__group">
	<h1 class="ui-title-bar__title">Import kho hàng</h1>
	<div class="ui-title-bar__des">Cập nhật thêm mới số lượng sản phẩm bằng file excel</div>
</div>
<div class="box mb-2">
	<div class="box-content">
		<form method="post" enctype="multipart/form-data" class="" id="js_import_inventory_form">
			{!! Admin::loading() !!}
			<div class="row">
				<div class="col-md-4">
					<p>Tải danh sách sản phẩm cần <b>cập nhật</b> lên hệ thống</p>
					<div class="upload-main-wrapper">
						<div class="upload-wrapper">
							<input accept="application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" type="file" name="file" id="js_import_inventory_input_file">
							<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="340.531" height="419.116" viewBox="0 0 340.531 419.116">
								<g id="files-new" clip-path="url(#clip-files-new)">
									<path id="Union_2" data-name="Union 2" d="M-2904.708-8.885A39.292,39.292,0,0,1-2944-48.177V-388.708A39.292,39.292,0,0,1-2904.708-428h209.558a13.1,13.1,0,0,1,9.3,3.8l78.584,78.584a13.1,13.1,0,0,1,3.8,9.3V-48.177a39.292,39.292,0,0,1-39.292,39.292Zm-13.1-379.823V-48.177a13.1,13.1,0,0,0,13.1,13.1h261.947a13.1,13.1,0,0,0,13.1-13.1V-323.221h-52.39a26.2,26.2,0,0,1-26.194-26.195v-52.39h-196.46A13.1,13.1,0,0,0-2917.805-388.708Zm146.5,241.621a14.269,14.269,0,0,1-7.883-12.758v-19.113h-68.841c-7.869,0-7.87-47.619,0-47.619h68.842v-18.8a14.271,14.271,0,0,1,7.882-12.758,14.239,14.239,0,0,1,14.925,1.354l57.019,42.764c.242.185.328.485.555.671a13.9,13.9,0,0,1,2.751,3.292,14.57,14.57,0,0,1,.984,1.454,14.114,14.114,0,0,1,1.411,5.987,14.006,14.006,0,0,1-1.411,5.973,14.653,14.653,0,0,1-.984,1.468,13.9,13.9,0,0,1-2.751,3.293c-.228.2-.313.485-.555.671l-57.019,42.764a14.26,14.26,0,0,1-8.558,2.847A14.326,14.326,0,0,1-2771.3-147.087Z" transform="translate(2944 428)" fill="var(--theme-color)"></path>
								</g>
							</svg>
							<span class="file-upload-text">Chọn File upload</span>
						</div>
					</div>
					<p class="mb-2 mt-2" style="color:#ccc">Chấp nhận file đuôi .xls và .xlsx</p>
					<div class="">
						<label class="mb-3">Cập nhật sản phẩm dựa trên trường</label>
						<label class="form-check" style="font-weight: normal">
							<input type="radio" name="columnMain" value="id" class="form-check-input" checked>
							ID sản phẩm
						</label>
						<label class="form-check" style="font-weight: normal">
							<input type="radio" name="columnMain" value="code" class="form-check-input">
							Mã sản phẩm
						</label>
					</div>
					<div class="mb-3">
						{!! \SkillDo\Form\Form::render([
    						'name' => 'branchId',
                            'label' => 'Chi nhánh cần cập nhật',
    						'type' => 'select',
    						'options' => $branchOptions
						]) !!}
					</div>
					<div class="text-right mt-2">
						<a href="{!! Url::admin('plugins?page=import-file-demo&file-download=inventories-demo') !!}" download class="btn btn-blue btn-blue-bg ms-0">Tải file mẫu</a>
						<button class="btn btn-blue">Upload File</button>
					</div>
				</div>
				<div class="col-md-4">
					<p class="heading">Kết quả phân tích</p>
					<div class="import-result-alert" id="js_import_inventory_result_upload" style="display: none">
						<p class="upload">Có <span>0</span> sản phẩm sẽ được <b>cập nhật</b> tồn kho</p>
						<p class="error">Có <span>0</span> sản phẩm <b>lỗi</b> không thể đăng</p>
						<div class="text-right mt-2">
							<button class="btn btn-blue" id="js_import_inventory_submit">Nhập dữ liệu</button>
							<button class="btn btn-red" id="js_import_inventory_analysis_error">Xem lỗi</button>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<p class="heading">Kết quả import</p>
					<div class="import-result-alert" id="js_import_inventory_result_import" style="display: none">
						<p class="upload">Có <span>0</span> sản phẩm đã <b>cập nhật</b> tồn kho</p>
						<p class="error">Có <span>0</span> sản phẩm <b>lỗi</b></p>
						<div class="text-right mt-2">
							<button class="btn btn-red" id="js_import_inventory_import_error">Xem lỗi</button>
						</div>
					</div>
				</div>
			</div>
		</form>

		<script>
			$(function () {

				const importInventory = new ImportInventoryHandel();

				$(document)
						.on('submit', '#js_import_inventory_form', function () {
							return importInventory.upload($(this))
						})
						.on('change', '#js_import_inventory_input_file', function () {
							return importInventory.changeFile($(this))
						})
						.on('click', '#js_import_inventory_analysis_error', function () {
							return importInventory.showUploadError($(this))
						})
						.on('click', '#js_import_inventory_submit', function () {
							return importInventory.import($(this))
						})
						.on('click', '#js_import_inventory_support', function () {
							return importInventory.clickSupport($(this))
						})
						.on('click', '#js_import_inventory_import_error', function () {
							return importInventory.showImportError($(this))
						})
			})
		</script>
	</div>
</div>
<div class="box mb-2" id="js_import_support_box" style="display:none;">
	<div class="box-content">
		<div class="js_import_add_support_box">
			<p class="heading">Thêm mới sản phẩm</p>

			<p class="badge text-bg-blue">Trường <b>ID Nhóm</b></p>
			<p> - Bỏ trống hoặc điền 0 nếu muốn thêm sản phẩm không có biến thể (không có các thuộc tính như màu sắc, size ...)</p>
			<p> - Đối với thêm mới sản phẩm có biến thể điền chữ cái giống nhau.
				Ví dụ thêm sản phẩm áo vest A (màu trắng - size S), áo vest A (màu trắng - size M), áo vest A (màu đỏ - size L)
				3 sản phẩm này là một nhóm sản phẩm biến thể thì Id nhóm phải giống nhau ví dụ Id nhóm là <b>VEST_A</b>
			</p>
			<p> - Đối với thêm một biến thể mới vào một sản phẩm đã có sẳn thì Id nhóm chính là id của sản phẩm cần thêm vào</p>

			<p class="badge text-bg-blue">Trường <b>Sản phẩm chính</b></p>
			<p>- Trường nhận giá trị là "Yes" hoặc "No"</p>
			<p>- Sản phẩm chính là sản phẩm sẽ được hiển thị nội dung, hình ảnh, giá tiền cho khách hàng thấy nếu sản phẩm có nhiều biến thể</p>
			<p>- Đối với sản phẩm không có biến thể thì giá trị trường này luôn là "yes"</p>
			<p>- Khi thêm mới 1 sản phẩm biến thể thì bắt buộc trong nhóm phải có duy nhất 1 sản phẩm có giá trị là yes, ví dụ với nhóm "VEST_A" ở trên
			thì bắt buộc 1 trong 3 sản phẩm áo vest A (màu trắng - size S), áo vest A (màu trắng - size M), áo vest A (màu đỏ - size L) phải có 1 sản phẩm có
			trường sản phẩm chính là yes</p>

			<p class="badge text-bg-blue">Các trường <b>Thuộc tính</b></p>
			<p><i style="font-style: italic">- Khi thêm một nhóm sản phẩm thì các sản phẩm trong nhóm phải có thuộc tính giống nhau. Ví dụ:</i></p>
			<p>Áo vest A : màu trắng - size S (Thuộc tính màu và size)</p>
			<p>Áo vest A : màu trắng - size M (Thuộc tính màu và size)</p>
			<p>Áo vest A : màu đỏ - size L (Thuộc tính màu và size)</p>
			<p><i style="font-style: italic">Bạn không thể thêm một nhóm sản phẩm mà các thuộc tính khác nhau hoặc không đồng điều số lượng thuộc tính. Ví dụ nhóm bị sai:</i></p>
			<p>Áo vest A : màu trắng - size S (Thuộc tính màu và size)</p>
			<p>Áo vest A : size M (Thuộc tính size)</p>
			<p>Áo vest A : Vải len - size L (Thuộc tính chất liệu và size)</p>

			<p><i style="font-style: italic">- Khi thêm một biến thể vào sản phẩm đã có trên website thì sản phẩm phải có thuộc tính giống thuộc tính sản phẩm đang có trong website. Ví dụ:</i></p>
			<p>Bạn thêm một biến thể vào sản phẩm Quần Tây B đã có thuộc tính (Màu và Size)</p>
			<p>Bạn không thể thêm biến thể Quần Tây B Vải len - size L vì Thuộc tính chất liệu không có trong sản phẩm cần thêm</p>
			<p>Bạn không thể thêm biến thể Quần Tây B size L vì thiếu thuộc tính màu</p>
		</div>
		<div class="js_import_update_support_box">
			<p class="heading">Cập nhật sản phẩm</p>

			<p class="badge text-bg-blue">Trường <b>ID</b></p>
			<p>- Sản phẩm phải có id trùng id sản phẩm trên website thì sản phẩm mới được cập nhật</p>

			<p class="badge text-bg-blue">Trường <b>Sản phẩm chính</b></p>
			<p>- Trường nhận giá trị là "Yes" hoặc "No"</p>
			<p>- Sản phẩm chính là sản phẩm sẽ được hiển thị nội dung, hình ảnh, giá tiền cho khách hàng thấy nếu sản phẩm có nhiều biến thể</p>
			<p>- Đối với sản phẩm không có biến thể thì giá trị trường này luôn là "yes"</p>
		</div>
		<p class="heading">Lưu ý</p>
		<p>1. Tên danh mục sản phẩm phải có sẳn trên website</p>
		<p>2. Có thể thêm nhiều danh mục bằng dấu ","</p>
		<p>ví dụ: <i style="font-style: italic">điện thoại, máy tính bảng</i></p>
		<p>3. Tên thuộc tính phải và giá trị thuộc tính phải có sẳn trên website</p>
		<p>4. Tên thương hiệu phải có sẳn trên website</p>
		<p>5. Danh mục, thuộc tính, giá trị thuộc tính, thương hiệu phân biệt hoa thường</p>
		<p>6. Tối đa 3 thuộc tính trên 1 sản phẩm</p>
	</div>
</div>
<div class="box mb-2" id="js_import_upload_error_box" style="display: none">
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
<div class="box mb-2" id="js_import_import_error_box" style="display: none">
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