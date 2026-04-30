@extends('layouts.app')

@section('content')
<style>
    .invoice-header-print {
        display: none;
    }

    @media print {
        .no-print, .btn, #add_item_btn, .remove-row, .card-header, .card-footer, .navbar, .sidebar, footer {
            display: none !important;
        }

        body {
            background-color: white !important;
            margin: 0 !important;
            padding: 15px !important;
            direction: rtl;
        }

        .container {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
        }

        .card {
            border: none !important;
        }

        .invoice-header-print {
            display: block !important;
            border-bottom: 3px solid #000;
            margin-bottom: 30px;
            padding-bottom: 10px;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: auto; 
        }

        table th, table td {
            border: 1px solid #000 !important;
            padding: 8px !important;
            overflow: visible !important;
            white-space: nowrap; 
            text-align: center !important;
        }

        th:last-child, td:last-child {
            display: none !important;
        }

        input, select {
            border: none !important;
            background: transparent !important;
            appearance: none;
            -webkit-appearance: none;
            color: black !important;
            font-weight: bold !important;
            width: 100% !important;
            text-align: center !important;
        }

        #grand_total { display: none !important; }
        .print-total-text { 
            display: inline-block !important; 
            font-weight: bold !important; 
            font-size: 1.4rem !important;
        }
    }

    .print-total-text { display: none; }
</style>

<div class="container py-4" dir="rtl">
    
    <div class="invoice-header-print">
        <div class="row">
            <div class="col-6">
                <h4 class="fw-bold mb-3">نوع الفاتورة: {{ $type == 'out' ? 'صادر (مبيعات)' : 'وارد (مشتريات)' }}</h4>
                <p class="mb-1">رقم الفاتورة: <strong>{{ $generatedCode }}</strong></p>
                <p class="mb-1">تاريخ الإصدار: <strong>{{ date('Y-m-d') }}</strong></p>
            </div>
            <div class="col-6 text-start">
                <p class="mb-1">المحرر: <strong>{{ Auth::user()->name }}</strong></p>
                <h4 class="fw-bold mt-2">الإجمالي الكلي: <span id="grand_total_print">0.00</span></h4>
            </div>
        </div>
    </div>

    <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3 no-print">
               <h5 class="mb-0">إنشاء فاتورة {{ $type == 'out' ? 'صادر' : 'وارد' }}</h5>
                <div class="text-start">
                    <span class="badge bg-secondary">المستخدم: {{ Auth::user()->name }}</span>
                </div>
            </div>
            
            <div class="card-body">
                <div class="row mb-4 no-print">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">رقم الفاتورة</label>
                        <input type="text" class="form-control bg-light fw-bold" value="{{ $generatedCode }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">تاريخ الفاتورة</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light text-center">
                            <tr>
                                <th style="min-width: 200px;">المادة</th>
                                <th style="width: 15%;">الكمية</th>
                                <th style="width: 20%;">السعر</th>
                                <th style="width: 20%;">الإجمالي</th>
                                <th style="width: 5%" class="no-print">حذف</th>
                            </tr>
                        </thead>
                        <tbody id="items_table_body">
                            <tr>
                                <td>
                                    <select name="items[0][item_id]" class="form-control" required>
                                        <option value="">اختر المادة...</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="items[0][quantity]" class="form-control qty text-center" required min="1"></td>
                                <td><input type="number" name="items[0][price]" class="form-control price text-center" required step="any"></td>
                                <td><input type="number" class="form-control line-total text-center fw-bold" value="0.00" readonly></td>
                                <td class="text-center no-print">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row border-0"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="3" class="text-end fw-bold">إجمالي الفاتورة النهائي:</td>
                                <td colspan="2" class="text-center">
                                    <input type="number" id="grand_total" name="total_amount" class="form-control fw-bold text-success border-0 bg-transparent shadow-none p-0 text-center" style="font-size: 1.4rem;" value="0.00" readonly>
                                    <span id="grand_total_span" class="print-total-text">0.00</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mt-3 no-print" id="add_item_btn">
                    <i class="fas fa-plus"></i> إضافة مادة أخرى
                </button>
            </div>
            
            <div class="card-footer bg-light py-3 d-flex justify-content-between no-print">
                <button type="button" class="btn btn-secondary px-4 fw-bold shadow-sm" onclick="window.print()">
                    <i class="fas fa-print"></i> طباعة الفاتورة
                </button>
                <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm">
                    حفظ وتأكيد الفاتورة
                </button>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let rowIdx = 1; 

    $('#add_item_btn').click(function() {
        let newRow = `
            <tr>
                <td>
                    <select name="items[${rowIdx}][item_id]" class="form-control" required>
                        <option value="">اختر المادة...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" name="items[${rowIdx}][quantity]" class="form-control qty text-center" required min="1"></td>
                <td><input type="number" name="items[${rowIdx}][price]" class="form-control price text-center" required step="any"></td>
                <td><input type="number" class="form-control line-total text-center fw-bold" value="0.00" readonly></td>
                <td class="text-center no-print">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-row border-0"><i class="fa fa-trash"></i></button>
                </td>
            </tr>`;
        $('#items_table_body').append(newRow);
        rowIdx++; 
    });

    $(document).on('input', '.qty, .price', function() {
        let row = $(this).closest('tr');
        let qty = parseFloat(row.find('.qty').val()) || 0;
        let price = parseFloat(row.find('.price').val()) || 0;
        let total = qty * price;
        
        row.find('.line-total').val(total.toFixed(2));
        updateTotals();
    });

    function updateTotals() {
        let sum = 0;
        $('.line-total').each(function() {
            sum += parseFloat($(this).val()) || 0;
        });
        
        let formattedSum = sum.toFixed(2);
        $('#grand_total').val(formattedSum);
        $('#grand_total_print').text(formattedSum);
        $('#grand_total_span').text(formattedSum); 
    }

    $(document).on('click', '.remove-row', function() {
        if ($('#items_table_body tr').length > 1) {
            $(this).closest('tr').remove();
            updateTotals();
        }
    });
</script>
@endsection