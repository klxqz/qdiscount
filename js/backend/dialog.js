(function ($) {
    $.dialog_qdiscount = {
        init: function () {
            this.initButtons();
        },
        initButtons: function () {
            $('#qdiscount-form').submit(function () {
                var products = $.product_list.getSelectedProducts(true);
                if (!products.count) {
                    alert($_('Please select at least one product'));
                    return false;
                }

                var form = $(this);
                var loading = $('<i class="icon16 loading"></i>');
                form.find('input[type=submit]').after(loading);
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    dataType: 'json',
                    data: form.serializeArray().concat(products.serialized),
                    success: function (data, textStatus) {
                        loading.remove();
                        if (data.status == 'ok') {
                            form.closest('.dialog').trigger('close');
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

            $('#qdiscount-form .cancel').click(function () {
                $(this).closest('.dialog').trigger('close');
                return false;
            });
        }
    };
})(jQuery);