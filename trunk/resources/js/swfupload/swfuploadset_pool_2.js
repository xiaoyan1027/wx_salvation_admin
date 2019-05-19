	var swfu;
    var swfu_a;
	window.onload = function() {
		var settings = {
			flash_url : "../resources/scripts/swfupload/swfupload.swf",
			upload_url: posturl,
			post_params: {"PHPSESSID" : ""},
			file_size_limit : file_size,
			file_types : "*.jpg;*.gif;*.png;*.jpeg;*.flv",
			file_types_description : "All Files",
			file_upload_limit : 100,
			file_queue_limit : 0,
			custom_settings : {
				progressTarget : upload_list_id,
				cancelButtonId : "btnCancel"
			},
			debug: false,

			// Button settings
			button_image_url: "../resources/images/upload.png",
			button_width: "66",
			button_height: "24",
			button_placeholder_id: upload_button_id,
			button_text: '',
			button_text_style: "",
			button_text_left_padding: 5,
			button_text_top_padding: 0,
			
			// The event handler functions are defined in handlers.js

			file_queued_handler : fileQueued,
			file_queue_error_handler : fileQueueError,
			file_dialog_complete_handler : fileDialogComplete,
			upload_start_handler : uploadStart,
			upload_progress_handler : uploadProgress,
			upload_error_handler : uploadError,
			upload_success_handler : uploadSuccess,
			upload_complete_handler : uploadComplete,
			queue_complete_handler : queueComplete	// Queue plugin event
		};
        
		swfu = new SWFUpload(settings);
	 };