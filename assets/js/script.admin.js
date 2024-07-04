class ExportProductHandel {
	constructor() {
		this.modalHandle = new bootstrap.Modal('#js_export_products_modal')
		this.modal = $('#js_export_products_modal')
	}
	openModal(element) {
		this.modal.find('#js_export_products_form').show();
		this.modal.find('#js_export_products_result').hide();
		this.modalHandle.show()
		return false;
	}
	export(element) {
		let self = this;

		let data;

		let exportType = this.modal.find('input[name="exportType"]:checked').val();

		if(exportType === 'pageCurrent') {

			data = {};

			data.products = [];

			let divElements = document.querySelectorAll('tr[class*="tr_"]');

			divElements.forEach(function(element) {
				let classList = element.classList;
				for (let i = 0; i < classList.length; i++) {
					if (classList[i].startsWith("tr_")) {
						let number = classList[i].substr(3); // Cắt bỏ phần "tr_"
						data.products.push(number)
					}
				}
			});

			if(data.products.length === 0) {
				SkilldoMessage.error('Trang không có sản phẩm nào');
				return false;
			}
		}

		if(exportType === 'products') {
			data = {};
		}

		if(exportType === 'productsCheck') {

			data = {};

			data.products = []; let i = 0;

			$('.select:checked').each(function () { data.products[i++] = $(this).val(); });

			if(data.products.length === 0) {
				SkilldoMessage.error('Bạn chưa chọn sản phẩm nào');
				return false;
			}
		}

		if(exportType === 'searchCurrent') {

			data = {};

			let filter  = $(':input', $('#table-form-filter')).serializeJSON();

			let search = $(':input', $('#table-form-search')).serializeJSON();

			data.search = {...search, ...filter}
		}

		if(typeof data == "undefined") {
			SkilldoMessage.error('Kiểu xuất dữ liệu không hợp lệ');
			return false;
		}

		this.modal.find('#js_export_products_form .loading').show();

		data.action = 'ExportAjax::products';

		data.exportType = exportType

		request.post(ajax, data).then(function (response) {
			if (response.status === 'success') {
				self.modal.find('#js_export_products_form .loading').hide();
				self.modal.find('#js_export_products_form').hide();
				self.modal.find('#js_export_products_result a').attr('href', response.data);
				self.modal.find('#js_export_products_result').show();
			}
		});

		return false;
	}
}

class ImportAddProductHandel {
	constructor() {
		this.loading = $('#js_import_add_products_form .loading');
		this.divSupport = $('#js_import_support_box');
		this.divUploadError = $('#js_import_upload_error_box');
		this.divImportError = $('#js_import_import_error_box');
		this.divResultUpload = $('#js_import_add_result_upload');
		this.divResultImport = $('#js_import_add_result_import');
	}
	clickSupport(element) {
		this.divSupport.show();
		this.divSupport.find('.js_import_update_support_box').hide();
		this.divSupport.find('.js_import_add_support_box').show();
		this.divUploadError.hide();
		this.divImportError.hide();
	}
	changeFile(element) {
		let filename = element.val();
		$('.file-upload-text').html(filename);
	}
	upload(element) {

		let self = this;

		this.divImportError.hide();

		this.loading.show();

		let fileData = element.find('input[type="file"]').prop('files')[0];

		let formData = new FormData(element[0]);

		formData.append('file', fileData);

		formData.append('action', 'ProductsImportAjax::addUpload');

		formData.append('csrf_test_name', encodeURIComponent(getCookie('csrf_cookie_name')));

		this.divResultUpload.hide();

		this.divResultImport.hide();

		request.post(ajax, formData).then((response) => {
			if(response.status === 'error') {
				SkilldoMessage.error(response.message);
			}
			if(response.status === 'success') {
				self.divResultUpload.find('.add span').html(response.data.add);
				self.divResultUpload.find('.upload span').html(response.data.upload);
				self.divResultUpload.find('.error span').html(response.data.errors);
				self.divResultUpload.show();
			}
			$('.file-upload-text').html('Chọn File upload');
			element.trigger('reset');
			self.loading.hide();
		})

		return false;
	}
	showUploadError(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'ProductsImportAjax::uploadError'
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				response.data = decodeURIComponent(atob(response.data).split('').map(function (c) {
					return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
				}).join(''));
				self.divSupport.hide();
				self.divImportError.hide();
				self.divUploadError.find('tbody').html(response.data);
				self.divUploadError.show();
			}
		});
		return false;
	}
	import(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'ProductsImportAjax::addImport'
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				self.divResultImport.find('.add span').html(response.data.add);
				self.divResultImport.find('.upload span').html(response.data.upload);
				self.divResultImport.find('.error span').html(response.data.errors);
				self.divResultImport.show();
			}
		});
		return false;
	}
	showImportError(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'ProductsImportAjax::importError'
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				response.data = decodeURIComponent(atob(response.data).split('').map(function (c) {
					return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
				}).join(''));
				self.divSupport.hide();
				self.divUploadError.hide();
				self.divImportError.find('tbody').html(response.data);
				self.divImportError.show();
			}
		});
		return false;
	}
}

