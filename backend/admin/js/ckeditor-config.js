document.addEventListener('DOMContentLoaded', function () {
    const editorSelector = 'textarea.editor-content';
    const editors = Array.from(document.querySelectorAll(editorSelector));
    if (!editors.length) {
        return;
    }

    function loadScript(url) {
        return new Promise(function (resolve, reject) {
            const script = document.createElement('script');
            script.src = url;
            script.onload = resolve;
            script.onerror = function () {
                reject(new Error('Không thể tải CKEditor từ ' + url));
            };
            document.head.appendChild(script);
        });
    }

    function getCsrfToken() {
        const input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    loadScript('https://cdn.ckeditor.com/ckeditor5/39.0.0/classic/ckeditor.js')
        .then(function () {
            const csrfToken = getCsrfToken();
            const baseConfig = {
                toolbar: [
                    'heading', '|',
                    'bold', 'italic', 'link', '|',
                    'bulletedList', 'numberedList', 'blockQuote', '|',
                    'insertTable', 'imageUpload', '|',
                    'codeBlock', 'alignment', '|',
                    'undo', 'redo'
                ],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2' },
                        { model: 'heading3', view: 'h3', title: 'Heading 3' }
                    ]
                },
                image: {
                    toolbar: ['imageTextAlternative', 'imageStyle:full', 'imageStyle:side']
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                },
                simpleUpload: {
                    uploadUrl: 'upload_image.php',
                    headers: {
                        'X-CSRF-Token': csrfToken
                    }
                }
            };

            editors.forEach(function (textarea) {
                ClassicEditor.create(textarea, baseConfig)
                    .catch(function (error) {
                        console.warn('CKEditor khởi tạo gặp lỗi, thử cấu hình dự phòng:', error);
                        const fallbackConfig = JSON.parse(JSON.stringify(baseConfig));
                        const unsupportedItems = ['alignment', 'codeBlock'];
                        fallbackConfig.toolbar = fallbackConfig.toolbar.filter(function (item) {
                            return unsupportedItems.indexOf(item) === -1;
                        });

                        ClassicEditor.create(textarea, fallbackConfig)
                            .catch(function (fallbackError) {
                                console.error('CKEditor khởi tạo bản fallback thất bại:', fallbackError);
                            });
                    });
            });
        })
        .catch(function (error) {
            console.error('Không thể tải CKEditor:', error);
        });
});
