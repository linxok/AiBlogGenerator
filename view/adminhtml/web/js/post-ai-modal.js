define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    return function (config, element) {
        var $root = $(element);
        var $modal = $('#mycompany-ai-modal');
        var modalInstance = modal({
            title: $t('Generate with AI'),
            modalClass: 'mycompany-ai-blog-generator-modal',
            buttons: [{
                text: $t('Generate Preview'),
                class: 'action-primary',
                click: function () {
                    generatePreview();
                }
            }, {
                text: $t('Insert to Editor'),
                class: 'action-secondary',
                click: function () {
                    insertToEditor();
                }
            }, {
                text: $t('Save Post'),
                class: 'action-primary',
                click: function () {
                    savePost();
                }
            }]
        }, $modal);

        function selectedStoreViews() {
            return $('#mycompany-ai-store-views').val() || [];
        }

        function collectPayload(singleStoreId) {
            var storeViews = selectedStoreViews();
            return {
                topic: $('#mycompany-ai-topic').val(),
                keywords: $('#mycompany-ai-keywords').val(),
                tone: $('#mycompany-ai-tone').val(),
                word_count: $('#mycompany-ai-word-count').val(),
                store_id: singleStoreId || (storeViews[0] || 0),
                store_ids: storeViews,
                category_id: $('#mycompany-ai-category').val(),
                product_id: $('#mycompany-ai-product-id').val(),
                post_id: $('[name="post_id"]').val() || $('[name="id"]').val() || '',
                form_key: window.FORM_KEY,
                auto_publish: config.autoPublish ? 1 : 0
            };
        }

        function renderPreview(data) {
            $('#mycompany-ai-preview').html(data.content_html || '');
            $('#mycompany-ai-generated-json').val(JSON.stringify(data));
        }

        function generatePreview() {
            var stores = selectedStoreViews();
            var payload = collectPayload(stores[0] || 0);
            $.post(config.previewUrl, payload).done(function (response) {
                if (response.success) {
                    renderPreview(response.data || {});
                } else {
                    $('#mycompany-ai-preview').html('<div class="message message-error">' + (response.message || $t('Generation failed.')) + '</div>');
                }
            });
        }

        function insertToEditor() {
            var generated = $('#mycompany-ai-generated-json').val();
            if (!generated) {
                return;
            }

            generated = JSON.parse(generated);
            $('#post_name').val(generated.title || '');
            $('#post_short_description').val(generated.short_description || '');
            $('#post_url_key').val(generated.url_key || '');
            if ($('#post_meta_title').length) {
                $('#post_meta_title').val(generated.meta_title || '');
            }
            if ($('#post_meta_description').length) {
                $('#post_meta_description').val(generated.meta_description || '');
            }
            if ($('#post_meta_keywords').length && Array.isArray(generated.tags)) {
                $('#post_meta_keywords').val(generated.tags.join(','));
            }
            if (window.tinyMCE && tinyMCE.get('post_post_content')) {
                tinyMCE.get('post_post_content').setContent(generated.content_html || '');
            } else {
                $('#post_post_content').val(generated.content_html || '');
            }
        }

        function savePost() {
            var generated = $('#mycompany-ai-generated-json').val();
            var stores = selectedStoreViews();
            if (!generated) {
                generatePreview();
                return;
            }

            $.post(config.saveUrl, $.extend({}, collectPayload(stores[0] || 0), {
                generated_json: generated
            })).done(function (response) {
                if (response.success && response.edit_url) {
                    window.location.href = response.edit_url;
                }
            });
        }

        function bindProductSearch() {
            $('#mycompany-ai-product-search').on('keyup', function () {
                var value = $(this).val();
                if (value.length < 2) {
                    return;
                }
                $.get(config.productSearchUrl, {q: value}).done(function (response) {
                    var html = '';
                    if (response.success && response.items) {
                        $.each(response.items, function (index, item) {
                            html += '<div><a href="#" class="mycompany-ai-product-option" data-id="' + item.id + '">' + item.label + '</a></div>';
                        });
                    }
                    $('#mycompany-ai-product-results').html(html);
                });
            });

            $(document).on('click', '.mycompany-ai-product-option', function (event) {
                event.preventDefault();
                $('#mycompany-ai-product-id').val($(this).data('id'));
                $('#mycompany-ai-product-search').val($(this).text());
                $('#mycompany-ai-product-results').empty();
            });
        }

        function openModal() {
            $modal.modal('openModal');
        }

        $('#mycompany-ai-open-inline').on('click', openModal);
        window.MyCompanyAiBlogGeneratorOpen = openModal;
        bindProductSearch();
        return modalInstance;
    };
});
