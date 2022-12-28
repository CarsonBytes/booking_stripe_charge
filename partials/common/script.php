<script type="text/javascript" src="../../lib/payment.js"></script>
<script>
    var J = Payment.J,
        //numeric = document.querySelector('[data-numeric]'),
        number = document.querySelector('.cc-number'),
        exp = document.querySelector('.cc-exp'),
        cvc = document.querySelector('.cc-cvc'),
        validation = document.querySelector('.validation');

    //Payment.restrictNumeric(numeric);
    Payment.formatCardNumber(number);
    Payment.formatCardExpiry(exp);
    Payment.formatCardCVC(cvc);

    document.querySelector('#new_customer').onclick = function(e) {
        J.toggleClass(document.querySelectorAll('input'), 'invalid');
        J.removeClass(validation, 'passed failed');

        var cardType = Payment.fns.cardType(J.val(number));

        J.toggleClass(number, 'invalid', !Payment.fns.validateCardNumber(J.val(number)));
        J.toggleClass(exp, 'invalid', !Payment.fns.validateCardExpiry(Payment.cardExpiryVal(exp)));

        J.toggleClass(cvc, 'invalid', !Payment.fns.validateCardCVC(J.val(cvc), cardType));

        if (document.querySelectorAll('.invalid').length) {
            J.addClass(validation, 'failed');
            return false;
        } else {
            J.addClass(validation, 'passed');
        }
    }
</script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery@3.6.2/dist/jquery.min.js"></script>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/js/tabulator.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/luxon/build/global/luxon.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB" crossorigin="anonymous"></script>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>

<script>
    function changetimestamp2date(value, data, type, cell) {
        return new Date(value * 1000);
    }

    //Build Tabulator
    var table = new Tabulator("#example-table", {
        height: false,
        layout: "fitColumns",
        selectable: 1,
        pagination: "local", //enable local pagination.
        paginationSize: 5, // this option can take any positive integer value (default = 10)
        clipboard: true,
        clipboardCopyStyled: false,
        placeholder: "No Data Set",
        data: 'customers.json',
        columns: [
            //{title:"ID@Wasaike", field:"wasaike_customer_id", sorter:"string", width:150},
            //{title:"ID@Mandy", field:"mandy_customer_id", sorter:"string", width:150},
            {
                title: "Live?",
                field: "is_live",
                formatter: 'tickCross',
                width: 30
            },
            {
                title: "Charger",
                field: "charger",
                sorter: "string",
                width: 30
            },
            {
                title: "Name",
                field: "name",
                headerFilter: "input",
                sorter: "string",
                minWidth: 100
            },
            {
                title: "Created",
                field: "created",
                formatter: function(cell, formatterParams, onRendered) {
                    try {
                        let dt = luxon.DateTime.fromMillis(cell.getValue() * 1000);
                        return dt.setLocale("zh").toFormat(formatterParams.outputFormat);
                    } catch (error) {
                        return formatterParams.invalidPlaceholder;
                    }
                },
                //mutator: changetimestamp2date,
                formatterParams: {
                    outputFormat: "yyyy年MM月dd日 hh:mma EEEE",
                    invalidPlaceholder: "(invalid date)",
                    timezone: "Asia/Hong_Kong",
                },
                align: "center",
                width: 250
            },
            {
                title: "Amount",
                field: "amount",
                formatter: "money",
                formatterParams: {
                    symbol: "円",
                    symbolAfter: true,
                    precision: false
                },
                hozAlign: "right",
                width: 80
            },
            {
                title: "Last 4 digit",
                field: "last4",
                width: 50
            },
            {
                title: "Brand",
                field: "brand",
                sorter: "string",
                width: 80
            },
            {
                title: "Country",
                field: "country",
                sorter: "string",
                width: 40
            }
        ],
        ajaxResponse: function(url, params, response) {
            return response.reverse();
        }
    });
    table.on("rowSelected", function(row) {
        $('form#charge input[name=mandy_customer_id]').val(row._row.data.mandy_customer_id);
        $('form#charge input[name=wasaike_customer_id]').val(row._row.data.wasaike_customer_id);
        $('form#charge input[name=customer_name]').val(row._row.data.name);
        $('form#charge input[name=last4]').val(row._row.data.last4);
        $('form#charge input[name=amount]').val(row._row.data.amount);
        $('#flexSwitchCheckChecked').prop('checked', row._row.data.is_live == 1 ? true : false).trigger('change');
        row_selected = true;
    });
    table.on("rowDeselected", function(row) {
        row_selected = false;
    });
    /* var ajaxConfig = {
        method: "post", //set request type to Position
        headers: {
            "Content-type": 'application/json; charset=utf-8', //set specific content type
        },
    }; */

    //table.setData("customers.json", {}, ajaxConfig); //make ajax request with advanced config options
</script>

<script>
    var row_selected = false;
    jQuery(function($) {
        $('body').on('change', '#flexSwitchCheckChecked', function(e) {
            if ($('#flexSwitchCheckChecked').is(':checked')) {
                $('[name="isTesting"]').val(0);
            } else {
                $('[name="isTesting"]').val(1);
            }
        }).on('click', '.dropdown-menu .visa', function(e) {
            e.preventDefault();
            var year = new Date().getFullYear() + 10;
            $('#flexSwitchCheckChecked').prop('checked', false).trigger('change');
            $(this).parents('form').find('[name="cc-number"]').val('4242424242424242');
            $(this).parents('form').find('[name="cc-exp"]').val('12/' + year.toString().substr(-2));
            $(this).parents('form').find('[name="cc-cvc"]').val('123');
        }).on('click', '.dropdown-menu .visa-debit', function(e) {
            e.preventDefault();
            var year = new Date().getFullYear() + 10;
            $('#flexSwitchCheckChecked').prop('checked', false).trigger('change');
            $(this).parents('form').find('[name="cc-number"]').val('4000056655665556');
            $(this).parents('form').find('[name="cc-exp"]').val('12/' + year.toString().substr(-2));
            $(this).parents('form').find('[name="cc-cvc"]').val('123');
        }).on('click', 'form#charge [type="submit"]', function(e) {
            $('.messages').html('');
            if (($('form#charge').find('[name="amount"]').val() % 2 != 0 && $('form#charge').find('#charge_percent-50').is(':checked')) || $('form#add_card').find('[name="amount"]') <= 0) {
                $('.html_template .alert .text').text('Please ensure the amount is an even positive value.');
                $('.html_template .alert').clone().appendTo('.messages');
                return false;
            }

            if (!row_selected) {
                $('.html_template .alert .text').text('Please select a row on the table.');
                $('.html_template .alert').clone().appendTo('.messages');
                return false;
            }
        })/* .on('click', 'form#add_card [type="submit"]', function(e) {
            $('.messages').html('');
            if ($('form#add_card').find('[name="amount"]').val() % 2 != 0 || $('form#add_card').find('[name="amount"]') <= 0) {
                $('.html_template .alert .text').text('Please ensure the amount is an even positive value.');
                $('.html_template .alert').clone().appendTo('.messages');
                return false;
            }
        }) */
    })
</script>