class ImportUpdateProductHandel {
	constructor() {
		this.loading = $('#js_import_update_products_form .loading');
		this.divSupport = $('#js_import_support_box');
		this.divUploadError = $('#js_import_upload_error_box');
		this.divImportError = $('#js_import_import_error_box');
		this.divResultUpload = $('#js_import_update_result_upload');
		this.divResultImport = $('#js_import_update_result_import');
		this.columnMain = 'id';
	}
	clickSupport(element) {
		this.divSupport.show();
		this.divSupport.find('.js_import_add_support_box').hide();
		this.divSupport.find('.js_import_update_support_box').show();
		this.divUploadError.hide();
		this.divImportError.hide();
	}
	changeFile(element) {
		let filename = element.val();
		$('.file-upload-text').html(filename);
	}
	upload(element) {

		let self = this;

		this.divImportError.hide();

		this.loading.show();

		this.columnMain = element.find('input[name="columnMain"]:checked').val();

		let fileData = element.find('input[type="file"]').prop('files')[0];

		let formData = new FormData(element[0]);

		formData.append('file', fileData);

		formData.append('columnMain', this.columnMain);

		formData.append('action', 'ProductsImportAjax::upload');

		formData.append('csrf_test_name', encodeURIComponent(getCookie('csrf_cookie_name')));

		this.divResultUpload.hide();

		this.divResultImport.hide();

		request.post(ajax, formData).then((response) => {
			if(response.status === 'error') {
				SkilldoMessage.error(response.message);
			}
			if(response.status === 'success') {
				self.divResultUpload.find('.add span').html(response.data.add);
				self.divResultUpload.find('.upload span').html(response.data.upload);
				self.divResultUpload.find('.error span').html(response.data.errors);
				self.divResultUpload.show();
			}
			$('.file-upload-text').html('Chọn File upload');
			element.trigger('reset');
			self.loading.hide();
			element.find('input[name="columnMain"][value="'+this.columnMain+'"]').prop('checked', true);
		})

		return false;
	}
	showUploadError(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'ProductsImportAjax::uploadError'
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				response.data = decodeURIComponent(atob(response.data).split('').map(function (c) {
					return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
				}).join(''));
				self.divSupport.hide();
				self.divImportError.hide();
				self.divUploadError.find('tbody').html(response.data);
				self.divUploadError.show();
			}
		});
		return false;
	}
	import(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'ProductsImportAjax::import',
			columnMain: this.columnMain
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				self.divResultImport.find('.add span').html(response.data.add);
				self.divResultImport.find('.upload span').html(response.data.upload);
				self.divResultImport.find('.error span').html(response.data.errors);
				self.divResultImport.show();
			}
		});
		return false;
	}
	showImportError(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'ProductsImportAjax::importError'
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				response.data = decodeURIComponent(atob(response.data).split('').map(function (c) {
					return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
				}).join(''));
				self.divSupport.hide();
				self.divUploadError.hide();
				self.divImportError.find('tbody').html(response.data);
				self.divImportError.show();
			}
		});
		return false;
	}
}


