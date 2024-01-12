<div class="modal fade" id="js_export_products_modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Xuất Sản phẩm</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
	        <div id="js_export_products_form">
		        <?php Admin::loading();?>
	            <div class="modal-body" style="overflow-x:auto; max-height:500px;">
		            <div class="form-group">
			            <label class="radio d-block mb-2">
				            <input type="radio" name="exportType" value="pageCurrent" class="icheck" checked> Trang hiện tại
			            </label>
			            <label class="radio d-block mb-2">
				            <input type="radio" name="exportType" value="products" class="icheck"> Tất cả sản phẩm
			            </label>
			            <label class="radio d-block mb-2">
				            <input type="radio" name="exportType" value="productsCheck" class="icheck"> Sản phẩm được chọn
			            </label>
			            <label class="radio d-block mb-2">
				            <input type="radio" name="exportType" value="searchCurrent" class="icheck"> Theo bộ lọc hiện tại
			            </label>
		            </div>
	            </div>
	            <div class="modal-footer">
	                <button class="btn btn-white" type="button" data-bs-dismiss="modal" aria-label="Close">Hủy</button>
	                <button class="btn btn-blue" type="button" id="js_export_products_btn_submit"><i class="fa-light fa-download"></i> Xuất dữ liệu</button>
	            </div>
	        </div>
	        <div id="js_export_products_result" style="display:none;">
		        <div class="modal-body">
		            <a href="" class="btn btn-blue btn-blue-bg" download><i class="fa-duotone fa-file-excel"></i> Tải File excel</a>
			        <button class="btn btn-white" type="button" data-bs-dismiss="modal" aria-label="Close">Đóng</button>
		        </div>
	        </div>
        </div>
    </div>
</div>
<script>
	$(function () {
		const exportProduct = new ExportProductHandel();
		$(document)
			.on('click', '#js_export_product_btn_modal', function () {
				return exportProduct.openModal($(this))
			})
			.on('click', '#js_export_products_btn_submit', function () {
				return exportProduct.export($(this))
			})
	})
</script>
