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

    public function handleSynchronization(?array $singleWebhookReservation = null): array
    {
        $report = [
            'created' => 0,
            'updated' => 0,
            'canceled' => 0,
            'cleaned' => 0,
            'errors' => []
        ];

        if ($singleWebhookReservation) {
            return $this->syncSingleReservation($singleWebhookReservation, $report);
        }

        $lodgifyReservations = $this->lodgifyServices->getBookings([
            'trash' => 'false',
            'status' => 'Booked',
            'limit' => 500,
            'departure_from' => now()->startOfMonth()->toDateString(),
            'updated_from' => now()->subDays(5)->toIso8601String(),
        ]);

        if (is_array($lodgifyReservations)) {
            foreach ($lodgifyReservations as $res) {
                $report = $this->syncSingleReservation($res, $report);
            }
        }

        $report = $this->cleanExpiredPasscodes($report);

        return $report;
    }
    // public function syncSingleReservation(array $res, array $report): array
    // {
    //     $type = $res['type'] ?? 'Booking';
    //     $bookingType = $res['booking_type'] ?? '';
    //     $status = $res['status'] ?? 'Booked';

    //     if ($type === 'ClosedPeriod' || $bookingType === 'EnquiryOnly' || strtolower($status) === 'open') {
    //         return $report;
    //     }
    //     $lodgifyBookingId = $res['id'];
    //     $lodgifyRoomId = $res['rooms'][0]['room_type_id'] ?? null;
    //     $status = $res['status'] ?? 'Booked';

    //     $arrivalDate = isset($res['arrival']) ? substr($res['arrival'], 0, 10) : null;
    //     $departureDate = isset($res['departure']) ? substr($res['departure'], 0, 10) : null;

    //     $guestName = $res['guest']['guest_name']['full_name'] ?? 'Voyageur';

    //     if (!$lodgifyRoomId || !$arrivalDate || !$departureDate) {
    //         return $report;
    //     }

    //     $mapping = PropertyMapping::where('lodgify_property_id', $lodgifyRoomId)->first();
    //     if (!$mapping) {
    //         return $report;
    //     }

    //     $lockId = $mapping->ttlock_lock_id;

    //     $startDate = $arrivalDate . '13:00:00';
    //     $endDate = $departureDate . '8:15:00';

    //     $localBooking = Booking::where('lodgify_booking_id', $lodgifyBookingId)->first();

    //     if (in_array(strtolower($status), ['canceled', 'cancelled', 'declined'])) {
    //         if ($localBooking && $localBooking->ttlock_pwd_id && $localBooking->status != 'Canceled') {

    //             $dateSuppressionAutorisee = Carbon::parse($localBooking->departure_date)->addDay();

    //             if (Carbon::now()->greaterThanOrEqualTo($dateSuppressionAutorisee)) {
    //                 $this->ttLockServices->deletePasscode($localBooking->ttlock_lock_id, $localBooking->ttlock_pwd_id);
    //                 if ($localBooking->ttlock_cour_pwd_id && $mapping->cour_lock_id) {
    //                     $this->ttLockServices->deletePasscode($mapping->cour_lock_id, $localBooking->ttlock_cour_pwd_id);
    //                 }
    //                 $localBooking->update(['status' => 'Canceled']);
    //                 $report['canceled']++;

    //             } else {
    //                 $report['errors'][] = "Réservation #{$lodgifyBookingId} terminée. Code conservé dans TTLock jusqu'au " . $dateSuppressionAutorisee->format('d/m/Y') . " par sécurité.";
    //             }
    //         }
    //         return $report;
    //     }

    //     if (!$localBooking && strtolower($status) === 'booked') {
    //         $passcode = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

    //         $result = $this->ttLockServices->createCustomPasscode($lockId, $passcode, $startDate, $endDate, $guestName);

    //         if (isset($result['keyboardPwdId'])) {
    //             $courPwdId = null;

    //             $codeCourEnregistre = "Non";

    //             if ($mapping->has_cour_access && $mapping->cour_lock_id) {
    //                 $resultCour = $this->ttLockServices->createCustomPasscode($mapping->cour_lock_id, $passcode, $startDate, $endDate, $guestName . " (Cour)");

    //                 if (isset($resultCour['keyboardPwdId'])) {
    //                     $courPwdId = $resultCour['keyboardPwdId'];
    //                     $codeCourEnregistre = "Oui";
    //                 } else {
    //                     $report['errors'][] = "Avertissement : Échec de la création du code Cour pour la réservation #{$lodgifyBookingId}";
    //                 }
    //             }
    //             Booking::create([
    //                 'lodgify_booking_id' => $lodgifyBookingId,
    //                 'lodgify_room_id' => $lodgifyRoomId,
    //                 'guest_name' => $guestName,
    //                 'arrival_date' => $arrivalDate,
    //                 'departure_date' => $departureDate,
    //                 'ttlock_lock_id' => $lockId,
    //                 'ttlock_pwd_id' => $result['keyboardPwdId'],
    //                 'ttlock_cour_pwd_id' => $courPwdId,
    //                 'generated_passcode' => $passcode,
    //                 'status' => 'Booked',
    //             ]);

    //             $noteTexte = "Code de serrure :\n
    //             Voyageur : {$guestName} \n
    //             Code : {$passcode} \n
    //             Valide du : " . date('d/m/Y', strtotime($arrivalDate)) . " à 15h00 \n
    //             Jusqu'au : " . date('d/m/Y', strtotime($departureDate)) . " à 10h00 \n
    //             Code porte de cour enregistré : {$codeCourEnregistre}";
    //             ;

    //             $roomTypeId = $res['rooms'][0]['room_type_id'] ?? null;

    //             if ($roomTypeId) {
    //                 $this->lodgifyServices->updateBookingKeyCodeV2($lodgifyBookingId, $roomTypeId, $passcode);

    //                 $lodgifyMiseJour = $this->lodgifyServices->updateBookingNotes($lodgifyBookingId, $noteTexte);

    //                 if ($lodgifyMiseJour) {
    //                     $report['created']++;
    //                 } else {
    //                     $report['errors'][] = "Code créé sur TTlock , mais echec de l'écriture de la note sur Lodgify (#{$lodgifyBookingId})";
    //                 }
    //             } else {
    //                 $report['errors'][] = "Impossible de trouver le room_type_id pour la réservation #{$lodgifyBookingId}";
    //             }
    //         } else {
    //             $report['errors'][] = "Erreur de création TTLock (#{$lodgifyBookingId})" . json_encode($result);
    //         }
    //     } elseif ($localBooking && strtolower($status) === 'booked') {
    //         if ($localBooking->arrival_date != $arrivalDate || $localBooking->departure_date != $departureDate) {
    //             $result = $this->ttLockServices->changePasscodeDates($localBooking->ttlock_lock_id, $localBooking->ttlock_pwd_id, $startDate, $endDate);

    //             if (isset($result['errcode']) && $result['errcode'] === 0) {

    //                 if ($localBooking->ttlock_cour_pwd_id && $mapping->cour_lock_id) {
    //                     $resultCour = $this->ttLockServices->changePasscodeDates($mapping->cour_lock_id, $localBooking->ttlock_cour_pwd_id, $startDate, $endDate);

    //                     if (!isset($resultCour['errcode']) || $resultCour['errcode'] !== 0) {
    //                         $report['errors'][] = "Erreur de modification des dates pour la Cour (#{$lodgifyBookingId})";
    //                     }
    //                 }

    //                 $localBooking->update([
    //                     'departure_date' => $departureDate,
    //                     'arrival_date' => $arrivalDate,
    //                 ]);

    //                 $report['updated']++;
    //             } else {
    //                 $report['errors'][] = "Erreur de modification dans TTlock (#{$lodgifyBookingId})";
    //             }
    //         }
    //     }

    //     return $report;

    // }

    public function syncSingleReservation(array $res, array $report): array
    {
        if (isset($res[0])) {
            $data = $res[0];
        } else {
            $data = $res;
        }

        // Si la clé 'booking' existe (VRAI webhook Lodgify, extrait ou direct)
        if (isset($data['booking'])) {
            $booking = $data['booking'];
            $guest = $data['guest'] ?? [];

            $type = $data['action'] ?? 'Booking';
            $bookingType = $booking['booking_type'] ?? '';
            $status = $booking['status'] ?? 'Booked';

            $lodgifyBookingId = $booking['id'] ?? null;
            $lodgifyRoomId = $booking['room_types'][0]['room_type_id'] ?? null;

            $arrivalRaw = $booking['date_arrival'] ?? null;
            $departureRaw = $booking['date_departure'] ?? null;

            $guestName = $guest['name'] ?? 'Voyageur';
        } else {
            $type = $res['type'] ?? 'Booking';
            $bookingType = $res['booking_type'] ?? '';
            $status = $res['status'] ?? 'Booked';

            $lodgifyBookingId = $res['id'] ?? null;
            $lodgifyRoomId = $res['rooms'][0]['room_type_id'] ?? null;

            $arrivalRaw = $res['arrival'] ?? null;
            $departureRaw = $res['departure'] ?? null;

            $guestName = $res['guest']['guest_name']['full_name'] ?? $res['name'] ?? 'Voyageur';
        }

        if ($type === 'ClosedPeriod' || $bookingType === 'EnquiryOnly' || strtolower($status) === 'open') {
            return $report;
        }

        if (!$lodgifyBookingId || !$lodgifyRoomId || !$arrivalRaw || !$departureRaw) {
            return $report;
        }

        $arrivalDate = substr($arrivalRaw, 0, 10);
        $departureDate = substr($departureRaw, 0, 10);

        $mapping = PropertyMapping::where('lodgify_property_id', $lodgifyRoomId)->first();
        if (!$mapping) {
            return $report;
        }

        $lockId = $mapping->ttlock_lock_id;
        $startDate = $arrivalDate . ' 13:00:00';
        $endDate = $departureDate . ' 08:15:00';

        $localBooking = Booking::where('lodgify_booking_id', $lodgifyBookingId)->first();

        if (in_array(strtolower($status), ['canceled', 'cancelled', 'declined'])) {
            if ($localBooking && $localBooking->ttlock_pwd_id && $localBooking->status != 'Canceled') {

                $dateSuppressionAutorisee = \Carbon\Carbon::parse($localBooking->departure_date)->addDay();

                if (\Carbon\Carbon::now()->greaterThanOrEqualTo($dateSuppressionAutorisee)) {
                    $this->ttLockServices->deletePasscode($localBooking->ttlock_lock_id, $localBooking->ttlock_pwd_id);
                    if ($localBooking->ttlock_cour_pwd_id && $mapping->cour_lock_id) {
                        $this->ttLockServices->deletePasscode($mapping->cour_lock_id, $localBooking->ttlock_cour_pwd_id);
                    }
                    $localBooking->update(['status' => 'Canceled']);
                    $report['canceled']++;
                } else {
                    $report['errors'][] = "Préservation #{$lodgifyBookingId} terminée. Code conservé dans TTLock jusqu'au " . $dateSuppressionAutorisee->format('d/m/Y') . " par sécurité.";
                }
            }
            return $report;
        }

        if (!$localBooking && strtolower($status) === 'booked') {
            $passcode = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);

            $result = $this->ttLockServices->createCustomPasscode($lockId, $passcode, $startDate, $endDate, $guestName);

            if (isset($result['keyboardPwdId'])) {
                $courPwdId = null;
                $codeCourEnregistre = "Non";

                if ($mapping->has_cour_access && $mapping->cour_lock_id) {
                    $resultCour = $this->ttLockServices->createCustomPasscode($mapping->cour_lock_id, $passcode, $startDate, $endDate, $guestName . " (Cour)");

                    if (isset($resultCour['keyboardPwdId'])) {
                        $courPwdId = $resultCour['keyboardPwdId'];
                        $codeCourEnregistre = "Oui";
                    } else {
                        $report['errors'][] = "Avertissement : Échec de la création du code Cour pour la réservation #{$lodgifyBookingId}";
                    }
                }

                Booking::create([
                    'lodgify_booking_id' => $lodgifyBookingId,
                    'lodgify_room_id' => $lodgifyRoomId,
                    'guest_name' => $guestName,
                    'arrival_date' => $arrivalDate,
                    'departure_date' => $departureDate,
                    'ttlock_lock_id' => $lockId,
                    'ttlock_pwd_id' => $result['keyboardPwdId'],
                    'ttlock_cour_pwd_id' => $courPwdId,
                    'generated_passcode' => $passcode,
                    'status' => 'Booked',
                ]);

                $noteTexte = "Code de serrure :\n\nVoyageur : {$guestName} \nCode : {$passcode} \nValide du : " . date('d/m/Y', strtotime($arrivalDate)) . " à 15h00 \nJusqu'au : " . date('d/m/Y', strtotime($departureDate)) . " à 10h00 \nCode porte de cour enregistré : {$codeCourEnregistre}";

                if ($lodgifyRoomId) {
                    $this->lodgifyServices->updateBookingKeyCodeV2($lodgifyBookingId, $lodgifyRoomId, $passcode);
                    $lodgifyMiseJour = $this->lodgifyServices->updateBookingNotes($lodgifyBookingId, $noteTexte);

                    if ($lodgifyMiseJour) {
                        $report['created']++;
                    } else {
                        $report['errors'][] = "Code créé sur TTlock, mais échec de l'écriture de la note sur Lodgify (#{$lodgifyBookingId})";
                    }
                } else {
                    $report['errors'][] = "Impossible de trouver le room_type_id pour la réservation #{$lodgifyBookingId}";
                }
            } else {
                $report['errors'][] = "Erreur de création TTLock (#{$lodgifyBookingId}) " . json_encode($result);
            }
        } elseif ($localBooking && strtolower($status) === 'booked') {
            if ($localBooking->arrival_date != $arrivalDate || $localBooking->departure_date != $departureDate) {
                $result = $this->ttLockServices->changePasscodeDates($localBooking->ttlock_lock_id, $localBooking->ttlock_pwd_id, $startDate, $endDate);

                if (isset($result['errcode']) && $result['errcode'] === 0) {

                    if ($localBooking->ttlock_cour_pwd_id && $mapping->cour_lock_id) {
                        $resultCour = $this->ttLockServices->changePasscodeDates($mapping->cour_lock_id, $localBooking->ttlock_cour_pwd_id, $startDate, $endDate);

                        if (!isset($resultCour['errcode']) || $resultCour['errcode'] !== 0) {
                            $report['errors'][] = "Erreur de modification des dates pour la Cour (#{$lodgifyBookingId})";
                        }
                    }

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
            if ($oldBooking->ttlock_cour_pwd_id) {
                $mapping = PropertyMapping::where('lodgify_property_id', $oldBooking->lodgify_room_id)->first();

                if ($mapping && $mapping->cour_lock_id) {
                    $this->ttLockServices->deletePasscode($mapping->cour_lock_id, $oldBooking->ttlock_cour_pwd_id);
                }
            }
            $oldBooking->update(['status' => 'Expired']);
            $report['cleaned']++;
        }

        return $report;
    }
}