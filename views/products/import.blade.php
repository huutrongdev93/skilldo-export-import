<div class="ui-title-bar__group">
	<h1 class="ui-title-bar__title">Import sản phẩm</h1>
	<div class="ui-title-bar__des">Cập nhật thêm mới sản phẩm bằng file excel</div>
</div>
<div class="box mb-2">
	<div class="box-content">
		<ul class="nav nav-tabs nav-tabs-horizontal mb-4" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active"
						id="product-add-tab"
						data-bs-toggle="tab"
						data-bs-target="#product-add-tab-pane"
						type="button"
						role="tab"
						aria-controls="product-add-tab-pane"
						aria-selected="true">Thêm sản phẩm</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link"
						id="product-edit-tab"
						data-bs-toggle="tab"
						data-bs-target="#product-edit-tab-pane"
						type="button"
						role="tab"
						aria-controls="product-edit-tab-pane"
						aria-selected="true">Cập nhật sản phẩm</button>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane fade show active" id="product-add-tab-pane" role="tabpanel" aria-labelledby="product-add-tab" tabindex="0">
				{!! Plugin::partial(EXIM_NAME, 'products/import-add') !!}
			</div>
			<div class="tab-pane fade" id="product-edit-tab-pane" role="tabpanel" aria-labelledby="product-edit-tab" tabindex="0">
				{!! Plugin::partial(EXIM_NAME, 'products/import-update') !!}
			</div>
		</div>
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