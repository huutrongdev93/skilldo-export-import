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
				show_message('Trang không có sản phẩm nào', 'error');
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
				show_message('Bạn chưa chọn sản phẩm nào', 'error');
				return false;
			}
		}

		if(exportType === 'searchCurrent') {

			data = {};

			data.search  = $(':input', $('form.search-box')).serializeJSON();
		}

		if(typeof data == "undefined") {
			show_message('Kiểu xuất dữ liệu không hợp lệ', 'error');
			return false;
		}

		this.modal.find('#js_export_products_form .loading').show();

		data.action = 'ProductsExportAjax::export';

		data.exportType = exportType

		$.post(ajax, data, function () {}, 'json').done(function (response) {
			if (response.status === 'success') {
				self.modal.find('#js_export_products_form .loading').hide();
				self.modal.find('#js_export_products_form').hide();
				self.modal.find('#js_export_products_result a').attr('href', response.path);
				self.modal.find('#js_export_products_result').show();
			}
		});

		return false;
	}
}

class ImportProductHandel {
	constructor() {
		this.loading = $('#js_import_products_form .loading');
		this.divSupport = $('#js_import_support_box');
		this.divUploadError = $('#js_import_upload_error_box');
		this.divImportError = $('#js_import_import_error_box');
		this.divResultUpload = $('#js_import_result_upload');
		this.divResultImport = $('#js_import_result_import');
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

		this.loading.show();

		let fileData = element.find('input[type="file"]').prop('files')[0];

		let formData = new FormData(element[0]);

		formData.append('file', fileData);

		formData.append('action', 'ProductsImportAjax::upload');

		formData.append('csrf_test_name', encodeURIComponent(getCookie('csrf_cookie_name')));

		this.divResultUpload.hide();
		this.divResultImport.hide();

		$.ajax({
			url: ajax,
			dataType: 'json',
			cache: false,
			contentType: false,
			processData: false,
			data: formData,
			type: 'post',
			beforeSend: function () {},
			success: function (res) {
				if(res.status == 'error') {
					show_message(res.message, res.status);
				}
				if(res.status == 'success') {
					self.divResultUpload.find('.add span').html(res.data.add);
					self.divResultUpload.find('.upload span').html(res.data.upload);
					self.divResultUpload.find('.error span').html(res.data.errors);
					self.divResultUpload.show();
				}
				$('.file-upload-text').html('Chọn File upload');
				element.trigger('reset');
				self.loading.hide();
			}
		});
		return false;
	}
	uploadError(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'ProductsImportAjax::uploadError'
		}

		$.post(ajax, data, function () {}, 'json').done(function (response) {
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
			action: 'ProductsImportAjax::import'
		}

		$.post(ajax, data, function () {}, 'json').done(function (response) {
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
	importError(element) {

		let self = this;

		this.loading.show();

		let data =  {
			action: 'ProductsImportAjax::importError'
		}

		$.post(ajax, data, function () {}, 'json').done(function (response) {
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