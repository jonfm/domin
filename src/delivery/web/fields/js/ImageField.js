$(function () {
    var updateSize = function (parent, data) {
        var img = parent.find('.image-placeholder');
        var width = parent.find('.image-width');
        var height = parent.find('.image-height');

        width.val(Math.round(data.width * factor(img) * zoomLevel(img)));
        height.val(Math.round(data.height * factor(img) * zoomLevel(img)));
    };

    var zoomLevel = function (img) {
        var container = img.cropper('getContainerData');
        var image = img.cropper('getImageData');
        if ((container.height / container.width) > (image.height / image.width)) {
            return image.width / container.width;
        } else {
            return image.height / container.height;
        }
    };

    var factor = function (img) {
        return parseFloat(img.data('factor'));
    };
    
    var startCropper = function (parent, data_uri, fileName, fileType) {
        var img = parent.find('.image-placeholder');
        var width = parent.find('.image-width');
        var height = parent.find('.image-height');

        parent.find('.image-container').show();

        img.data('factor', 1.0);

        var set = function (prop) {
            return function (e) {
                var data = img.cropper('getData');
                data[prop] = parseInt($(e.target).val()) / zoomLevel(img) / factor(img);
                img.cropper('setData', data);
            };
        };

        width.change(set('width'));
        height.change(set('height'));

        var options = $cropperOptions$;
        options.crop = function (data) {
            updateSize(parent, data);
        };

        img.cropper(options);
        img.cropper('replace', data_uri);

        var resize = function (callback) {
            var cropped = document.createElement('img');
            $(cropped).load(function () {
                var canvas = document.createElement('canvas');
                canvas.width = parseInt(width.val());
                canvas.height = parseInt(height.val());
                canvas.getContext('2d').drawImage(cropped, 0, 0, canvas.width, canvas.height);

                parent.find('.image-data').val(fileName + ';;' + canvas.toDataURL(fileType));
                $('body').append(cropped);

                console.log('Resized');
                callback();
            });
            console.log('Resize');
            $(cropped).attr('src', img.cropper('getCroppedCanvas').toDataURL(fileType));
        };

        var resized = false;
        img.closest('form').submit(function (e) {
            if (!resized) {
                resize(function () {
                    console.log(e.target);
                    resized = true;
                    $(e.target).submit();
                });
                return false;
            }
        });
    };

    $('.image-controls .btn').each(function () {
        $(this).init.prototype.setOption = function (option, value) {
            $(this).closest('.image-cropper').find('.image-placeholder').cropper(option, value);
        };

        $(this).init.prototype.changeFactor = function (byFactor) {
            var parent = $(this).closest('.image-cropper');
            var img = parent.find('.image-placeholder');

            img.data('factor', factor(img) * byFactor);
            updateSize(parent, img.cropper('getData'));
        };
    });

    $('.image-cropper .image-input').change(function (e) {

        var target = $(e.target);
        var parent = target.closest('.image-cropper');

        var file = target.prop('files')[0];
        var data_uri = URL.createObjectURL(file);

        startCropper(parent, data_uri, file.name, file.type);
    });

    $('.image-cropper .take-photo').click(function (e) {
        var target = $(e.target);
        var parent = target.closest('.image-cropper');
        var container = parent.find('.photo-preview-container');
        var preview = container.find('.photo-preview');

        parent.find('.image-container').hide();

        var id = String.fromCharCode(65 + Math.floor(Math.random() * 26)) + Date.now();

        Webcam.reset();

        var options = $webcamjsOptions$;
        Webcam.set(options);

        var format = 'jpeg';
        if ('image_format' in options) {
            format = options.image_format;
        }

        preview.attr('id', id);
        Webcam.attach('#' + id);

        container.show();

        preview.unbind('click');
        preview.click(function () {
            Webcam.snap(function (data_uri) {
                container.hide();
                Webcam.reset();

                startCropper(parent, data_uri, 'snapshort.' + format, 'image/' + format)
            });
        });
    });
});