<?php

namespace App\Services;

use App\Models\PropertyMapping;
use App\Models\Booking;
use Illuminate\Support\Carbon;
class LockSyncServices
{

    protected LodgifyServices $lodgifyServices;
    protected TTLockServices $ttLockServices;

    public function __construct(LodgifyServices $lodgifyServices, TTLockServices $ttLockServices)
    {
        $this->lodgifyServices = $lodgifyServices;
        $this->ttLockServices = $ttLockServices;
    }

    public function handleSynchronization(): array
    {
        $report = [
            'created' => 0,
            'updated' => 0,
            'canceled' => 0,
            'cleaned' => 0,
            'errors' => []
        ];

        $lodgifyReservations = $this->lodgifyServices->getBookings([
            'trash' => 'false',
            'status' => 'Booked',
            'limit' => 500,
            'departure_from' => now()->startOfMonth()->toDateString(),
        ]);

        if (is_array($lodgifyReservations)) {
            foreach ($lodgifyReservations as $res) {
                $report = $this->syncSingleReservation($res, $report);
            }
        }

        $report = $this->cleanExpiredPasscodes($report);

        return $report;
    }

    public function syncSingleReservation(array $res, array $report): array
    {
        $type = $res['type'] ?? 'Booking';
        $bookingType = $res['booking_type'] ?? '';
        $status = $res['status'] ?? 'Booked';

        if ($type === 'ClosedPeriod' || $bookingType === 'EnquiryOnly' || strtolower($status) === 'open') {
            return $report;
        }
        $lodgifyBookingId = $res['id'];
        $lodgifyRoomId = $res['rooms'][0]['room_type_id'] ?? null;
        $status = $res['status'] ?? 'Booked';

        $arrivalDate = isset($res['arrival']) ? substr($res['arrival'], 0, 10) : null;
        $departureDate = isset($res['departure']) ? substr($res['departure'], 0, 10) : null;

        $guestName = $res['guest']['guest_name']['full_name'] ?? 'Voyageur';

        if (!$lodgifyRoomId || !$arrivalDate || !$departureDate) {
            return $report;
        }

        $mapping = PropertyMapping::where('lodgify_property_id', $lodgifyRoomId)->first();
        if (!$mapping) {
            return $report;
        }

        $lockId = $mapping->ttlock_lock_id;

        $startDate = $arrivalDate . '13:00:00';
        $endDate = $departureDate . '8:15:00';

        $localBooking = Booking::where('lodgify_booking_id', $lodgifyBookingId)->first();

        if (in_array(strtolower($status), ['canceled', 'cancelled', 'declined'])) {
            if ($localBooking && $localBooking->ttlock_pwd_id && $localBooking->status != 'Canceled') {

                $dateSuppressionAutorisee = Carbon::parse($localBooking->departure_date)->addDay();

                if (Carbon::now()->greaterThanOrEqualTo($dateSuppressionAutorisee)) {
                    $this->ttLockServices->deletePasscode($localBooking->ttlock_lock_id, $localBooking->ttlock_pwd_id);
                    $localBooking->update(['status' => 'Canceled']);
                    $report['canceled']++;

                } else {
                    $report['errors'][] = "Réservation #{$lodgifyBookingId} terminée. Code conservé dans TTLock jusqu'au " . $dateSuppressionAutorisee->format('d/m/Y') . " par sécurité.";
                }
            }
            return $report;
        }

        if (!$localBooking && strtolower($status) === 'booked') {
            $passcode = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

            $result = $this->ttLockServices->createCustomPasscode($lockId, $passcode, $startDate, $endDate, $guestName);

            if (isset($result['keyboardPwdId'])) {
                Booking::create([
                    'lodgify_booking_id' => $lodgifyBookingId,
                    'lodgify_room_id' => $lodgifyRoomId,
                    'guest_name' => $guestName,
                    'arrival_date' => $arrivalDate,
                    'departure_date' => $departureDate,
                    'ttlock_lock_id' => $lockId,
                    'ttlock_pwd_id' => $result['keyboardPwdId'],
                    'generated_passcode' => $passcode,
                    'status' => 'Booked',
                ]);

                $noteTexte = "Code de serrure :\n
                Voyageur : {$guestName} \n
                Code : {$passcode} \n
                Valide du : " . date('d/m/Y', strtotime($arrivalDate)) . " à 15h00 \n
                Jusqu'au : " . date('d/m/Y', strtotime($departureDate)) . " à 10h00";

                $roomTypeId = $res['rooms'][0]['room_type_id'] ?? null;

                if ($roomTypeId) {
                    $this->lodgifyServices->updateBookingKeyCodeV2($lodgifyBookingId, $roomTypeId, $passcode);

                    $lodgifyMiseJour = $this->lodgifyServices->updateBookingNotes($lodgifyBookingId, $noteTexte);

                    if ($lodgifyMiseJour) {
                        $report['created']++;
                    } else {
                        $report['errors'][] = "Code créé sur TTlock , mais echec de l'écriture de la note sur Lodgify (#{$lodgifyBookingId})";
                    }
                } else {
                    $report['errors'][] = "Impossible de trouver le room_type_id pour la réservation #{$lodgifyBookingId}";
                }
            } else {
                $report['errors'][] = "Erreur de création TTLock (#{$lodgifyBookingId})" . json_encode($result);
            }
        } elseif ($localBooking && strtolower($status) === 'booked') {
            if ($localBooking->arrival_date != $arrivalDate || $localBooking->departure_date != $departureDate) {
                $result = $this->ttLockServices->changePasscodeDates($localBooking->ttlock_lock_id, $localBooking->ttlock_pwd_id, $startDate, $endDate);

                if (isset($result['errcode']) && $result['errcode'] === 0) {
                    $localBooking->update([
                        'departure_date' => $departureDate,
                        'arrival_date' => $arrivalDate,
                    ]);

                    $report['updated']++;
                } else {
                    $report['errors'][] = "Erreur de modification dans TTlock (#{$lodgifyBookingId})";
                }
            }
        }

        return $report;

    }

    public function cleanExpiredPasscodes(array $report): array
    {
        $expiredBookings = Booking::where('departure_date', '<', now()->toDateString())
            ->where('status', 'Booked')
            ->get();

        foreach ($expiredBookings as $oldBooking) {
            if ($oldBooking->ttlock_pwd_id) {
                $this->ttLockServices->deletePasscode($oldBooking->ttlock_lock_id, $oldBooking->ttlock_pwd_id);
            }
            $oldBooking->update(['status' => 'Expired']);
            $report['cleaned']++;
        }

        return $report;
    }
}