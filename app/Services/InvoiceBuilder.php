<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\InvoiceSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/** Renders the PDF invoice for an order using the settings from Dashboard → Invoice. */
class InvoiceBuilder
{
    public function build(Order $order, ?array $settings = null): DomPdf
    {
        $settings ??= InvoiceSettings::all();

        $order->loadMissing(['items', 'user']);

        $pdf = Pdf::loadView('invoices.order', [
            'order' => $order,
            's' => $settings,
            'logo' => InvoiceSettings::logoDataUri($settings),
            'invoiceNumber' => InvoiceSettings::numberFor($order->order_number, $settings),
            'copyright' => InvoiceSettings::copyrightLine($settings),
        ])->setPaper($settings['paper'] === 'letter' ? 'letter' : 'a4');

        // Document properties: who made the file and who owns the content.
        $dompdf = $pdf->getDomPDF();
        $dompdf->addInfo('Title', 'Invoice '.InvoiceSettings::numberFor($order->order_number, $settings));
        $dompdf->addInfo('Author', $settings['legal_name']);
        $dompdf->addInfo('Creator', $settings['company_name']);
        $dompdf->addInfo('Subject', 'Invoice');
        $dompdf->addInfo('Keywords', 'invoice, '.$settings['legal_name']);

        return $pdf;
    }

    /** An in-memory example order so the admin can preview the design before any real order exists. */
    public function sampleOrder(): Order
    {
        $order = new Order([
            'order_number' => 'EKY-SAMPLE01',
            'status' => 'processing',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'subtotal' => 3350,
            'discount' => 0,
            'tax' => 0,
            'shipping' => 250,
            'gift_wrap' => true,
            'gift_wrap_fee' => 150,
            'gift_message' => 'Happy birthday! Made with love.',
            'total' => 3750,
            'notes' => 'Please deliver after 5 pm.',
            'contact_name' => 'Ayesha Khan',
            'contact_email' => 'ayesha@example.com',
            'contact_phone' => '923001234567',
            'shipping_address' => [
                'full_name' => 'Ayesha Khan',
                'phone' => '0300 1234567',
                'email' => 'ayesha@example.com',
                'address_line' => 'House 12, Street 4, Block 7, Clifton',
                'city' => 'Karachi',
                'postal_code' => '75600',
            ],
        ]);
        $order->created_at = now();

        $order->setRelation('items', collect([
            new OrderItem(['product_name' => 'Mini Bunny Amigurumi', 'product_sku' => 'EKY-0001-M', 'size' => 'M', 'quantity' => 2, 'unit_price' => 1299, 'line_total' => 2598]),
            new OrderItem(['product_name' => 'Initial Letter Keychain', 'product_sku' => 'EKY-0007-S', 'size' => 'S', 'quantity' => 1, 'unit_price' => 752, 'line_total' => 752]),
        ]));
        $order->setRelation('user', null);

        return $order;
    }
}
