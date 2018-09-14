(function ($) {
    $.qdiscount_plugin = {
        options: {},
        init: function (options) {
            this.options = options;
            if (Object.keys(this.options.skus).length > 1) {
                this.changeSkus();
                $(".skus input[type=radio]:checked").change();
                this.changeFeatures();
                $(".sku-feature").change();
            } else if (Object.keys(this.options.skus).length == 1) {
                var keys = Object.keys(this.options.skus);
                var sku_id = keys.pop();
                this.updateOldPrice(sku_id);
                $('.qdiscount-hint').hide();
                $('.qdiscount-item').hide();
                $('.qdiscount-item[data-sku-id=' + sku_id + '],.qdiscount-item[data-sku-id=0]').show();
                if ($('.qdiscount-item:visible').length > 0) {
                    $('.qdiscount-hint').show();
                }
            }
            this.changeDiscountVariant();
        },
        changeSkus: function () {
            var self = this;
            $(".skus input[type=radio]").change(function () {
                var sku_id = $(this).val();
                $('.qdiscount-hint').hide();
                $('.qdiscount-item').hide();
                $('.qdiscount-item[data-sku-id=' + sku_id + '],.qdiscount-item[data-sku-id=0]').show();
                self.updateOldPrice(sku_id);
                if ($('.qdiscount-item:visible').length > 0) {
                    $('.qdiscount-hint').show();
                }
            });
        },
        changeFeatures: function () {
            var self = this;
            $(".sku-feature").change(function () {
                var key = "";
                $(".sku-feature").each(function () {
                    key += $(this).data('feature-id') + ':' + $(this).val() + ';';
                });
                var sku = self.options.features[key];
                if (sku) {
                    $('.qdiscount-hint').hide();
                    $('.qdiscount-item').hide();
                    $('.qdiscount-item[data-sku-id=' + sku.id + '],.qdiscount-item[data-sku-id=0]').show();
                    self.updateOldPrice(sku.id);
                    if ($('.qdiscount-item:visible').length > 0) {
                        $('.qdiscount-hint').show();
                    }
                }
            });
        },
        updateOldPrice: function (sku_id) {
            var self = this;
            var no_html = !this.options.html_currency;
            var sku = this.options.skus[sku_id];
            $('.qdiscount-item[data-sku-id=' + sku_id + '],.qdiscount-item[data-sku-id=0]').closest('tr').data('old-price', sku.price);
            $('.qdiscount-item[data-sku-id=' + sku_id + '],.qdiscount-item[data-sku-id=0]').closest('tr').data('compare-price', sku.compare_price);
            $('.qdiscount-item[data-sku-id=' + sku_id + '],.qdiscount-item[data-sku-id=0]').find('.old-price').html(self.currencyFormat(sku.price, no_html));
            $('.qdiscount-item[data-sku-id=' + sku_id + '],.qdiscount-item[data-sku-id=0]').find('.total-old-price').each(function () {
                var count = $(this).closest('tr').data('count');
                $(this).html(self.currencyFormat(count * sku.price, no_html));
            });
        },
        changeDiscountVariant: function () {
            var self = this;
            var no_html = !this.options.html_currency;
            var $form = $(this.options.form_selector);
            var $price = $(this.options.product_price_selector);
            var $compare_price = $(this.options.product_compare_price_selector);

            if (!$form.length) {
                console.log('Указан неверный селектор "' + this.options.form_selector + '"');
                return false;
            }
            if (!$price.length) {
                console.log('Указан неверный селектор "' + this.options.product_price_selector + '"');
                return false;
            }
            /*
             if (!$compare_price.length) {
             console.log('Указан неверный селектор "' + this.options.product_compare_price_selector + '"');
             return false;
             }*/

            $('.qdiscount-variant').click(function () {
                if (!$(this).data('checked')) {
                    $('.qdiscount-variant').data('checked', false);
                    $(this).attr('checked', true);
                    $(this).data('checked', true);
                } else {
                    $(this).removeAttr('checked');
                    $(this).data('checked', false);
                }

                if (!$form.find('input[name=quantity]').length) {
                    $form.append('<input type="hidden" name="quantity" value="1" />');
                }

                var tr = $(this).closest('tr');
                if ($(this).data('checked')) {
                    $form.find('input[name=quantity]').val($(this).val());
                    $price.html(self.currencyFormat(tr.data('new-price'), no_html));
                    if ($compare_price.length) {
                        $compare_price.html(self.currencyFormat(tr.data('old-price'), no_html));
                    }
                } else {
                    $form.find('input[name=quantity]').val(1);
                    $price.html(self.currencyFormat(tr.data('old-price'), no_html));
                    if (tr.data('compare-price') && $compare_price.length) {
                        $compare_price.html(self.currencyFormat(tr.data('compare-price'), no_html));
                    }
                }
            });
        },
        currencyFormat: function (number, no_html) {
            var i, j, kw, kd, km;
            var decimals = this.options.currency.frac_digits;
            var dec_point = this.options.currency.decimal_point;
            var thousands_sep = this.options.currency.thousands_sep;

            // input sanitation & defaults
            if (isNaN(decimals = Math.abs(decimals))) {
                decimals = 2;
            }
            if (dec_point == undefined) {
                dec_point = ",";
            }
            if (thousands_sep == undefined) {
                thousands_sep = ".";
            }

            i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

            if ((j = i.length) > 3) {
                j = j % 3;
            } else {
                j = 0;
            }

            km = (j ? i.substr(0, j) + thousands_sep : "");
            kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
            //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
            kd = (decimals && (number - i) ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


            var number = km + kw + kd;
            var s = no_html ? this.options.currency.sign : this.options.currency.sign_html;
            if (!this.options.currency.sign_position) {
                return s + this.options.currency.sign_delim + number;
            } else {
                return number + this.options.currency.sign_delim + s;
            }
        }
    };
})(jQuery);