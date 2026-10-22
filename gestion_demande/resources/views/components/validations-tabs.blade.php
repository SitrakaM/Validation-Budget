<div class="overflow-x-auto">
    <table class="w-full border-collapse">
        <thead>
            <tr class="border-b-2 border-gray-300 bg-gray-50">
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Validateur</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Décision</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Commentaire</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Décidé le</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fichiers</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($validations as $idx => $validation)
                <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors {{ $idx % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $validation['validateur'] }}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            @if (str_contains($validation['decision'], 'Validé'))
                                bg-green-100 text-green-800
                            @elseif (str_contains($validation['decision'], 'Correction'))
                                bg-yellow-100 text-yellow-800
                            @elseif (str_contains($validation['decision'], 'Rejeté'))
                                bg-red-100 text-red-800
                            @else
                                bg-gray-100 text-gray-800
                            @endif">
                            {{ $validation['decision'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate" title="{{ $validation['commentaire'] }}">{{ $validation['commentaire'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $validation['validated_at'] }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate" title="{{ $validation['fichiers'] }}">{{ $validation['fichiers'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

