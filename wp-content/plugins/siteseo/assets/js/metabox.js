jQuery(document).ready(function($){
	var mediaUploader;
	
	init_uploaders();
	
	let debounce;
	function siteseo_debounce(func, timeout = 500){
		clearTimeout(debounce);
		debounce = setTimeout(() => {
			func.apply(this, arguments);
		}, timeout);
	}
	
	$(document).on('click', '.siteseo-metabox-tab-label', function(){
		let jEle = $(this),
		parent_tab = jEle.closest('.siteseo-metabox-tabs, .siteseo-metabox-subtabs'),
		active_tab = parent_tab.find('.siteseo-metabox-tab-label-active');

		if(active_tab.length){
			active_tab.removeClass('siteseo-metabox-tab-label-active');
		}

		jEle.addClass('siteseo-metabox-tab-label-active');
		let target = jEle.data('tab');

		parent_tab.siblings('.'+target).show();
		parent_tab.siblings('.'+target).siblings('.siteseo-metabox-tab').hide();
	});

	function init_media_uploader(buttonId, inputId, previewClass, attachmentIdField, widthField, heightField){
        var mediaUploader;
        
        $(document).on('click', '#' + buttonId, function(e){
            e.preventDefault();
            
            mediaUploader = wp.media({
                title: 'Choose Image',
                button:{
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function(){
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                var isValid = validateImageDimensions(
                    attachment.width, 
                    attachment.height, 
                    buttonId.includes('facebook')
                );

                if(!isValid.valid){
                    var errorSpan = $('#' + inputId).siblings('span');
                    if(errorSpan.length === 0){
						
                        $('#' + inputId).after('<span class="error-message" style="color: red;"></span>');
                        errorSpan = $('#' + inputId).siblings('span');
                    }
                    errorSpan.text(isValid.message).show();
                    return;
                }
				
                $('#' + inputId).siblings('span').hide();
                $('#' + inputId).val(attachment.url);
                $('#' + attachmentIdField).val(attachment.id);
                $('#' + widthField).val(attachment.width);
                $('#' + heightField).val(attachment.height);
                
                var previewImg = $('.' + previewClass + ' img');
                if (previewImg.length === 0) {
                    $('.' + previewClass).append('<img src="' + attachment.url + '" />');
                } else {
                    previewImg.attr('src', attachment.url);
                }
            });

            mediaUploader.open();
        });
    }
	
	function init_uploaders(){
		init_media_uploader(
            'siteseo_social_fb_img_upload',
            'siteseo_social_fb_img_meta',
            'siteseo-metabox-fb-image',
            'siteseo_social_fb_img_attachment_id',
            'siteseo_social_fb_img_width',
            'siteseo_social_fb_img_height'
        );

        init_media_uploader(
            'siteseo_social_twitter_img_upload',
            'siteseo_social_twitter_img_meta',
            'siteseo-metabox-x-image',
            'siteseo_social_twitter_img_attachment_id',
            'siteseo_social_twitter_img_width',
            'siteseo_social_twitter_img_height'
        );
	}

    function validateImageDimensions(width, height, isFacebook){
        if(isFacebook){
            if(width < 200 || height < 200){
                return {
                    valid: false,
                    message: 'Image must be at least 200x200 pixels for Facebook'
                };
            }
            
            if((width * height * 4) / (1024 * 1024) > 8){
                return {
                    valid: false,
                    message: 'Image size exceeds Facebook 8MB limit'
                };
            }
			
			return { valid: true };
		}

		if(width < 144 || height < 144){
			return {
				valid: false,
				message: 'Image must be at least 144x144 pixels for X'
			};
		}
		
		if((width * height * 4) / (1024 * 1024) > 5){
			return {
				valid: false,
				message: 'Image size exceeds X 5MB limit'
			};
		}
        return { valid: true };
    }

    $(document).on('widget-added widget-updated', init_uploaders);
	
	$(document).on('click', '.siteseo-metabox-tag', function(){
		let tag = $(this).data('tag'),
		$wrapper = $(this).closest('.siteseo-metabox-input-wrap'),
		$input = $wrapper.find('#siteseo_titles_title_meta, textarea'),
		
		currentValue = $input.val(),
		newValue = currentValue + " " + tag;

		$input.val(newValue);
		update_char_counter($input);

		$input.trigger('input');
	});

    $(document).on('input paste', '.siteseo_titles_title_meta, .siteseo_titles_desc_meta', function(e){
		update_char_counter($(e.target));
    });

    function update_char_counter($input){
		let max_chars = $input.hasClass('siteseo_titles_title_meta') ? 60 : 160,
		current_length = $input.val().length,
		percentage = Math.min((current_length/max_chars) * 100, 100),
		$wrapper = $input.closest('.siteseo-metabox-input-wrap'),
		$meter = $wrapper.find('.siteseo-metabox-limits-meter span'),
		$counter = $wrapper.find('.siteseo-metabox-limits-numbers em');
		
		if(max_chars == 60){
			update_title_placeholder($input.val());
		} else {
			update_desc_placeholder($input.val());
		}

		$meter.css('width', percentage + '%');
		$counter.text(current_length);
    }
	
	function update_title_placeholder(title){
		if(title.length > 60){
			title = title.substring(0, 60) + '...';
		}
		
		if(title.includes('%%')){
			siteseo_debounce(() => resolve_dynamic_variables(title, 'title'));
			return;
		}
		
		$('.siteseo-metabox-search-preview h3').text(title);
	}
	
	function update_desc_placeholder(desc){
		if(desc.length > 160){
			desc = desc.substring(0, 160) + '...';
		}
		
		if(desc.includes('%%')){
			siteseo_debounce(() => resolve_dynamic_variables(desc, 'desc'));
			return;
		}
		
		$('.siteseo-search-preview-description').text(desc);
	}
	
	// seo analysis and readiblity toggle 
	function loadTabs(selector){
		$(selector).load(" #siteseo-analysis-tabs-1", "", initializeToggle);
    }
	
	loadTabs("#siteseo-analysis-tabs");
	loadTabs("#siteseo-metabox-wrapper #siteseo-analysis-tabs");

	function initializeToggle(){
		let preventClick = false;

		$(document).off('click', '.siteseo-analysis-block-title').on('click', '.siteseo-analysis-block-title', function (event){
			if(preventClick){
				event.stopImmediatePropagation();
				event.preventDefault();
				preventClick = false;
				return;
			}

			let $title = $(this),
			$content = $title.next(".siteseo-analysis-block-content");

			$title.toggleClass("open");
			let isExpanded = $title.attr('aria-expanded') === "true",
			isHidden = $content.attr('aria-hidden') === "true";

			$title.attr('aria-expanded', !isExpanded);
			$content.toggle();
			$content.attr('aria-hidden', !isHidden);
		});

		$(document).on('click', '#expand-all', function (event){
			event.preventDefault();
			$(".siteseo-analysis-block-content").show();
			$(".siteseo-analysis-block-title").attr('aria-expanded', true);
			$(".siteseo-analysis-block-content").attr('aria-hidden', false);
		});

		$(document).on('click', '#close-all', function (event){
			event.preventDefault();
			$(".siteseo-analysis-block-content").hide();
			$(".siteseo-analysis-block-title").attr('aria-expanded', false);
			$(".siteseo-analysis-block-content").attr('aria-hidden', true);
		});
	}
	

	/**suggestion btn **/
	$('.siteseo-suggetion').hide();

	$(document).on('click', '.siteseo-tag-select-btn', function(e){
		e.preventDefault();
		e.stopPropagation();

		var $suggestion = $(this).next('.siteseo-suggestions-wrapper').find('.siteseo-suggetion');
		if($suggestion.length){
			$('.siteseo-suggetion').not($suggestion).hide();
			$suggestion.toggle();
		}
	});

	$(document).on('click', '.siteseo-suggestions-container .section', function(e){
		e.preventDefault();
		e.stopPropagation();

		let tag = $(this).find('.tag').text(),
		$container = $(this).closest('.siteseo-metabox-input-wrap, .siteseo-sidebar-input-wrap'),
		$targetField;

		// Check for both metabox and sidebar fields
		if($container.find('#siteseo_titles_title_meta, .siteseo-sidebar-title').length){
			$targetField = $container.find('#siteseo_titles_title_meta, .siteseo-sidebar-title');
		} else if($container.find('#siteseo_titles_desc_meta, .siteseo-sidebar-desc').length){
			$targetField = $container.find('#siteseo_titles_desc_meta, .siteseo-sidebar-desc');
		}

		if($targetField && $targetField.length){
			append_suggestion_tag($targetField, tag);
			update_char_counter($targetField);
			$(this).closest('.siteseo-suggetion').hide();
		}
	});

	// Close when click outside
	$(document).on('click', function(e){
		if(!$(e.target).closest('.siteseo-metabox-input-wrap, .siteseo-sidebar-input-wrap').length){
			$('.siteseo-suggetion').hide();
		}
	});

  //search
	$(document).on('input', '.search-box', function(){
		var searchText = $(this).val().toLowerCase().trim();
		var $sections = $(this).closest('.siteseo-suggetion').find('.section');

		$sections.each(function(){
			var sectionText = $(this).text().toLowerCase();
			var tagText = $(this).find('.tag').text().toLowerCase();
			
			$(this).toggle(
				sectionText.indexOf(searchText) > -1 || 
				tagText.indexOf(searchText) > -1
			);
		});
	});

	function append_suggestion_tag($field, text){
		let field = $field[0],
		currentValue = field.value,
		newValue = currentValue + " " + text;

		field.value = newValue;
		field.focus();
		$field.trigger('input');
	}

	// Refresh SEO analysis
	$(document).on('click', '#siteseo_refresh_seo_analysis', function(e){
		e.preventDefault();
		
		var button = $(this);
		var post_id = button.attr('data_id');
		var post_type = button.attr('data_post_type');
		
		var target_keywords;
		if(button.closest('.widget-content').length){
			target_keywords = button.closest('.widget-content').find('.siteseo_analysis_target_kw').val();
		}else{
			target_keywords = $('#siteseo_tags_hidden').val();
		}

		button.prop('disabled', true);
		button.text('Analyzing...');
		
		$.ajax({
			url: siteseoAdminAjax.url,
			type: 'POST',
			data: {
				action: 'siteseo_refresh_analysis',
				nonce: siteseoAdminAjax.nonce,
				post_id: post_id,
				post_type: post_type,
				target_keywords: target_keywords
			},
			success: function(response){
				if(response.success){
					
					var container;
					if(button.closest('.widget-content').length){
						container = button.closest('.widget-content').find('.siteseo-widget-seo-analysis');
					}else{
						container = $('#siteseo-metabox-content-analysis .siteseo-metabox-seo-analysis-tab');
					}

					container.html(response.data.html);

					if(!button.closest('.widget-content').length){
						let activeTab = $('#siteseo-metabox-content-analysis .siteseo-metabox-tab-label-active').data('tab');
						$('#siteseo-metabox-content-analysis .' + activeTab).show();
					}
				}else{
					alert('Analysis failed: ' + (response.data.message || 'Unknown error'));
				}
			},
			error: function(xhr, status, error){
				alert('Error performing analysis. Please try again.');
			},
			complete: function(){
				button.prop('disabled', false);
				button.text('Refresh analysis');
			}
		});
	});

	function initializeTabs(){
		$('#siteseo-metabox-content-analysis .siteseo-metabox-tab-label').off('click');
		
		$(document).on('click','#siteseo-metabox-content-analysis .siteseo-metabox-tab-label',function(){
			var tabId = $(this).data('tab');
			var $tabsContainer = $(this).closest('#siteseo-metabox-content-analysis');

			$tabsContainer.find('.siteseo-metabox-tab-label').removeClass('siteseo-metabox-tab-label-active');
			$(this).addClass('siteseo-metabox-tab-label-active');
			
			$tabsContainer.find('.siteseo-metabox-tab').hide();
			$tabsContainer.find('.' + tabId).show();
		});
	}
  
	initializeTabs();

	// Toggle Mobile and Desktop view of Google SERP
	$(document).on('click', '#siteseo-metabox-search-mobile', function(){
		$(this).hide();
		$(this).prev().show();
		$('.siteseo-search-preview-desktop').css('max-width', '414px');
	});
		
	$(document).on('click', '#siteseo-metabox-search-pc', function(){
		$(this).hide();
		$(this).next().show();
		$('.siteseo-search-preview-desktop').css('max-width', '');
	});
	
	// Tags
	const $input = $('#siteseo_analysis_target_kw_meta');
    const $hiddenInput = $('#siteseo_tags_hidden');
    let tags = [];

  const $realHiddenInput = $('<input>').attr({
    type: 'hidden',
    name: 'siteseo_analysis_target_kw',
    id: 'siteseo_real_tags_hidden'
  }).insertAfter($hiddenInput);

  $hiddenInput.remove();

  function createTag(text){
    if(!text || tags.includes(text)) return;

		  const $tag = $('<span>').addClass('siteseo-tag').text(text),
        $removeBtn = $('<span>').addClass('siteseo-remove-tag').text('×');

        $tag.append($removeBtn);
        $tag.insertBefore($input);
        tags.push(text);
        updateHiddenInput();
    }

    $(document).on('click', '.siteseo-remove-tag', function(e) {
  		e.preventDefault();
  		e.stopImmediatePropagation();
		
  		const tag = $(this).parent(),
  		tagText = tag.text().slice(0, -1);
		
		  tags = tags.filter(item => item !== tagText);
      tag.remove();
		
	    updateHiddenInput();
      $realHiddenInput[0].dispatchEvent(new Event('change', {bubbles: true}));
		
      return false;
	});
    function updateHiddenInput(){
        $realHiddenInput.val(tags.join(','));
	  }

	// Initialize with existing tags
	const existingTags = $input.data('existing-tags');
	if(existingTags){
    existingTags.split(',').forEach(tag => {
    if(tag.trim())createTag(tag.trim());
		});
	}

      $input.on('blur keypress', function(e){
        if(e.type === 'blur' || (e.type === 'keypress' && e.key === 'Enter')){
        const text = $(this).val().trim();
        if(text){
        	createTag(text);
        	$(this).val('');
        }
			e.preventDefault();
        }
    });

	$(document).on('click', '#siteseo-sidebar-wrapper .siteseo-sidebar-tabs', function(){
		$(this).toggleClass('siteseo-sidebar-tabs-opened');
		$(this).next().slideToggle('fast');
	});
	
	function resolve_dynamic_variables(content, type){

		let post_id = jQuery('.siteseo-metabox-tabs').attr('data_id');

		jQuery.ajax({
			url : siteseoAdminAjax.url,
			type : 'POST',
			data : {
				content : content,
				action : 'siteseo_resolve_variables',
				post_id : post_id,
				nonce: siteseoAdminAjax.nonce,
			}, success : function(res) {
				if(!res.success){
					return;
				}

				if(type == 'title'){
					update_title_placeholder(res.data);
					return;
				}
				
				update_desc_placeholder(res.data);
			}
		});
	}
});
