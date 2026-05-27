<x-layout>
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Clés d'accès actives (Serrure + Cour)</h1>

        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Voyageur (Guest)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Propriété</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Durée du séjour
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Codes d'accès</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($activeBookings as $booking)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $booking->guest_name }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $booking->propertyMapping->lodgify_property_name ?? 'Appartement #' . $booking->lodgify_room_id }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                Du <span
                                    class="font-semibold">{{ \Carbon\Carbon::parse($booking->arrival_date)->format('d/m/Y') }}</span>
                                au <span
                                    class="font-semibold">{{ \Carbon\Carbon::parse($booking->departure_date)->format('d/m/Y') }}</span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex flex-col space-y-1">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 w-max">
                                        🚪 Chambre : <strong
                                            class="ml-1 text-sm font-mono text-gray-900">{{ $booking->generated_passcode }}</strong>
                                    </span>

                                    @if($booking->ttlock_cour_pwd_id)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 w-max">
                                            🌳 Cour : <strong
                                                class="ml-1 text-sm font-mono text-gray-900">{{ $booking->generated_passcode }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 italic">
                                Aucune réservation active (Booked) pour le moment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>