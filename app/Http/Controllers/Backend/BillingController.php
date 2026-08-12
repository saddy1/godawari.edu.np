<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\BillingCatalogItem;
use App\Models\BillingInvoice;
use App\Models\Card\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $bills = BillingInvoice::query()
            ->with('creator')
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('bill_no', 'like', "%{$search}%")
                        ->orWhere('party_name', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%");
                });
            })
            ->latest('issued_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'received' => BillingInvoice::where('type', 'receipt')->sum('total'),
            'paid' => BillingInvoice::where('type', 'payment')->sum('total'),
            'count' => BillingInvoice::count(),
        ];

        return view('backend.billing.index', compact('bills', 'summary'));
    }

    public function create(): View
    {
        return view('backend.billing.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['receipt', 'payment'])],
            'party_source_type' => ['nullable', Rule::in(['hr_member'])],
            'party_source_id' => ['nullable', 'integer', 'min:1'],
            'party_name' => ['required', 'string', 'max:190'],
            'party_identifier' => ['nullable', 'string', 'max:100'],
            'party_phone' => ['nullable', 'string', 'max:30'],
            'party_email' => ['nullable', 'email', 'max:190'],
            'party_address' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:190'],
            'payment_method' => ['required', Rule::in(['cash', 'bank', 'cheque', 'online'])],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:190'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'items.*.rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $items = collect($validated['items'])
            ->map(function (array $item, int $index) {
                $quantity = round((float) $item['quantity'], 2);
                $rate = round((float) $item['rate'], 2);

                return [
                    'description' => trim($item['description']),
                    'quantity' => $quantity,
                    'rate' => $rate,
                    'amount' => round($quantity * $rate, 2),
                    'sort_order' => $index,
                ];
            })
            ->filter(fn ($item) => $item['description'] !== '')
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->withErrors(['items' => 'Add at least one billing item.']);
        }

        $subtotal = round($items->sum('amount'), 2);
        $discount = round((float) ($validated['discount'] ?? 0), 2);
        $tax = round((float) ($validated['tax'] ?? 0), 2);
        $total = max(round($subtotal - $discount + $tax, 2), 0);

        $bill = DB::transaction(function () use ($validated, $items, $subtotal, $discount, $tax, $total) {
            $bill = BillingInvoice::create([
                'bill_no' => $this->nextBillNo(),
                'type' => $validated['type'],
                'party_source_type' => $validated['party_source_type'] ?? null,
                'party_source_id' => $validated['party_source_id'] ?? null,
                'party_name' => $validated['party_name'],
                'party_identifier' => $validated['party_identifier'] ?? null,
                'party_phone' => $validated['party_phone'] ?? null,
                'party_email' => $validated['party_email'] ?? null,
                'party_address' => $validated['party_address'] ?? null,
                'purpose' => $validated['purpose'],
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'amount_words' => $this->amountInWords($total),
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
                'issued_at' => now(),
            ]);

            $bill->items()->createMany($items->all());

            $items->each(function ($item) {
                BillingCatalogItem::firstOrCreate(
                    ['name' => $item['description']],
                    ['default_rate' => $item['rate'], 'is_active' => true]
                );
            });

            return $bill;
        });

        return redirect()->route('admin.billing.show', $bill)->with('success', 'Bill generated successfully.');
    }

    public function show(BillingInvoice $bill): View
    {
        $bill->load(['items', 'creator']);

        return view('backend.billing.show', compact('bill'));
    }

    public function destroy(BillingInvoice $bill): RedirectResponse
    {
        $bill->delete();

        return redirect()->route('admin.billing.index')->with('success', 'Bill deleted.');
    }

    public function searchPeople(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $people = Student::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('roll_number', 'like', "%{$search}%")
                        ->orWhere('registration_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(12)
            ->get()
            ->map(fn (Student $member) => [
                'id' => $member->id,
                'source_type' => 'hr_member',
                'name' => $member->full_name,
                'label' => $member->full_name.' · '.ucfirst((string) $member->member_type),
                'identifier' => $member->roll_number ?: $member->registration_no,
                'phone' => $member->mobile ?: $member->guardian_contact ?: $member->parent_contact,
                'email' => $member->email,
                'address' => $member->address_en ?: $member->address_label,
                'meta' => collect([$member->stream, $member->section, $member->batch])->filter()->implode(' · '),
            ])
            ->values();

        return response()->json(['results' => $people]);
    }

    public function searchItems(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $items = BillingCatalogItem::query()
            ->where('is_active', true)
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn (BillingCatalogItem $item) => [
                'name' => $item->name,
                'rate' => (float) $item->default_rate,
            ])
            ->values();

        return response()->json(['results' => $items]);
    }

    private function nextBillNo(): string
    {
        $prefix = 'BILL-'.now()->format('Ym').'-';
        $last = BillingInvoice::where('bill_no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('bill_no')
            ->value('bill_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function amountInWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paisa = (int) round(($amount - $rupees) * 100);
        $words = $this->numberToWords($rupees).' rupees';

        if ($paisa > 0) {
            $words .= ' and '.$this->numberToWords($paisa).' paisa';
        }

        return ucfirst($words).' only';
    }

    private function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        $parts = [];

        foreach ([10000000 => 'crore', 100000 => 'lakh', 1000 => 'thousand', 100 => 'hundred'] as $value => $label) {
            if ($number >= $value) {
                $parts[] = $this->numberToWords((int) floor($number / $value)).' '.$label;
                $number %= $value;
            }
        }

        if ($number >= 20) {
            $parts[] = $tens[(int) floor($number / 10)].($number % 10 ? ' '.$ones[$number % 10] : '');
        } elseif ($number > 0) {
            $parts[] = $ones[$number];
        }

        return implode(' ', $parts);
    }
}
