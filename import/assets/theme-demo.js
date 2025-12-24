(function($) {
    "use strict";
    var ThemeDemo = {};

    (function() {
        var $this;
        ThemeDemo = {
            init: function(e) {
                $this = ThemeDemo;
                $this.events(e);
				$this.initSearch();
				
            },
            events: function(e) {
                $(document)
                    .on('click', '.demo-import-open', function(e) {
                        $this.openImportDemo(e, this);
                    })
                   .on('click', '.demo-import-close', function(e) {
                        $this.closeImportDemo(e, this);
                    })
                    .on('click', '.demo-import-start', function(e) {
                        $this.startImportDemo(e, this);
                    })
                    .on('click', '.demo-item', function(e) {
                        $this.openPreviewDemo(e, this);
                    })
                    .on('click', '.prev-demo', function(e) {
                        $this.openPreviewPrevDemo(e, this);
                    })
                    .on('click', '.next-demo', function(e) {
                        $this.openPreviewNextDemo(e, this);
                    })
                    .on('click', '.preview-cancel a', function(e) {
                        $this.closePreviewDemo(e, this);
                    })
                    .on('change', '.import-data .checkbox', function() {
                    const $importStartBtn = $('.demo-import-start');
                    const $checkedVisible = $('.import-data form:not(.hidden) .checkbox:checked');
                
                    if ($checkedVisible.length === 0) {
                        $importStartBtn.addClass('disabled').prop('disabled', true);
                    } else {
                        $importStartBtn.removeClass('disabled').prop('disabled', false);
                    }
                });
            },
			   // Initialize search functionality
				initSearch: function() {
					$('#demo-search').on('input', function() {
						var searchTerm = $(this).val().toLowerCase();

						if (searchTerm.length > 0) {
							$('.demo-item').each(function() {
								var demoName = $(this).data('name').toLowerCase();
								if (demoName.includes(searchTerm)) {
									$(this).show();
								} else {
									$(this).hide();
								}
							});
						} else {
							$('.demo-item').show();
						}
					});
				},

            openImportDemo: function(e, object) {
                var $demo_id = $(object).data('id');
                $('body').addClass('import-theme-active');
                var data = {
                    action: 'som_html_import_data',
                    nonce: ThemeDemoConfig.nonce,
                    demo_id: $demo_id
                };
                $('.import-data .checkbox').trigger('change');

                $('.import-step').removeClass('import-step-active')
                    .first().addClass('import-step-active');
                $('.import-theme .msg-warning').remove();
                $('.import-start .import-output').html('').addClass('import-load');
                $('.import-process .import-progress-label').html('');
                $('.import-process .import-progress-indicator').attr('style', '--indicator: 0%;');
                $('.import-process .import-progress-sublabel').html('0%');

                $.post(ThemeDemoConfig.ajax_url, data)
                    .done(function(response) {
                        $('.import-start .import-output').removeClass('import-load');
                        
                        if (response.success) {
                            $('.import-start .import-output').html(response.data);
                        } else {
                            var message = response.data || ThemeDemoConfig.failed_message;
                            $('.import-start .import-output').html(`<div class="msg-warning">${message}</div>`);
                        }
                    })
                    .fail(function() {
                        $('.import-start .import-output').removeClass('import-load')
                            .html(`<div class="msg-warning">${ThemeDemoConfig.failed_message}</div>`);
                    });

                e.preventDefault();
            },

            closeImportDemo: function(e, object) {
                $('body').removeClass('import-theme-active');
                e.preventDefault();
            },

            startImportDemo: function(e, object) {
                $('.import-step').removeClass('import-step-active');
                $('.import-process').addClass('import-step-active');

                setTimeout(function() {
                    $this.importContent(e, object);
                }, 10);

                e.preventDefault();
            },

            openPreviewDemo: function(e, object) {
                if (!$(e.target).is('.demo-import-open, .demo-import-url')) {
                    $this.openPreview(e, object);
                    e.preventDefault();
                }
            },

            openPreviewPrevDemo: function(e, object) {
                var prev = $('.demo-item-open').prev('.demo-item-active');
                if (prev.length) {
                    $this.openPreview(e, prev);
                }
                e.preventDefault();
            },
            openPreviewNextDemo: function(e, object) {
                var next = $('.demo-item-open').next('.demo-item-active');
                if (next.length) {
                    $this.openPreview(e, next);
                }
                e.preventDefault();
            },

            closePreviewDemo: function(e, object) {
                $('.demo-item').removeClass('demo-item-open');
                $('body').removeClass('preview-active');
                $('.preview .preview-iframe').removeAttr('src');
                e.preventDefault();
            },

            importIndicator: function(e, object, $data) {
                var indicator = Math.round(100 / $data.steps * $data.index);
                $('.import-process .import-progress-indicator')
                    .attr('style', `--indicator: ${indicator}%;`);
                $('.import-process .import-progress-sublabel').html(`${indicator}%`);
            },

           importStep: function(e, object, $data) {
    if (!$('body').hasClass('import-theme-active')) {
        return;
    }

    // Done.
    if ($data.index >= $data.steps) {
        setTimeout(function() {
            $('.import-step').removeClass('import-step-active');
            $('.import-finish').addClass('import-step-active');
            $(document).trigger('DOMImportFinish');
        }, 200);
        return;
    }

    var currentAction = $($data.forms).eq($data.index).find('input[name="action"]').val();

    // Set progress label.
    $('.import-progress-label').html($($data.forms).eq($data.index).find('input[name="step_name"]').val());

    // Initialize retryCount if not set
    if (typeof $data.retryCount === 'undefined') {
        $data.retryCount = 0;
    }

    $.post({
        url: ThemeDemoConfig.ajax_url,
        type: 'POST',
        data: $($data.forms).eq($data.index).serialize(),
        timeout: 600000,  // 10 minutes timeout
    }).done(function(response) {
        if (response.success || 'elementor_recreate_kit' === currentAction) {
            $data.retryCount = 0;

            if (typeof response.status !== 'undefined' && response.status === 'newAJAX') {
                this.importStep(e, object, $data);
            } else {
                $data.index++;
                this.importIndicator(e, object, $data);
                this.importStep(e, object, $data);
            }
        } else if (response.data) {
            // Show warning message near progress
            if ($('.import-process').hasClass('import-step-active')) {
            $('.import-step').removeClass('import-step-active');
            $('.import-error').addClass('import-step-active');
            $('.import-error .error-message').after(`<div class="msg-warning">${response.data}</div>`);
        }
        } else {
            if ($('.import-process').hasClass('import-step-active')) {
            $('.import-step').removeClass('import-step-active');
            $('.import-error').addClass('import-step-active');
            $('.import-error .error-message').html(ThemeDemoConfig.failed_message);
        }
        }
    }.bind(this)).fail(function(xhr, textStatus, e) {
        // Retry up to 3 times before showing error
        if ($data.retryCount < 3) {
            $data.retryCount++;
            setTimeout(function() {
                this.importStep(e, object, $data);
            }.bind(this), 3000);  // retry after 3 seconds
            return;
        }

        if (currentAction === 'elementor_recreate_kit') {
            $data.index++;
            this.importIndicator(e, object, $data);
            this.importStep(e, object, $data);
        } else {
            if ($('.import-process').hasClass('import-step-active')) {
            $('.import-step').removeClass('import-step-active');
            $('.import-error').addClass('import-step-active');
            $('.import-error .error-message').html(ThemeDemoConfig.failed_message);
        }
        }
    }.bind(this));
},


            importContent: function(e, object) {
				var forms = $('.import-start form').filter(function() {
					return $(this).find('.checkbox').prop('checked');
				});

				var steps = forms.length;
				if (steps <= 0) return;

				// Reset error state when starting new import
				$('.import-error .error-message').html(ThemeDemoConfig.failed_message);

				$this.importStep(e, object, {
					forms: forms,
					steps: steps,
					index: 0
				});
			},

            openPreview: function(e, object) {
                var demo_id = $(object).data('id');
                var preview = $(object).data('preview');
                var name = $(object).data('name');

                if (preview === 'false') return;

                $(object).siblings().removeClass('demo-item-open');
                $(object).addClass('demo-item-open');
                $('.preview .demo-import-open').attr('data-id', demo_id);

                $('.preview').find('.prev-demo, .next-demo').removeClass('inactive');
                if (!$(object).prev('.demo-item-active').length) {
                    $('.preview .prev-demo').addClass('inactive');
                }
                if (!$(object).next('.demo-item-active').length) {
                    $('.preview .next-demo').addClass('inactive');
                }

                $('.preview .header-info').html('');
                if (name) {
                    $('.preview .header-info').prepend(`<div class="demo-name">${name}</div>`);
                }
                $('.preview .preview-actions').html(
				  $(object).find('.demo-action .demo-import-open').prop('outerHTML')
				);
                $('.preview .preview-iframe').attr('src', preview);
                $('body').addClass('preview-active');
            }
        };
    })();

    ThemeDemo.init();
})(jQuery);