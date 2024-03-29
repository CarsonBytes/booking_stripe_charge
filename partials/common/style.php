<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/tabulator-tables@5/dist/css/tabulator.min.css">

<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">

<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap-datepicker@1.9.0/dist/css/bootstrap-datepicker.min.css">

<style type="text/css" media="screen">
    .header {
        display: flex;
        justify-content: space-between;
    }

    input.invalid {
        border: 2px solid red;
    }

    .validation.failed {
        display: block;
    }

    .validation.failed:after {
        color: red;
        content: 'Validation failed';
    }

    .validation.passed {
        display: none;
    }

    .validation.passed:after {
        color: green;
        content: 'Validation passed';
    }

    label[role="button"] {
        user-select: none;
    }
</style>
