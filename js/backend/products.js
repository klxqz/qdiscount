(function ($) {
    $(document).on('click', '.delete-qdiscount', function () {
        var products = $.product_list.getSelectedProducts(true);
        if (!products.count) {
            alert($_('Please select at least one product'));
            return false;
        }

        var loading = $('<i class="icon16 loading" style="margin-left:5px;"></i>');
        $(this).append(loading);
        $.ajax({
            url: '?plugin=qdiscount&action=delete',
            type: 'POST',
            dataType: 'json',
            data: products.serialized,
            success: function (data, textStatus) {
                loading.remove();
                if (data.status == 'ok') {
                    window.location.reload();
                } else {
                    alert(data.errors.join(', '));
                }
            }, error: function (jqXHR, textStatus, errorThrown) {
                loading.remove();
                alert(jqXHR.responseText);
            }
        });
        return false;
    });
    $(document).on('click', '.add-qdiscount', function () {
        var products = $.product_list.getSelectedProducts(true);
        if (!products.count) {
            alert($_('Please select at least one product'));
            return false;
        }

        if (!$('#qdiscount-dialog').length) {
            $('<div id="qdiscount-dialog"></div>').hide().appendTo('body');
        }

        if ($('#qdiscount-dialog .dialog-content-indent').length) {
            $('#qdiscount-dialog .dialog-content-indent').html('<i class="icon16 loading"></i>');
        } else {
            $('#qdiscount-dialog').html('<i class="icon16 loading"></i>');
        }
        var dialog = $('#qdiscount-dialog').waDialog({
            disableButtonsOnSubmit: false,
            buttons: $('<input type="submit" class="button green" value="Закрыть">').click(function () {
                dialog.trigger('close');
            }),
            onSubmit: function (d) {
                return false;
            }
        });

        $.ajax({
            type: 'GET',
            url: '?plugin=qdiscount&action=dialog',
            dataType: 'html',
            success: function (html) {
                if ($(html).find('.dialog-window').length) {
                    $('#qdiscount-dialog').html(html);
                } else {
                    $('#qdiscount-dialog .dialog-content-indent').html(html);
                }
            }
        });

        return false;
    });
})(jQuery);