class ExportInventoryHandel {
	constructor() {
		this.modalHandle = new bootstrap.Modal('#js_export_inventory_modal')
		this.modal = $('#js_export_inventory_modal')
	}
	openModal(element) {
		this.modal.find('#js_export_inventory_form').show();
		this.modal.find('#js_export_inventory_result').hide();
		this.modalHandle.show()
		return false;
	}
	export(element) {
		let self = this;

		let data;

		let exportType = this.modal.find('input[name="exportType"]:checked').val();

		if(exportType === 'pageCurrent') {

			data = {};

			data.products = [];

			let divElements = document.querySelectorAll('tr[class*="tr_"]');

			divElements.forEach(function(element) {
				let classList = element.classList;
				for (let i = 0; i < classList.length; i++) {
					if (classList[i].startsWith("tr_")) {
						let number = classList[i].substr(3); // Cắt bỏ phần "tr_"
						data.products.push(number)
					}
				}
			});

			if(data.products.length === 0) {
				SkilldoMessage.error('Trang không có sản phẩm nào');
				return false;
			}
		}

		if(exportType === 'products') {
			data = {};
		}

		if(exportType === 'productsCheck') {

			data = {};

			data.products = []; let i = 0;

			$('.select:checked').each(function () { data.products[i++] = $(this).val(); });

			if(data.products.length === 0) {
				SkilldoMessage.error('Bạn chưa chọn sản phẩm nào');
				return false;
			}
		}

		if(exportType === 'searchCurrent') {

			data = {};

			let filter  = $(':input', $('#table-form-filter')).serializeJSON();

			let search = $(':input', $('#table-form-search')).serializeJSON();

			data.search = {...search, ...filter}
		}

		if(typeof data == "undefined") {
			SkilldoMessage.error('Kiểu xuất dữ liệu không hợp lệ');
			return false;
		}

		this.modal.find('#js_export_products_form .loading').show();

		data.action = 'ExportAjax::inventory';

		data.exportType = exportType

		request.post(ajax, data).then(function (response) {
			if (response.status === 'success') {
				self.modal.find('#js_export_inventory_form .loading').hide();
				self.modal.find('#js_export_inventory_form').hide();
				self.modal.find('#js_export_inventory_result a').attr('href', response.data);
				self.modal.find('#js_export_inventory_result').show();
			}
		});

		return false;
	}
}

