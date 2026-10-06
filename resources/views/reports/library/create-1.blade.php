<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Report</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        body {
            background: #f4f6f9;

            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .page-header {
            background:
                linear-gradient(
                    135deg,
                    #172033,
                    #273b63
                );

            color: white;

            padding: 30px 0;

            margin-bottom: 25px;
        }

        .page-title {
            font-size: 25px;
            font-weight: 700;
        }

        .page-subtitle {
            color: rgba(255,255,255,.7);
            font-size: 14px;
        }

        .form-card {
            border: 0;

            border-radius: 14px;

            box-shadow:
                0 3px 15px rgba(0,0,0,.06);
        }

        .form-label {
            font-size: 12px;

            font-weight: 700;

            color: #495057;

            text-transform: uppercase;

            letter-spacing: .3px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;

            border-color: #dee2e6;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;

            box-shadow:
                0 0 0 .2rem
                rgba(102,126,234,.12);
        }

        textarea.form-control {
            min-height: 110px;
        }

        .section-title {
            font-size: 14px;

            font-weight: 700;

            color: #273b63;

            text-transform: uppercase;

            letter-spacing: .4px;
        }

    </style>

</head>

<body>


<div class="page-header">

    <div class="container-fluid px-4">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <div class="page-title">

                    <i class="bi bi-plus-circle me-2"></i>

                    Add Report

                </div>

                <div class="page-subtitle">

                    Add a report and document its technical details

                </div>

            </div>


            <a
                href="{{ route('reports.library') }}"
                class="btn btn-light"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Report Library

            </a>

        </div>

    </div>

</div>


<div class="container-fluid px-4 pb-5">


    @if($errors->any())

        <div class="alert alert-danger">

            <strong>
                Please correct the following:
            </strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <form
        method="POST"
        action="{{ route('reports.library.store') }}"
    >

        @csrf


        <!-- BASIC -->

        <div class="card form-card mb-4">

            <div class="card-body p-4">

                <div class="section-title mb-4">

                    <i class="bi bi-info-circle me-1"></i>

                    Basic Information

                </div>


                <div class="row g-3">


                    <div class="col-md-3">

                        <label class="form-label">
                            Report Code *
                        </label>

                        <input
                            type="text"
                            name="report_code"
                            class="form-control"
                            value="{{ old('report_code') }}"
                            placeholder="RPT-ASV-001"
                            required
                        >

                    </div>


                    <div class="col-md-5">

                        <label class="form-label">
                            Report Name *
                        </label>

                        <input
                            type="text"
                            name="report_name"
                            class="form-control"
                            value="{{ old('report_name') }}"
                            placeholder="ASV Item Issue Report"
                            required
                        >

                    </div>


                    <div class="col-md-2">

                        <label class="form-label">
                            Module
                        </label>

                        <input
                            type="text"
                            name="module"
                            class="form-control"
                            value="{{ old('module') }}"
                            placeholder="Inventory"
                        >

                    </div>


                    <div class="col-md-2">

                        <label class="form-label">
                            Sub Module
                        </label>

                        <input
                            type="text"
                            name="sub_module"
                            class="form-control"
                            value="{{ old('sub_module') }}"
                            placeholder="Item Issue"
                        >

                    </div>


                    <div class="col-12">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            placeholder="Describe what this report shows..."
                        >{{ old('description') }}</textarea>

                    </div>


                    <div class="col-12">

                        <label class="form-label">
                            Purpose
                        </label>

                        <textarea
                            name="purpose"
                            class="form-control"
                            placeholder="Why is this report used?"
                        >{{ old('purpose') }}</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- TECHNICAL -->

        <div class="card form-card mb-4">

            <div class="card-body p-4">

                <div class="section-title mb-4">

                    <i class="bi bi-database me-1"></i>

                    Technical Information

                </div>


                <div class="row g-3">


                    <div class="col-md-4">

                        <label class="form-label">
                            Main Table
                        </label>

                        <input
                            type="text"
                            name="main_table"
                            class="form-control"
                            value="{{ old('main_table') }}"
                            placeholder="itemtran_body"
                        >

                    </div>


                    <div class="col-md-8">

                        <label class="form-label">
                            Data Sources
                        </label>

                        <input
                            type="text"
                            name="data_source"
                            class="form-control"
                            value="{{ old('data_source') }}"
                            placeholder="itemtran_body, item_mast, prod_mast"
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Filters
                        </label>

                        <textarea
                            name="filters"
                            class="form-control"
                            placeholder="From Month&#10;To Month&#10;Division&#10;Item Code / Name"
                        >{{ old('filters') }}</textarea>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Grouping & Calculation
                        </label>

                        <textarea
                            name="grouping_details"
                            class="form-control"
                            placeholder="Group by item, month and division&#10;SUM(QTYISSUED)"
                        >{{ old('grouping_details') }}</textarea>

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Output Types
                        </label>

                        <input
                            type="text"
                            name="output_types"
                            class="form-control"
                            value="{{ old('output_types') }}"
                            placeholder="Web, CSV, Print"
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Report Route
                        </label>

                        <input
                            type="text"
                            name="route"
                            class="form-control"
                            value="{{ old('route') }}"
                            placeholder="/reports/asv"
                        >

                    </div>


                    <div class="col-md-4">

                        <label class="form-label">
                            Access Role
                        </label>

                        <input
                            type="text"
                            name="access_role"
                            class="form-control"
                            value="{{ old('access_role') }}"
                            placeholder="Inventory / Management"
                        >

                    </div>


                    <div class="col-md-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option
                                value="active"
                                {{ old('status', 'active') === 'active' ? 'selected' : '' }}
                            >
                                Active
                            </option>

                            <option
                                value="inactive"
                                {{ old('status') === 'inactive' ? 'selected' : '' }}
                            >
                                Inactive
                            </option>

                        </select>

                    </div>


                    <div class="col-12">

                        <label class="form-label">
                            Notes
                        </label>

                        <textarea
                            name="notes"
                            class="form-control"
                            placeholder="Any technical notes, assumptions or special instructions..."
                        >{{ old('notes') }}</textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- ACTION -->

        <div class="d-flex justify-content-end gap-2">

            <a
                href="{{ route('reports.library') }}"
                class="btn btn-outline-secondary px-4"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn btn-primary px-4"
            >

                <i class="bi bi-check-lg me-1"></i>

                Save Report

            </button>

        </div>

    </form>

</div>


</body>

</html>