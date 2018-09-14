(function ($) {
    $.product_qdiscount = {
        init: function (options) {
            this.options = options;
            this.initButtons();
        },
        initButtons: function () {
            var that = this;
            $('.add-row').click(function () {
                $('#qdiscount-tmpl').tmpl(that.options).appendTo('#qdiscount-table tbody');
                return false;
            });

            if (!$('#qdiscount-table tbody tr').length) {
                $('.add-row').click();
            }

            $('#qdiscount-table').on('click', '.delete-row', function () {
                $(this).closest('tr').remove();
                return false;
            });
            $('#qdiscount-table').on('change', 'select[name="qdiscount_type[]"]', function () {
                if ($(this).val() != 'percent') {
                    $(this).closest('tr').find('.qdiscount-currency').show();
                    $(this).closest('tr').find('.qdiscount-percent').hide();
                } else {
                    $(this).closest('tr').find('.qdiscount-currency').hide();
                    $(this).closest('tr').find('.qdiscount-percent').show();
                }
            });
        }
    };
})(jQuery);