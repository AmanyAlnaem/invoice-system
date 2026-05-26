<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Item;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * عرض سجل الفواتير (الوارد أو الصادر)
     */
    public function index(Request $request)
    {
        // استقبال نوع الحركة من الرابط (in أو out)
        $type = $request->query('type');

        // جلب الفواتير مع العلاقات لضمان عرض اسم المادة، المستخدم، والبيانات المالية
        $invoices = Invoice::with(['items', 'user'])
            ->when($type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->where('user_id', Auth::id()) // جلب فواتير المستخدم الحالي فقط
            ->latest()
            ->get();

        return view('invoices.index', compact('invoices', 'type'));
    }

    /**
     * عرض نموذج إنشاء فاتورة جديدة
     */
    public function create($type = 'in')
    {
        $items = Item::all();

        $pool = '01234567890123456789ABCDEF';
        do {
            // توليد الكود بطول 12 خانة
            $generatedCode = substr(str_shuffle(str_repeat($pool, 5)), 0, 14);
        } while (Invoice::where('invoice_number', $generatedCode)->exists());

        // تمرير النوع للملف لضمان تغير العناوين والحقول المخفية
        return view('invoices.create', compact('items', 'type', 'generatedCode'));
    }
    /**
     * حفظ الفاتورة الجديدة في قاعدة البيانات
     */
    public function store(Request $request)
    {

        // 2. إنشاء الفاتورة بالرقم المولد تلقائياً
        $invoice = Invoice::create([
            'invoice_number' => $request->invoice_number,
            'type'           => $request->type,
            'date'           => $request->date,
            'user_id'        => auth()->id(),
            'total_amount'   => $request->total_amount,
        ]);

        // 3. معالجة المواد داخل الفاتورة
        foreach ($request->items as $itemData) {
            // تجاهل السطر إذا كان رقم المادة فارغاً أو غير موجود
            if (empty($itemData['item_id'])) {
                continue;
            }

            $item = Item::find($itemData['item_id']);

            if ($item) {
                $invoice->items()->attach($item->id, [
                    'quantity' => $itemData['quantity'] ?? 0, // تجنب أخطاء القيم الفارغة أيضاً
                    'price' => $itemData['price'] ?? 0
                ]);
            }
        }

        return redirect()->route('dashboard')->with('success', 'تم إنشاء الفاتورة بنجاح رقم: ' . $request->invoice_number,);
    }
    /**
     * عرض تفاصيل فاتورة محددة
     */
    public function show(Invoice $invoice)
    {
        // تحميل العلاقات للتأكد من عرض البيانات في جدول التفاصيل
        $invoice->load(['items', 'user']);
        return view('invoices.show', compact('invoice'));
    }
}
