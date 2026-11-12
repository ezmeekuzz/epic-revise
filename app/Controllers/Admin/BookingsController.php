<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\BookingLinkService;
use App\Libraries\BookingNotifier;
use App\Libraries\BookingPricingService;
use App\Libraries\BookingService;
use App\Libraries\SmtpService;
use App\Models\BookingItemModel;
use App\Models\BookingModel;
use App\Models\DormModel;
use App\Models\ItemSizeModel;
use App\Models\PaymentModel;
use App\Models\TransactionModel;
use App\Models\UserModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

final class BookingsController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function index(): string
    {
        return $this->listing(null);
    }

    public function summerStorage(): string
    {
        return $this->listing('summer_storage');
    }

    public function summerAdvantage(): string
    {
        return $this->listing('summer_advantage');
    }

    private function listing(?string $type): string
    {
        $search   = trim((string) $this->request->getGet('q'));
        $status   = (string) $this->request->getGet('status');
        $bookings = $this->filteredBookings($type, $search, $status)->orderBy('created_at', 'DESC')->findAll();

        return view('admin/bookings/index', [
            'title' => $type === 'summer_storage' ? 'Summer Storage Bookings' : ($type === 'summer_advantage' ? 'Summer Advantage Bookings' : 'Booking Masterlist'),
            'activeAdminPage' => 'bookings', 'bookings' => $bookings,
            'filters' => ['q' => $search, 'status' => $status], 'statuses' => $this->statuses(), 'bookingType' => $type,
            'notificationTypes' => $this->notificationTypes(),
            'paidByBooking' => $this->paidAmountsByBooking(array_column($bookings, 'id')),
        ]);
    }

    private function filteredBookings(?string $type, string $search, string $status): BookingModel
    {
        $model = new BookingModel();

        if ($search !== '') {
            $model->groupStart()->like('reference_code', $search)->orLike('customer_name', $search)->orLike('customer_email', $search)->groupEnd();
        }
        if (in_array($status, $this->statuses(), true)) {
            $model->where('status', $status);
        }
        if ($type !== null) {
            $model->where('booking_type', $type);
        }

        return $model;
    }

    public function exportCsv(): ResponseInterface
    {
        $table  = $this->exportTable($this->requestedType());
        $buffer = fopen('php://temp', 'r+');
        fputcsv($buffer, $table['headers']);
        foreach ($table['rows'] as $row) {
            fputcsv($buffer, $row);
        }
        rewind($buffer);
        $csv = stream_get_contents($buffer);
        fclose($buffer);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="bookings-' . date('Y-m-d-His') . '.csv"')
            ->setBody($csv);
    }

    public function exportExcel(): ResponseInterface
    {
        $table = $this->exportTable($this->requestedType());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bookings');
        $sheet->fromArray($table['headers'], null, 'A1');
        $sheet->fromArray($table['rows'], null, 'A2');
        $lastColumn = Coordinate::stringFromColumnIndex(count($table['headers']));
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
        foreach (array_keys($table['headers']) as $index) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setAutoSize(true);
        }
        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="bookings-' . date('Y-m-d-His') . '.xlsx"')
            ->setBody((string) $content);
    }

    public function exportPdf(int $id): ResponseInterface
    {
        $booking = $this->find($id);
        $paid    = $this->paidAmountsByBooking([$id])[$id] ?? 0.0;

        $html = view('admin/bookings/pdf_receipt', [
            'booking' => $booking,
            'dorm' => $booking['dorm_id'] ? (new DormModel())->find($booking['dorm_id']) : null,
            'bookingItems' => (new BookingItemModel())->where('booking_id', $id)->orderBy('created_at', 'ASC')->findAll(),
            'paid' => $paid,
            'balance' => max(0, (float) $booking['total_amount'] - $paid),
            'logoDataUri' => $this->logoDataUri(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $booking['reference_code'] . '.pdf"')
            ->setBody($dompdf->output());
    }

    public function new(): string
    {
        return $this->formView(null);
    }

    public function create(): RedirectResponse
    {
        $data = $this->input();
        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if (! $this->linkedCustomerIsValid($data['user_id']) || ! $this->dormIsValid($data['dorm_id'])) {
            return redirect()->back()->withInput()->with('errors', ['user_id' => 'Select an active customer and dorm, or leave customer/dorm blank.']);
        }
        $data = $this->withAutoLinkedCustomer($data);

        $data['reference_code'] = (new BookingService())->reference();
        $data['ordernumber'] ??= (new BookingService())->orderNumber();
        $data['payment_status'] = 'unpaid';
        $data['subtotal'] = '0.00';
        $data['total_amount'] = '0.00';
        try {
            $id = (int) (new BookingModel())->insert($data, true);
            if ($id < 1) {
                throw new DatabaseException('Booking insert failed.');
            }
            (new BookingService())->recalculate($id);
        } catch (Throwable) {
            return redirect()->back()->withInput()->with('error', 'The booking could not be created. Please review the details.');
        }

        return redirect()->route('admin.bookings.show', [$id])->with('success', 'Booking created. Add its size-specific items below.');
    }

    public function show(int $id): string
    {
        $booking = $this->find($id);

        return view('admin/bookings/show', [
            'customerProfile' => $booking['user_id'] ? (new UserModel())->find((int) $booking['user_id']) : null,
            'title' => 'Booking ' . $booking['reference_code'], 'activeAdminPage' => 'bookings', 'booking' => $booking,
            'dorm' => $booking['dorm_id'] ? (new DormModel())->find($booking['dorm_id']) : null,
            'bookingItems' => (new BookingItemModel())->where('booking_id', $id)->orderBy('created_at', 'ASC')->findAll(),
            'availableItems' => $booking['booking_type'] === 'summer_storage'
                ? (new ItemSizeModel())->select('item_sizes.*, items.name AS item_name')->join('items', 'items.id = item_sizes.item_id')->where('item_sizes.is_active', 1)->where('items.is_active', 1)->orderBy('items.name', 'ASC')->orderBy('item_sizes.name', 'ASC')->findAll()
                : [],
            'payments' => (new PaymentModel())->where('booking_id', $id)->orderBy('created_at', 'DESC')->findAll(),
            'transactions' => (new TransactionModel())->where('booking_id', $id)->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function edit(int $id): string
    {
        return $this->formView($this->find($id));
    }

    public function update(int $id): RedirectResponse
    {
        $existing = $this->find($id);
        $data = $this->input();
        if (! $this->validateData($data, $this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if (! $this->linkedCustomerIsValid($data['user_id']) || ! $this->dormIsValid($data['dorm_id'])) {
            return redirect()->back()->withInput()->with('errors', ['user_id' => 'Select an active customer and dorm, or leave customer/dorm blank.']);
        }
        $data = $this->withAutoLinkedCustomer($data);

        $db = db_connect();
        $db->transBegin();

        try {
            if (! (new BookingModel())->update($id, $data)) {
                throw new DatabaseException('Booking update failed.');
            }
            if ($data['user_id'] !== null) {
                // Payments made before the booking was linked follow it into the account.
                $db->table('payments')->where('booking_id', $id)->where('user_id', null)->update(['user_id' => $data['user_id']]);
            }
            if ($existing['booking_type'] !== 'summer_advantage' && $data['booking_type'] === 'summer_advantage') {
                (new BookingItemModel())->where('booking_id', $id)->delete();
            }
            (new BookingService())->recalculate($id);
            if ($db->transStatus() === false) {
                throw new DatabaseException('Booking transaction failed.');
            }
            $db->transCommit();
        } catch (Throwable) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', 'The booking could not be updated. Please review the details.');
        }

        (new BookingNotifier())->scheduleChanged($existing, $id);

        return redirect()->route('admin.bookings.show', [$id])->with('success', 'Booking details updated.');
    }

    public function updateWarehouse(int $id): RedirectResponse
    {
        $this->find($id);
        $value = (string) $this->request->getPost('row_in_warehouse');
        if (! $this->validateData(['row_in_warehouse' => $value], ['row_in_warehouse' => 'permit_empty|is_natural_no_zero'])) {
            return redirect()->route('admin.bookings.show', [$id])->with('errors', $this->validator->getErrors());
        }

        (new BookingModel())->update($id, ['row_in_warehouse' => $value === '' ? null : (int) $value]);

        return redirect()->route('admin.bookings.show', [$id])->with('success', 'Warehouse row updated.');
    }

    public function notifyConfirmation(int $id): RedirectResponse
    {
        $booking = $this->find($id);
        if (! $this->throttleNotification($id, 'confirmation')) {
            return redirect()->route('admin.bookings.show', [$id])->with('error', 'Please wait a minute before resending this notice.');
        }

        try {
            $bookingItems = (new BookingItemModel())->where('booking_id', $id)->orderBy('created_at', 'ASC')->findAll();
            $dorm = $booking['dorm_id'] ? (new DormModel())->find($booking['dorm_id']) : null;
            $scheduleUrl = $booking['pickup_date'] ? null : $this->accountCtaUrl($booking, '/account/bookings/' . $id . '/pickup');
            if (! (new SmtpService())->sendBookingConfirmation($booking, $bookingItems, $dorm, $scheduleUrl)) {
                throw new RuntimeException('The mail server rejected the message.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Booking confirmation notice failed for booking {id}: {type}', ['id' => $id, 'type' => $exception::class]);

            return redirect()->route('admin.bookings.show', [$id])->with('alert', ['type' => 'error', 'title' => 'Email not sent', 'message' => 'The confirmation email could not be sent. Check the email settings.']);
        }

        return redirect()->route('admin.bookings.show', [$id])->with('success', 'Booking confirmation sent to ' . $booking['customer_email'] . '.');
    }

    /** "Invite to register": claim link if the email has an account, otherwise sign-up with the email pre-filled. */
    public function notifyAccountInvite(int $id): RedirectResponse
    {
        $booking = $this->find($id);
        if (! empty($booking['user_id'])) {
            return redirect()->route('admin.bookings.show', [$id])->with('info', 'This booking is already linked to a customer account.');
        }
        if (! $this->throttleNotification($id, 'account-invite')) {
            return redirect()->route('admin.bookings.show', [$id])->with('error', 'Please wait a minute before resending this notice.');
        }

        try {
            $link = (new BookingLinkService())->accountLink($booking, '/account/bookings/' . $id);
            if (! (new SmtpService())->sendAccountInvite($booking, $link)) {
                throw new RuntimeException('The mail server rejected the message.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Account invite failed for booking {id}: {type}', ['id' => $id, 'type' => $exception::class]);

            return redirect()->route('admin.bookings.show', [$id])->with('alert', ['type' => 'error', 'title' => 'Email not sent', 'message' => 'The invitation could not be sent. Check the email settings.']);
        }

        return redirect()->route('admin.bookings.show', [$id])->with('success', 'Invitation sent to ' . $booking['customer_email'] . '.');
    }

    public function notifyBalanceDue(int $id): RedirectResponse
    {
        $booking = $this->find($id);
        $paid    = $this->paidAmountsByBooking([$id])[$id] ?? 0.0;
        $balance = max(0, round((float) $booking['total_amount'] - $paid, 2));
        if ($balance < 0.01) {
            return redirect()->route('admin.bookings.show', [$id])->with('error', 'This booking has no outstanding balance.');
        }
        if (! $this->throttleNotification($id, 'balance-due')) {
            return redirect()->route('admin.bookings.show', [$id])->with('error', 'Please wait a minute before resending this notice.');
        }

        try {
            $bookingItems = (new BookingItemModel())->where('booking_id', $id)->orderBy('created_at', 'ASC')->findAll();
            $dorm = $booking['dorm_id'] ? (new DormModel())->find($booking['dorm_id']) : null;
            $paymentUrl = $this->accountCtaUrl($booking, '/account/bookings/' . $id . '/payment');
            if (! (new SmtpService())->sendBalanceDueNotice($booking, $bookingItems, $dorm, $paid, $balance, $paymentUrl)) {
                throw new RuntimeException('The mail server rejected the message.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Balance due notice failed for booking {id}: {type}', ['id' => $id, 'type' => $exception::class]);

            return redirect()->route('admin.bookings.show', [$id])->with('alert', ['type' => 'error', 'title' => 'Email not sent', 'message' => 'The balance due notice could not be sent. Check the email settings.']);
        }

        return redirect()->route('admin.bookings.show', [$id])->with('success', 'Balance due notice sent to ' . $booking['customer_email'] . '.');
    }

    public function notifyPickupReminder(int $id): RedirectResponse
    {
        $booking = $this->find($id);
        if (! $booking['pickup_date']) {
            return redirect()->route('admin.bookings.show', [$id])->with('error', 'Set a leaving campus before sending a leaving campus reminder.');
        }
        if (! $this->throttleNotification($id, 'pickup-reminder')) {
            return redirect()->route('admin.bookings.show', [$id])->with('error', 'Please wait a minute before resending this notice.');
        }

        try {
            $location = $booking['pickup_location'] ?: null;
            if (! (new SmtpService())->sendPickupReminder($booking['customer_email'], $booking['customer_name'], $booking['reference_code'], $booking['pickup_date'], $booking['pickup_time'], $location)) {
                throw new RuntimeException('The mail server rejected the message.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Leaving Campus reminder failed for booking {id}: {type}', ['id' => $id, 'type' => $exception::class]);

            return redirect()->route('admin.bookings.show', [$id])->with('alert', ['type' => 'error', 'title' => 'Email not sent', 'message' => 'The leaving campus reminder could not be sent. Check the email settings.']);
        }

        return redirect()->route('admin.bookings.show', [$id])->with('success', 'Leaving Campus reminder sent to ' . $booking['customer_email'] . '.');
    }

    public function notifyDropoffReminder(int $id): RedirectResponse
    {
        $booking = $this->find($id);
        if (! $booking['dropoff_date']) {
            return redirect()->route('admin.bookings.show', [$id])->with('error', 'Set a returning to campus before sending a returning to campus reminder.');
        }
        if (! $this->throttleNotification($id, 'dropoff-reminder')) {
            return redirect()->route('admin.bookings.show', [$id])->with('error', 'Please wait a minute before resending this notice.');
        }

        try {
            $location = $booking['dropoff_location'] ?: null;
            if (! (new SmtpService())->sendDropoffReminder($booking['customer_email'], $booking['customer_name'], $booking['reference_code'], $booking['dropoff_date'], $booking['dropoff_time'], $location)) {
                throw new RuntimeException('The mail server rejected the message.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Returning to Campus reminder failed for booking {id}: {type}', ['id' => $id, 'type' => $exception::class]);

            return redirect()->route('admin.bookings.show', [$id])->with('alert', ['type' => 'error', 'title' => 'Email not sent', 'message' => 'The returning to campus reminder could not be sent. Check the email settings.']);
        }

        return redirect()->route('admin.bookings.show', [$id])->with('success', 'Returning to Campus reminder sent to ' . $booking['customer_email'] . '.');
    }

    public function notifyBulk(): RedirectResponse
    {
        $type = (string) $this->request->getPost('notification_type');
        if (! array_key_exists($type, $this->notificationTypes())) {
            return redirect()->back()->with('error', 'Choose a valid notification type.');
        }

        if ((string) $this->request->getPost('mode') === 'selected') {
            $ids = array_values(array_unique(array_filter(
                array_map('intval', (array) $this->request->getPost('booking_ids')),
                static fn (int $id): bool => $id > 0,
            )));
            if ($ids === []) {
                return redirect()->back()->with('error', 'Select at least one booking to notify.');
            }
            $bookings = (new BookingModel())->whereIn('id', $ids)->findAll();
        } else {
            $bookingType = in_array((string) $this->request->getPost('type'), ['summer_storage', 'summer_advantage'], true) ? (string) $this->request->getPost('type') : null;
            $bookings = $this->filteredBookings($bookingType, trim((string) $this->request->getPost('q')), (string) $this->request->getPost('status'))->findAll();
        }

        if ($bookings === []) {
            return redirect()->back()->with('error', 'No bookings matched.');
        }
        if (count($bookings) > 200) {
            return redirect()->back()->with('alert', ['type' => 'warning', 'title' => 'Too many bookings', 'message' => 'That matches more than 200 bookings. Narrow the filters before sending in bulk.']);
        }
        if (! $this->throttleBulkNotification($type)) {
            return redirect()->back()->with('error', 'Please wait a couple of minutes before sending another bulk notice.');
        }

        $paidByBooking = $this->paidAmountsByBooking(array_column($bookings, 'id'));
        $smtp = new SmtpService();
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($bookings as $booking) {
            if (! $this->isEligibleForNotification($booking, $type, $paidByBooking)) {
                $skipped++;
                continue;
            }

            try {
                $ok = $this->sendNotificationByType($smtp, $booking, $type, $paidByBooking);
            } catch (Throwable $exception) {
                log_message('error', 'Bulk {type} notice failed for booking {id}: {err}', ['type' => $type, 'id' => $booking['id'], 'err' => $exception::class]);
                $ok = false;
            }

            $ok ? $sent++ : $failed++;
        }

        $message = 'Sent ' . $sent . ' notice' . ($sent === 1 ? '' : 's') . '.';
        if ($skipped > 0) {
            $message .= ' Skipped ' . $skipped . ' not eligible for this notice.';
        }
        if ($failed > 0) {
            $message .= ' ' . $failed . ' failed to send.';
        }

        return redirect()->back()->with($sent === 0 && $failed > 0 ? 'error' : 'success', $message);
    }

    public function addItem(int $bookingId): RedirectResponse
    {
        $booking = $this->find($bookingId);
        if ($booking['booking_type'] !== 'summer_storage') {
            return redirect()->back()->with('error', 'Summer Advantage bookings are priced only by box quantity.');
        }
        $itemSizeId = (string) $this->request->getPost('item_size_id');
        if ($itemSizeId === '' && (int) $this->request->getPost('item_id') > 0) {
            $fallback = (new ItemSizeModel())->where('item_id', (int) $this->request->getPost('item_id'))->where('is_active', 1)->orderBy('id', 'ASC')->first();
            $itemSizeId = (string) ($fallback['id'] ?? '');
        }
        $data = ['item_size_id' => $itemSizeId, 'quantity' => (string) $this->request->getPost('quantity')];
        if (! $this->validateData($data, ['item_size_id' => 'required|is_natural_no_zero', 'quantity' => 'required|is_natural_no_zero|less_than_equal_to[100]'])) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $size = (new ItemSizeModel())->select('item_sizes.*, items.name AS item_name')->join('items', 'items.id = item_sizes.item_id')->where('item_sizes.id', (int) $data['item_size_id'])->where('item_sizes.is_active', 1)->where('items.is_active', 1)->first();
        if ($size === null) {
            return redirect()->back()->with('error', 'The selected item size is not available.');
        }

        $quantity = (int) $data['quantity'];
        (new BookingItemModel())->insert([
            'booking_id' => $bookingId, 'item_id' => $size['item_id'], 'item_size_id' => $size['id'], 'item_name' => $size['item_name'], 'size_name' => $size['name'],
            'unit_price' => $size['unit_price'], 'quantity' => $quantity, 'line_total' => number_format((float) $size['unit_price'] * $quantity, 2, '.', ''),
        ]);
        (new BookingService())->recalculate($bookingId);

        return redirect()->route('admin.bookings.show', [$bookingId])->with('success', 'Size-specific item added to the booking.');
    }

    public function removeItem(int $bookingId, int $itemId): RedirectResponse
    {
        $this->find($bookingId);
        $items = new BookingItemModel();
        if ($items->where('id', $itemId)->where('booking_id', $bookingId)->first() === null) {
            throw PageNotFoundException::forPageNotFound('Booking item not found.');
        }
        $items->delete($itemId, true);
        (new BookingService())->recalculate($bookingId);

        return redirect()->route('admin.bookings.show', [$bookingId])->with('success', 'Item removed from the booking.');
    }

    public function delete(int $id): RedirectResponse
    {
        $booking = $this->find($id);

        try {
            if (! (new BookingModel())->delete($id)) {
                throw new DatabaseException('Booking delete failed.');
            }
        } catch (DatabaseException $exception) {
            log_message('error', 'Booking delete failed for booking {id}: {type}', ['id' => $id, 'type' => $exception::class]);

            return redirect()->back()->with('alert', ['type' => 'warning', 'title' => 'Booking cannot be deleted', 'message' => 'This booking has payment or transaction records and cannot be deleted. Cancel it instead, or remove its financial records first.']);
        }

        return redirect()->back()->with('success', 'Booking ' . $booking['reference_code'] . ' deleted.');
    }

    /** @return array<string, mixed> */
    private function input(): array
    {
        $userId = (int) $this->request->getPost('user_id');
        $dormId = (int) $this->request->getPost('dorm_id');
        $type = (string) $this->request->getPost('booking_type');
        if (! in_array($type, ['summer_storage', 'summer_advantage'], true)) {
            $legacyService = strtolower((string) $this->request->getPost('service_name'));
            $type = str_contains($legacyService, 'advantage') ? 'summer_advantage' : 'summer_storage';
        }
        $isStorage = $type === 'summer_storage';
        $boxQuantity = max(0, (int) $this->request->getPost('additional_box_quantity'));
        $boxPrice = max(0, (float) $this->request->getPost('additional_box_amount'));
        $isStudyAbroad = $isStorage && $this->request->getPost('is_study_abroad') === '1';
        $isSummerSchool = $isStorage && $this->request->getPost('is_summer_school') === '1';
        $summerSchoolFee = $isSummerSchool ? max(0, (float) $this->request->getPost('summer_school_fee')) : 0.0;

        return [
            'user_id' => $userId > 0 ? $userId : null, 'customer_name' => trim((string) $this->request->getPost('customer_name')),
            'customer_email' => mb_strtolower(trim((string) $this->request->getPost('customer_email'))), 'customer_phone' => trim((string) $this->request->getPost('customer_phone')),
            'student_id' => trim((string) $this->request->getPost('student_id')), 'dorm_id' => $dormId > 0 ? $dormId : null,
            'parent_phone_number' => trim((string) $this->request->getPost('parent_phone_number')) ?: null,
            'parent_email_address' => mb_strtolower(trim((string) $this->request->getPost('parent_email_address'))) ?: null,
            'booking_type' => $type, 'service_name' => $this->serviceLabels()[$type] ?? '',
            'ordernumber' => trim((string) $this->request->getPost('ordernumber')) ?: null, 'card_holder_name' => trim((string) $this->request->getPost('card_holder_name')) ?: null,
            'booking_date' => (string) $this->request->getPost('booking_date') ?: date('Y-m-d'), 'base_price' => number_format($isStorage ? max(0, (float) $this->request->getPost('base_price')) : 0, 2, '.', ''),
            'additional_box_quantity' => $boxQuantity, 'additional_box_amount' => number_format($boxPrice, 2, '.', ''),
            'addtl_box_total_amount' => number_format($boxQuantity * $boxPrice, 2, '.', ''), 'study_abroad_additional_storage_price' => '0.00',
            'is_study_abroad' => $isStudyAbroad ? 1 : 0, 'is_summer_school' => $isSummerSchool ? 1 : 0, 'summer_school_fee' => number_format($summerSchoolFee, 2, '.', ''),
            'notes' => trim((string) $this->request->getPost('notes')) ?: null,
            'row_in_warehouse' => (int) $this->request->getPost('row_in_warehouse') > 0 ? (int) $this->request->getPost('row_in_warehouse') : null,
            'pickup_date' => (string) $this->request->getPost('pickup_date') ?: null, 'pickup_time' => (string) $this->request->getPost('pickup_time') ?: null,
            'pickup_location' => trim((string) $this->request->getPost('pickup_location')), 'room_number' => trim((string) $this->request->getPost('room_number')),
            'dropoff_date' => (string) $this->request->getPost('dropoff_date') ?: null, 'dropoff_time' => (string) $this->request->getPost('dropoff_time') ?: null,
            'dropoff_location' => trim((string) $this->request->getPost('dropoff_location')), 'dropoff_room_number' => trim((string) $this->request->getPost('dropoff_room_number')),
            'pickup_status' => (string) $this->request->getPost('pickup_status') ?: 'unscheduled', 'dropoff_status' => (string) $this->request->getPost('dropoff_status') ?: 'unscheduled',
            'status' => (string) $this->request->getPost('status'), 'discount_amount' => number_format(max(0, (float) $this->request->getPost('discount_amount')), 2, '.', ''),
            'admin_notes' => trim((string) $this->request->getPost('admin_notes')),
            'service_fee_percent' => number_format(max(0, min(20, (float) $this->request->getPost('service_fee_percent'))), 2, '.', ''),
        ];
    }

    /** @return array<string, string> */
    private function rules(): array
    {
        return [
            'user_id' => 'permit_empty|is_natural_no_zero', 'customer_name' => 'required|min_length[2]|max_length[120]', 'customer_email' => 'required|valid_email|max_length[254]',
            'customer_phone' => 'permit_empty|max_length[30]', 'student_id' => 'permit_empty|max_length[50]', 'dorm_id' => 'permit_empty|is_natural_no_zero',
            'parent_phone_number' => 'permit_empty|max_length[30]', 'parent_email_address' => 'permit_empty|valid_email|max_length[254]',
            'booking_type' => 'required|in_list[summer_storage,summer_advantage]', 'service_name' => 'required|max_length[120]',
            'ordernumber' => 'permit_empty|max_length[20]', 'card_holder_name' => 'permit_empty|max_length[100]', 'booking_date' => 'required|valid_date[Y-m-d]',
            'base_price' => 'required|decimal|greater_than_equal_to[0]', 'additional_box_quantity' => 'required|is_natural|less_than_equal_to[100]', 'additional_box_amount' => 'required|decimal|greater_than_equal_to[0]',
            'addtl_box_total_amount' => 'required|decimal|greater_than_equal_to[0]', 'study_abroad_additional_storage_price' => 'required|decimal|greater_than_equal_to[0]',
            'is_study_abroad' => 'required|in_list[0,1]', 'is_summer_school' => 'required|in_list[0,1]', 'summer_school_fee' => 'required|decimal|greater_than_equal_to[0]', 'notes' => 'permit_empty|max_length[5000]',
            'row_in_warehouse' => 'permit_empty|is_natural_no_zero',
            'pickup_date' => 'permit_empty|valid_date[Y-m-d]', 'pickup_time' => 'permit_empty|regex_match[/^([01]\\d|2[0-3]):[0-5]\\d$/]',
            'pickup_location' => 'permit_empty|max_length[160]', 'room_number' => 'permit_empty|max_length[30]', 'dropoff_date' => 'permit_empty|valid_date[Y-m-d]',
            'dropoff_time' => 'permit_empty|regex_match[/^([01]\\d|2[0-3]):[0-5]\\d$/]', 'dropoff_location' => 'permit_empty|max_length[160]',
            'dropoff_room_number' => 'permit_empty|max_length[30]', 'pickup_status' => 'required|in_list[unscheduled,scheduled,completed,cancelled]',
            'dropoff_status' => 'required|in_list[unscheduled,scheduled,completed,cancelled]', 'status' => 'required|in_list[' . implode(',', $this->statuses()) . ']',
            'discount_amount' => 'required|decimal|greater_than_equal_to[0]', 'admin_notes' => 'permit_empty|max_length[5000]',
            'service_fee_percent' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[20]',
        ];
    }

    /** @return list<string> */
    private function statuses(): array
    {
        return ['pending', 'confirmed', 'picked_up', 'in_storage', 'scheduled_for_delivery', 'completed', 'cancelled'];
    }

    /** @return array<string, string> */
    private function serviceLabels(): array
    {
        return ['summer_storage' => 'Summer Storage', 'summer_advantage' => 'Summer Advantage Program'];
    }

    private function linkedCustomerIsValid(?int $userId): bool
    {
        return $userId === null || (new UserModel())->where('id', $userId)->where('role', 'customer')->where('status', 'active')->first() !== null;
    }

    private function dormIsValid(?int $dormId): bool
    {
        return $dormId === null || (new DormModel())->where('id', $dormId)->where('is_active', 1)->first() !== null;
    }

    /** @return array{headers: list<string>, rows: list<list<string>>} */
    private function exportTable(?string $type): array
    {
        $search   = trim((string) $this->request->getGet('q'));
        $status   = (string) $this->request->getGet('status');
        $bookings = $this->filteredBookings($type, $search, $status)->orderBy('created_at', 'DESC')->findAll();
        $paidByBooking = $this->paidAmountsByBooking(array_column($bookings, 'id'));

        $headers = ['Reference', 'Type', 'Customer', 'Email', 'Phone', 'Student ID', 'Leaving Campus', 'Leaving Campus location', 'Returning to Campus', 'Returning to Campus location', 'Warehouse row', 'Total', 'Paid', 'Balance', 'Payment status', 'Status', 'Created'];
        $rows = [];
        foreach ($bookings as $booking) {
            $paid = $paidByBooking[(int) $booking['id']] ?? 0.0;
            $rows[] = [
                $booking['reference_code'],
                $booking['booking_type'] === 'summer_advantage' ? 'Summer Advantage' : 'Summer Storage',
                $booking['customer_name'],
                $booking['customer_email'],
                $booking['customer_phone'] ?: '',
                $booking['student_id'] ?: '',
                $booking['pickup_date'] ? date('Y-m-d', strtotime((string) $booking['pickup_date'])) : '',
                $booking['pickup_location'] ?: '',
                $booking['dropoff_date'] ? date('Y-m-d', strtotime((string) $booking['dropoff_date'])) : '',
                $booking['dropoff_location'] ?: '',
                $booking['row_in_warehouse'] !== null ? (string) $booking['row_in_warehouse'] : '',
                number_format((float) $booking['total_amount'], 2, '.', ''),
                number_format($paid, 2, '.', ''),
                number_format(max(0, (float) $booking['total_amount'] - $paid), 2, '.', ''),
                ucfirst($booking['payment_status']),
                ucwords(str_replace('_', ' ', $booking['status'])),
                date('Y-m-d H:i', strtotime((string) $booking['created_at'])),
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /** @param list<int> $bookingIds @return array<int, float> */
    private function paidAmountsByBooking(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $rows = (new TransactionModel())
            ->select('booking_id, SUM(amount) AS paid')
            ->whereIn('booking_id', $bookingIds)
            ->where('status', 'completed')
            ->groupBy('booking_id')
            ->findAll();

        $paid = [];
        foreach ($rows as $row) {
            $paid[(int) $row['booking_id']] = round((float) $row['paid'], 2);
        }

        return $paid;
    }

    private function requestedType(): ?string
    {
        $type = (string) $this->request->getGet('type');

        return in_array($type, ['summer_storage', 'summer_advantage'], true) ? $type : null;
    }

    private function logoDataUri(): ?string
    {
        $path = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'epic-logo-white.png';
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode((string) file_get_contents($path));
    }

    private function throttleNotification(int $bookingId, string $type): bool
    {
        $key = 'booking_notify_' . $type . '_' . $bookingId . '_' . hash('sha256', (string) session()->get('admin_user_id'));

        return service('throttler')->check($key, 3, MINUTE);
    }

    private function throttleBulkNotification(string $type): bool
    {
        $key = 'booking_notify_bulk_' . $type . '_' . hash('sha256', (string) session()->get('admin_user_id'));

        return service('throttler')->check($key, 1, 2 * MINUTE);
    }

    /**
     * Link for a notification email's call-to-action.
     * Linked booking: one-click login to $path. No account yet: payment emails get a secure
     * pay link; other emails get "add to my account" (email has an account) or sign-up.
     *
     * @param array<string, mixed> $booking
     */
    private function accountCtaUrl(array $booking, string $path): string
    {
        $links = new BookingLinkService();
        if (empty($booking['user_id']) && str_ends_with($path, '/payment')) {
            return $links->paymentUrl($booking);
        }

        return $links->accountLink($booking, $path)['url'];
    }

    /**
     * Staff walk-in bookings: if the email belongs to an active customer account, link it.
     * Staff seeing the customer in person is the proof of ownership here.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function withAutoLinkedCustomer(array $data): array
    {
        if ($data['user_id'] === null) {
            $match = (new BookingLinkService())->activeCustomerByEmail((string) $data['customer_email']);
            if ($match !== null) {
                $data['user_id'] = (int) $match['id'];
            }
        }

        return $data;
    }

    /** @return array<string, string> */
    private function notificationTypes(): array
    {
        return [
            'confirmation' => 'Booking confirmation',
            'balance-due' => 'Balance due reminder',
            'pickup-reminder' => 'Leaving Campus reminder (already scheduled)',
            'pickup-needed' => 'Schedule leaving campus reminder (not yet scheduled)',
            'dropoff-reminder' => 'Returning to Campus reminder (already scheduled)',
        ];
    }

    /** @param array<string, mixed> $booking @param array<int, float> $paidByBooking */
    private function isEligibleForNotification(array $booking, string $type, array $paidByBooking): bool
    {
        return match ($type) {
            'confirmation' => true,
            'balance-due' => max(0, round((float) $booking['total_amount'] - ($paidByBooking[(int) $booking['id']] ?? 0.0), 2)) >= 0.01,
            'pickup-reminder' => $booking['pickup_date'] !== null,
            'pickup-needed' => $booking['pickup_date'] === null,
            'dropoff-reminder' => $booking['dropoff_date'] !== null,
            default => false,
        };
    }

    /** @param array<string, mixed> $booking @param array<int, float> $paidByBooking */
    private function sendNotificationByType(SmtpService $smtp, array $booking, string $type, array $paidByBooking): bool
    {
        $id = (int) $booking['id'];

        return match ($type) {
            'confirmation' => $smtp->sendBookingConfirmation(
                $booking,
                (new BookingItemModel())->where('booking_id', $id)->orderBy('created_at', 'ASC')->findAll(),
                $booking['dorm_id'] ? (new DormModel())->find($booking['dorm_id']) : null,
                $booking['pickup_date'] ? null : $this->accountCtaUrl($booking, '/account/bookings/' . $id . '/pickup'),
            ),
            'balance-due' => $smtp->sendBalanceDueNotice(
                $booking,
                (new BookingItemModel())->where('booking_id', $id)->orderBy('created_at', 'ASC')->findAll(),
                $booking['dorm_id'] ? (new DormModel())->find($booking['dorm_id']) : null,
                $paidByBooking[$id] ?? 0.0,
                max(0, round((float) $booking['total_amount'] - ($paidByBooking[$id] ?? 0.0), 2)),
                $this->accountCtaUrl($booking, '/account/bookings/' . $id . '/payment'),
            ),
            'pickup-reminder' => $smtp->sendPickupReminder($booking['customer_email'], $booking['customer_name'], $booking['reference_code'], $booking['pickup_date'], $booking['pickup_time'], $booking['pickup_location'] ?: null),
            'pickup-needed' => $smtp->sendPickupNeededNotice($booking['customer_email'], $booking['customer_name'], $booking['reference_code'], $this->accountCtaUrl($booking, '/account/bookings/' . $id . '/pickup')),
            'dropoff-reminder' => $smtp->sendDropoffReminder($booking['customer_email'], $booking['customer_name'], $booking['reference_code'], $booking['dropoff_date'], $booking['dropoff_time'], $booking['dropoff_location'] ?: null),
            default => false,
        };
    }

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        $booking = (new BookingModel())->find($id);
        if ($booking === null) {
            throw PageNotFoundException::forPageNotFound('Booking not found.');
        }
        return $booking;
    }

    /** @param array<string, mixed>|null $booking */
    private function formView(?array $booking): string
    {
        $prices  = (new BookingPricingService())->displayPrices();
        $prefill = [];
        if ($booking === null) {
            // New order: start from the current price list; "Create order" from a customer pre-fills their details.
            $prefill = [
                'booking_type' => 'summer_storage', 'base_price' => $prices['summer_storage_base_price'],
                'additional_box_amount' => $prices['summer_storage_additional_box_price'], 'summer_school_fee' => $prices['summer_school_fee'],
                'service_fee_percent' => $prices['service_fee_percent'],
            ];
            $customer = (new UserModel())->where('role', 'customer')->find((int) $this->request->getGet('user'));
            if ($customer !== null) {
                $prefill = [
                    ...$prefill, 'user_id' => (int) $customer['id'], 'customer_name' => $customer['name'], 'customer_email' => $customer['email'],
                    'customer_phone' => $customer['phone_number'] ?? '', 'student_id' => $customer['student_id'] ?? '',
                    'parent_phone_number' => $customer['parent_phone_number'] ?? '', 'parent_email_address' => $customer['parent_email_address'] ?? '',
                    'dorm_id' => $customer['dorm_id'] ?? '', 'room_number' => $customer['dorm_room_number'] ?? '',
                ];
            }
        }

        return view('admin/bookings/form', [
            'prefill' => $prefill,
            'title' => $booking === null ? 'Add Order' : 'Edit Booking', 'activeAdminPage' => 'bookings', 'booking' => $booking,
            'customers' => (new UserModel())->where('role', 'customer')->where('status', 'active')->orderBy('name', 'ASC')->findAll(),
            'dorms' => (new DormModel())->where('is_active', 1)->orderBy('name', 'ASC')->findAll(), 'bookingTypes' => $this->serviceLabels(),
            'statuses' => $this->statuses(), 'logisticsStatuses' => ['unscheduled', 'scheduled', 'completed', 'cancelled'],
        ]);
    }
}