class ImportInventoryHandel {
	constructor() {
		this.loading = $('#js_import_inventory_form .loading');
		this.divSupport = $('#js_import_support_box');
		this.divUploadError = $('#js_import_upload_error_box');
		this.divImportError = $('#js_import_import_error_box');
		this.divResultUpload = $('#js_import_inventory_result_upload');
		this.divResultImport = $('#js_import_inventory_result_import');
		this.columnMain = 'id';
		this.branchId = 0;
	}
	clickSupport(element) {
		this.divSupport.show();
		this.divUploadError.hide();
		this.divImportError.hide();
	}
	changeFile(element) {
		let filename = element.val();
		$('.file-upload-text').html(filename);
	}
	upload(element) {

		let self = this;

		this.divImportError.hide();

		this.loading.show();

		this.columnMain = element.find('input[name="columnMain"]:checked').val();

		this.branchId = element.find('select[name="branchId"]').val();

		let fileData = element.find('input[type="file"]').prop('files')[0];

		let formData = new FormData(element[0]);

		formData.append('action', 'InventoryImportAjax::upload');

		formData.append('csrf_test_name', encodeURIComponent(getCookie('csrf_cookie_name')));

		this.divResultUpload.hide();

		this.divResultImport.hide();

		request.post(ajax, formData).then((response) => {
			if(response.status === 'error') {
				SkilldoMessage.error(response.message);
			}
			if(response.status === 'success') {
				self.divResultUpload.find('.add span').html(response.data.add);
				self.divResultUpload.find('.upload span').html(response.data.upload);
				self.divResultUpload.find('.error span').html(response.data.errors);
				self.divResultUpload.show();
			}
			$('.file-upload-text').html('Chọn File upload');
			element.trigger('reset');
			self.loading.hide();
			element.find('input[name="columnMain"][value="'+this.columnMain+'"]').prop('checked', true);
			element.find('select[name="branchId"]').val(this.branchId).change();
		})

		return false;
	}
	showUploadError(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'InventoryImportAjax::uploadError'
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				response.data = decodeURIComponent(atob(response.data).split('').map(function (c) {
					return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
				}).join(''));
				self.divSupport.hide();
				self.divImportError.hide();
				self.divUploadError.find('tbody').html(response.data);
				self.divUploadError.show();
			}
		});
		return false;
	}
	import(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'InventoryImportAjax::import',
			columnMain: this.columnMain,
			branchId: this.branchId
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				self.divResultImport.find('.add span').html(response.data.add);
				self.divResultImport.find('.upload span').html(response.data.upload);
				self.divResultImport.find('.error span').html(response.data.errors);
				self.divResultImport.show();
			}
		});
		return false;
	}
	showImportError(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'InventoryImportAjax::importError'
		}

		request.post(ajax, data).then((response) => {
			self.loading.hide();
			if (response.status === 'success') {
				response.data = decodeURIComponent(atob(response.data).split('').map(function (c) {
					return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
				}).join(''));
				self.divSupport.hide();
				self.divUploadError.hide();
				self.divImportError.find('tbody').html(response.data);
				self.divImportError.show();
			}
		});
		return false;
	}
}

class ExportOrderHandel {
	constructor() {
		this.modalHandle = new bootstrap.Modal('#js_export_order_modal')
		this.modal = $('#js_export_order_modal')
	}
	openModal(element) {
		this.modal.find('#js_export_order_form').show();
		this.modal.find('#js_export_order_result').hide();
		this.modalHandle.show()
		return false;
	}
	export(element) {
		let self = this;

		let data;

		let exportType = this.modal.find('input[name="exportType"]:checked').val();

		if(exportType === 'pageCurrent') {

			data = {};

			data.orders = [];

			let divElements = document.querySelectorAll('tr[class*="tr_"]');

			divElements.forEach(function(element) {
				let classList = element.classList;
				for (let i = 0; i < classList.length; i++) {
					if (classList[i].startsWith("tr_")) {
						let number = classList[i].substr(3); // Cắt bỏ phần "tr_"
						data.orders.push(number)
					}
				}
			});

			if(data.orders.length === 0) {
				SkilldoMessage.error('Trang không có đơn hàng nào');
				return false;
			}
		}

		if(exportType === 'orderCheck') {

			data = {};

			data.orders = []; let i = 0;

			$('.select:checked').each(function () { data.orders[i++] = $(this).val(); });

			if(data.orders.length === 0) {
				SkilldoMessage.error('Bạn chưa chọn đơn hàng nào');
				return false;
			}
		}

		if(exportType === 'searchCurrent') {

			data = {};

			let filter  = $(':input', $('#table-form-filter')).serializeJSON();

			let search = $(':input', $('#table-form-search')).serializeJSON();

			data.search = {...search, ...filter}
		}

		if(typeof data == "undefined") {
			SkilldoMessage.error('Kiểu xuất dữ liệu không hợp lệ');
			return false;
		}

		this.modal.find('#js_export_order_form .loading').show();

		data.action = 'ExportAjax::order';

		data.exportType = exportType

		request.post(ajax, data).then(function (response) {
			if (response.status === 'success') {
				self.modal.find('#js_export_order_form .loading').hide();
				self.modal.find('#js_export_order_form').hide();
				self.modal.find('#js_export_order_result a').attr('href', response.data);
				self.modal.find('#js_export_order_result').show();
			}
		});

		return false;
	}
}