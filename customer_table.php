<html>

<head>
    <!-- <link rel="stylesheet" type="text/css" href="/tabulator/dist/css/tabulator.min.css"> -->
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/tabulator-tables@4.9.3/dist/css/tabulator.min.css">
</head>

<body style="margin: 15px auto; width: 100%;position:relative;">
    <div id="example-table"></div>

    <!-- <script type="text/javascript" src="/tabulator/dist/js/tabulator.min.js"></script> -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/tabulator-tables@4.9.3/dist/js/tabulator.min.js"></script>


    <!--<script type="text/javascript" src="/tabulator/moment.js"></script> -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script>
        function changetimestamp2date(value, data, type, cell) {
            return new Date(value * 1000);
        }
        //Build Tabulator
        var table = new Tabulator("#example-table", {
            height: false,
            width: 610,
            layout: "fitColumns",
            selectable: 1,
            pagination: "local", //enable local pagination.
            paginationSize: 5, // this option can take any positive integer value (default = 10)
            clipboard: true,
            clipboardCopyStyled: false,
            clipboardCopyHeader: false, //disable header titles in copied data
            //paginationSize:20,
            placeholder: "No Data Set",
            columns: [{
                    title: "ID",
                    field: "id",
                    sorter: "string",
                    width: 150
                },
                {
                    title: "Name",
                    field: "name",
                    sorter: "string",
                    width: 120
                },
                {
                    title: "Created",
                    field: "created",
                    sorter: "date",
                    mutator: changetimestamp2date,
                    align: "center",
                    width: 190
                },
                {
                    title: "Brand",
                    field: "brand",
                    sorter: "string",
                    width: 40
                },
                {
                    title: "Country",
                    field: "country",
                    sorter: "string",
                    width: 40
                },
                {
                    title: "Last 4 digit",
                    field: "last4",
                    width: 70
                }
            ],

            ajaxResponse: function(url, params, response) {
                return response.reverse();
            },
            rowSelectionChanged: function(data, rows) {
                if (data.length) console.log(data[0].id);
            },
        });
        var ajaxConfig = {
            method: "post", //set request type to Position
            headers: {
                "Content-type": 'application/json; charset=utf-8', //set specific content type
            },
        };

        table.setData("customers.json", {}, ajaxConfig); //make ajax request with advanced config options
    </script>
</body>

</html>

<?php
